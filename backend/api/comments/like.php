<?php
require_once '../../db.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方法不允许');
}

$input = json_decode(file_get_contents('php://input'), true);
$commentId = isset($input['comment_id']) ? (int)$input['comment_id'] : 0;

if ($commentId <= 0) {
    jsonResponse(400, '评论ID无效');
}

// 获取真实IP（支持反向代理/CDN）
function getRealIp() {
    $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

$ip = getRealIp();
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

try {
    // 检查是否已经点赞过
    $checkStmt = $pdo->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND ip_address = ? AND user_agent = ?");
    $checkStmt->execute([$commentId, $ip, $userAgent]);
    
    if ($checkStmt->fetch()) {
        jsonResponse(400, '您已经点赞过了');
    }
    
    // 添加点赞记录
    $likeStmt = $pdo->prepare("INSERT INTO comment_likes (comment_id, ip_address, user_agent) VALUES (?, ?, ?)");
    $likeStmt->execute([$commentId, $ip, $userAgent]);
    
    // 更新评论点赞数
    $updateStmt = $pdo->prepare("UPDATE comments SET like_count = like_count + 1 WHERE id = ?");
    $updateStmt->execute([$commentId]);
    
    jsonResponse(200, '点赞成功');
} catch (PDOException $e) {
    jsonResponse(500, '点赞失败: ' . $e->getMessage());
}
