<?php
require_once '../../db.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方法错误');
}

$input = json_decode(file_get_contents('php://input'), true);

if (empty($input['title'])) {
    jsonResponse(400, '文章标题不能为空');
}

try {
    $pdo->beginTransaction();

    $title = trim($input['title']);
    $description = isset($input['description']) ? trim($input['description']) : '';
    $content = isset($input['content']) ? $input['content'] : '';
    $coverImage = isset($input['cover_image']) && $input['cover_image'] ? trim($input['cover_image']) : null;
    $categoryId = isset($input['category_id']) && $input['category_id'] ? (int)$input['category_id'] : null;
    $icon = isset($input['icon']) ? trim($input['icon']) : '📝';
    $isPublished = isset($input['is_published']) ? (int)$input['is_published'] : 1;
    $wordCount = isset($input['word_count']) ? (int)$input['word_count'] : 0;
    $readTime = isset($input['read_time']) ? (int)$input['read_time'] : 5;
    $tags = isset($input['tags']) ? $input['tags'] : [];

    $stmt = $pdo->prepare("INSERT INTO articles (title, description, content, cover_image, category_id, icon, is_published) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $description, $content, $coverImage, $categoryId, $icon, $isPublished]);

    $articleId = $pdo->lastInsertId();

    if (!empty($tags)) {
        foreach ($tags as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) continue;

            $tagStmt = $pdo->prepare("SELECT id FROM tags WHERE name = ?");
            $tagStmt->execute([$tagName]);
            $tag = $tagStmt->fetch(PDO::FETCH_ASSOC);

            if (!$tag) {
                $insertTag = $pdo->prepare("INSERT INTO tags (name) VALUES (?)");
                $insertTag->execute([$tagName]);
                $tagId = $pdo->lastInsertId();
            } else {
                $tagId = $tag['id'];
            }

            $linkStmt = $pdo->prepare("INSERT IGNORE INTO article_tags (article_id, tag_id) VALUES (?, ?)");
            $linkStmt->execute([$articleId, $tagId]);
        }
    }

    $pdo->commit();

    jsonResponse(200, '文章创建成功', [
        'id' => (int)$articleId,
        'title' => $title
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(500, '创建失败: ' . $e->getMessage());
}
