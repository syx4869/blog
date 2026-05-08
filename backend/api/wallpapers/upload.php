<?php
// 捕获所有 PHP 错误，防止返回 HTML 错误页面
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => 500,
        'message' => '服务器错误: ' . $errstr . ' (文件: ' . basename($errfile) . ', 行: ' . $errline . ')',
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

set_exception_handler(function($e) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'code' => 500,
        'message' => '服务器异常: ' . $e->getMessage(),
        'data' => null
    ], JSON_UNESCAPED_UNICODE);
    exit;
});

require_once '../../config.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, '请求方法错误');
}

// 上传目录
$uploadDir = __DIR__ . '/../../wallpapers/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0777, true)) {
        jsonResponse(500, '创建上传目录失败，请检查权限');
    }
}

// 检查文件
if (!isset($_FILES['file'])) {
    jsonResponse(400, '没有上传文件');
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => '文件大小超过服务器限制 (upload_max_filesize)',
        UPLOAD_ERR_FORM_SIZE => '文件大小超过表单限制 (MAX_FILE_SIZE)',
        UPLOAD_ERR_PARTIAL => '文件上传不完整',
        UPLOAD_ERR_NO_FILE => '没有文件被上传',
        UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
        UPLOAD_ERR_CANT_WRITE => '文件写入失败',
        UPLOAD_ERR_EXTENSION => '上传被扩展阻止'
    ];
    $errorMsg = isset($errorMessages[$file['error']]) ? $errorMessages[$file['error']] : '未知上传错误 (代码: ' . $file['error'] . ')';
    jsonResponse(400, $errorMsg);
}

// 验证文件类型
$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];

// 优先使用 finfo，如果不支持则使用 getimagesize
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
} else {
    $imageInfo = getimagesize($file['tmp_name']);
    $mimeType = $imageInfo ? $imageInfo['mime'] : 'unknown';
}

if (!in_array($mimeType, $allowedTypes)) {
    jsonResponse(400, '不支持的文件类型: ' . $mimeType . '，仅支持 JPG, PNG');
}

// 验证文件大小 (最大 20MB)
$maxSize = 20 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    jsonResponse(400, '文件大小超过 20MB 限制');
}

// 获取图片尺寸
$imageInfo = getimagesize($file['tmp_name']);
if (!$imageInfo) {
    jsonResponse(400, '无法读取图片信息');
}

$width = $imageInfo[0];
$height = $imageInfo[1];

// 最小分辨率检查 (1920x1080)
if ($width < 1920 || $height < 1080) {
    jsonResponse(400, "图片分辨率过低: {$width}x{$height}，最低要求 1920x1080");
}

// 生成唯一文件名
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
if (empty($ext)) {
    $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/jpg' => 'jpg'];
    $ext = isset($extMap[$mimeType]) ? $extMap[$mimeType] : 'jpg';
}

// 兼容 PHP 5/7 - 使用 uniqid + mt_rand 替代 random_bytes
if (function_exists('random_bytes')) {
    $random = bin2hex(random_bytes(4));
} else {
    $random = uniqid() . mt_rand(1000, 9999);
}

$filename = 'wallpaper_' . time() . '_' . $random . '.' . strtolower($ext);
$filepath = $uploadDir . $filename;

// 移动上传文件
if (!move_uploaded_file($file['tmp_name'], $filepath)) {
    jsonResponse(500, '保存文件失败，请检查 wallpapers 目录权限');
}

// 保存到数据库
require_once '../../db.php';
$pdo = getDB();

$title = isset($_POST['title']) ? trim($_POST['title']) : null;
$category = isset($_POST['category']) ? trim($_POST['category']) : 'default';
$fileUrl = SITE_URL . '/wallpapers/' . $filename;

try {
    $stmt = $pdo->prepare("INSERT INTO wallpapers (title, filename, url, width, height, file_size, mime_type, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $filename, $fileUrl, $width, $height, $file['size'], $mimeType, $category]);
    $wallpaperId = $pdo->lastInsertId();

    jsonResponse(200, '壁纸上传成功', [
        'id' => (int)$wallpaperId,
        'url' => $fileUrl,
        'filename' => $filename,
        'width' => $width,
        'height' => $height,
        'size' => $file['size']
    ]);
} catch (PDOException $e) {
    // 删除已上传的文件
    if (file_exists($filepath)) {
        unlink($filepath);
    }
    jsonResponse(500, '保存到数据库失败: ' . $e->getMessage());
}
