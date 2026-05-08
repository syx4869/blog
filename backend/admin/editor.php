<?php
require_once 'auth.php';
requireAdminLogin();
require_once '../db.php';
$pdo = getDB();

// 获取文章列表
$articles = $pdo->query("SELECT id, title, description, is_published, icon, cover_image, category_id, created_at FROM articles ORDER BY created_at DESC LIMIT 50")->fetchAll();

// 获取分类列表
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY sort, id")->fetchAll();

// 如果有编辑请求，获取文章详情
$editArticle = null;
$editId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($editId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM articles WHERE id = ?");
    $stmt->execute([$editId]);
    $editArticle = $stmt->fetch();

    // 获取文章标签
    if ($editArticle) {
        $tagStmt = $pdo->prepare("SELECT t.name FROM tags t JOIN article_tags at ON t.id = at.tag_id WHERE at.article_id = ?");
        $tagStmt->execute([$editId]);
        $editArticle['tags'] = $tagStmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>博客文章管理</title>
  <link rel="stylesheet" href="iconfont.css">
  <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
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

    .layout { display: flex; min-height: calc(100vh - 54px); }

    .sidebar { width: 260px; background: #1e1e24; border-right: 1px solid #2a2a30; padding: 16px; overflow-y: auto; }
    .sidebar-title { font-size: 0.72rem; color: #71717a; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 12px; font-weight: 600; }
    .article-list { display: flex; flex-direction: column; gap: 2px; }
    .article-item { padding: 10px 12px; background: transparent; border-radius: 6px; cursor: pointer; transition: all 0.15s; text-decoration: none; color: inherit; display: block; }
    .article-item:hover { background: #27272a; }
    .article-item.active { background: #27272a; }
    .article-item-title { font-size: 0.85rem; color: #e4e4e7; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .article-item-meta { font-size: 0.72rem; color: #52525b; }
    .article-item-draft { color: #f59e0b; font-size: 0.68rem; margin-left: 6px; }

    .main { flex: 1; padding: 28px; overflow-y: auto; }

    .form-group { margin-bottom: 18px; }
    .form-label { display: block; font-size: 0.8rem; color: #a1a1aa; margin-bottom: 6px; font-weight: 500; }
    .form-input { width: 100%; padding: 10px 14px; background: #27272a; border: 1px solid #2a2a30; border-radius: 6px; color: #e4e4e7; font-size: 0.9rem; outline: none; transition: border-color 0.15s; font-family: inherit; }
    .form-input:focus { border-color: #3b82f6; }
    .form-input::placeholder { color: #52525b; }
    .form-row { display: flex; gap: 14px; }
    .form-row .form-group { flex: 1; }
    textarea.form-input { resize: vertical; min-height: 70px; }

    .editor-wrapper { border-radius: 8px; overflow: hidden; border: 1px solid #2a2a30; }
    .editor-tabs { display: flex; gap: 2px; margin-bottom: 10px; }
    .tab-btn { padding: 6px 16px; background: #27272a; border: 1px solid #2a2a30; border-radius: 6px; color: #a1a1aa; font-size: 0.8rem; cursor: pointer; transition: all 0.15s; }
    .tab-btn:hover { background: #323238; color: #e4e4e7; }
    .tab-btn.active { background: #3b82f6; color: white; border-color: #3b82f6; }
    .html-editor-wrapper { border-radius: 8px; overflow: hidden; border: 1px solid #2a2a30; }
    .html-editor { font-family: 'Consolas', 'Monaco', 'Courier New', monospace; font-size: 0.85rem; line-height: 1.6; min-height: 400px; }
    .ql-toolbar.ql-snow { background: #1e1e24; border: none !important; border-bottom: 1px solid #2a2a30 !important; }
    .ql-container.ql-snow { background: #18181b; border: none !important; min-height: 400px; font-size: 0.95rem; color: #d4d4d8; }
    .ql-editor { min-height: 400px; }
    .ql-editor.ql-blank::before { color: #52525b; }
    .ql-snow .ql-stroke { stroke: #71717a; }
    .ql-snow .ql-fill { fill: #71717a; }
    .ql-snow .ql-picker-label { color: #71717a; }
    .ql-snow .ql-picker-options { background: #1e1e24; border-color: #2a2a30; }
    .ql-snow .ql-picker-item { color: #d4d4d8; }
    .ql-snow .ql-active .ql-stroke { stroke: #3b82f6; }
    .ql-snow .ql-active .ql-fill { fill: #3b82f6; }
    .ql-snow .ql-active { color: #3b82f6 !important; }
    .ql-editor h1, .ql-editor h2, .ql-editor h3 { color: #e4e4e7; }
    .ql-editor a { color: #60a5fa; }
    .ql-editor blockquote { border-left-color: #3b82f6; }
    .ql-editor pre.ql-syntax { background: #1e1e24; border-radius: 6px; color: #d4d4d8; }
    .ql-editor img { max-width: 100%; border-radius: 6px; }

    .icon-color-row { display: flex; gap: 10px; align-items: center; }
    .icon-preview { font-size: 1.5rem; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; background: #27272a; border-radius: 6px; border: 1px solid #2a2a30; }
    .color-preview { width: 42px; height: 36px; border-radius: 6px; border: 1px solid #2a2a30; }

    .tag-input-wrapper { display: flex; flex-wrap: wrap; gap: 6px; padding: 6px 10px; background: #27272a; border: 1px solid #2a2a30; border-radius: 6px; min-height: 40px; align-items: center; }
    .tag-chip { display: flex; align-items: center; gap: 4px; padding: 3px 8px; background: #3b82f6; border-radius: 4px; font-size: 0.75rem; color: white; }
    .tag-chip .remove { cursor: pointer; opacity: 0.7; font-size: 0.7rem; }
    .tag-chip .remove:hover { opacity: 1; }
    .tag-input { border: none; background: transparent; color: #e4e4e7; outline: none; font-size: 0.85rem; flex: 1; min-width: 80px; }
    .tag-input::placeholder { color: #52525b; }

    .cover-upload { border: 1px dashed #3a3a40; border-radius: 8px; padding: 28px; text-align: center; cursor: pointer; transition: all 0.15s; background: transparent; position: relative; overflow: hidden; }
    .cover-upload:hover { border-color: #3b82f6; background: #1e1e24; }
    .cover-upload.has-image { border-style: solid; border-color: #2a2a30; padding: 0; }
    .cover-upload img { width: 100%; height: 160px; object-fit: cover; display: block; }
    .cover-upload .cover-placeholder { color: #52525b; }
    .cover-upload .cover-placeholder .iconfont { font-size: 2rem; margin-bottom: 6px; }
    .cover-upload .cover-placeholder .text { font-size: 0.82rem; }
    .cover-upload .cover-actions { position: absolute; bottom: 8px; right: 8px; display: flex; gap: 6px; }
    .cover-upload .cover-actions button { font-size: 0.72rem; padding: 4px 10px; }
    #coverFileInput { display: none; }

    .toast { position: fixed; top: 70px; right: 28px; padding: 12px 20px; border-radius: 6px; font-size: 0.85rem; z-index: 1000; transform: translateX(120%); transition: transform 0.2s ease; font-weight: 500; }
    .toast.show { transform: translateX(0); }
    .toast-success { background: #052e16; color: #4ade80; border: 1px solid #166534; }
    .toast-error { background: #450a0a; color: #f87171; border: 1px solid #7f1d1d; }

    .empty-state { text-align: center; padding: 60px 20px; color: #52525b; }
    .empty-state .iconfont { font-size: 3.5rem; margin-bottom: 14px; color: #3f3f46; }
    .empty-state p { font-size: 0.9rem; }

    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 200; display: flex; align-items: center; justify-content: center; }
    .modal { background: #1e1e24; border-radius: 10px; padding: 28px; max-width: 380px; width: 90%; border: 1px solid #2a2a30; }
    .modal h3 { color: #e4e4e7; margin-bottom: 14px; font-weight: 600; }
    .modal p { color: #a1a1aa; font-size: 0.9rem; }
    .modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 20px; }

    @media (max-width: 768px) {
      .layout { flex-direction: column; }
      .sidebar { width: 100%; max-height: 200px; }
      .form-row { flex-direction: column; }
    }
  </style>
</head>
<body>
  <div class="header">
    <h1><i class="iconfont icon-bianjiwenjian"></i> 博客文章管理</h1>
    <div class="header-actions">
      <a href="index.php" class="btn btn-secondary"><i class="iconfont icon-xiangguanwenzhang"></i> 返回首页</a>
      <button class="btn btn-primary" onclick="createNewArticle()"><i class="iconfont icon-bianjiwenjian"></i> 新建文章</button>
    </div>
  </div>

  <div class="layout">
    <div class="sidebar">
      <div class="sidebar-title">文章列表</div>
      <div class="article-list" id="articleList">
        <?php if (empty($articles)): ?>
          <div style="color:#52525b;font-size:0.85rem;padding:10px;">暂无文章</div>
        <?php else: ?>
          <?php foreach ($articles as $article): ?>
            <a href="?id=<?php echo $article['id']; ?>" class="article-item <?php echo ($editId == $article['id']) ? 'active' : ''; ?>">
              <div class="article-item-title"><?php echo $article['icon'] ? '<i class="iconfont ' . htmlspecialchars($article['icon']) . '"></i>' : '<i class="iconfont icon-bianjiwenjian"></i>'; ?> <?php echo htmlspecialchars($article['title']); ?></div>
              <div class="article-item-meta">
                <?php
                $catName = '';
                foreach ($categories as $cat) {
                  if ($cat['id'] == $article['category_id']) { $catName = $cat['name']; break; }
                }
                echo $catName ?: '未分类';
                ?> · <?php echo date('m-d', strtotime($article['created_at'])); ?>
                <?php if ($article['is_published'] == 0): ?><span class="article-item-draft">草稿</span><?php endif; ?>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="main" id="editorArea">
      <div class="empty-state" id="emptyState" style="<?php echo ($editId > 0) ? 'display:none;' : ''; ?>">
        <i class="iconfont icon-bianjiwenjian"></i>
        <p>选择一篇文章编辑，或点击"新建文章"开始创作</p>
      </div>

      <div id="editorForm" style="<?php echo ($editId > 0) ? 'display:block;' : 'display:none;'; ?>">
        <div class="form-group">
          <label class="form-label">文章标题</label>
          <input type="text" class="form-input" id="articleTitle" placeholder="输入文章标题..." value="<?php echo $editArticle ? htmlspecialchars($editArticle['title']) : ''; ?>" autocomplete="off">
        </div>

        <div class="form-group">
          <label class="form-label">文章描述</label>
          <textarea class="form-input" id="articleDesc" placeholder="简短描述文章内容..." rows="2"><?php echo $editArticle ? htmlspecialchars($editArticle['description'] ?? '') : ''; ?></textarea>
        </div>

        <div class="form-group">
          <label class="form-label">封面图</label>
          <div class="cover-upload <?php echo ($editArticle && $editArticle['cover_image']) ? 'has-image' : ''; ?>" id="coverUpload" onclick="document.getElementById('coverFileInput').click()">
            <div class="cover-placeholder" id="coverPlaceholder" style="<?php echo ($editArticle && $editArticle['cover_image']) ? 'display:none;' : ''; ?>">
              <div class="iconfont icon-shoucang"></div>
              <div class="text">点击上传封面图（推荐 16:9，最小 800x450）</div>
            </div>
            <img id="coverPreview" style="<?php echo ($editArticle && $editArticle['cover_image']) ? 'display:block;' : 'display:none;'; ?>" alt="封面预览" src="<?php echo $editArticle ? ($editArticle['cover_image'] ?? '') : ''; ?>">
            <div class="cover-actions" id="coverActions" style="<?php echo ($editArticle && $editArticle['cover_image']) ? 'display:flex;' : 'display:none;'; ?>">
              <button class="btn btn-secondary" onclick="event.stopPropagation(); changeCover()">更换</button>
              <button class="btn btn-danger" onclick="event.stopPropagation(); removeCover()">移除</button>
            </div>
          </div>
          <input type="file" id="coverFileInput" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="uploadCover(event)">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">分类</label>
            <select class="form-input" id="articleCategory">
              <option value="">选择分类</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['id']; ?>" <?php echo ($editArticle && $editArticle['category_id'] == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">发布状态</label>
            <select class="form-input" id="articleStatus">
              <option value="1" <?php echo ($editArticle && $editArticle['is_published'] == 1) ? 'selected' : ''; ?>>已发布</option>
              <option value="0" <?php echo ($editArticle && $editArticle['is_published'] == 0) ? 'selected' : ''; ?>>草稿</option>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label">图标 (iconfont 类名)</label>
            <div class="icon-color-row">
              <span class="icon-preview" id="iconPreview"><i class="iconfont <?php echo $editArticle ? ($editArticle['icon'] ?: 'icon-bianjiwenjian') : 'icon-bianjiwenjian'; ?>"></i></span>
              <input type="text" class="form-input" id="articleIcon" value="<?php echo $editArticle ? htmlspecialchars($editArticle['icon'] ?? 'icon-bianjiwenjian') : 'icon-bianjiwenjian'; ?>" oninput="updateIconPreview(this.value)" style="flex:1">
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">标签（回车添加）</label>
          <div class="tag-input-wrapper" id="tagWrapper">
            <input type="text" class="tag-input" id="tagInput" placeholder="输入标签后回车...">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">文章内容</label>
          <div class="editor-tabs">
            <button type="button" class="tab-btn active" id="tabVisual" onclick="switchTab('visual')">可视化</button>
            <button type="button" class="tab-btn" id="tabHtml" onclick="switchTab('html')">HTML</button>
          </div>
          <div class="editor-wrapper" id="visualEditor">
            <div id="quillEditor"></div>
          </div>
          <div class="html-editor-wrapper" id="htmlEditor" style="display:none">
            <textarea class="form-input html-editor" id="htmlContent" placeholder="在此输入 HTML 代码..." rows="20"></textarea>
          </div>
        </div>

        <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
          <button class="btn btn-secondary" onclick="saveArticle(0)"><i class="iconfont icon-bianjiwenjian"></i> 保存草稿</button>
          <button class="btn btn-primary" onclick="saveArticle(1)"><i class="iconfont icon-shoucang"></i> 发布文章</button>
          <button class="btn btn-danger" id="deleteBtn" style="<?php echo $editId > 0 ? 'display:inline-block;' : 'display:none;'; ?>" onclick="confirmDelete()"><i class="iconfont icon-icon-"></i> 删除</button>
        </div>
      </div>
    </div>
  </div>

  <div class="toast" id="toast"></div>
  <div class="modal-overlay" id="deleteModal" style="display:none">
    <div class="modal">
      <h3>确认删除</h3>
      <p style="color:rgba(255,255,255,0.7)">确定要删除这篇文章吗？此操作不可恢复。</p>
      <div class="modal-actions">
        <button class="btn btn-secondary" onclick="closeDeleteModal()">取消</button>
        <button class="btn btn-danger" onclick="deleteArticle()">确认删除</button>
      </div>
    </div>
  </div>

  <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
  <script>
    const API_BASE = '../api';
    let quill = null;
    let currentArticleId = <?php echo $editId; ?>;
    let currentTags = <?php echo json_encode($editArticle['tags'] ?? []); ?>;
    let categories = <?php echo json_encode($categories); ?>;
    let coverImageUrl = <?php echo json_encode($editArticle['cover_image'] ?? null); ?>;

    function initQuill() {
      quill = new Quill('#quillEditor', {
        theme: 'snow',
        placeholder: '开始写作...',
        modules: {
          toolbar: {
            container: [
              [{ 'header': [1, 2, 3, 4, false] }],
              ['bold', 'italic', 'underline', 'strike'],
              [{ 'color': [] }, { 'background': [] }],
              [{ 'list': 'ordered' }, { 'list': 'bullet' }],
              ['blockquote', 'code-block'],
              ['link', 'image'],
              [{ 'align': [] }],
              ['clean']
            ],
            handlers: {
              image: imageHandler
            }
          }
        }
      });

      <?php if ($editArticle && $editArticle['content']): ?>
      quill.root.innerHTML = <?php echo json_encode($editArticle['content']); ?>;
      document.getElementById('htmlContent').value = <?php echo json_encode($editArticle['content']); ?>;
      <?php endif; ?>

      quill.blur();
    }

    async function imageHandler() {
      const input = document.createElement('input');
      input.setAttribute('type', 'file');
      input.setAttribute('accept', 'image/*');
      input.click();

      input.onchange = async () => {
        const file = input.files[0];
        if (!file) return;

        if (file.size > 10 * 1024 * 1024) {
          showToast('图片大小不能超过 10MB', 'error');
          return;
        }

        const formData = new FormData();
        formData.append('file', file);

        showToast('正在上传图片...', 'success');

        try {
          const res = await fetch(`${API_BASE}/articles/upload.php`, {
            method: 'POST',
            body: formData
          });

          const data = await res.json();

          if (data.code === 200 && data.data?.url) {
            const range = quill.getSelection(true);
            quill.insertEmbed(range.index, 'image', data.data.url);
            quill.setSelection(range.index + 1);
            showToast('图片上传成功', 'success');
          } else {
            showToast(data.message || '图片上传失败', 'error');
          }
        } catch (e) {
          showToast('图片上传失败: ' + e.message, 'error');
        }
      };
    }

    function updateIconPreview(val) {
      document.getElementById('iconPreview').innerHTML = '<i class="iconfont ' + (val || 'icon-bianjiwenjian') + '"></i>';
    }

    function createNewArticle() {
      window.location.href = 'editor.php?action=new';
    }

    <?php if (isset($_GET['action']) && $_GET['action'] === 'new'): ?>
    document.addEventListener('DOMContentLoaded', () => {
      showEditor();
    });
    <?php endif; ?>

    function showEditor() {
      document.getElementById('emptyState').style.display = 'none';
      document.getElementById('editorForm').style.display = 'block';
    }

    function switchTab(tab) {
      const tabVisual = document.getElementById('tabVisual');
      const tabHtml = document.getElementById('tabHtml');
      const visualEditor = document.getElementById('visualEditor');
      const htmlEditor = document.getElementById('htmlEditor');
      const htmlContent = document.getElementById('htmlContent');

      if (tab === 'html') {
        tabVisual.classList.remove('active');
        tabHtml.classList.add('active');
        visualEditor.style.display = 'none';
        htmlEditor.style.display = 'block';
        htmlContent.value = quill.root.innerHTML;
      } else {
        tabHtml.classList.remove('active');
        tabVisual.classList.add('active');
        htmlEditor.style.display = 'none';
        visualEditor.style.display = 'block';
        const html = htmlContent.value;
        quill.setContents([]);
        quill.clipboard.dangerouslyPasteHTML(0, html);
      }
    }

    function renderTags() {
      const wrapper = document.getElementById('tagWrapper');
      const input = document.getElementById('tagInput');
      wrapper.innerHTML = '';
      currentTags.forEach((tag, i) => {
        const chip = document.createElement('span');
        chip.className = 'tag-chip';
        chip.innerHTML = `${tag} <span class="remove" onclick="removeTag(${i})">✕</span>`;
        wrapper.appendChild(chip);
      });
      wrapper.appendChild(input);
    }

    function removeTag(index) {
      currentTags.splice(index, 1);
      renderTags();
    }

    function resetCoverPreview() {
      coverImageUrl = null;
      document.getElementById('coverPreview').style.display = 'none';
      document.getElementById('coverPreview').src = '';
      document.getElementById('coverPlaceholder').style.display = 'block';
      document.getElementById('coverActions').style.display = 'none';
      document.getElementById('coverUpload').classList.remove('has-image');
      document.getElementById('coverFileInput').value = '';
    }

    async function uploadCover(event) {
      const file = event.target.files[0];
      if (!file) return;

      if (file.size > 10 * 1024 * 1024) {
        showToast('图片大小不能超过 10MB', 'error');
        return;
      }

      const formData = new FormData();
      formData.append('file', file);

      showToast('正在上传封面图...', 'success');

      try {
        const res = await fetch(`${API_BASE}/articles/upload.php`, {
          method: 'POST',
          body: formData
        });

        const data = await res.json();

        if (data.code === 200 && data.data?.url) {
          coverImageUrl = data.data.url;
          document.getElementById('coverPreview').src = coverImageUrl;
          document.getElementById('coverPreview').style.display = 'block';
          document.getElementById('coverPlaceholder').style.display = 'none';
          document.getElementById('coverActions').style.display = 'flex';
          document.getElementById('coverUpload').classList.add('has-image');
          showToast('封面图上传成功', 'success');
        } else {
          showToast(data.message || '封面图上传失败', 'error');
        }
      } catch (e) {
        showToast('封面图上传失败: ' + e.message, 'error');
      }
    }

    function changeCover() {
      document.getElementById('coverFileInput').click();
    }

    function removeCover() {
      coverImageUrl = null;
      resetCoverPreview();
    }

    document.addEventListener('DOMContentLoaded', () => {
      initQuill();
      renderTags();

      document.getElementById('articleTitle').focus();

      document.getElementById('tagInput').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          const val = e.target.value.trim();
          if (val && !currentTags.includes(val)) {
            currentTags.push(val);
            renderTags();
            e.target.value = '';
          }
        }
      });
    });

    async function saveArticle(isPublished) {
      const title = document.getElementById('articleTitle').value.trim();
      if (!title) { showToast('请输入文章标题', 'error'); return; }
      if (isPublished && !coverImageUrl) { showToast('请上传封面图', 'error'); return; }

      const isHtmlMode = document.getElementById('htmlEditor').style.display === 'block';
      const content = isHtmlMode ? document.getElementById('htmlContent').value : quill.root.innerHTML;
      const plainText = isHtmlMode ? content.replace(/<[^>]*>/g, '').trim() : quill.getText().trim();
      const wordCount = plainText.length;
      const readTime = Math.max(1, Math.ceil(wordCount / 300));

      const articleData = {
        title,
        description: document.getElementById('articleDesc').value.trim(),
        content,
        cover_image: coverImageUrl || null,
        category_id: document.getElementById('articleCategory').value || null,
        icon: document.getElementById('articleIcon').value || 'icon-bianjiwenjian',

        is_published: isPublished,
        tags: currentTags,
        word_count: wordCount,
        read_time: readTime
      };

      try {
        let url, method;
        if (currentArticleId) {
          url = `${API_BASE}/articles/update.php?id=${currentArticleId}`;
          method = 'PUT';
        } else {
          url = `${API_BASE}/articles/create.php`;
          method = 'POST';
        }

        const res = await fetch(url, {
          method,
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(articleData)
        });
        const data = await res.json();

        if (data.code === 200) {
          showToast(currentArticleId ? '文章已更新' : '文章已创建', 'success');
          if (!currentArticleId && data.data?.id) {
            window.location.href = '?id=' + data.data.id;
          }
        } else {
          showToast(data.message || '保存失败', 'error');
        }
      } catch (e) {
        showToast('保存失败: ' + e.message, 'error');
      }
    }

    function confirmDelete() {
      document.getElementById('deleteModal').style.display = 'flex';
    }

    function closeDeleteModal() {
      document.getElementById('deleteModal').style.display = 'none';
    }

    async function deleteArticle() {
      if (!currentArticleId) return;
      try {
        const res = await fetch(`${API_BASE}/articles/delete.php?id=${currentArticleId}`, { method: 'DELETE' });
        const data = await res.json();
        if (data.code === 200) {
          showToast('文章已删除', 'success');
          window.location.href = 'editor.php';
        } else {
          showToast(data.message || '删除失败', 'error');
        }
      } catch (e) {
        showToast('删除失败', 'error');
      }
      closeDeleteModal();
    }

    function showToast(msg, type) {
      const toast = document.getElementById('toast');
      toast.textContent = msg;
      toast.className = `toast toast-${type} show`;
      setTimeout(() => { toast.classList.remove('show'); }, 3000);
    }
  </script>
</body>
</html>
