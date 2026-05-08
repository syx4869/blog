<?php
require_once '../../db.php';

$pdo = getDB();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    jsonResponse(400, '文章ID无效');
}

$articleId = (int)$_GET['id'];

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("UPDATE articles SET view_count = view_count + 1 WHERE id = ?");
    $stmt->execute([$articleId]);
    
    $sql = "SELECT a.id, a.title, a.description, a.content, a.cover_image, a.category_id, a.icon,
                   a.view_count, a.like_count, a.collect_count, a.is_published, a.created_at, a.updated_at,
                   c.name as category_name
            FROM articles a
            LEFT JOIN categories c ON a.category_id = c.id
            WHERE a.id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$articleId]);
    $article = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$article) {
        $pdo->rollBack();
        jsonResponse(404, '文章不存在');
    }
    
    if ($article['is_published'] == 0) {
        $pdo->rollBack();
        jsonResponse(404, '文章不存在');
    }
    
    $tagStmt = $pdo->prepare("SELECT t.id, t.name FROM tags t 
                              JOIN article_tags at ON t.id = at.tag_id 
                              WHERE at.article_id = ?");
    $tagStmt->execute([$articleId]);
    $tags = $tagStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $prevStmt = $pdo->prepare("SELECT id, title FROM articles 
                               WHERE id < ? AND is_published = 1 
                               ORDER BY id DESC LIMIT 1");
    $prevStmt->execute([$articleId]);
    $prevArticle = $prevStmt->fetch(PDO::FETCH_ASSOC);
    
    $nextStmt = $pdo->prepare("SELECT id, title FROM articles 
                               WHERE id > ? AND is_published = 1 
                               ORDER BY id ASC LIMIT 1");
    $nextStmt->execute([$articleId]);
    $nextArticle = $nextStmt->fetch(PDO::FETCH_ASSOC);
    
    $tagIds = array_column($tags, 'id');
    $relatedArticles = [];
    if (!empty($tagIds)) {
        $placeholders = str_repeat('?,', count($tagIds) - 1) . '?';
        $relatedSql = "SELECT DISTINCT a.id, a.title, a.cover_image, a.icon, a.view_count
                       FROM articles a
                       JOIN article_tags at ON a.id = at.article_id
                       WHERE at.tag_id IN ($placeholders)
                         AND a.id != ?
                         AND a.is_published = 1
                       ORDER BY a.view_count DESC
                       LIMIT 5";
        $relatedParams = array_merge($tagIds, [$articleId]);
        $relatedStmt = $pdo->prepare($relatedSql);
        $relatedStmt->execute($relatedParams);
        $relatedArticles = $relatedStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $content = $article['content'];
    $wordCount = mb_strlen(strip_tags($content));
    $readTime = max(1, ceil($wordCount / 300));
    
    // 获取收藏数
    $collectStmt = $pdo->prepare("SELECT COUNT(*) FROM collects WHERE article_id = ?");
    $collectStmt->execute([$articleId]);
    $collectCount = $collectStmt->fetchColumn();

    // 获取当前IP是否收藏
    $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    $ipAddress = '0.0.0.0';
    foreach ($headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                $ipAddress = $ip;
                break;
            }
        }
    }
    $isCollectedStmt = $pdo->prepare("SELECT id FROM collects WHERE article_id = ? AND ip_address = ?");
    $isCollectedStmt->execute([$articleId, $ipAddress]);
    $isCollected = (bool)$isCollectedStmt->fetch();

    $result = [
        'id' => (int)$article['id'],
        'title' => $article['title'],
        'content' => $article['content'],
        'summary' => $article['description'],
        'cover_image' => $article['cover_image'],
        'is_published' => (int)$article['is_published'],
        'author' => [
            'id' => 1,
            'name' => '博主',
            'avatar' => '',
            'bio' => '热爱技术的开发者'
        ],
        'category' => [
            'id' => (int)$article['category_id'],
            'name' => $article['category_name'] ?: '未分类'
        ],
        'tags' => $tags,
        'viewCount' => (int)$article['view_count'],
        'likeCount' => (int)$article['like_count'],
        'collectCount' => (int)($article['collect_count'] ?? 0),
        'isCollected' => $isCollected,
        'commentCount' => 0,
        'wordCount' => $wordCount,
        'readTime' => $readTime,
        'createdAt' => $article['created_at'],
        'updatedAt' => $article['updated_at'],
        'prevArticle' => $prevArticle ? ['id' => (int)$prevArticle['id'], 'title' => $prevArticle['title']] : null,
        'nextArticle' => $nextArticle ? ['id' => (int)$nextArticle['id'], 'title' => $nextArticle['title']] : null,
        'relatedArticles' => array_map(function($item) {
            return [
                'id' => (int)$item['id'],
                'title' => $item['title'],
                'cover_image' => $item['cover_image'],
                'icon' => $item['icon'] ?: '📝',
                'viewCount' => (int)$item['view_count']
            ];
        }, $relatedArticles)
    ];
    
    $pdo->commit();
    
    jsonResponse(200, 'success', $result);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(500, '查询失败: ' . $e->getMessage());
}
