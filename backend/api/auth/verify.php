<?php
require_once '../../config.php';
require_once '../../db.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(405, '请求方法错误');
}

// 获取 Token
$headers = getallheaders();
$token = isset($headers['Authorization']) ? str_replace('Bearer ', '', $headers['Authorization']) : '';

if (empty($token)) {
    jsonResponse(401, '未登录或登录已过期');
}

$pdo = getDB();

// 验证 Token
$stmt = $pdo->prepare("SELECT t.admin_id, t.expires_at, a.username, a.nickname, a.avatar, a.role 
                       FROM admin_tokens t 
                       JOIN admins a ON t.admin_id = a.id 
                       WHERE t.token = ? AND t.expires_at > NOW() AND a.is_active = 1");
$stmt->execute([$token]);
$admin = $stmt->fetch();

if (!$admin) {
    jsonResponse(401, '登录已过期，请重新登录');
}

jsonResponse(200, '验证成功', [
    'id' => $admin['admin_id'],
    'username' => $admin['username'],
    'nickname' => $admin['nickname'],
    'avatar' => $admin['avatar'],
    'role' => $admin['role']
]);
