<?php
require_once '../../db.php';

$pdo = getDB();

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = isset($_GET['pageSize']) ? min(50, max(1, (int)$_GET['pageSize'])) : 20;
$category = isset($_GET['category']) ? trim($_GET['category']) : null;
$status = isset($_GET['status']) ? $_GET['status'] : 'all'; // all, active, inactive

$offset = ($page - 1) * $pageSize;

try {
    $whereClause = '1=1';
    $params = [];

    if ($category) {
        $whereClause .= ' AND category = ?';
        $params[] = $category;
    }

    if ($status === 'active') {
        $whereClause .= ' AND is_active = 1';
    } elseif ($status === 'inactive') {
        $whereClause .= ' AND is_active = 0';
    }

    // 获取列表
    $sql = "SELECT id, title, filename, url, width, height, file_size, mime_type, category, is_active, used_count, last_used_date, created_at 
            FROM wallpapers 
            WHERE $whereClause 
            ORDER BY created_at DESC 
            LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_merge($params, [$pageSize, $offset]));
    $wallpapers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 获取总数
    $countSql = "SELECT COUNT(*) FROM wallpapers WHERE $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    // 获取分类列表
    $catStmt = $pdo->query("SELECT DISTINCT category FROM wallpapers ORDER BY category");
    $categories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

    jsonResponse(200, 'success', [
        'list' => $wallpapers,
        'total' => (int)$total,
        'page' => $page,
        'pageSize' => $pageSize,
        'categories' => $categories
    ]);
} catch (PDOException $e) {
    jsonResponse(500, '查询失败: ' . $e->getMessage());
}
