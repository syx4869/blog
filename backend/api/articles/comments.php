<?php
require_once '../../db.php';

$pdo = getDB();

if (!isset($_GET['article_id']) || !is_numeric($_GET['article_id'])) {
    jsonResponse(400, '文章ID无效');
}

$articleId = (int)$_GET['article_id'];
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = isset($_GET['pageSize']) ? max(1, min(50, (int)$_GET['pageSize'])) : 10;

try {
    $offset = ($page - 1) * $pageSize;
    
    $sql = "SELECT c.id, c.nickname, c.content, c.like_count, c.is_author, c.created_at
            FROM comments c
            WHERE c.article_id = ? AND c.parent_id IS NULL
            ORDER BY c.created_at DESC
            LIMIT $offset, $pageSize";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$articleId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($comments as &$comment) {
        $replySql = "SELECT c.id, c.nickname, c.content, c.reply_to, c.created_at
                     FROM comments c
                     WHERE c.parent_id = ?
                     ORDER BY c.created_at ASC";
        $replyStmt = $pdo->prepare($replySql);
        $replyStmt->execute([$comment['id']]);
        $comment['replies'] = $replyStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $countSql = "SELECT COUNT(*) FROM comments WHERE article_id = ? AND parent_id IS NULL";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([$articleId]);
    $total = $countStmt->fetchColumn();
    
    jsonResponse(200, 'success', [
        'list' => $comments,
        'total' => (int)$total,
        'page' => $page,
        'pageSize' => $pageSize
    ]);
} catch (PDOException $e) {
    jsonResponse(500, '查询失败: ' . $e->getMessage());
}
