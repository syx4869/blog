<?php
require_once '../../config.php';
require_once '../../db.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方法错误');
}

// 获取 Token
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

if (empty($token)) {
    jsonResponse(200, '已退出登录');
}

$pdo = getDB();

// 删除 Token
$stmt = $pdo->prepare("DELETE FROM admin_tokens WHERE token = ?");
$stmt->execute([$token]);

jsonResponse(200, '退出登录成功');
