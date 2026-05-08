<?php
require_once '../../db.php';

$pdo = getDB();

$articleId = isset($_GET['article_id']) ? (int)$_GET['article_id'] : 0;

if ($articleId <= 0) {
    jsonResponse(400, '文章ID无效');
}

try {
    // 获取主评论（parent_id IS NULL）
    $stmt = $pdo->prepare("SELECT id, article_id, parent_id, reply_to, nickname, content, like_count, is_author, created_at 
                           FROM comments 
                           WHERE article_id = ? AND parent_id IS NULL 
                           ORDER BY created_at DESC");
    $stmt->execute([$articleId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 获取回复评论
    $replyStmt = $pdo->prepare("SELECT id, article_id, parent_id, reply_to, nickname, content, like_count, is_author, created_at 
                                FROM comments 
                                WHERE article_id = ? AND parent_id IS NOT NULL 
                                ORDER BY created_at ASC");
    $replyStmt->execute([$articleId]);
    $replies = $replyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 将回复按 parent_id 分组
    $replyMap = [];
    foreach ($replies as $reply) {
        $parentId = $reply['parent_id'];
        if (!isset($replyMap[$parentId])) {
            $replyMap[$parentId] = [];
        }
        $replyMap[$parentId][] = [
            'id' => (int)$reply['id'],
            'nickname' => $reply['nickname'],
            'content' => $reply['content'],
            'replyTo' => $reply['reply_to'],
            'likeCount' => (int)$reply['like_count'],
            'isAuthor' => (bool)$reply['is_author'],
            'createdAt' => $reply['created_at']
        ];
    }
    
    // 格式化主评论
    $result = [];
    foreach ($comments as $comment) {
        $result[] = [
            'id' => (int)$comment['id'],
            'nickname' => $comment['nickname'],
            'content' => $comment['content'],
            'likeCount' => (int)$comment['like_count'],
            'isAuthor' => (bool)$comment['is_author'],
            'createdAt' => $comment['created_at'],
            'replies' => $replyMap[$comment['id']] ?? []
        ];
    }
    
    jsonResponse(200, 'success', $result);
} catch (PDOException $e) {
    jsonResponse(500, '查询失败: ' . $e->getMessage());
}
