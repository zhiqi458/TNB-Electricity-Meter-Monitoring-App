<?php
// submit_reading.php
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "message" => "Invalid Request Method"]);
    exit;
}

$meter_id = intval($_POST['meter_id'] ?? 0);
$user_id = intval($_POST['user_id'] ?? 0);
$current_reading = floatval($_POST['reading_value'] ?? 0);
$app_version = intval($_POST['app_version'] ?? 1); // 1 = V1, 2 = V2

if ($meter_id <= 0 || $user_id <= 0 || $current_reading <= 0) {
    echo json_encode(["status" => "error", "message" => "请填写所有必填字段"]);
    exit;
}

// 检查图片上传
if (!isset($_FILES['meter_photo']) || $_FILES['meter_photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["status" => "error", "message" => "V2 版本要求必须上传/拍摄电表照片"]);
    exit;
}

// 保存照片文件
$upload_dir = 'uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$file_ext = pathinfo($_FILES['meter_photo']['name'], PATHINFO_EXTENSION);
$file_name = 'meter_' . $meter_id . '_' . date('Ymd_His') . '.' . ($file_ext ?: 'jpg');
$target_path = $upload_dir . $file_name;

if (!move_uploaded_file($_FILES['meter_photo']['tmp_name'], $target_path)) {
    echo json_encode(["status" => "error", "message" => "照片保存失败"]);
    exit;
}

$current_date = date('Y-m-d');
$current_time = date('H:i:s');
$created_at = date('Y-m-d H:i:s');
$is_live_photo = ($app_version === 2) ? 1 : 0;

// 获取前一次电表读数
$prev_stmt = $conn->prepare("SELECT reading_value FROM meter_readings WHERE meter_id = ? ORDER BY id DESC LIMIT 1");
$prev_stmt->bind_param("i", $meter_id);
$prev_stmt->execute();
$prev_result = $prev_stmt->get_result();

$daily_usage = 0.00;
if ($row = $prev_result->fetch_assoc()) {
    $previous_reading = floatval($row['reading_value']);
    $daily_usage = $current_reading - $previous_reading;
}

// 插入新记录
$insert_stmt = $conn->prepare("INSERT INTO meter_readings (meter_id, user_id, reading_value, daily_usage, photo_path, is_live_photo, submission_date, submission_time, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
$insert_stmt->bind_param("iiddsisss", $meter_id, $user_id, $current_reading, $daily_usage, $target_path, $is_live_photo, $current_date, $current_time, $created_at);

if ($insert_stmt->execute()) {
    // 检查用量超标警报 (Over-Usage Alert)
    $meter_stmt = $conn->prepare("SELECT daily_limit_kwh, building_name FROM meters WHERE id = ?");
    $meter_stmt->bind_param("i", $meter_id);
    $meter_stmt->execute();
    $meter_info = $meter_stmt->get_result()->fetch_assoc();

    $alert_created = false;
    $alert_msg = "";

    if ($meter_info && $daily_usage > $meter_info['daily_limit_kwh']) {
        $exceeded = $daily_usage - $meter_info['daily_limit_kwh'];
        $alert_msg = "【用量超标警报】电表 {$meter_info['building_name']} 今日用电量 {$daily_usage} kWh，超出目标上限 {$exceeded} kWh！";

        $alert_stmt = $conn->prepare("INSERT INTO alerts (meter_id, alert_type, message, created_at) VALUES (?, 'OVER_USAGE', ?, ?)");
        $alert_stmt->bind_param("iss", $meter_id, $alert_msg, $created_at);
        $alert_stmt->execute();
        $alert_created = true;
    }

    echo json_encode([
        "status" => "success",
        "message" => "读数提交成功",
        "data" => [
            "daily_usage" => $daily_usage,
            "alert_triggered" => $alert_created,
            "alert_message" => $alert_msg,
            "submission_date" => $current_date,
            "submission_time" => $current_time
        ]
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "数据保存失败"]);
}
?>