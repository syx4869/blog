<?php
require_once '../../db.php';

$pdo = getDB();

// 只允许 POST 请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方法不允许');
    return;
}

// 获取参数
$input = json_decode(file_get_contents('php://input'), true);
$wallpaperId = isset($input['wallpaper_id']) ? (int)$input['wallpaper_id'] : 0;

if ($wallpaperId <= 0) {
    jsonResponse(400, '请提供有效的壁纸ID');
    return;
}

$today = date('Y-m-d');

try {
    // 1. 检查壁纸是否存在且启用
    $checkStmt = $pdo->prepare("SELECT id, title, filename, url, width, height, file_size, category FROM wallpapers WHERE id = ? AND is_active = 1");
    $checkStmt->execute([$wallpaperId]);
    $wallpaper = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallpaper) {
        jsonResponse(404, '壁纸不存在或已禁用');
        return;
    }

    // 2. 开启事务
    $pdo->beginTransaction();

    // 3. 删除今天的旧记录（如果存在）
    $deleteStmt = $pdo->prepare("DELETE FROM daily_wallpapers WHERE date = ?");
    $deleteStmt->execute([$today]);

    // 4. 插入新的今日壁纸记录
    $insertStmt = $pdo->prepare("INSERT INTO daily_wallpapers (date, wallpaper_id) VALUES (?, ?)");
    $insertStmt->execute([$today, $wallpaperId]);

    // 5. 更新壁纸使用次数和最后使用日期
    $updateStmt = $pdo->prepare("UPDATE wallpapers SET used_count = used_count + 1, last_used_date = ? WHERE id = ?");
    $updateStmt->execute([$today, $wallpaperId]);

    $pdo->commit();

    jsonResponse(200, '今日壁纸已更新', [
        'id' => (int)$wallpaper['id'],
        'title' => $wallpaper['title'] ?? '每日壁纸',
        'url' => $wallpaper['url'],
        'filename' => $wallpaper['filename'],
        'width' => (int)$wallpaper['width'],
        'height' => (int)$wallpaper['height'],
        'fileSize' => (int)$wallpaper['file_size'],
        'category' => $wallpaper['category'] ?? 'default',
        'date' => $today
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(500, '设置失败: ' . $e->getMessage());
}
