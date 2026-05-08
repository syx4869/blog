<?php
require_once '../../db.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方法不允许');
}

$input = json_decode(file_get_contents('php://input'), true);

$id = isset($input['id']) ? (int)$input['id'] : 0;
$name = isset($input['name']) ? trim($input['name']) : '';
$description = isset($input['description']) ? trim($input['description']) : '';
$url = isset($input['url']) ? trim($input['url']) : '';
$type = isset($input['type']) ? trim($input['type']) : 'tutorial';
$category = isset($input['category']) ? trim($input['category']) : '';
$icon = isset($input['icon']) ? trim($input['icon']) : '';
$level = isset($input['level']) ? trim($input['level']) : 'beginner';

if ($id <= 0 || empty($name) || empty($url)) {
    jsonResponse(400, '参数错误');
}

try {
    $stmt = $pdo->prepare("UPDATE resources 
                           SET name = ?, description = ?, url = ?, icon = ?, type = ?, category = ?, level = ? 
                           WHERE id = ?");
    $stmt->execute([$name, $description, $url, $icon, $type, $category, $level, $id]);
    
    jsonResponse(200, '更新成功');
} catch (PDOException $e) {
    jsonResponse(500, '更新失败: ' . $e->getMessage());
}
