<?php
require_once 'auth.php';
requireAdminLogin();
require_once '../db.php';
$pdo = getDB();

// 获取统计数据
$totalCount = $pdo->query("SELECT COUNT(*) FROM wallpapers")->fetchColumn();
$activeCount = $pdo->query("SELECT COUNT(*) FROM wallpapers WHERE is_active = 1")->fetchColumn();

// 获取今日壁纸
$today = date('Y-m-d');
$todayStmt = $pdo->prepare("SELECT w.* FROM daily_wallpapers dw JOIN wallpapers w ON dw.wallpaper_id = w.id WHERE dw.date = ?");
$todayStmt->execute([$today]);
$todayWallpaper = $todayStmt->fetch();

// 获取分类列表
$categoryStmt = $pdo->query("SELECT DISTINCT category FROM wallpapers WHERE category IS NOT NULL AND category != '' ORDER BY category");
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

// 获取壁纸列表（分页）
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$pageSize = 12;
$offset = ($page - 1) * $pageSize;

$where = [];
$params = [];

$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

if ($categoryFilter) {
    $where[] = "category = ?";
    $params[] = $categoryFilter;
}
if ($statusFilter === 'active') {
    $where[] = "is_active = 1";
} elseif ($statusFilter === 'inactive') {
    $where[] = "is_active = 0";
}
if ($search) {
    $where[] = "(title LIKE ? OR filename LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM wallpapers $whereSql");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $pageSize));

