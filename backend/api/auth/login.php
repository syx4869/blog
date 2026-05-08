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

// 获取请求数据
$input = json_decode(file_get_contents('php://input'), true);
$username = isset($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) ? $input['password'] : '';

if (empty($username) || empty($password)) {
    jsonResponse(400, '用户名和密码不能为空');
}

// 验证用户名格式
if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    jsonResponse(400, '用户名格式不正确');
}

$pdo = getDB();

// 查询用户
$stmt = $pdo->prepare("SELECT id, username, password, nickname, avatar, role, is_active FROM admins WHERE username = ?");
$stmt->execute([$username]);
$admin = $stmt->fetch();

if (!$admin) {
    // 记录失败日志
    logLoginAttempt($pdo, null, 'failed', '用户不存在');
    jsonResponse(401, '用户名或密码错误');
}

if (!$admin['is_active']) {
    logLoginAttempt($pdo, $admin['id'], 'failed', '账号已禁用');
    jsonResponse(403, '账号已被禁用，请联系超级管理员');
}

// 验证密码
if (!password_verify($password, $admin['password'])) {
    logLoginAttempt($pdo, $admin['id'], 'failed', '密码错误');
    jsonResponse(401, '用户名或密码错误');
}

// 生成 Token
$token = bin2hex(random_bytes(32));
$expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));

// 保存 Token 到数据库
$stmt = $pdo->prepare("INSERT INTO admin_tokens (admin_id, token, expires_at, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([
    $admin['id'],
    $token,
    $expiresAt,
    $_SERVER['REMOTE_ADDR'] ?? null,
    $_SERVER['HTTP_USER_AGENT'] ?? null
]);

// 更新最后登录时间
$stmt = $pdo->prepare("UPDATE admins SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?");
$stmt->execute([$_SERVER['REMOTE_ADDR'] ?? null, $admin['id']]);

// 记录成功日志
logLoginAttempt($pdo, $admin['id'], 'success', '登录成功');

jsonResponse(200, '登录成功', [
    'token' => $token,
    'expires_at' => $expiresAt,
    'admin' => [
        'id' => $admin['id'],
        'username' => $admin['username'],
        'nickname' => $admin['nickname'],
        'avatar' => $admin['avatar'],
        'role' => $admin['role']
    ]
]);

function logLoginAttempt($pdo, $adminId, $status, $message) {
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_login_logs (admin_id, ip_address, user_agent, status, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $adminId ?: 0,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $status,
            $message
        ]);
    } catch (Exception $e) {
        // 忽略日志记录错误
    }
}
