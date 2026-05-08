<?php
require_once '../../db.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方法不允许');
}

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : 0;

if ($id <= 0) {
    jsonResponse(400, '评论ID无效');
}

try {
    // 先删除该评论的所有回复
    $pdo->prepare("DELETE FROM comments WHERE parent_id = ?")->execute([$id]);
    
    // 再删除评论本身
    $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->execute([$id]);
    
    jsonResponse(200, '删除成功');
} catch (PDOException $e) {
    jsonResponse(500, '删除失败: ' . $e->getMessage());
}