$wallpaperStmt = $pdo->prepare("SELECT * FROM wallpapers $whereSql ORDER BY created_at DESC LIMIT ? OFFSET ?");
$wallpaperStmt->execute(array_merge($params, [$pageSize, $offset]));
$wallpapers = $wallpaperStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>壁纸管理</title>
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

    .container { max-width: 1360px; margin: 0 auto; padding: 24px 28px; }

    .stats-bar { display: flex; gap: 16px; margin-bottom: 24px; flex-wrap: wrap; }
    .stat-card { background: #1e1e24; border: 1px solid #2a2a30; border-radius: 8px; padding: 16px 20px; flex: 1; min-width: 160px; }
    .stat-value { font-size: 1.8rem; font-weight: 700; color: #e4e4e7; }
    .stat-label { font-size: 0.78rem; color: #71717a; margin-top: 2px; }

    .toolbar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
    .filter-select { padding: 8px 14px; background: #27272a; border: 1px solid #2a2a30; border-radius: 6px; color: #d4d4d8; font-size: 0.85rem; outline: none; }
    .filter-select:focus { border-color: #3b82f6; }
    .filter-select option { background: #1e1e24; }
    .search-input { padding: 8px 14px; background: #27272a; border: 1px solid #2a2a30; border-radius: 6px; color: #d4d4d8; font-size: 0.85rem; outline: none; min-width: 180px; }
    .search-input:focus { border-color: #3b82f6; }
    .search-input::placeholder { color: #52525b; }

    .wallpaper-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
    .wallpaper-card { background: #1e1e24; border: 1px solid #2a2a30; border-radius: 8px; overflow: hidden; transition: all 0.15s; }
    .wallpaper-card:hover { border-color: #3f3f46; }
    .wallpaper-card.inactive { opacity: 0.4; }
    .wallpaper-image { width: 100%; height: 160px; object-fit: cover; display: block; cursor: pointer; }
    .wallpaper-info { padding: 14px; }
    .wallpaper-title { font-size: 0.9rem; color: #e4e4e7; margin-bottom: 6px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-weight: 500; }
    .wallpaper-meta { display: flex; gap: 10px; font-size: 0.75rem; color: #52525b; margin-bottom: 10px; flex-wrap: wrap; align-items: center; }
    .wallpaper-meta span { display: inline-flex; align-items: center; }
    .wallpaper-actions { display: flex; gap: 6px; }

    .upload-area { border: 1px dashed #3a3a40; border-radius: 8px; padding: 48px 36px; text-align: center; margin-bottom: 24px; cursor: pointer; transition: all 0.15s; background: transparent; }
    .upload-area:hover { border-color: #3b82f6; background: #1e1e24; }
    .upload-area.dragover { border-color: #3b82f6; background: #1e1e24; }
    .upload-area .iconfont { font-size: 2.5rem; margin-bottom: 12px; color: #3f3f46; }
    .upload-text { font-size: 0.95rem; color: #a1a1aa; margin-bottom: 6px; }
    .upload-hint { font-size: 0.78rem; color: #52525b; }
    #fileInput { display: none; }

    .pagination { display: flex; justify-content: center; align-items: center; gap: 12px; margin-top: 32px; }
    .page-btn { padding: 8px 16px; background: #27272a; border: 1px solid #2a2a30; border-radius: 6px; color: #d4d4d8; cursor: pointer; transition: all 0.15s; font-size: 0.85rem; text-decoration: none; display: inline-flex; align-items: center; }
    .page-btn:hover:not(:disabled) { background: #3f3f46; }
    .page-btn:disabled { opacity: 0.4; cursor: not-allowed; }
    .page-info { color: #71717a; font-size: 0.85rem; }

    .toast { position: fixed; top: 70px; right: 28px; padding: 12px 20px; border-radius: 6px; font-size: 0.85rem; z-index: 1000; transform: translateX(120%); transition: transform 0.2s ease; font-weight: 500; }
    .toast.show { transform: translateX(0); }
    .toast-success { background: #052e16; color: #4ade80; border: 1px solid #166534; }
    .toast-error { background: #450a0a; color: #f87171; border: 1px solid #7f1d1d; }

    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 200; display: none; align-items: center; justify-content: center; }
    .modal-overlay.show { display: flex; }
    .modal { background: #1e1e24; border-radius: 10px; padding: 28px; max-width: 480px; width: 90%; border: 1px solid #2a2a30; }
    .modal h3 { color: #e4e4e7; margin-bottom: 14px; font-weight: 600; }
    .modal-image { width: 100%; border-radius: 8px; margin-bottom: 14px; }
    .modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 20px; }

    .progress-bar { width: 100%; height: 3px; background: #2a2a30; border-radius: 2px; margin-top: 10px; overflow: hidden; }
    .progress-fill { height: 100%; background: #3b82f6; border-radius: 2px; transition: width 0.3s ease; }

    .empty-state { text-align: center; padding: 60px 20px; color: #52525b; }
    .empty-state .iconfont { font-size: 3.5rem; margin-bottom: 14px; color: #3f3f46; }
    .empty-state p { font-size: 0.9rem; }

    @media (max-width: 768px) {
      .wallpaper-grid { grid-template-columns: repeat(2, 1fr); }
      .stats-bar { flex-direction: column; }
    }
    @media (max-width: 480px) {
      .wallpaper-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="header">
    <h1><i class="iconfont icon-shoucang"></i> 壁纸管理</h1>
    <div class="header-actions">
      <a href="index.php" class="btn btn-secondary"><i class="iconfont icon-xiangguanwenzhang"></i> 返回首页</a>
      <button class="btn btn-secondary" onclick="viewTodayWallpaper()"><i class="iconfont icon-24gf-calendar"></i> 今日壁纸</button>
      <button class="btn btn-primary" onclick="document.getElementById('fileInput').click()"><i class="iconfont icon-shoucang"></i> 上传壁纸</button>
    </div>
  </div>

  <div class="container">
    <div class="stats-bar" id="statsBar">
      <div class="stat-card">
        <div class="stat-value"><?php echo $total; ?></div>
        <div class="stat-label">壁纸总数</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $activeCount; ?></div>
        <div class="stat-label">已启用</div>
      </div>
      <div class="stat-card">
        <div class="stat-value"><?php echo $todayWallpaper ? '#' . $todayWallpaper['id'] : '默认'; ?></div>
        <div class="stat-label">今日壁纸</div>
      </div>
    </div>

    <div class="upload-area" id="uploadArea" onclick="document.getElementById('fileInput').click()">
      <i class="iconfont icon-shoucang"></i>
      <div class="upload-text">点击或拖拽上传壁纸</div>
      <div class="upload-hint">支持 JPG/PNG 格式，最小分辨率 1920x1080，最大 20MB</div>
      <div class="progress-bar" id="uploadProgress" style="display:none;">
        <div class="progress-fill" id="progressFill" style="width:0%"></div>
      </div>
    </div>
    <input type="file" id="fileInput" accept="image/jpeg,image/png,image/jpg" multiple onchange="handleFileSelect(event)">

    <form class="toolbar" method="GET" action="">
      <select class="filter-select" name="category" onchange="this.form.submit()">
        <option value="">全部分类</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
        <?php endforeach; ?>
      </select>
      <select class="filter-select" name="status" onchange="this.form.submit()">
        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>全部状态</option>
        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>已启用</option>
        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>已禁用</option>
      </select>
      <input type="text" class="search-input" name="search" placeholder="搜索壁纸..." value="<?php echo htmlspecialchars($search); ?>">
      <button type="submit" class="btn btn-secondary btn-sm"><i class="iconfont icon-icon-"></i> 搜索</button>
      <?php if ($search || $categoryFilter || $statusFilter !== 'all'): ?>
        <a href="wallpapers.php" class="btn btn-secondary btn-sm">重置</a>
      <?php endif; ?>
    </form>

    <div class="wallpaper-grid" id="wallpaperGrid">
      <?php if (empty($wallpapers)): ?>
        <div class="empty-state" style="grid-column: 1/-1;">
          <i class="iconfont icon-shoucang"></i>
          <p>暂无壁纸，点击上方上传</p>
        </div>
      <?php else: ?>
        <?php foreach ($wallpapers as $w): ?>
          <div class="wallpaper-card <?php echo $w['is_active'] == 0 ? 'inactive' : ''; ?>">
            <img class="wallpaper-image" src="<?php echo htmlspecialchars($w['url']); ?>" alt="<?php echo htmlspecialchars($w['title'] ?? ''); ?>" loading="lazy" onclick="previewWallpaper(<?php echo $w['id']; ?>)">
            <div class="wallpaper-info">
              <div class="wallpaper-title"><?php echo htmlspecialchars($w['title'] ?? $w['filename']); ?></div>
              <div class="wallpaper-meta">
                <span><?php echo $w['width']; ?>x<?php echo $w['height']; ?></span>
                <span><?php echo formatSize($w['file_size']); ?></span>
                <span><?php echo htmlspecialchars($w['category'] ?? ''); ?></span>
                <span><?php echo $w['is_active'] == 1 ? '<i class="iconfont icon-shoucang-yishoucang" style="color:#f59e0b;"></i>' : '<i class="iconfont icon-shoucang"></i>'; ?></span>
                <span>使用<?php echo $w['used_count']; ?>次</span>
              </div>
              <div class="wallpaper-actions">
                <button class="btn btn-primary btn-sm" onclick="setDailyWallpaper(<?php echo $w['id']; ?>)">设为今日</button>
                <button class="btn btn-secondary btn-sm" onclick="toggleWallpaper(<?php echo $w['id']; ?>)"><?php echo $w['is_active'] == 1 ? '禁用' : '启用'; ?></button>
                <button class="btn btn-danger btn-sm" onclick="deleteWallpaper(<?php echo $w['id']; ?>)">删除</button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
      <div class="pagination" id="pagination">
        <a href="?page=<?php echo max(1, $page - 1); ?>&category=<?php echo urlencode($categoryFilter); ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($search); ?>" class="page-btn" <?php echo $page <= 1 ? 'style="opacity:0.4;pointer-events:none;"' : ''; ?>>上一页</a>
        <span class="page-info"><?php echo $page; ?> / <?php echo $totalPages; ?></span>
        <a href="?page=<?php echo min($totalPages, $page + 1); ?>&category=<?php echo urlencode($categoryFilter); ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($search); ?>" class="page-btn" <?php echo $page >= $totalPages ? 'style="opacity:0.4;pointer-events:none;"' : ''; ?>>下一页</a>
      </div>
    <?php endif; ?>
  </div>

  <div class="modal-overlay" id="previewModal">
    <div class="modal">
      <h3 id="previewTitle">壁纸预览</h3>
      <img class="modal-image" id="previewImage" src="" alt="">
      <div class="modal-actions">
        <button class="btn btn-secondary" onclick="closeModal()">关闭</button>
        <button class="btn btn-danger" id="modalDeleteBtn" onclick="">删除</button>
      </div>
    </div>
  </div>

  <div class="toast" id="toast"></div>

  <script>
    const API_BASE = '../api';
    const wallpapers = <?php echo json_encode($wallpapers); ?>;

    const uploadArea = document.getElementById('uploadArea');
    uploadArea.addEventListener('dragover', (e) => { e.preventDefault(); uploadArea.classList.add('dragover'); });
    uploadArea.addEventListener('dragleave', () => uploadArea.classList.remove('dragover'));
    uploadArea.addEventListener('drop', (e) => {
      e.preventDefault();
      uploadArea.classList.remove('dragover');
      const files = Array.from(e.dataTransfer.files).filter(f => f.type.startsWith('image/'));
      if (files.length > 0) uploadFiles(files);
    });

    function handleFileSelect(event) {
      const files = Array.from(event.target.files);
      if (files.length > 0) uploadFiles(files);
      event.target.value = '';
    }

    async function uploadFiles(files) {
      const progressBar = document.getElementById('uploadProgress');
      const progressFill = document.getElementById('progressFill');
      progressBar.style.display = 'block';

      for (let i = 0; i < files.length; i++) {
        const file = files[i];
        progressFill.style.width = `${((i + 0.5) / files.length) * 100}%`;

        const formData = new FormData();
        formData.append('file', file);
        formData.append('title', file.name.replace(/\.[^/.]+$/, ''));

        try {
          const res = await fetch(`${API_BASE}/wallpapers/upload.php`, {
            method: 'POST',
            body: formData
          });
          const data = await res.json();

          if (data.code === 200) {
            showToast(`「${file.name}」上传成功`, 'success');
          } else {
            showToast(`「${file.name}」${data.message}`, 'error');
          }
        } catch (e) {
          showToast(`「${file.name}」上传失败`, 'error');
        }

        progressFill.style.width = `${((i + 1) / files.length) * 100}%`;
      }

      setTimeout(() => {
        progressBar.style.display = 'none';
        progressFill.style.width = '0%';
        window.location.reload();
      }, 500);
    }

    async function setDailyWallpaper(id) {
      if (!confirm('确定将这张壁纸设为今日壁纸吗？')) return;
      try {
        const res = await fetch(`${API_BASE}/wallpapers/set_daily.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ wallpaper_id: id })
        });
        const data = await res.json();
        if (data.code === 200) {
          showToast('今日壁纸已更新', 'success');
          window.location.reload();
        } else {
          showToast(data.message || '设置失败', 'error');
        }
      } catch (e) {
        showToast('设置失败', 'error');
      }
    }

    async function toggleWallpaper(id) {
      try {
        const res = await fetch(`${API_BASE}/wallpapers/toggle.php?id=${id}`, { method: 'POST' });
        const data = await res.json();
        if (data.code === 200) {
          showToast(data.data.is_active ? '壁纸已启用' : '壁纸已禁用', 'success');
          window.location.reload();
        }
      } catch (e) {
        showToast('操作失败', 'error');
      }
    }

    async function deleteWallpaper(id) {
      if (!confirm('确定要删除这张壁纸吗？')) return;
      try {
        const res = await fetch(`${API_BASE}/wallpapers/delete.php?id=${id}`, { method: 'DELETE' });
        const data = await res.json();
        if (data.code === 200) {
          showToast('壁纸已删除', 'success');
          window.location.reload();
        } else {
          showToast(data.message || '删除失败', 'error');
        }
      } catch (e) {
        showToast('删除失败', 'error');
      }
    }

    function previewWallpaper(id) {
      const w = wallpapers.find(item => item.id === id);
      if (!w) return;
      document.getElementById('previewTitle').textContent = w.title || w.filename;
      document.getElementById('previewImage').src = w.url;
      document.getElementById('modalDeleteBtn').onclick = () => { closeModal(); deleteWallpaper(id); };
      document.getElementById('previewModal').classList.add('show');
    }

    function closeModal() {
      document.getElementById('previewModal').classList.remove('show');
    }

    async function viewTodayWallpaper() {
      try {
        const res = await fetch(`${API_BASE}/wallpapers/daily.php`);
        const data = await res.json();
        if (data.code === 200 && data.data) {
          const w = data.data;
          if (w.id > 0) {
            document.getElementById('previewTitle').textContent = '今日壁纸 - ' + (w.title || w.filename);
            document.getElementById('previewImage').src = w.url;
            document.getElementById('modalDeleteBtn').style.display = 'none';
            document.getElementById('previewModal').classList.add('show');
          } else {
            showToast('今日使用默认壁纸', 'success');
          }
        }
      } catch (e) {
        showToast('获取今日壁纸失败', 'error');
      }
    }

    function showToast(msg, type) {
      const toast = document.getElementById('toast');
      toast.textContent = msg;
      toast.className = `toast toast-${type} show`;
      setTimeout(() => { toast.classList.remove('show'); }, 3000);
    }

    document.getElementById('previewModal').addEventListener('click', (e) => {
      if (e.target === document.getElementById('previewModal')) closeModal();
    });
  </script>
</body>
</html>
<?php
function formatSize($bytes) {
  if ($bytes === 0) return '0 B';
  $k = 1024;
  $sizes = ['B', 'KB', 'MB', 'GB'];
  $i = floor(log($bytes) / log($k));
  return round($bytes / pow($k, $i), 1) . ' ' . $sizes[$i];
}
?>
