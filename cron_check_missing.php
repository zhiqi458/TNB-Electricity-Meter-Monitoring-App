<?php
// cron_check_missing.php
require_once 'db.php';

$today = date('Y-m-d');
$now = date('H:i:s');
$created_at = date('Y-m-d H:i:s');

// 查询所有电表
$meters_query = "SELECT id, building_name, submission_deadline FROM meters";
$meters_result = $conn->query($meters_query);

while ($meter = $meters_result->fetch_assoc()) {
    $meter_id = $meter['id'];
    $deadline = $meter['submission_deadline'];

    // 检查超过截止时间且今日无记录
    if ($now >= $deadline) {
        $check_stmt = $conn->prepare("SELECT id FROM meter_readings WHERE meter_id = ? AND submission_date = ?");
        $check_stmt->bind_param("is", $meter_id, $today);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows === 0) {
            // 检查今日是否已生成过缺失警报
            $alert_check = $conn->prepare("SELECT id FROM alerts WHERE meter_id = ? AND alert_type = 'MISSING_READING' AND DATE(created_at) = ?");
            $alert_check->bind_param("is", $meter_id, $today);
            $alert_check->execute();

            if ($alert_check->get_result()->num_rows === 0) {
                $alert_msg = "【缺失读数提醒】电表 {$meter['building_name']} 在 {$today} 仍未找到电表读数提交记录。";
                $insert_alert = $conn->prepare("INSERT INTO alerts (meter_id, alert_type, message, created_at) VALUES (?, 'MISSING_READING', ?, ?)");
                $insert_alert->bind_param("iss", $meter_id, $alert_msg, $created_at);
                $insert_alert->execute();
                
                echo "警报已生成: {$alert_msg}\n";
            }
        }
    }
}
?>