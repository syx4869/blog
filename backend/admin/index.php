<?php
require_once 'auth.php';
requireAdminLogin();
require_once '../db.php';
$pdo = getDB();
$currentAdmin = getCurrentAdmin();

// 获取统计数据
$articleCount = $pdo->query("SELECT COUNT(*) FROM articles WHERE is_published = 1")->fetchColumn();
$wallpaperCount = $pdo->query("SELECT COUNT(*) FROM wallpapers WHERE is_active = 1")->fetchColumn();
$commentCount = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$totalViews = $pdo->query("SELECT SUM(view_count) FROM articles")->fetchColumn() ?: 0;

// 获取最近文章
$recentArticles = $pdo->query("SELECT id, title, created_at FROM articles WHERE is_published = 1 ORDER BY created_at DESC LIMIT 5")->fetchAll();

// 获取今日评论数
$today = date('Y-m-d');
$todayComments = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE DATE(created_at) = ?");
$todayComments->execute([$today]);
$todayCommentCount = $todayComments->fetchColumn();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>博客后台管理</title>
  <link rel="stylesheet" href="iconfont.css">
  <style>
    @font-face {
      font-family: 'PingFang HK';
      src: url('PingFang HK.ttf') format('truetype');
      font-weight: normal;
      font-style: normal;
      font-display: swap;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'PingFang HK', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif; background: #18181b; color: #d4d4d8; min-height: 100vh; }

    .header { background: #1e1e24; border-bottom: 1px solid #2a2a30; padding: 14px 28px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 100; }
    .header h1 { font-size: 1.2rem; color: #e4e4e7; font-weight: 600; }
    .header-actions { display: flex; gap: 10px; }

    .btn { padding: 8px 20px; border: none; border-radius: 6px; font-size: 0.85rem; cursor: pointer; transition: all 0.15s; font-family: inherit; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .btn-primary { background: #3b82f6; color: white; }
    .btn-primary:hover { background: #2563eb; }
    .btn-secondary { background: transparent; color: #a1a1aa; border: 1px solid #2a2a30; }
    .btn-secondary:hover { background: #27272a; color: #e4e4e7; }

    .container { max-width: 1360px; margin: 0 auto; padding: 24px 28px; }

    /* 导航卡片 */
    .nav-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 28px; }
    .nav-card { background: #1e1e24; border: 1px solid #2a2a30; border-radius: 10px; padding: 24px; cursor: pointer; transition: all 0.15s; text-decoration: none; color: inherit; }
    .nav-card:hover { border-color: #3f3f46; transform: translateY(-2px); }
    .nav-icon { font-size: 1.8rem; margin-bottom: 12px; color: #3b82f6; }
    .nav-icon .iconfont { font-size: 1.8rem; }
    .nav-title { font-size: 1.05rem; color: #e4e4e7; font-weight: 600; margin-bottom: 4px; }
    .nav-desc { font-size: 0.82rem; color: #71717a; }

    /* 统计卡片 */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 28px; }
    .stat-card { background: #1e1e24; border: 1px solid #2a2a30; border-radius: 10px; padding: 20px; }
    .stat-value { font-size: 2rem; font-weight: 700; color: #e4e4e7; margin-bottom: 4px; }
    .stat-label { font-size: 0.82rem; color: #71717a; }

    /* 快捷操作 */
    .section-title { font-size: 1.1rem; color: #e4e4e7; font-weight: 600; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
    .section-title .iconfont { font-size: 1.2rem; color: #3b82f6; }
    .quick-actions { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 28px; }

    /* 最近动态 */
    .activity-list { background: #1e1e24; border: 1px solid #2a2a30; border-radius: 10px; overflow: hidden; }
    .activity-item { padding: 14px 20px; border-bottom: 1px solid #2a2a30; display: flex; align-items: center; gap: 12px; }
    .activity-item:last-child { border-bottom: none; }
    .activity-icon { width: 36px; height: 36px; border-radius: 8px; background: #27272a; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .activity-icon .iconfont { font-size: 1rem; color: #3b82f6; }
    .activity-content { flex: 1; }
    .activity-text { font-size: 0.9rem; color: #d4d4d8; }
    .activity-time { font-size: 0.78rem; color: #52525b; margin-top: 2px; }

    .empty-state { text-align: center; padding: 40px; color: #52525b; }

    @media (max-width: 768px) {
      .nav-grid { grid-template-columns: 1fr; }
      .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
  </style>
</head>
<body>
  <div class="header">
    <h1>博客后台管理</h1>
    <div class="header-actions">
      <span style="color: #a1a1aa; font-size: 0.85rem; margin-right: 10px;">
        <?php echo htmlspecialchars($currentAdmin['nickname'] ?? $currentAdmin['username'] ?? '管理员'); ?>
      </span>
      <a href="../" class="btn btn-secondary" target="_blank">查看前台</a>
      <a href="logout.php" class="btn btn-secondary">退出登录</a>
    </div>
  </div>

  <div class="container">
    <!-- 功能导航 -->
    <div class="nav-grid">
      <a href="editor.php" class="nav-card">
        <div class="nav-icon"><i class="iconfont icon-bianjiwenjian"></i></div>
        <div class="nav-title">文章管理</div>
        <div class="nav-desc">发布、编辑、删除文章</div>
      </a>
      <a href="comments.php" class="nav-card">
        <div class="nav-icon"><i class="iconfont icon-31pinglun"></i></div>
        <div class="nav-title">评论管理</div>
        <div class="nav-desc">审核、回复、删除评论</div>
      </a>
      <a href="wallpapers.php" class="nav-card">
        <div class="nav-icon"><i class="iconfont icon-shoucang"></i></div>
        <div class="nav-title">壁纸管理</div>
        <div class="nav-desc">上传、管理每日壁纸</div>
      </a>
      <a href="resources.php" class="nav-card">
        <div class="nav-icon"><i class="iconfont icon-xiangguanwenzhang"></i></div>
        <div class="nav-title">资源管理</div>
        <div class="nav-desc">管理资源推荐内容</div>
      </a>
    </div>

    <!-- 数据统计 -->
    <div class="section-title"><i class="iconfont icon-icon-"></i> 数据统计</div>
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-value"><?php echo $articleCount; ?></div>
        <div class="stat-label">文章总数</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $todayCommentCount; ?></div>
        <div class="stat-label">今日评论</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $wallpaperCount; ?></div>
        <div class="stat-label">壁纸总数</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo number_format($totalViews); ?></div>
        <div class="stat-label">总浏览量</div>
      </div>
    </div>

    <!-- 快捷操作 -->
    <div class="section-title"><i class="iconfont icon-shijian"></i> 快捷操作</div>
    <div class="quick-actions">
      <a href="editor.php" class="btn btn-primary">+ 新建文章</a>
      <a href="wallpapers.php" class="btn btn-secondary">上传壁纸</a>
      <a href="resources.php" class="btn btn-secondary">添加资源</a>
    </div>

    <!-- 最近动态 -->
    <div class="section-title"><i class="iconfont icon-24gf-calendar"></i> 最近动态</div>
    <div class="activity-list">
      <?php if (empty($recentArticles)): ?>
        <div class="empty-state">暂无动态</div>
      <?php else: ?>
        <?php foreach ($recentArticles as $article): ?>
          <div class="activity-item">
            <div class="activity-icon"><i class="iconfont icon-bianjiwenjian"></i></div>
            <div class="activity-content">
              <div class="activity-text">发布了文章《<?php echo htmlspecialchars($article['title']); ?>》</div>
              <div class="activity-time"><?php echo date('Y-m-d H:i', strtotime($article['created_at'])); ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
