<?php
require_once '../../db.php';

$pdo = getDB();

try {
    $stmt = $pdo->query("SELECT c.id, c.name, COUNT(a.id) as count 
                          FROM categories c
                          LEFT JOIN articles a ON c.id = a.category_id AND a.is_published = 1
                          GROUP BY c.id
                          ORDER BY c.sort ASC, c.id ASC");
    $categories = $stmt->fetchAll();
    
    foreach ($categories as &$cat) {
        $cat['count'] = (int)$cat['count'];
    }
    
    jsonResponse(200, 'success', $categories);
} catch (PDOException $e) {
    jsonResponse(500, '查询失败: ' . $e->getMessage());
}
