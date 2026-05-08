<?php
require_once '../../db.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方法不允许');
}

$input = json_decode(file_get_contents('php://input'), true);

$name = isset($input['name']) ? trim($input['name']) : '';
$description = isset($input['description']) ? trim($input['description']) : '';
$url = isset($input['url']) ? trim($input['url']) : '';
$type = isset($input['type']) ? trim($input['type']) : 'tutorial';
$category = isset($input['category']) ? trim($input['category']) : '';
$icon = isset($input['icon']) ? trim($input['icon']) : '';
$level = isset($input['level']) ? trim($input['level']) : 'beginner';

if (empty($name) || empty($url)) {
    jsonResponse(400, '名称和链接不能为空');
}

try {
    $stmt = $pdo->prepare("INSERT INTO resources (name, description, url, icon, type, category, level, sort) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
    $stmt->execute([$name, $description, $url, $icon, $type, $category, $level]);
    
    $id = $pdo->lastInsertId();
    
    jsonResponse(200, '添加成功', ['id' => (int)$id]);
} catch (PDOException $e) {
    jsonResponse(500, '添加失败: ' . $e->getMessage());
}
