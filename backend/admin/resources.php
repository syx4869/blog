<?php
require_once 'auth.php';
requireAdminLogin();
require_once '../db.php';
$pdo = getDB();

// 获取资源列表
$typeFilter = isset($_GET['type']) ? $_GET['type'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$sql = "SELECT * FROM resources WHERE 1=1";
$params = [];

if ($typeFilter) {
  $sql .= " AND type = ?";
  $params[] = $typeFilter;
}
if ($search) {
  $sql .= " AND (name LIKE ? OR description LIKE ?)";
  $params[] = "%$search%";
  $params[] = "%$search%";
}

$sql .= " ORDER BY sort, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$resources = $stmt->fetchAll();

// 获取资源统计
$typeStats = $pdo->query("SELECT type, COUNT(*) as count FROM resources GROUP BY type")->fetchAll();
$totalResources = $pdo->query("SELECT COUNT(*) FROM resources")->fetchColumn();

// 从 resource_categories 表动态获取类型标签
$typeLabels = [];
$catStmt = $pdo->query("SELECT `key`, name FROM resource_categories WHERE is_active = 1 ORDER BY sort ASC");
foreach ($catStmt->fetchAll() as $cat) {
  $typeLabels[$cat['key']] = $cat['name'];
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>资源管理</title>
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
    .stat-card { background: #1e1e24; border: 1px solid #2a2a30; border-radius: 8px; padding: 16px 20px; flex: 1; min-width: 120px; }
    .stat-value { font-size: 1.8rem; font-weight: 700; color: #e4e4e7; }
    .stat-label { font-size: 0.78rem; color: #71717a; margin-top: 2px; }

    .toolbar { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; align-items: center; }
    .filter-select { padding: 8px 14px; background: #27272a; border: 1px solid #2a2a30; border-radius: 6px; color: #d4d4d8; font-size: 0.85rem; outline: none; }
    .filter-select:focus { border-color: #3b82f6; }
    .search-input { padding: 8px 14px; background: #27272a; border: 1px solid #2a2a30; border-radius: 6px; color: #d4d4d8; font-size: 0.85rem; outline: none; min-width: 200px; }
    .search-input:focus { border-color: #3b82f6; }

    .resource-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
    .resource-card { background: #1e1e24; border: 1px solid #2a2a30; border-radius: 10px; padding: 20px; }
    .resource-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
    .resource-name { font-size: 1rem; color: #e4e4e7; font-weight: 600; }
    .resource-type { padding: 3px 10px; border-radius: 4px; font-size: 0.72rem; font-weight: 500; }
    .resource-type.tutorial { background: #1e3a5f; color: #60a5fa; }
    .resource-type.tool { background: #3b1f5e; color: #c084fc; }
    .resource-type.framework { background: #1a3d2e; color: #4ade80; }
    .resource-type.community { background: #4a2d1a; color: #fb923c; }
    .resource-type.design { background: #4a1a2d; color: #f472b6; }
    .resource-desc { font-size: 0.85rem; color: #a1a1aa; line-height: 1.5; margin-bottom: 12px; }
    .resource-url { font-size: 0.78rem; color: #52525b; margin-bottom: 12px; word-break: break-all; }
    .resource-actions { display: flex; gap: 8px; }

    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 200; display: none; align-items: center; justify-content: center; }
    .modal-overlay.show { display: flex; }
    .modal { background: #1e1e24; border-radius: 10px; padding: 28px; max-width: 500px; width: 90%; border: 1px solid #2a2a30; max-height: 90vh; overflow-y: auto; }
    .modal h3 { color: #e4e4e7; margin-bottom: 20px; font-weight: 600; }
    .form-group { margin-bottom: 16px; }
    .form-label { display: block; font-size: 0.8rem; color: #a1a1aa; margin-bottom: 6px; }
    .form-input { width: 100%; padding: 10px 14px; background: #27272a; border: 1px solid #2a2a30; border-radius: 6px; color: #e4e4e7; font-size: 0.9rem; outline: none; }
    .form-input:focus { border-color: #3b82f6; }
    textarea.form-input { resize: vertical; min-height: 80px; }

    .empty-state { text-align: center; padding: 60px 20px; color: #52525b; }
    .empty-state .iconfont { font-size: 3rem; margin-bottom: 12px; color: #3f3f46; }

    .toast { position: fixed; top: 70px; right: 28px; padding: 12px 20px; border-radius: 6px; font-size: 0.85rem; z-index: 1000; transform: translateX(120%); transition: transform 0.2s ease; }
    .toast.show { transform: translateX(0); }
    .toast-success { background: #052e16; color: #4ade80; border: 1px solid #166534; }
    .toast-error { background: #450a0a; color: #f87171; border: 1px solid #7f1d1d; }
  </style>
</head>
<body>
  <div class="header">
    <h1><i class="iconfont icon-xiangguanwenzhang"></i> 资源管理</h1>
    <div class="header-actions">
      <a href="index.php" class="btn btn-secondary"><i class="iconfont icon-xiangguanwenzhang"></i> 返回首页</a>
      <button class="btn btn-primary" onclick="openModal()"><i class="iconfont icon-bianjiwenjian"></i> 添加资源</button>
    </div>
  </div>

  <div class="container">
    <div class="stats-bar">
      <div class="stat-card">
        <div class="stat-value"><?php echo $totalResources; ?></div>
        <div class="stat-label">资源总数</div>
      </div>
      <?php foreach ($typeStats as $stat): ?>
        <div class="stat-card">
          <div class="stat-value"><?php echo $stat['count']; ?></div>
          <div class="stat-label"><?php echo $typeLabels[$stat['type']] ?? $stat['type']; ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <form class="toolbar" method="GET" action="">
      <select class="filter-select" name="type" onchange="this.form.submit()">
        <option value="">全部分类</option>
        <?php foreach ($typeLabels as $key => $label): ?>
          <option value="<?php echo $key; ?>" <?php echo $typeFilter === $key ? 'selected' : ''; ?>><?php echo $label; ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" class="search-input" name="search" placeholder="搜索资源..." value="<?php echo htmlspecialchars($search); ?>">
      <button type="submit" class="btn btn-secondary btn-sm"><i class="iconfont icon-icon-"></i> 搜索</button>
      <?php if ($search || $typeFilter): ?>
        <a href="resources.php" class="btn btn-secondary btn-sm">重置</a>
      <?php endif; ?>
    </form>

    <div class="resource-grid" id="resourceGrid">
      <?php if (empty($resources)): ?>
        <div class="empty-state" style="grid-column: 1/-1;">
          <i class="iconfont icon-xiangguanwenzhang"></i>
          <p><?php echo $search || $typeFilter ? '未找到匹配的资源' : '暂无资源'; ?></p>
        </div>
      <?php else: ?>
        <?php foreach ($resources as $r): ?>
          <div class="resource-card" data-id="<?php echo $r['id']; ?>">
            <div class="resource-header">
              <span class="resource-name"><?php echo htmlspecialchars($r['name']); ?></span>
              <span class="resource-type <?php echo $r['type']; ?>"><?php echo $typeLabels[$r['type']] ?? $r['type']; ?></span>
            </div>
            <div class="resource-desc"><?php echo htmlspecialchars($r['description'] ?: '暂无描述'); ?></div>
            <div class="resource-url"><?php echo htmlspecialchars($r['url']); ?></div>
            <div class="resource-actions">
              <button class="btn btn-secondary btn-sm" onclick="editResource(<?php echo $r['id']; ?>)"><i class="iconfont icon-bianjiwenjian"></i> 编辑</button>
              <button class="btn btn-danger btn-sm" onclick="deleteResource(<?php echo $r['id']; ?>)"><i class="iconfont icon-icon-"></i> 删除</button>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- 添加/编辑资源模态框 -->
  <div class="modal-overlay" id="resourceModal">
    <div class="modal">
      <h3 id="modalTitle">添加资源</h3>
      <div class="form-group">
        <label class="form-label">资源名称 *</label>
        <input type="text" class="form-input" id="resName" placeholder="例如：Vue.js 官方文档">
      </div>
      <div class="form-group">
        <label class="form-label">描述</label>
        <textarea class="form-input" id="resDesc" placeholder="简短描述..."></textarea>
      </div>
      <div class="form-group">
        <label class="form-label">链接 *</label>
        <input type="url" class="form-input" id="resUrl" placeholder="https://...">
      </div>
      <div class="form-group">
        <label class="form-label">分类 *</label>
        <select class="form-input" id="resType">
          <?php foreach ($typeLabels as $key => $label): ?>
            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">子分类</label>
        <input type="text" class="form-input" id="resCategory" placeholder="例如：前端基础">
      </div>
      <div class="form-group">
        <label class="form-label">图标 (iconfont 类名)</label>
        <input type="text" class="form-input" id="resIcon" placeholder="例如：icon-xiangguanwenzhang">
      </div>
      <div class="form-group">
        <label class="form-label">难度等级</label>
        <select class="form-input" id="resLevel">
          <option value="beginner">入门</option>
          <option value="intermediate">进阶</option>
          <option value="advanced">高级</option>
        </select>
      </div>
      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <button class="btn btn-secondary" onclick="closeModal()">取消</button>
        <button class="btn btn-primary" onclick="saveResource()"><i class="iconfont icon-bianjiwenjian"></i> 保存</button>
      </div>
    </div>
  </div>

  <div class="toast" id="toast"></div>

  <script>
    const API_BASE = '../api';
    let currentResourceId = null;
    const resources = <?php echo json_encode($resources); ?>;

    function showToast(message, type = 'success') {
      const toast = document.getElementById('toast');
      toast.textContent = message;
      toast.className = `toast toast-${type} show`;
      setTimeout(() => toast.classList.remove('show'), 3000);
    }

    function openModal() {
      currentResourceId = null;
      document.getElementById('modalTitle').textContent = '添加资源';
      document.getElementById('resName').value = '';
      document.getElementById('resDesc').value = '';
      document.getElementById('resUrl').value = '';
      document.getElementById('resType').value = 'tutorial';
      document.getElementById('resCategory').value = '';
      document.getElementById('resIcon').value = '';
      document.getElementById('resLevel').value = 'beginner';
      document.getElementById('resourceModal').classList.add('show');
    }

    function closeModal() {
      document.getElementById('resourceModal').classList.remove('show');
    }

    async function saveResource() {
      const name = document.getElementById('resName').value.trim();
      const url = document.getElementById('resUrl').value.trim();
      const type = document.getElementById('resType').value;
      const description = document.getElementById('resDesc').value.trim();
      const category = document.getElementById('resCategory').value.trim();
      const icon = document.getElementById('resIcon').value.trim();
      const level = document.getElementById('resLevel').value;

      if (!name || !url) {
        showToast('请填写必填项', 'error');
        return;
      }

      try {
        const apiUrl = currentResourceId ? `${API_BASE}/resources/update.php` : `${API_BASE}/resources/create.php`;
        const body = { name, url, type, description, category, icon, level };
        if (currentResourceId) body.id = currentResourceId;

        const res = await fetch(apiUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(body)
        });
        const data = await res.json();

        if (data.code === 200) {
          showToast(currentResourceId ? '更新成功' : '添加成功');
          closeModal();
          window.location.reload();
        } else {
          showToast(data.message || '操作失败', 'error');
        }
      } catch (e) {
        console.error('保存资源失败:', e);
        showToast('保存失败', 'error');
      }
    }

    function editResource(id) {
      currentResourceId = id;
      document.getElementById('modalTitle').textContent = '编辑资源';

      const r = resources.find(item => item.id == id);
      if (!r) {
        showToast('未找到资源', 'error');
        return;
      }

      document.getElementById('resName').value = r.name;
      document.getElementById('resDesc').value = r.description || '';
      document.getElementById('resUrl').value = r.url;
      document.getElementById('resType').value = r.type;
      document.getElementById('resCategory').value = r.category || '';
      document.getElementById('resIcon').value = r.icon || '';
      document.getElementById('resLevel').value = r.level || 'beginner';
      document.getElementById('resourceModal').classList.add('show');
    }

    async function deleteResource(id) {
      if (!confirm('确定要删除这个资源吗？')) return;

      try {
        const res = await fetch(`${API_BASE}/resources/delete.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ id })
        });
        const data = await res.json();

        if (data.code === 200) {
          showToast('删除成功');
          const card = document.querySelector(`.resource-card[data-id="${id}"]`);
          if (card) card.remove();
          if (document.querySelectorAll('.resource-card').length === 0) {
            window.location.reload();
          }
        } else {
          showToast(data.message || '删除失败', 'error');
        }
      } catch (e) {
        console.error('删除资源失败:', e);
        showToast('删除失败', 'error');
      }
    }

    // 点击模态框背景关闭
    document.getElementById('resourceModal').addEventListener('click', (e) => {
      if (e.target === document.getElementById('resourceModal')) closeModal();
    });
  </script>
</body>
</html>
