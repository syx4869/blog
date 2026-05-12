<template>
  <div class="article-detail">
    <div class="reading-progress" :style="{ width: progressWidth + '%' }"></div>

    <div class="container">
      <div class="back-btn" @click="goBack">
        ← 返回列表
      </div>

      <div class="loading-state" v-if="loading">
        <div class="loading-spinner"></div>
        <p>加载中...</p>
      </div>

      <div class="error-state" v-if="!loading && error">
        <div class="error-icon">❌</div>
        <p class="error-text">{{ error }}</p>
        <button class="retry-btn" @click="loadArticle">重新加载</button>
      </div>

      <div class="content-layout" v-if="!loading && !error && article">
        <article class="article-content-wrapper">
          <header class="article-header">
            <div class="article-meta-top">
              <span class="article-category">{{ article.category?.name || article.category }}</span>
              <span class="article-date">{{ formatDate(article.createdAt) }}</span>
              <span class="article-word-count"><i class="iconfont icon-bianjiwenjian"></i> {{ article.wordCount || 0 }} 字</span>
              <span class="article-read-time"><i class="iconfont icon-shijian"></i> {{ article.readTime || 5 }} 分钟阅读</span>
            </div>
            <h1 class="article-title">{{ article.title }}</h1>
            <div class="article-meta-bottom">
              <span class="meta-item"><i class="iconfont icon-icon-"></i> {{ article.viewCount || 0 }} 浏览</span>
              <span class="meta-item"><i class="iconfont icon-31pinglun"></i> {{ article.commentCount || 0 }} 评论</span>
              <span v-if="article.updatedAt" class="meta-item"><i class="iconfont icon-24gf-calendar"></i> 更新于 {{ formatDate(article.updatedAt) }}</span>
            </div>
          </header>

          <div class="article-body" v-html="article.content" @click="handleImageClick">
          </div>

          <div class="article-tags" v-if="article.tags && article.tags.length > 0">
            <span class="tag" v-for="tag in article.tags" :key="tag.id || tag">{{ tag.name || tag }}</span>
          </div>

          <div class="article-actions">
            <button class="action-btn like-btn" :class="{ liked: isLiked }" @click="handleLike">
              <span class="btn-icon"><i :class="isLiked ? 'iconfont icon-xihuan' : 'iconfont icon-xihuan1'"></i></span>
              <span>{{ isLiked ? '已喜欢' : '喜欢' }}</span>
              <span class="like-count" v-if="article.likeCount">({{ article.likeCount }})</span>
            </button>
            <button class="action-btn collect-btn" :class="{ collected: isCollected }" @click="handleCollect">
              <span class="btn-icon"><i :class="isCollected ? 'iconfont icon-shoucang-yishoucang' : 'iconfont icon-shoucang'"></i></span>
              <span>{{ isCollected ? '已收藏' : '收藏' }}</span>
            </button>
            <div class="share-dropdown" ref="shareDropdownRef">
              <button class="action-btn share-btn" @click="toggleShareDropdown">
                <span class="btn-icon"><i class="iconfont icon-a-fenxiangweifenxiang"></i></span>
                <span>分享</span>
              </button>
              <Teleport to="body">
                <div class="share-menu" v-show="showShareDropdown" :style="shareMenuStyle">
                  <div class="share-option" @click="copyLink">
                    <span><i class="iconfont icon-a-fenxiangweifenxiang"></i></span>
                    <span>复制链接</span>
                  </div>
                  <div class="share-option" @click="shareToWeibo">
                    <span><i class="iconfont icon-a-fenxiangweifenxiang"></i></span>
                    <span>分享到微博</span>
                  </div>
                </div>
              </Teleport>
            </div>
          </div>
        </article>

        <aside class="toc-sidebar" v-if="toc.length > 0">
          <div class="toc-sticky">
            <h3 class="toc-title"><i class="iconfont icon-bianjiwenjian"></i> 文章目录</h3>
            <nav class="toc-nav">
              <a
                v-for="item in toc"
                :key="item.id"
                :class="['toc-link', { active: activeToc === item.id }]"
                :style="{ paddingLeft: (item.level - 2) * 16 + 'px' }"
                @click="scrollToHeading(item.id)"
              >
                {{ item.text }}
              </a>
            </nav>
          </div>
        </aside>
      </div>

      <div class="article-navigation" v-if="!loading && !error && article">
        <div
          class="nav-item prev"
          v-if="article.prevArticle"
          @click="goToArticle(article.prevArticle.id)"
        >
          <span class="nav-label">← 上一篇</span>
          <span class="nav-title">{{ article.prevArticle.title }}</span>
        </div>
        <div class="nav-spacer" v-if="!article.prevArticle"></div>
        <div
          class="nav-item next"
          v-if="article.nextArticle"
          @click="goToArticle(article.nextArticle.id)"
        >
          <span class="nav-label">下一篇 →</span>
          <span class="nav-title">{{ article.nextArticle.title }}</span>
        </div>
        <div class="nav-spacer" v-if="!article.nextArticle"></div>
      </div>

      <div class="related-articles" v-if="!loading && !error && article?.relatedArticles?.length > 0">
        <h2 class="section-title"><i class="iconfont icon-xiangguanwenzhang"></i> 相关文章</h2>
        <div class="related-grid">
          <article
            class="related-card"
            v-for="item in article.relatedArticles"
            :key="item.id"
            @click="goToArticle(item.id)"
            style="cursor: pointer;"
          >
            <div class="related-cover" :style="{ backgroundImage: `url(${item.cover_image})`, backgroundSize: 'cover', backgroundPosition: 'center' }">
            </div>
            <div class="related-info">
              <h3 class="related-title">{{ item.title }}</h3>
              <span class="related-views"><i class="iconfont icon-icon-"></i> {{ item.viewCount || 0 }}</span>
            </div>
          </article>
        </div>
      </div>

      <div class="comments-section" v-if="!loading && !error && article">
        <h2 class="section-title"><i class="iconfont icon-31pinglun"></i> 评论 ({{ comments.length }})</h2>

        <div class="comment-form">
          <div class="form-row">
            <input
              v-model="commentForm.nickname"
              type="text"
              placeholder="昵称 *"
              class="form-input"
            />
            <input
              v-model="commentForm.email"
              type="email"
              placeholder="邮箱 * (不会公开)"
              class="form-input"
            />
          </div>
          <textarea
            v-model="commentForm.content"
            placeholder="写下你的评论..."
            class="form-textarea"
            rows="4"
          ></textarea>
          <button class="submit-btn" @click="submitComment" :disabled="submittingComment">
            {{ submittingComment ? '发送中...' : '发表评论' }}
          </button>
        </div>

        <div class="comments-list">
          <div class="comment-item" v-for="comment in comments" :key="comment.id">
            <div class="comment-avatar">{{ getAvatarInitial(comment.nickname) }}</div>
            <div class="comment-content">
              <div class="comment-header">
                <span class="comment-nickname">{{ comment.nickname }}</span>
                <span v-if="comment.isAuthor" class="author-badge">作者</span>
                <span class="comment-time">{{ formatRelativeTime(comment.createdAt) }}</span>
              </div>
              <p class="comment-text">{{ comment.content }}</p>
              <div class="comment-actions">
                <button class="comment-action-btn" @click="handleCommentLike(comment)">
                  <span><i class="iconfont icon-dianzan"></i></span> {{ comment.likeCount || 0 }}
                </button>
                <button class="comment-action-btn" @click="replyToComment(comment)">
                  <i class="iconfont icon-31pinglun"></i> 回复
                </button>
              </div>

              <div class="comment-replies" v-if="comment.replies && comment.replies.length > 0">
                <div class="reply-item" v-for="reply in comment.replies" :key="reply.id">
                  <div class="reply-avatar">{{ getAvatarInitial(reply.nickname) }}</div>
                  <div class="reply-content">
                    <div class="reply-header">
                      <span class="reply-nickname">{{ reply.nickname }}</span>
                      <span v-if="reply.replyTo" class="reply-to">回复 {{ reply.replyTo }}</span>
                      <span class="reply-time">{{ formatRelativeTime(reply.createdAt) }}</span>
                    </div>
                    <p class="reply-text">{{ reply.content }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="no-comments" v-if="comments.length === 0">
          <div class="no-comments-icon"><i class="iconfont icon-31pinglun"></i></div>
          <p>暂无评论，来说点什么吧~</p>
        </div>
      </div>
    </div>

    <button
      class="back-to-top"
      :class="{ visible: showBackToTop }"
      @click="scrollToTop"
    >
      ↑
    </button>

    <div class="lightbox" v-if="lightbox.show" @click="closeLightbox">
      <img :src="lightbox.url" alt="大图" />
      <span class="lightbox-close">✕</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { articleApi, commentApi } from '../services/api'

const router = useRouter()
const route = useRoute()

const article = ref(null)
const loading = ref(false)
const error = ref('')
const isLiked = ref(false)
const isCollected = ref(false)
const toc = ref([])
const activeToc = ref('')
const progressWidth = ref(0)
const showBackToTop = ref(false)
const showShareDropdown = ref(false)
const shareDropdownRef = ref(null)
const shareMenuStyle = ref({})
const lightbox = ref({ show: false, url: '' })

const comments = ref([])

const commentForm = ref({
  nickname: '',
  email: '',
  content: '',
  parentId: null,
  replyTo: null
})
const submittingComment = ref(false)

const mockArticle = null

onMounted(async () => {
  await loadArticle()
  window.addEventListener('scroll', handleScroll)
  window.addEventListener('scroll', updateShareMenuPosition)
  window.addEventListener('resize', updateShareMenuPosition)
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  window.removeEventListener('scroll', updateShareMenuPosition)
  window.removeEventListener('resize', updateShareMenuPosition)
  document.removeEventListener('click', handleClickOutside)
})

// 监听路由参数变化，当文章ID变化时重新加载文章
watch(() => route.params.id, async (newId, oldId) => {
  if (newId && newId !== oldId) {
    await loadArticle()
  }
})

async function loadArticle() {
  loading.value = true
  error.value = ''
  article.value = null
  try {
    const articleId = route.params.id
    const result = await articleApi.getDetail(articleId)
    if (result.code === 200 && result.data) {
      article.value = result.data
      loadLocalState()
      await loadComments()
      await nextTick()
      extractTOC()
    } else {
      error.value = result.message || '文章不存在或已被删除'
    }
  } catch (err) {
    console.error('加载文章详情失败:', err)
    error.value = '加载失败，请稍后重试'
  } finally {
    loading.value = false
  }
}

async function loadComments() {
  if (!article.value?.id) return
  try {
    const result = await commentApi.getList(article.value.id)
    if (result.code === 200 && result.data) {
      comments.value = result.data
    }
  } catch (error) {
    console.error('加载评论失败:', error)
  }
}

function loadLocalState() {
  if (!article.value) return

  const likedArticles = JSON.parse(localStorage.getItem('likedArticles') || '[]')
  isLiked.value = likedArticles.includes(article.value.id)

  // 收藏状态从服务器获取（已在 article 中返回）
  isCollected.value = article.value.isCollected || false
}

function extractTOC() {
  const body = document.querySelector('.article-body')
  if (!body) return
  
  const headings = body.querySelectorAll('h2, h3, h4')
  toc.value = Array.from(headings).map((h, index) => ({
    id: h.id || `heading-${index}`,
    text: h.textContent,
    level: parseInt(h.tagName.charAt(1))
  }))
  
  toc.value.forEach(item => {
    const el = body.querySelector(`h${item.level}:contains("${item.text}")`) || body.querySelectorAll(`h${item.level}`)[toc.value.filter(t => t.level === item.level).indexOf(item)]
    if (el && !el.id) {
      el.id = item.id
    }
  })
}

function handleScroll() {
  const scrollTop = window.scrollY
  const docHeight = document.documentElement.scrollHeight - window.innerHeight
  progressWidth.value = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0
  showBackToTop.value = scrollTop > 500
  
  updateActiveToc()
}

function updateActiveToc() {
  if (toc.value.length === 0) return
  
  const scrollPos = window.scrollY + 100
  let activeId = toc.value[0]?.id
  
  for (const item of toc.value) {
    const el = document.getElementById(item.id)
    if (el && el.offsetTop <= scrollPos) {
      activeId = item.id
    }
  }
  
  activeToc.value = activeId
}

function scrollToHeading(id) {
  const el = document.getElementById(id)
  if (el) {
    el.scrollIntoView({ behavior: 'smooth' })
  }
}

function scrollToTop() {
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

async function handleLike() {
  if (!article.value) return
  
  try {
    const result = await articleApi.like(article.value.id)
    if (result.code === 200) {
      updateLikeState()
    }
  } catch (err) {
    updateLikeState()
  }
}

function updateLikeState() {
  isLiked.value = !isLiked.value
  
  const likedArticles = JSON.parse(localStorage.getItem('likedArticles') || '[]')
  if (isLiked.value) {
    likedArticles.push(article.value.id)
    article.value.likeCount = (article.value.likeCount || 0) + 1
  } else {
    const index = likedArticles.indexOf(article.value.id)
    if (index > -1) likedArticles.splice(index, 1)
    article.value.likeCount = Math.max(0, (article.value.likeCount || 0) - 1)
  }
  localStorage.setItem('likedArticles', JSON.stringify(likedArticles))
}

async function handleCollect() {
  if (!article.value?.id) return
  try {
    const result = await articleApi.collect(article.value.id)
    if (result.code === 200) {
      isCollected.value = result.data?.collected ?? !isCollected.value
      article.value.collectCount = result.data?.collectCount ?? (article.value.collectCount || 0)
    } else {
      alert(result.message || '收藏操作失败')
    }
  } catch (err) {
    console.error('收藏请求失败:', err)
    alert('收藏操作失败，请稍后重试')
  }
}

function toggleShareDropdown() {
  if (!showShareDropdown.value) {
    const rect = shareDropdownRef.value.getBoundingClientRect()
    shareMenuStyle.value = {
      position: 'fixed',
      top: `${rect.bottom + 10}px`,
      left: `${rect.left}px`,
      zIndex: 999999
    }
  }
  showShareDropdown.value = !showShareDropdown.value
}

function updateShareMenuPosition() {
  if (showShareDropdown.value && shareDropdownRef.value) {
    const rect = shareDropdownRef.value.getBoundingClientRect()
    shareMenuStyle.value = {
      position: 'fixed',
      top: `${rect.bottom + 10}px`,
      left: `${rect.left}px`,
      zIndex: 999999
    }
  }
}

function handleClickOutside(e) {
  if (shareDropdownRef.value && !shareDropdownRef.value.contains(e.target)) {
    showShareDropdown.value = false
  }
}

function copyLink() {
  navigator.clipboard.writeText(window.location.href).then(() => {
    alert('链接已复制！')
  })
  showShareDropdown.value = false
}

function shareToWeibo() {
  const url = encodeURIComponent(window.location.href)
  const title = encodeURIComponent(article.value?.title || '')
  window.open(`https://service.weibo.com/share/share.php?url=${url}&title=${title}`, '_blank')
  showShareDropdown.value = false
}

function handleImageClick(e) {
  if (e.target.tagName === 'IMG') {
    lightbox.value = { show: true, url: e.target.src }
  }
}

function closeLightbox() {
  lightbox.value = { show: false, url: '' }
}

function goBack() {
  router.push('/articles')
}

function goToArticle(id) {
  if (!id) return
  router.push(`/articles/${id}`)
}

async function submitComment() {
  if (!commentForm.value.nickname || !commentForm.value.email || !commentForm.value.content) {
    alert('请填写完整信息！')
    return
  }

  submittingComment.value = true

  try {
    const result = await commentApi.create({
      article_id: article.value.id,
      nickname: commentForm.value.nickname,
      email: commentForm.value.email,
      content: commentForm.value.content,
      parent_id: commentForm.value.parentId,
      reply_to: commentForm.value.replyTo
    })

    if (result.code === 200) {
      commentForm.value = { nickname: '', email: '', content: '', parentId: null, replyTo: null }
      await loadComments()
      alert('评论发表成功！')
    } else {
      alert(result.message || '评论发表失败')
    }
  } catch (error) {
    console.error('发表评论失败:', error)
    alert('评论发表失败，请稍后重试')
  } finally {
    submittingComment.value = false
  }
}

function replyToComment(comment) {
  commentForm.value.replyTo = comment.nickname
  commentForm.value.parentId = comment.id
  document.querySelector('.form-textarea').focus()
}

async function handleCommentLike(comment) {
  try {
    const result = await commentApi.like(comment.id)
    if (result.code === 200) {
      comment.likeCount = (comment.likeCount || 0) + 1
    } else {
      alert(result.message || '点赞失败')
    }
  } catch (error) {
    console.error('点赞失败:', error)
    alert('点赞失败，请稍后重试')
  }
}

function formatDate(dateStr) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString('zh-CN', { year: 'numeric', month: 'long', day: 'numeric' })
}

function formatRelativeTime(dateStr) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  const now = new Date()
  const diff = Math.floor((now - date) / 1000)
  
  if (diff < 60) return '刚刚'
  if (diff < 3600) return `${Math.floor(diff / 60)} 分钟前`
  if (diff < 86400) return `${Math.floor(diff / 3600)} 小时前`
  if (diff < 604800) return `${Math.floor(diff / 86400)} 天前`
  return formatDate(dateStr)
}

