<?php
require_once '../../db.php';

$pdo = getDB();

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = isset($_GET['pageSize']) ? max(1, min(50, (int)$_GET['pageSize'])) : 10;
$category = isset($_GET['category']) ? $_GET['category'] : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

try {
    $where = ['a.is_published = 1'];
    $params = [];
    
    if ($category) {
        $where[] = 'a.category_id = ?';
        $params[] = $category;
    }
    
    if ($keyword) {
        $where[] = '(a.title LIKE ? OR a.description LIKE ?)';
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
    }
    
    $whereClause = implode(' AND ', $where);
    
    $offset = ($page - 1) * $pageSize;
    
    $sql = "SELECT a.id, a.title, a.description, a.cover_image, a.category_id, a.icon,
                   a.view_count, a.like_count, a.is_published, a.created_at,
                   c.name as category_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE $whereClause
            ORDER BY a.created_at DESC
            LIMIT $offset, $pageSize";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $articles = $stmt->fetchAll();
    
    foreach ($articles as &$article) {
        $tagStmt = $pdo->prepare("SELECT t.name FROM tags t 
                                  JOIN article_tags at ON t.id = at.tag_id 
                                  WHERE at.article_id = ?");
        $tagStmt->execute([$article['id']]);
        $article['tags'] = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
        
        $article['category'] = $article['category_name'] ?: '未分类';
        $article['date'] = date('Y-m-d', strtotime($article['created_at']));
        $article['viewCount'] = (int)$article['view_count'];
        $article['likeCount'] = (int)$article['like_count'];
        
        unset($article['category_name'], $article['created_at'], $article['view_count'], $article['like_count']);
    }
    
    $countSql = "SELECT COUNT(*) FROM articles a WHERE $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    
    jsonResponse(200, 'success', [
        'list' => $articles,
        'total' => (int)$total,
        'page' => $page,
        'pageSize' => $pageSize
    ]);
} catch (PDOException $e) {
    jsonResponse(500, '查询失败: ' . $e->getMessage());
}
