<?php
// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', '数据库名');  // 数据库名
define('DB_USER', '数据库用户名');  // 数据库用户名
define('DB_PASS', '数据库密码');  // 数据库密码
define('DB_CHARSET', 'utf8mb4');

// 网站配置
define('SITE_URL', '后台域名');  // 后台域名
define('API_BASE', SITE_URL . '/api');

// 响应格式
function jsonResponse($code, $message, $data = null) {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    
    echo json_encode([
        'code' => $code,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 处理 OPTIONS 请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    jsonResponse(200, 'OK');
}
