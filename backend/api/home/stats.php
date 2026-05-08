<?php
require_once '../../db.php';

$pdo = getDB();

try {
    $articleCount = $pdo->query("SELECT COUNT(*) FROM articles WHERE is_published = 1")->fetchColumn();
    $viewCount = $pdo->query("SELECT COALESCE(SUM(view_count), 0) FROM articles WHERE is_published = 1")->fetchColumn();
    $likeCount = $pdo->query("SELECT COALESCE(SUM(like_count), 0) FROM articles WHERE is_published = 1")->fetchColumn();
    $friendCount = 0;
    
    jsonResponse(200, 'success', [
        'articleCount' => (int)$articleCount,
        'viewCount' => (int)$viewCount,
        'likeCount' => (int)$likeCount,
        'friendCount' => (int)$friendCount
    ]);
} catch (PDOException $e) {
    jsonResponse(500, '查询失败: ' . $e->getMessage());
}
