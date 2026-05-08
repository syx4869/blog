<?php
require_once '../../db.php';

$pdo = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    jsonResponse(400, '资源ID无效');
}

try {
    $stmt = $pdo->prepare("SELECT id, name, description, url, icon, type, category, level 
                           FROM resources WHERE id = ? AND is_active = 1");
    $stmt->execute([$id]);
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$resource) {
        jsonResponse(404, '资源不存在');
    }
    
    jsonResponse(200, 'success', [
        'id' => (int)$resource['id'],
        'name' => $resource['name'],
        'description' => $resource['description'] ?? '',
        'url' => $resource['url'],
        'icon' => $resource['icon'] ?? '',
        'type' => $resource['type'],
        'category' => $resource['category'] ?? '',
        'level' => $resource['level'] ?? 'beginner'
    ]);
} catch (PDOException $e) {
    jsonResponse(500, '查询失败: ' . $e->getMessage());
}
