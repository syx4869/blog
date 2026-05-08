<?php
require_once '../../db.php';

$pdo = getDB();

// 获取分类参数
$type = isset($_GET['type']) ? $_GET['type'] : '';

try {
    $sql = "SELECT id, name, description, url, icon, type, category, level, sort 
            FROM resources 
            WHERE is_active = 1";
    $params = [];

    if ($type && $type !== 'all') {
        $sql .= " AND type = ?";
        $params[] = $type;
    }

    $sql .= " ORDER BY sort ASC, id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resources = $stmt->fetchAll();

    // 格式化返回数据
    $result = [];
    foreach ($resources as $item) {
        $result[] = [
            'id' => (int)$item['id'],
            'name' => $item['name'],
            'description' => $item['description'] ?? '',
            'url' => $item['url'],
            'icon' => $item['icon'] ?? '',
            'type' => $item['type'],
            'category' => $item['category'] ?? '',
            'level' => $item['level'] ?? 'beginner',
            'sort' => (int)$item['sort']
        ];
    }

    jsonResponse(200, 'success', $result);
} catch (PDOException $e) {
    jsonResponse(500, '查询失败: ' . $e->getMessage());
}
