<?php
require_once '../../db.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonResponse(405, '请求方法错误');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    jsonResponse(400, '文章ID无效');
}

$articleId = (int)$_GET['id'];

try {
    $pdo->beginTransaction();

    $checkStmt = $pdo->prepare("SELECT id FROM articles WHERE id = ?");
    $checkStmt->execute([$articleId]);
    if (!$checkStmt->fetch()) {
        $pdo->rollBack();
        jsonResponse(404, '文章不存在');
    }

    $deleteTags = $pdo->prepare("DELETE FROM article_tags WHERE article_id = ?");
    $deleteTags->execute([$articleId]);

    $deleteComments = $pdo->prepare("DELETE FROM comments WHERE article_id = ?");
    $deleteComments->execute([$articleId]);

    $deleteLikes = $pdo->prepare("DELETE FROM likes WHERE article_id = ?");
    $deleteLikes->execute([$articleId]);

    $deleteArticle = $pdo->prepare("DELETE FROM articles WHERE id = ?");
    $deleteArticle->execute([$articleId]);

    $pdo->commit();

    jsonResponse(200, '文章删除成功');
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(500, '删除失败: ' . $e->getMessage());
}
