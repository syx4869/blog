<?php
require_once '../../db.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方法不允许');
}

$input = json_decode(file_get_contents('php://input'), true);
$id = isset($input['id']) ? (int)$input['id'] : 0;

if ($id <= 0) {
    jsonResponse(400, '资源ID无效');
}

try {
    $stmt = $pdo->prepare("DELETE FROM resources WHERE id = ?");
    $stmt->execute([$id]);
    
    jsonResponse(200, '删除成功');
} catch (PDOException $e) {
    jsonResponse(500, '删除失败: ' . $e->getMessage());
}
