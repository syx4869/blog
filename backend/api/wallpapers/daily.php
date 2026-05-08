<?php
require_once '../../db.php';

$pdo = getDB();

// 获取今天的日期
$today = date('Y-m-d');

try {
    // 1. 检查今天是否已经有选定的壁纸
    $checkStmt = $pdo->prepare("SELECT dw.date, w.id, w.title, w.filename, w.url, w.width, w.height, w.file_size, w.category 
                                FROM daily_wallpapers dw 
                                JOIN wallpapers w ON dw.wallpaper_id = w.id 
                                WHERE dw.date = ?");
    $checkStmt->execute([$today]);
    $todayWallpaper = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($todayWallpaper) {
        // 今天已经有壁纸了，直接返回
        jsonResponse(200, 'success', formatWallpaperResponse($todayWallpaper));
        return;
    }

    // 2. 选择今天的壁纸
    // 策略：优先选择使用次数最少且最近7天未使用过的壁纸
    $sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));

    // 先尝试找最近7天未使用过的
    $selectStmt = $pdo->prepare("
        SELECT w.id, w.title, w.filename, w.url, w.width, w.height, w.file_size, w.category, w.used_count
        FROM wallpapers w
        WHERE w.is_active = 1
        AND (w.last_used_date IS NULL OR w.last_used_date < ?)
        ORDER BY w.used_count ASC, RAND()
        LIMIT 1
    ");
    $selectStmt->execute([$sevenDaysAgo]);
    $selectedWallpaper = $selectStmt->fetch(PDO::FETCH_ASSOC);

    // 如果没有找到（所有壁纸最近都用过），则随机选一个使用次数最少的
    if (!$selectedWallpaper) {
        $fallbackStmt = $pdo->prepare("
            SELECT w.id, w.title, w.filename, w.url, w.width, w.height, w.file_size, w.category, w.used_count
            FROM wallpapers w
            WHERE w.is_active = 1
            ORDER BY w.used_count ASC, RAND()
            LIMIT 1
        ");
        $fallbackStmt->execute();
        $selectedWallpaper = $fallbackStmt->fetch(PDO::FETCH_ASSOC);
    }

    if (!$selectedWallpaper) {
        // 没有找到可用的壁纸，返回空数据（使用 CSS 渐变背景）
        jsonResponse(200, 'success', getDefaultWallpaper());
        return;
    }

    // 3. 记录今日壁纸
    $pdo->beginTransaction();

    $insertStmt = $pdo->prepare("INSERT INTO daily_wallpapers (date, wallpaper_id) VALUES (?, ?)");
    $insertStmt->execute([$today, $selectedWallpaper['id']]);

    // 4. 更新壁纸使用次数和最后使用日期
    $updateStmt = $pdo->prepare("UPDATE wallpapers SET used_count = used_count + 1, last_used_date = ? WHERE id = ?");
    $updateStmt->execute([$today, $selectedWallpaper['id']]);

    $pdo->commit();

    jsonResponse(200, 'success', formatWallpaperResponse($selectedWallpaper));
} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // 出错时返回默认壁纸
    jsonResponse(200, 'success', getDefaultWallpaper());
}

function formatWallpaperResponse($wallpaper) {
    return [
        'id' => (int)$wallpaper['id'],
        'title' => $wallpaper['title'] ?? '每日壁纸',
        'url' => $wallpaper['url'],
        'filename' => $wallpaper['filename'],
        'width' => (int)$wallpaper['width'],
        'height' => (int)$wallpaper['height'],
        'fileSize' => (int)$wallpaper['file_size'],
        'category' => $wallpaper['category'] ?? 'default',
        'date' => date('Y-m-d')
    ];
}

function getDefaultWallpaper() {
    return [
        'id' => 0,
        'title' => '默认壁纸',
        'url' => 'https://images.unsplash.com/photo-1477346611705-65d1883cee1e?w=1920&q=80',
        'filename' => 'default.jpg',
        'width' => 1920,
        'height' => 1080,
        'fileSize' => 0,
        'category' => 'default',
        'date' => date('Y-m-d')
    ];
}
