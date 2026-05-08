<?php
/**
 * 后台认证公共文件
 * 在需要登录保护的页面顶部引入: require_once 'auth.php';
 */

session_start();

// 检查是否已登录
function checkAdminAuth() {
    // 优先检查 Session
    if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_token'])) {
        return true;
    }
    
    // 检查是否有 Token Cookie
    if (isset($_COOKIE['admin_token'])) {
        $token = $_COOKIE['admin_token'];
        
        // 验证 Token
        require_once __DIR__ . '/../db.php';
        try {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT t.admin_id, t.expires_at, a.username, a.nickname, a.avatar, a.role 
                                   FROM admin_tokens t 
                                   JOIN admins a ON t.admin_id = a.id 
                                   WHERE t.token = ? AND t.expires_at > NOW() AND a.is_active = 1");
            $stmt->execute([$token]);
            $admin = $stmt->fetch();
            
            if ($admin) {
                $_SESSION['admin_id'] = $admin['admin_id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_nickname'] = $admin['nickname'];
                $_SESSION['admin_avatar'] = $admin['avatar'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_token'] = $token;
                return true;
            }
        } catch (Exception $e) {
            // 忽略错误
        }
    }
    
    return false;
}

// 需要登录的页面调用此函数
function requireAdminLogin() {
    if (!checkAdminAuth()) {
        // 清除登录状态
        session_destroy();
        setcookie('admin_token', '', time() - 3600, '/');
        
        // 如果是 AJAX 请求，返回 JSON
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['code' => 401, 'message' => '未登录或登录已过期']);
            exit;
        }
        
        // 页面请求，重定向到登录页
        header('Location: login.php');
        exit;
    }
}

// 获取当前登录管理员信息
function getCurrentAdmin() {
    if (!checkAdminAuth()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'username' => $_SESSION['admin_username'] ?? null,
        'nickname' => $_SESSION['admin_nickname'] ?? null,
        'avatar' => $_SESSION['admin_avatar'] ?? null,
        'role' => $_SESSION['admin_role'] ?? null
    ];
}
