<?php
require_once '../../db.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    jsonResponse(405, '请求方法错误');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    jsonResponse(400, '文章ID无效');
}

$articleId = (int)$_GET['id'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $pdo->beginTransaction();

    $checkStmt = $pdo->prepare("SELECT id FROM articles WHERE id = ?");
    $checkStmt->execute([$articleId]);
    if (!$checkStmt->fetch()) {
        $pdo->rollBack();
        jsonResponse(404, '文章不存在');
    }

    $fields = [];
    $params = [];

    if (isset($input['title'])) {
        $fields[] = 'title = ?';
        $params[] = trim($input['title']);
    }
    if (array_key_exists('description', $input)) {
        $fields[] = 'description = ?';
        $params[] = trim($input['description']);
    }
    if (array_key_exists('content', $input)) {
        $fields[] = 'content = ?';
        $params[] = $input['content'];
    }
    if (array_key_exists('cover_image', $input)) {
        $fields[] = 'cover_image = ?';
        $params[] = $input['cover_image'] ?: null;
    }
    if (array_key_exists('category_id', $input)) {
        $fields[] = 'category_id = ?';
        $params[] = $input['category_id'] ? (int)$input['category_id'] : null;
    }
    if (isset($input['icon'])) {
        $fields[] = 'icon = ?';
        $params[] = trim($input['icon']);
    }

    if (array_key_exists('is_published', $input)) {
        $fields[] = 'is_published = ?';
        $params[] = (int)$input['is_published'];
    }

    if (!empty($fields)) {
        $params[] = $articleId;
        $sql = "UPDATE articles SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    if (isset($input['tags'])) {
        $deleteStmt = $pdo->prepare("DELETE FROM article_tags WHERE article_id = ?");
        $deleteStmt->execute([$articleId]);

        foreach ($input['tags'] as $tagName) {
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

    jsonResponse(200, '文章更新成功', ['id' => $articleId]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(500, '更新失败: ' . $e->getMessage());
}
