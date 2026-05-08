<template>
  <div class="home">
    <div class="hero-section">
      <div class="hero-content">
        <h1 class="hero-title">{{ siteTitle }}</h1>
        <div class="typewriter-wrapper">
          <Typewriter ref="typewriterRef" :texts="welcomeTexts" :loop="true" />
        </div>
        <div class="hero-buttons">
          <button class="btn btn-primary" @click="scrollToSection('articles')">
            浏览文章
          </button>
          <button class="btn btn-secondary" @click="router.push('/fun')">
            查看资源
          </button>
        </div>
      </div>
    </div>

    <div class="articles-section" id="articles">
      <div class="container">
        <h2 class="section-title">最新文章</h2>
        <div class="articles-grid">
          <article class="article-card" v-for="(article, index) in recentArticles" :key="index" @click="goToArticleDetail(article.id)">
            <div class="article-image" :style="{ backgroundImage: article.cover_image ? `url(${article.cover_image})` : article.color }">
              <div v-if="!article.cover_image" class="article-icon">{{ article.icon }}</div>
            </div>
            <div class="article-content">
              <div class="article-meta">
                <span class="article-category">{{ article.category }}</span>
                <span class="article-date">{{ article.date }}</span>
              </div>
              <h3 class="article-title">{{ article.title }}</h3>
              <p class="article-desc">{{ article.description }}</p>
              <div class="article-tags">
                <span class="tag" v-for="tag in article.tags" :key="tag">{{ tag }}</span>
              </div>
            </div>
          </article>
        </div>
        <div class="section-footer">
          <button class="btn btn-outline" @click="goToArticles">查看更多文章 →</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Typewriter from '../components/Typewriter.vue'
import { articleApi, homeApi } from '../services/api'

const router = useRouter()
const typewriterRef = ref(null)
const welcomeTexts = ref([])
const siteTitle = ref('')

const recentArticles = ref([])

onMounted(async () => {
  await loadArticles()
  await loadWelcomeTexts()
})

async function loadWelcomeTexts() {
  try {
    const result = await homeApi.getWelcomeTexts()
    if (result.code === 200 && result.data) {
      welcomeTexts.value = result.data.texts || []
      siteTitle.value = result.data.siteTitle || ''
    }
  } catch (error) {
    console.error('加载欢迎文字失败:', error)
  }
}

async function loadArticles() {
  try {
    const result = await articleApi.getList({ page: 1, pageSize: 3 })
    if (result.code === 200 && result.data && result.data.list && result.data.list.length > 0) {
      recentArticles.value = result.data.list
    }
  } catch (error) {
    console.error('加载文章列表失败:', error)
  }
}

function scrollToSection(sectionId) {
  const element = document.getElementById(sectionId)
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' })
  }
}

function goToArticles() {
  router.push('/articles')
}

function goToArticleDetail(id) {
  if (id) {
    router.push(`/articles/${id}`)
  } else {
    router.push('/articles')
  }
}
</script>

<style scoped>
.home {
  width: 100%;
  position: relative;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.hero-section {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 40px 20px;
}

.hero-content {
  text-align: center;
  max-width: 800px;
}

.hero-title {
  font-size: clamp(2rem, 6vw, 4rem);
  color: white;
  margin-bottom: 20px;
  text-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.typewriter-wrapper {
  min-height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 40px;
}

.hero-buttons {
  display: flex;
  gap: 20px;
  justify-content: center;
  flex-wrap: wrap;
}

.btn {
  padding: 14px 32px;
  font-size: 1rem;
  border: none;
  border-radius: 50px;
  cursor: pointer;
  transition: all 0.3s ease;
  font-family: inherit;
}

.btn-primary {
  background: rgba(255, 255, 255, 0.95);
  color: #111;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.3);
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.15);
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.3);
  backdrop-filter: blur(10px);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.25);
  transform: translateY(-2px);
}

.btn-outline {
  background: transparent;
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.3);
}

.btn-outline:hover {
  background: rgba(255, 255, 255, 0.1);
}

.section-title {
  font-size: clamp(1.8rem, 4vw, 2.5rem);
  color: white;
  text-align: center;
  margin-bottom: 50px;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.articles-section {
  padding: 80px 0;
}

.articles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 30px;
}

.article-card {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.15);
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.article-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.article-image {
  height: 180px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

.article-icon {
  font-size: 4rem;
}

.article-content {
  padding: 25px;
}

.article-meta {
  display: flex;
  gap: 15px;
  margin-bottom: 12px;
}

.article-category {
  background: rgba(255, 255, 255, 0.2);
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  color: white;
}

.article-date {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.85rem;
}

.article-title {
  font-size: 1.3rem;
  color: white;
  margin-bottom: 10px;
}

.article-desc {
  color: rgba(255, 255, 255, 0.75);
  line-height: 1.6;
  margin-bottom: 15px;
}

.article-tags {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.tag {
  background: rgba(255, 255, 255, 0.1);
  padding: 4px 10px;
  border-radius: 15px;
  font-size: 0.8rem;
  color: rgba(255, 255, 255, 0.8);
}

.section-footer {
  text-align: center;
  margin-top: 40px;
}

@media (max-width: 768px) {
  .articles-grid {
    grid-template-columns: 1fr;
  }
}
</style>