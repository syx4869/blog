<?php
require_once '../../db.php';

$pdo = getDB();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方法错误');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    jsonResponse(400, '壁纸ID无效');
}

$wallpaperId = (int)$_GET['id'];

try {
    // 获取当前状态
    $stmt = $pdo->prepare("SELECT is_active FROM wallpapers WHERE id = ?");
    $stmt->execute([$wallpaperId]);
    $wallpaper = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallpaper) {
        jsonResponse(404, '壁纸不存在');
    }

    $newStatus = $wallpaper['is_active'] ? 0 : 1;

    $updateStmt = $pdo->prepare("UPDATE wallpapers SET is_active = ? WHERE id = ?");
    $updateStmt->execute([$newStatus, $wallpaperId]);

    jsonResponse(200, '状态更新成功', [
        'id' => $wallpaperId,
        'is_active' => $newStatus
    ]);
} catch (PDOException $e) {
    jsonResponse(500, '更新失败: ' . $e->getMessage());
}
