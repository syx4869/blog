<?php
require_once '../../db.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方法不允许');
}

$input = json_decode(file_get_contents('php://input'), true);

$articleId = isset($input['article_id']) ? (int)$input['article_id'] : 0;
$nickname = isset($input['nickname']) ? trim($input['nickname']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$content = isset($input['content']) ? trim($input['content']) : '';
$parentId = isset($input['parent_id']) ? (int)$input['parent_id'] : null;
$replyTo = isset($input['reply_to']) ? trim($input['reply_to']) : null;

if ($articleId <= 0 || empty($nickname) || empty($email) || empty($content)) {
    jsonResponse(400, '请填写完整信息');
}

if (strlen($content) > 2000) {
    jsonResponse(400, '评论内容不能超过2000字');
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

try {
    $stmt = $pdo->prepare("INSERT INTO comments (article_id, parent_id, reply_to, nickname, email, content, ip_address, user_agent) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $articleId,
        $parentId,
        $replyTo,
        $nickname,
        $email,
        $content,
        getRealIp(),
        $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
    
    $commentId = $pdo->lastInsertId();
    
    jsonResponse(200, '评论发表成功', [
        'id' => (int)$commentId,
        'article_id' => $articleId,
        'nickname' => $nickname,
        'content' => $content,
        'created_at' => date('Y-m-d H:i:s')
    ]);
} catch (PDOException $e) {
    jsonResponse(500, '发表评论失败: ' . $e->getMessage());
}
