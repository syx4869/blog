<?php
require_once '../../db.php';

$pdo = getDB();

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

if (!$id) {
    jsonResponse(400, '文章ID不能为空');
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

$ipAddress = getRealIp();
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

try {
    $pdo->beginTransaction();
    
    $checkStmt = $pdo->prepare("SELECT id FROM likes WHERE article_id = ? AND ip_address = ?");
    $checkStmt->execute([$id, $ipAddress]);
    $existing = $checkStmt->fetch();
    
    if ($existing) {
        $deleteStmt = $pdo->prepare("DELETE FROM likes WHERE id = ?");
        $deleteStmt->execute([$existing['id']]);
        
        $updateStmt = $pdo->prepare("UPDATE articles SET like_count = GREATEST(0, like_count - 1) WHERE id = ?");
        $updateStmt->execute([$id]);
        $liked = false;
    } else {
        $insertStmt = $pdo->prepare("INSERT INTO likes (article_id, ip_address, user_agent) VALUES (?, ?, ?)");
        $insertStmt->execute([$id, $ipAddress, $userAgent]);
        
        $updateStmt = $pdo->prepare("UPDATE articles SET like_count = like_count + 1 WHERE id = ?");
        $updateStmt->execute([$id]);
        $liked = true;
    }
    
    $countStmt = $pdo->prepare("SELECT like_count FROM articles WHERE id = ?");
    $countStmt->execute([$id]);
    $likeCount = $countStmt->fetchColumn();
    
    $pdo->commit();
    
    jsonResponse(200, $liked ? '点赞成功' : '取消点赞', ['likeCount' => (int)$likeCount]);
} catch (PDOException $e) {
    $pdo->rollBack();
    jsonResponse(500, '操作失败: ' . $e->getMessage());
}
