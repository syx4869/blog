<?php
require_once '../../db.php';

$pdo = getDB();

// 获取关于我完整数据
function getAboutData($pdo) {
    // 基本信息
    $stmt = $pdo->query("SELECT * FROM about_info ORDER BY id DESC LIMIT 1");
    $info = $stmt->fetch();

    if (!$info) {
        $info = [
            'name' => '博主',
            'title' => '全栈开发工程师',
            'bio' => '一个热爱技术、喜欢折腾的人。',
            'avatar' => '',
            'email' => '',
            'github' => '',
            'wechat' => ''
        ];
    }

    // 经历
    $timeline = [];
    try {
        $stmt = $pdo->query("SELECT * FROM about_timeline ORDER BY sort ASC, id ASC");
        $timeline = $stmt->fetchAll();
    } catch (PDOException $e) {
        // 表可能不存在
    }

    // 项目
    $projects = [];
    try {
        $stmt = $pdo->query("SELECT * FROM about_projects ORDER BY sort ASC, id ASC");
        $projects = $stmt->fetchAll();
    } catch (PDOException $e) {
        // 表可能不存在
    }

    // 兴趣
    $interests = [];
    try {
        $stmt = $pdo->query("SELECT * FROM about_interests ORDER BY sort ASC, id ASC");
        $interests = $stmt->fetchAll();
    } catch (PDOException $e) {
        // 表可能不存在
    }

    // 价值观/引言
    $quote = '';
    try {
        $stmt = $pdo->query("SELECT quote FROM about_quotes ORDER BY id DESC LIMIT 1");
        $quoteRow = $stmt->fetch();
        if ($quoteRow) {
            $quote = $quoteRow['quote'];
        }
    } catch (PDOException $e) {
        // 表可能不存在
    }

    return [
        'info' => [
            'name' => $info['name'] ?? '博主',
            'title' => $info['title'] ?? '全栈开发工程师',
            'bio' => $info['bio'] ?? '一个热爱技术、喜欢折腾的人。',
            'avatar' => $info['avatar'] ?? '',
            'email' => $info['email'] ?? '',
            'github' => $info['github'] ?? '',
            'wechat' => $info['wechat'] ?? ''
        ],
        'timeline' => $timeline,
        'projects' => $projects,
        'interests' => $interests,
        'quote' => $quote
    ];
}

// 处理请求
try {
    $data = getAboutData($pdo);
    jsonResponse(200, 'success', $data);
} catch (PDOException $e) {
    jsonResponse(500, '查询失败: ' . $e->getMessage());
}
