<?php
require_once 'auth.php';
requireAdminLogin();
require_once '../db.php';
$pdo = getDB();

// 获取所有评论（关联文章信息）
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "
  SELECT c.*, a.title as article_title, a.id as article_id
  FROM comments c
  LEFT JOIN articles a ON c.article_id = a.id
  WHERE 1=1
";
$params = [];

if ($search) {
  $sql .= " AND (c.content LIKE ? OR c.nickname LIKE ? OR a.title LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
  $params[] = "%$search%";
}

$sql .= " ORDER BY c.created_at DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$comments = $stmt->fetchAll();

// 获取评论统计
$totalComments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$today = date('Y-m-d');
$todayCount = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE DATE(created_at) = ?");
$todayCount->execute([$today]);
$todayComments = $todayCount->fetchColumn();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>评论管理</title>
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
    .header h1 { font-size: 1.2rem; color: #e4e4e7; font-weight: 600; display: flex; align-items: center; gap: 8px; }
    .header h1 .iconfont { font-size: 1.3rem; color: #3b82f6; }
    .header-actions { display: flex; gap: 10px; }

    .btn { padding: 8px 20px; border: none; border-radius: 6px; font-size: 0.85rem; cursor: pointer; transition: all 0.15s; font-family: inherit; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .btn-primary { background: #3b82f6; color: white; }
    .btn-primary:hover { background: #2563eb; }
    .btn-secondary { background: transparent; color: #a1a1aa; border: 1px solid #2a2a30; }
    .btn-secondary:hover { background: #27272a; color: #e4e4e7; }
    .btn-danger { background: transparent; color: #f87171; border: 1px solid #451a1a; }
    .btn-danger:hover { background: #451a1a; }
    .btn-sm { padding: 5px 12px; font-size: 0.78rem; }
    .btn .iconfont { font-size: 0.9rem; }

    .container { max-width: 1200px; margin: 0 auto; padding: 24px 28px; }

    .stats-bar { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .stat-card { background: #1e1e24; border: 1px solid #2a2a30; border-radius: 8px; padding: 16px 20px; flex: 1; min-width: 160px; }
    .stat-value { font-size: 1.8rem; font-weight: 700; color: #e4e4e7; }
    .stat-label { font-size: 0.78rem; color: #71717a; margin-top: 2px; }

    .toolbar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
    .search-input { padding: 8px 14px; background: #27272a; border: 1px solid #2a2a30; border-radius: 6px; color: #d4d4d8; font-size: 0.85rem; outline: none; min-width: 200px; }
    .search-input:focus { border-color: #3b82f6; }
    .search-input::placeholder { color: #52525b; }

    .comment-list { display: flex; flex-direction: column; gap: 12px; }
    .comment-card { background: #1e1e24; border: 1px solid #2a2a30; border-radius: 10px; padding: 20px; }
    .comment-header { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
    .comment-avatar { width: 40px; height: 40px; border-radius: 50%; background: #27272a; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
    .comment-avatar .iconfont { font-size: 1.2rem; color: #3b82f6; }
    .comment-meta { flex: 1; }
    .comment-author { font-size: 0.95rem; color: #e4e4e7; font-weight: 500; }
    .comment-time { font-size: 0.78rem; color: #52525b; }
    .comment-article { font-size: 0.82rem; color: #71717a; margin-top: 2px; }
    .comment-article a { color: #60a5fa; text-decoration: none; }
    .comment-article a:hover { text-decoration: underline; }
    .comment-content { color: #d4d4d8; line-height: 1.6; margin-bottom: 12px; padding-left: 52px; }
    .comment-actions { display: flex; gap: 8px; padding-left: 52px; }

    .replies { margin-top: 12px; margin-left: 52px; padding-left: 16px; border-left: 2px solid #2a2a30; }
    .reply-item { padding: 10px 0; border-bottom: 1px solid #2a2a30; }
    .reply-item:last-child { border-bottom: none; }
    .reply-header { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
    .reply-author { font-size: 0.88rem; color: #e4e4e7; }
    .reply-to { font-size: 0.78rem; color: #71717a; }
    .reply-content { font-size: 0.88rem; color: #a1a1aa; }

    .empty-state { text-align: center; padding: 60px 20px; color: #52525b; }
    .empty-state .iconfont { font-size: 3rem; margin-bottom: 12px; color: #3f3f46; }

    .toast { position: fixed; top: 70px; right: 28px; padding: 12px 20px; border-radius: 6px; font-size: 0.85rem; z-index: 1000; transform: translateX(120%); transition: transform 0.2s ease; font-weight: 500; }
    .toast.show { transform: translateX(0); }
    .toast-success { background: #052e16; color: #4ade80; border: 1px solid #166534; }
    .toast-error { background: #450a0a; color: #f87171; border: 1px solid #7f1d1d; }

    @media (max-width: 768px) {
      .comment-content, .comment-actions, .replies { padding-left: 0; margin-left: 0; }
    }
  </style>
</head>
<body>
  <div class="header">
    <h1><i class="iconfont icon-31pinglun"></i> 评论管理</h1>
    <div class="header-actions">
      <a href="index.php" class="btn btn-secondary"><i class="iconfont icon-xiangguanwenzhang"></i> 返回首页</a>
    </div>
  </div>

  <div class="container">
    <div class="stats-bar">
      <div class="stat-card">
        <div class="stat-value"><?php echo $totalComments; ?></div>
        <div class="stat-label">评论总数</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $todayComments; ?></div>
        <div class="stat-label">今日评论</div>
      </div>
    </div>

    <form class="toolbar" method="GET" action="">
      <input type="text" class="search-input" name="search" placeholder="搜索评论内容或昵称..." value="<?php echo htmlspecialchars($search); ?>">
      <button type="submit" class="btn btn-secondary btn-sm"><i class="iconfont icon-icon-"></i> 搜索</button>
      <?php if ($search): ?>
        <a href="comments.php" class="btn btn-secondary btn-sm">重置</a>
      <?php endif; ?>
    </form>

    <div class="comment-list" id="commentList">
      <?php if (empty($comments)): ?>
        <div class="empty-state">
          <i class="iconfont icon-31pinglun"></i>
          <p><?php echo $search ? '未找到匹配的评论' : '暂无评论'; ?></p>
        </div>
      <?php else: ?>
        <?php foreach ($comments as $comment): ?>
          <div class="comment-card" data-id="<?php echo $comment['id']; ?>">
            <div class="comment-header">
              <div class="comment-avatar"><i class="iconfont icon-31pinglun"></i></div>
              <div class="comment-meta">
                <div class="comment-author"><?php echo htmlspecialchars($comment['nickname']); ?></div>
                <div class="comment-time"><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></div>
                <div class="comment-article">
                  文章：<?php echo $comment['article_title'] ? '<a href="../api/articles/view.php?id=' . $comment['article_id'] . '" target="_blank">' . htmlspecialchars($comment['article_title']) . '</a>' : '未知文章'; ?>
                </div>
              </div>
            </div>
            <div class="comment-content"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></div>
            <div class="comment-actions">
              <button class="btn btn-danger btn-sm" onclick="deleteComment(<?php echo $comment['id']; ?>)"><i class="iconfont icon-icon-"></i> 删除</button>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="toast" id="toast"></div>

  <script>
    const API_BASE = '../api';

    function showToast(message, type = 'success') {
      const toast = document.getElementById('toast');
      toast.textContent = message;
      toast.className = `toast toast-${type} show`;
      setTimeout(() => toast.classList.remove('show'), 3000);
    }

    async function deleteComment(id) {
      if (!confirm('确定要删除这条评论吗？')) return;

      try {
        const res = await fetch(`${API_BASE}/comments/delete.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id })
        });
        const data = await res.json();

        if (data.code === 200) {
          showToast('删除成功');
          const card = document.querySelector(`.comment-card[data-id="${id}"]`);
          if (card) card.remove();
          if (document.querySelectorAll('.comment-card').length === 0) {
            window.location.reload();
          }
        } else {
          showToast(data.message || '删除失败', 'error');
        }
      } catch (e) {
        console.error('删除评论失败:', e);
        showToast('删除失败', 'error');
      }
    }
  </script>
</body>
</html>