function getAvatarInitial(name) {
  return name ? name.charAt(0).toUpperCase() : '?'
}
</script>

<style scoped>
.article-detail {
  width: 100%;
  min-height: 100vh;
  padding: 100px 0 60px;
  position: relative;
}

.reading-progress {
  position: fixed;
  top: 0;
  left: 0;
  height: 3px;
  background: rgba(255, 255, 255, 0.9);
  z-index: 1000;
  transition: width 0.1s linear;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 12px 24px;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 50px;
  color: white;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-bottom: 30px;
  font-family: inherit;
}

.back-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateX(-5px);
}

.loading-state,
.error-state {
  text-align: center;
  padding: 80px 20px;
}

.loading-spinner {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(255, 255, 255, 0.2);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 20px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.error-icon {
  font-size: 5rem;
  margin-bottom: 20px;
}

.error-text {
  font-size: 1.2rem;
  color: rgba(255, 255, 255, 0.7);
  margin-bottom: 20px;
}

.retry-btn {
  padding: 12px 24px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 50px;
  color: white;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  font-family: inherit;
}

.retry-btn:hover {
  background: rgba(255, 255, 255, 0.25);
}

.content-layout {
  display: flex;
  gap: 40px;
  align-items: flex-start;
}

.article-content-wrapper {
  flex: 1;
  min-width: 0;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  padding: 40px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  position: relative;
  z-index: 1;
}

.article-header {
  margin-bottom: 40px;
  padding-bottom: 30px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.article-meta-top {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.article-category {
  background: rgba(255, 255, 255, 0.2);
  padding: 6px 16px;
  border-radius: 20px;
  font-size: 0.9rem;
  color: white;
}

.article-date,
.article-word-count,
.article-read-time {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.9rem;
  display: flex;
  align-items: center;
}

.article-title {
  font-size: clamp(1.8rem, 4vw, 2.8rem);
  color: white;
  line-height: 1.3;
  margin-bottom: 20px;
}

.article-meta-bottom {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

.meta-item {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.95rem;
}

.article-body {
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.8;
  font-size: 1.1rem;
  word-wrap: break-word;
  overflow-wrap: break-word;
}

.article-body :deep(h1),
.article-body :deep(h2),
.article-body :deep(h3),
.article-body :deep(h4) {
  color: white;
  margin: 1.5em 0 0.8em;
  scroll-margin-top: 100px;
  line-height: 1.4;
}

.article-body :deep(h1) {
  font-size: 2rem;
}

.article-body :deep(h2) {
  font-size: 1.6rem;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.article-body :deep(h3) {
  font-size: 1.3rem;
}

.article-body :deep(p) {
  margin: 1em 0;
}

.article-body :deep(a) {
  color: rgba(255, 255, 255, 0.9);
  text-decoration: underline;
  text-underline-offset: 3px;
  transition: color 0.2s;
}

.article-body :deep(a:hover) {
  color: #8b9cf7;
}

.article-body :deep(img) {
  max-width: 100%;
  border-radius: 12px;
  margin: 1.5em auto;
  display: block;
  cursor: pointer;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.article-body :deep(img:hover) {
  transform: scale(1.02);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.4);
}

.article-body :deep(strong) {
  color: white;
  font-weight: 600;
}

.article-body :deep(em) {
  color: rgba(255, 255, 255, 0.95);
  font-style: italic;
}

.article-body :deep(u) {
  text-decoration-color: rgba(102, 126, 234, 0.5);
  text-underline-offset: 3px;
}

.article-body :deep(s) {
  opacity: 0.5;
}

.article-body :deep(code) {
  background: rgba(102, 126, 234, 0.15);
  padding: 3px 8px;
  border-radius: 6px;
  font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;
  font-size: 0.9em;
  color: #a0b4ff;
  border: 1px solid rgba(102, 126, 234, 0.2);
}

.article-body :deep(pre) {
  background: rgba(0, 0, 0, 0.5);
  padding: 20px 24px;
  border-radius: 12px;
  overflow-x: auto;
  margin: 1.5em 0;
  border: 1px solid rgba(255, 255, 255, 0.08);
  position: relative;
}

.article-body :deep(pre code) {
  background: transparent;
  padding: 0;
  color: #c8d6e5;
  border: none;
  font-size: 0.9rem;
  line-height: 1.6;
}

.article-body :deep(blockquote) {
  border-left: 4px solid rgba(255, 255, 255, 0.5);
  margin: 1.5em 0;
  color: rgba(255, 255, 255, 0.85);
  background: rgba(255, 255, 255, 0.08);
  padding: 16px 20px;
  border-radius: 0 12px 12px 0;
  font-style: italic;
}

.article-body :deep(ul),
.article-body :deep(ol) {
  margin: 1em 0;
  padding-left: 2em;
}

.article-body :deep(li) {
  margin: 0.5em 0;
  line-height: 1.7;
}

.article-body :deep(ul li)::marker {
  color: rgba(255, 255, 255, 0.7);
}

.article-body :deep(ol li)::marker {
  color: rgba(255, 255, 255, 0.7);
  font-weight: bold;
}

.article-body :deep(table) {
  width: 100%;
  border-collapse: collapse;
  margin: 1.5em 0;
  border-radius: 12px;
  overflow: hidden;
}

.article-body :deep(th),
.article-body :deep(td) {
  border: 1px solid rgba(255, 255, 255, 0.1);
  padding: 12px 16px;
  text-align: left;
}

.article-body :deep(th) {
  background: rgba(102, 126, 234, 0.15);
  color: white;
  font-weight: 600;
}

.article-body :deep(tr:hover td) {
  background: rgba(255, 255, 255, 0.03);
}

.article-body :deep(hr) {
  border: none;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
  margin: 2em 0;
}

.article-body :deep(iframe) {
  max-width: 100%;
  border-radius: 12px;
  margin: 1.5em 0;
}

.article-body :deep(.ql-align-center) {
  text-align: center;
}

.article-body :deep(.ql-align-right) {
  text-align: right;
}

.article-body :deep(.ql-align-justify) {
  text-align: justify;
}

.article-body :deep(.ql-indent-1) {
  padding-left: 2em;
}

.article-body :deep(.ql-indent-2) {
  padding-left: 4em;
}

.article-body :deep(.ql-indent-3) {
  padding-left: 6em;
}

.article-body :deep(.ql-size-small) {
  font-size: 0.85em;
}

.article-body :deep(.ql-size-large) {
  font-size: 1.25em;
}

.article-body :deep(.ql-size-huge) {
  font-size: 1.5em;
}

.article-tags {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  margin: 40px 0;
  padding-top: 30px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.tag {
  background: rgba(255, 255, 255, 0.1);
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.85);
  transition: background 0.3s ease;
}

.tag:hover {
  background: rgba(255, 255, 255, 0.2);
}

.article-actions {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 14px 28px;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 50px;
  color: white;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  font-family: inherit;
}

.action-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
}

.like-btn.liked {
  background: rgba(255, 107, 129, 0.3);
  border-color: rgba(255, 107, 129, 0.5);
}

.like-btn.liked:hover {
  background: rgba(255, 107, 129, 0.4);
}

.collect-btn.collected {
  background: rgba(255, 193, 7, 0.3);
  border-color: rgba(255, 193, 7, 0.5);
}

.collect-btn.collected:hover {
  background: rgba(255, 193, 7, 0.4);
}

.share-dropdown {
  position: relative;
}

.share-menu {
  background: rgba(30, 30, 40, 0.95);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 15px;
  padding: 10px 0;
  min-width: 180px;
  animation: dropdownFade 0.2s ease;
}

@keyframes dropdownFade {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.share-option {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 20px;
  color: white;
  cursor: pointer;
  transition: background 0.2s ease;
}

.share-option:hover {
  background: rgba(255, 255, 255, 0.1);
}

.btn-icon {
  font-size: 1.2rem;
  display: flex;
  align-items: center;
}

.btn-icon .iconfont {
  font-size: inherit;
  vertical-align: middle;
  line-height: 1;
}

.like-btn.liked .btn-icon .iconfont {
  color: #ff4757;
}

.collect-btn.collected .btn-icon .iconfont {
  color: #ffc107;
}

.like-count {
  opacity: 0.8;
}

.toc-sidebar {
  width: 240px;
  flex-shrink: 0;
}

.toc-sticky {
  position: sticky;
  top: 100px;
}

.toc-title {
  font-size: 1.1rem;
  color: white;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.toc-nav {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.toc-link {
  display: block;
  padding: 8px 12px;
  color: rgba(255, 255, 255, 0.6);
  text-decoration: none;
  font-size: 0.9rem;
  border-radius: 8px;
  transition: all 0.2s ease;
  cursor: pointer;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.toc-link:hover {
  color: white;
  background: rgba(255, 255, 255, 0.1);
}

.toc-link.active {
  color: white;
  background: rgba(255, 255, 255, 0.15);
}

.article-navigation {
  display: flex;
  gap: 20px;
  margin: 60px 0;
  position: relative;
  z-index: 2;
}

.nav-item {
  flex: 1;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 15px;
  padding: 25px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.nav-item:hover {
  background: rgba(255, 255, 255, 0.15);
  transform: translateY(-3px);
}

.nav-item.prev {
  text-align: left;
}

.nav-item.next {
  text-align: right;
}

.nav-spacer {
  flex: 1;
}

.nav-label {
  display: block;
  font-size: 0.9rem;
  color: rgba(255, 255, 255, 0.5);
  margin-bottom: 8px;
}

.nav-title {
  display: block;
  font-size: 1.1rem;
  color: white;
  font-weight: 500;
}

.related-articles {
  margin: 60px 0;
  position: relative;
  z-index: 2;
}

.section-title {
  font-size: 1.6rem;
  color: white;
  margin-bottom: 30px;
}

.related-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 20px;
}

.related-card {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 15px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s ease;
}

.related-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
}

.related-cover {
  height: 150px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.related-icon {
  font-size: 3rem;
}

.related-info {
  padding: 20px;
}

.related-title {
  font-size: 1.1rem;
  color: white;
  margin-bottom: 10px;
  line-height: 1.4;
}

.related-views {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.9rem;
}

.comments-section {
  margin: 60px 0;
  padding: 40px;
  position: relative;
  z-index: 2;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-radius: 20px;
}

.comment-form {
  margin-bottom: 40px;
}

.form-row {
  display: flex;
  gap: 15px;
  margin-bottom: 15px;
}

.form-input {
  flex: 1;
  padding: 14px 20px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  color: white;
  font-size: 1rem;
  outline: none;
  transition: border-color 0.3s ease;
  font-family: inherit;
}

.form-input:focus {
  border-color: rgba(255, 255, 255, 0.5);
}

.form-input::placeholder {
  color: rgba(255, 255, 255, 0.4);
}

.form-textarea {
  width: 100%;
  padding: 14px 20px;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  color: white;
  font-size: 1rem;
  outline: none;
  transition: border-color 0.3s ease;
  font-family: inherit;
  resize: vertical;
  margin-bottom: 15px;
}

.form-textarea:focus {
  border-color: rgba(255, 255, 255, 0.5);
}

.form-textarea::placeholder {
  color: rgba(255, 255, 255, 0.4);
}

.submit-btn {
  padding: 14px 40px;
  background: rgba(255, 255, 255, 0.95);
  border: none;
  border-radius: 50px;
  color: #111;
  font-size: 1rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
  font-family: inherit;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

.submit-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.comments-list {
  display: flex;
  flex-direction: column;
  gap: 30px;
}

.comment-item {
  display: flex;
  gap: 15px;
}

.comment-avatar,
.reply-avatar {
  width: 45px;
  height: 45px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-weight: bold;
  font-size: 1.1rem;
  flex-shrink: 0;
}

.comment-content {
  flex: 1;
}

.comment-header,
.reply-header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 8px;
  flex-wrap: wrap;
}

.comment-nickname,
.reply-nickname {
  color: white;
  font-weight: 500;
}

.author-badge {
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: white;
  font-size: 0.75rem;
  padding: 2px 8px;
  border-radius: 10px;
}

.comment-time,
.reply-time {
  color: rgba(255, 255, 255, 0.5);
  font-size: 0.85rem;
}

.comment-text,
.reply-text {
  color: rgba(255, 255, 255, 0.9);
  line-height: 1.6;
  margin-bottom: 10px;
}

.comment-actions {
  display: flex;
  gap: 15px;
}

.comment-action-btn {
  background: transparent;
  border: none;
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.9rem;
  cursor: pointer;
  padding: 0;
  transition: color 0.2s ease;
  font-family: inherit;
}

.comment-action-btn:hover {
  color: white;
}

.comment-replies {
  margin-top: 20px;
  padding-left: 20px;
  border-left: 2px solid rgba(255, 255, 255, 0.1);
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.reply-item {
  display: flex;
  gap: 12px;
}

.reply-to {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.85rem;
}

.no-comments {
  text-align: center;
  padding: 60px 20px;
  color: rgba(255, 255, 255, 0.7);
}

.no-comments-icon {
  margin-bottom: 20px;
}

.no-comments-icon .icon-31pinglun{
  font-size: 5rem;
}

.back-to-top {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 50px;
  height: 50px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(20px);
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 50%;
  color: white;
  font-size: 1.5rem;
  cursor: pointer;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
  z-index: 100;
}

.back-to-top.visible {
  opacity: 1;
  visibility: visible;
}

.back-to-top:hover {
  background: rgba(255, 255, 255, 0.3);
  transform: translateY(-3px);
}

.lightbox {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
  cursor: zoom-out;
}

.lightbox img {
  max-width: 90%;
  max-height: 90%;
  border-radius: 10px;
}

.lightbox-close {
  position: absolute;
  top: 30px;
  right: 30px;
  color: white;
  font-size: 2rem;
  cursor: pointer;
}

@media (max-width: 1024px) {
  .content-layout {
    flex-direction: column;
  }
  
  .toc-sidebar {
    display: none;
  }
}

@media (max-width: 768px) {
  .article-content-wrapper {
    padding: 25px;
  }

  .article-title {
    font-size: 1.6rem;
  }

  .article-body {
    font-size: 1rem;
  }

  .article-actions {
    flex-direction: column;
  }

  .action-btn {
    width: 100%;
    justify-content: center;
  }

  .article-navigation {
    flex-direction: column;
  }

  .related-grid {
    grid-template-columns: 1fr;
  }

  .comments-section {
    padding: 25px;
  }

  .form-row {
    flex-direction: column;
  }

  .comment-item,
  .reply-item {
    flex-direction: column;
  }

  .comment-replies {
    padding-left: 0;
    border-left: none;
  }
}
</style>
