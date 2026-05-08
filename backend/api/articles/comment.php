<?php
require_once '../../db.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['article_id']) || !is_numeric($input['article_id'])) {
    jsonResponse(400, '文章ID无效');
}

if (empty($input['nickname']) || empty($input['content'])) {
    jsonResponse(400, '昵称和评论内容不能为空');
}

try {
    $articleId = (int)$input['article_id'];
    $nickname = trim($input['nickname']);
    $email = isset($input['email']) ? trim($input['email']) : null;
    $content = trim($input['content']);
    $parentId = isset($input['parent_id']) && is_numeric($input['parent_id']) ? (int)$input['parent_id'] : null;
    $replyTo = isset($input['reply_to']) ? trim($input['reply_to']) : null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = $pdo->prepare("INSERT INTO comments 
                          (article_id, parent_id, reply_to, nickname, email, content, ip_address, user_agent)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$articleId, $parentId, $replyTo, $nickname, $email, $content, $ipAddress, $userAgent]);
    
    $commentId = $pdo->lastInsertId();
    
    $stmt = $pdo->prepare("SELECT id, nickname, content, like_count, is_author, created_at
                          FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    jsonResponse(200, '评论发表成功', $comment);
} catch (PDOException $e) {
    jsonResponse(500, '评论发表失败: ' . $e->getMessage());
}
