<?php
require_once '../../db.php';

$pdo = getDB();

try {
    $stmt = $pdo->query("SELECT id, `key`, name, icon, sort 
                          FROM resource_categories 
                          WHERE is_active = 1
                          ORDER BY sort ASC, id ASC");
    $categories = $stmt->fetchAll();
    
    // 格式化返回数据
    $result = [];
    foreach ($categories as $cat) {
        $result[] = [
            'key' => $cat['key'],
            'name' => $cat['name'],
            'icon' => $cat['icon'] ?? ''
        ];
    }
    
    jsonResponse(200, 'success', $result);
} catch (PDOException $e) {
    jsonResponse(500, '查询失败: ' . $e->getMessage());
}
