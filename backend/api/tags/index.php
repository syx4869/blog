<?php
require_once '../../db.php';

$pdo = getDB();

try {
    $stmt = $pdo->query("SELECT t.id, t.name, COUNT(at.article_id) as count 
                          FROM tags t
                          LEFT JOIN article_tags at ON t.id = at.tag_id
                          LEFT JOIN articles a ON at.article_id = a.id AND a.is_published = 1
                          GROUP BY t.id
                          ORDER BY count DESC, t.id ASC");
    $tags = $stmt->fetchAll();
    
    foreach ($tags as &$tag) {
        $tag['count'] = (int)$tag['count'];
    }
    
    jsonResponse(200, 'success', $tags);
} catch (PDOException $e) {
    jsonResponse(500, '查询失败: ' . $e->getMessage());
}
