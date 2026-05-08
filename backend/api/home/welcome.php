<?php
require_once '../../db.php';

$pdo = getDB();

try {
    $stmt = $pdo->query("SELECT text FROM welcome_texts ORDER BY sort ASC, id ASC");
    $texts = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    jsonResponse(200, 'success', ['texts' => $texts]);
} catch (PDOException $e) {
    jsonResponse(500, '查询失败: ' . $e->getMessage());
}
