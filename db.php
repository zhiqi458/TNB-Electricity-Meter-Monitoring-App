<?php
// db.php

// 设置马来西亚标准时区
date_default_timezone_set('Asia/Kuala_Lumpur');

// MySQL Workbench 连接参数配置
$db_host = 'localhost';      // 或 'localhost'
$db_port = 3306;             // MySQL Workbench 默认端口
$db_user = 'synergy1_yenping';           // 你的 MySQL 用户名
$db_pass = 'R.zb0ZwEuGZ}*fW2';               // 你的 MySQL 密码
$db_name = ' synergy1_zhiqi_TNB_Electricity_Meter_Monitoring_App';   // 数据库名称

// 建立连接
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

// 检查连接
if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error", 
        "message" => "数据库连接失败: " . $conn->connect_error
    ]);
    exit;
}

// 设置字符集为 utf8mb4
$conn->set_charset("utf8mb4");
?>