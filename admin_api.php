<?php
// admin_api.php
header('Content-Type: application/json');
require_once 'db.php';

$action = $_GET['action'] ?? '';

// 1. 获取所有电表配置与最新状态
if ($action === 'get_meters') {
    $sql = "SELECT m.*, 
            (SELECT reading_value FROM meter_readings WHERE meter_id = m.id ORDER BY id DESC LIMIT 1) as last_reading,
            (SELECT submission_date FROM meter_readings WHERE meter_id = m.id ORDER BY id DESC LIMIT 1) as last_date
            FROM meters m";
    $result = $conn->query($sql);
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $data]);
    exit;
}

// 2. 更新电表限制和截止时间
if ($action === 'update_meter') {
    $meter_id = intval($_POST['meter_id'] ?? 0);
    $limit = floatval($_POST['daily_limit_kwh'] ?? 80.00);
    $deadline = $_POST['submission_deadline'] ?? '18:00:00';

    $stmt = $conn->prepare("UPDATE meters SET daily_limit_kwh = ?, submission_deadline = ? WHERE id = ?");
    $stmt->bind_param("dsi", $limit, $deadline, $meter_id);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "电表设置更新成功"]);
    } else {
        echo json_encode(["status" => "error", "message" => "更新失败"]);
    }
    exit;
}

// 3. 获取所有警报记录 (超标/缺失)
if ($action === 'get_alerts') {
    $sql = "SELECT a.*, m.building_name 
            FROM alerts a 
            JOIN meters m ON a.meter_id = m.id 
            ORDER BY a.id DESC";
    $result = $conn->query($sql);
    $alerts = [];
    while ($row = $result->fetch_assoc()) {
        $alerts[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $alerts]);
    exit;
}

echo json_encode(["status" => "error", "message" => "无效的请求"]);
?>