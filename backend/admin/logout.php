<?php
session_start();

// 清除数据库中的 Token
if (isset($_SESSION['admin_token'])) {
    require_once '../db.php';
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("DELETE FROM admin_tokens WHERE token = ?");
        $stmt->execute([$_SESSION['admin_token']]);
    } catch (Exception $e) {
        // 忽略错误
    }
}

// 清除 Session
session_destroy();

// 清除 Cookie
setcookie('admin_token', '', time() - 3600, '/');

// 跳转到登录页
header('Location: login.php');
exit;
