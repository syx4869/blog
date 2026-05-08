<?php
require_once '../../db.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonResponse(405, '请求方法错误');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    jsonResponse(400, '壁纸ID无效');
}

$wallpaperId = (int)$_GET['id'];

try {
    // 获取壁纸信息
    $stmt = $pdo->prepare("SELECT filename FROM wallpapers WHERE id = ?");
    $stmt->execute([$wallpaperId]);
    $wallpaper = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallpaper) {
        jsonResponse(404, '壁纸不存在');
    }

    // 检查是否被用作每日壁纸
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM daily_wallpapers WHERE wallpaper_id = ?");
    $checkStmt->execute([$wallpaperId]);
    $usedCount = $checkStmt->fetchColumn();

    // 删除数据库记录
    $deleteStmt = $pdo->prepare("DELETE FROM wallpapers WHERE id = ?");
    $deleteStmt->execute([$wallpaperId]);

    // 删除物理文件
    $filepath = __DIR__ . '/../../wallpapers/' . $wallpaper['filename'];
    if (file_exists($filepath)) {
        unlink($filepath);
    }

    jsonResponse(200, '壁纸删除成功', [
        'wasUsed' => $usedCount > 0
    ]);
} catch (PDOException $e) {
    jsonResponse(500, '删除失败: ' . $e->getMessage());
}
