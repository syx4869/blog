<template>
  <div class="about-page">
    <!-- 个人简介区域 -->
    <section class="hero-section">
      <div class="container">
        <div class="profile-card" v-if="aboutData.info">
          <div class="avatar-wrapper">
            <div class="avatar">{{ aboutData.info.avatar || '👨‍💻' }}</div>
            <div class="avatar-ring"></div>
          </div>
          <h1 class="name">{{ aboutData.info.name }}</h1>
          <p class="title">{{ aboutData.info.title }}</p>
          <p class="bio">{{ aboutData.info.bio }}</p>
          <div class="social-links">
            <a v-if="aboutData.info.github" :href="aboutData.info.github" class="social-link" target="_blank">
              <span class="icon"><i class="iconfont icon-GitHub"></i></span>
              <span>GitHub</span>
            </a>
            <a v-if="aboutData.info.email" :href="'mailto:' + aboutData.info.email" class="social-link">
              <span class="icon"><i class="iconfont icon-youxiang"></i></span>
              <span>邮箱</span>
            </a>
            <a v-if="aboutData.info.wechat" href="#" class="social-link" title="微信号">
              <span class="icon">💬</span>
              <span>微信</span>
            </a>
          </div>
        </div>
        <div v-else class="loading-state">
          <div class="loading-spinner"></div>
          <p>加载中...</p>
        </div>
      </div>
    </section>

    <!-- 经历时间线 -->
    <section class="timeline-section" v-if="timelineItems.length > 0">
      <div class="container">
        <h2 class="section-title">经历时间线</h2>
        <div class="timeline">
          <div class="timeline-item" v-for="(item, index) in timelineItems" :key="index" :class="{ 'timeline-right': index % 2 === 1 }">
            <div class="timeline-content">
              <div class="timeline-badge"><i :class="item.type === 'work' ? 'iconfont icon-gongzuo' : 'iconfont icon-xueli'"></i></div>
              <div class="timeline-date">{{ item.date }}</div>
              <h3 class="timeline-title">{{ item.title }}</h3>
              <p class="timeline-org">{{ item.organization }}</p>
              <p class="timeline-desc">{{ item.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 精选项目展示 -->
    <section class="projects-section" v-if="projects.length > 0">
      <div class="container">
        <h2 class="section-title">精选项目</h2>
        <div class="projects-grid">
          <div class="project-card" v-for="project in projects" :key="project.name">
            <div class="project-header">
              <div class="project-icon">{{ project.icon }}</div>
              <div class="project-links">
                <a v-if="project.demo_url" :href="project.demo_url" class="project-link" target="_blank">演示</a>
                <a v-if="project.repo_url" :href="project.repo_url" class="project-link" target="_blank">源码</a>
              </div>
            </div>
            <h3 class="project-name">{{ project.name }}</h3>
            <p class="project-desc">{{ project.description }}</p>
            <div class="project-stack">
              <span class="stack-tag" v-for="tech in project.stackArray" :key="tech">{{ tech }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- 个人兴趣与价值观 -->
    <section class="interests-section" v-if="interests.length > 0">
      <div class="container">
        <h2 class="section-title">兴趣与价值观</h2>
        <div class="interests-grid">
          <div class="interest-card" v-for="interest in interests" :key="interest.name">
            <div class="interest-icon">{{ interest.icon }}</div>
            <h3 class="interest-name">{{ interest.name }}</h3>
            <p class="interest-desc">{{ interest.description }}</p>
          </div>
        </div>
        <div class="values-quote" v-if="aboutData.quote">
          <blockquote>
            {{ aboutData.quote }}
          </blockquote>
        </div>
      </div>
    </section>

    <!-- 联系方式 -->
    <section class="contact-section" v-if="aboutData.info && (aboutData.info.email || aboutData.info.github || aboutData.info.wechat)">
      <div class="container">
        <h2 class="section-title">联系我</h2>
        <div class="contact-grid">
          <div class="contact-item" v-if="aboutData.info.email">
            <div class="contact-icon"><i class="iconfont icon-youxiang"></i></div>
            <div class="contact-info">
              <div class="contact-label">邮箱</div>
              <div class="contact-value">{{ aboutData.info.email }}</div>
            </div>
          </div>
          <div class="contact-item" v-if="aboutData.info.github">
            <div class="contact-icon"><i class="iconfont icon-GitHub"></i></div>
            <div class="contact-info">
              <div class="contact-label">GitHub</div>
              <div class="contact-value">{{ aboutData.info.github.replace('https://', '') }}</div>
            </div>
          </div>
          <div class="contact-item" v-if="aboutData.info.wechat">
            <div class="contact-icon">💬</div>
            <div class="contact-info">
              <div class="contact-label">微信</div>
              <div class="contact-value">{{ aboutData.info.wechat }}</div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { aboutApi } from '../services/api'

const aboutData = ref({
  info: null,
  timeline: [],
  projects: [],
  interests: [],
  quote: ''
})

const loading = ref(true)

const timelineItems = computed(() => aboutData.value.timeline)

const projects = computed(() => {
  return aboutData.value.projects.map(p => ({
    ...p,
    stackArray: p.stack ? p.stack.split(',') : []
  }))
})

const interests = computed(() => aboutData.value.interests)

async function loadAboutData() {
  try {
    loading.value = true
    const result = await aboutApi.getInfo()
    if (result.code === 200 && result.data) {
      aboutData.value = result.data
    }
  } catch (error) {
    console.error('加载关于我数据失败:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadAboutData()
})
</script>

<style scoped>
.about-page {
  width: 100%;
  min-height: 100vh;
  padding-top: 80px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

.section-title {
  font-size: clamp(1.5rem, 3vw, 2rem);
  color: #e4e4e7;
  text-align: center;
  margin-bottom: 48px;
  font-weight: 600;
}

/* 个人简介区域 */
.hero-section {
  padding: 60px 0 80px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.profile-card {
  text-align: center;
  max-width: 640px;
}

.avatar-wrapper {
  position: relative;
  display: inline-block;
  margin-bottom: 24px;
}

.avatar {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  background: #27272a;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3.5rem;
  border: 3px solid #3f3f46;
}

.avatar-ring {
  position: absolute;
  inset: -6px;
  border-radius: 50%;
  border: 2px solid transparent;
  border-top-color: #3b82f6;
  border-right-color: #3b82f6;
  animation: spin 8s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.name {
  font-size: clamp(1.8rem, 4vw, 2.5rem);
  color: #e4e4e7;
  font-weight: 700;
  margin-bottom: 8px;
}

.title {
  font-size: 1.1rem;
  color: #3b82f6;
  font-weight: 500;
  margin-bottom: 20px;
}

.bio {
  font-size: 1rem;
  color: #000000;
  line-height: 1.8;
  margin-bottom: 28px;
}

.social-links {
  display: flex;
  gap: 16px;
  justify-content: center;
  flex-wrap: wrap;
}

.social-link {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  background: #27272a;
  border: 1px solid #2a2a30;
  border-radius: 8px;
  color: #d4d4d8;
  text-decoration: none;
  font-size: 0.9rem;
  transition: all 0.15s;
}

.social-link:hover {
  background: #3f3f46;
  border-color: #3b82f6;
  color: #e4e4e7;
}

.social-link .icon {
  font-size: 1.2rem;
  display: flex;
  align-items: center;
}

.social-link .icon .iconfont {
  font-size: 1.2rem;
}

/* 加载状态 */
.loading-state {
  text-align: center;
  padding: 60px 20px;
  color: #71717a;
}

.loading-spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #27272a;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 16px;
}

/* 经历时间线 */
.timeline-section {
  padding: 80px 0;
}

.timeline {
  position: relative;
  max-width: 800px;
  margin: 0 auto;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  width: 2px;
  height: 100%;
  background: #2a2a30;
}

.timeline-item {
  position: relative;
  width: 50%;
  padding: 0 40px 40px 0;
}

.timeline-item.timeline-right {
  margin-left: 50%;
  padding: 0 0 40px 40px;
}

.timeline-content {
  background: #1e1e24;
  border: 1px solid #2a2a30;
  border-radius: 10px;
  padding: 20px;
  position: relative;
}

.timeline-badge {
  position: absolute;
  width: 40px;
  height: 40px;
  background: #27272a;
  border: 2px solid #3b82f6;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.2rem;
  top: 20px;
}

.timeline-badge .iconfont {
  font-size: 1.2rem;
  color: #3b82f6;
}

.timeline-item:not(.timeline-right) .timeline-badge {
  right: -60px;
}

.timeline-item.timeline-right .timeline-badge {
  left: -60px;
}

.timeline-date {
  font-size: 0.8rem;
  color: #3b82f6;
  font-weight: 500;
  margin-bottom: 8px;
}

.timeline-title {
  font-size: 1.1rem;
  color: #e4e4e7;
  font-weight: 600;
  margin-bottom: 4px;
}

.timeline-org {
  font-size: 0.9rem;
  color: #a1a1aa;
  margin-bottom: 10px;
}

.timeline-desc {
  font-size: 0.85rem;
  color: #71717a;
  line-height: 1.6;
}

/* 精选项目展示 */
.projects-section {
  padding: 80px 0;
}

.projects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
}

.project-card {
  background: #1e1e24;
  border: 1px solid #2a2a30;
  border-radius: 10px;
  padding: 24px;
  transition: all 0.15s;
  cursor: default;
}

.project-card:hover {
  border-color: #3f3f46;
}

.project-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
}

.project-icon {
  font-size: 2rem;
}

.project-links {
  display: flex;
  gap: 8px;
}

.project-link {
  padding: 4px 12px;
  background: transparent;
  border: 1px solid #2a2a30;
  border-radius: 4px;
  color: #a1a1aa;
  text-decoration: none;
  font-size: 0.78rem;
  transition: all 0.15s;
}

.project-link:hover {
  background: #27272a;
  color: #e4e4e7;
  border-color: #3b82f6;
}

.project-name {
  font-size: 1.1rem;
  color: #e4e4e7;
  font-weight: 600;
  margin-bottom: 10px;
}

.project-desc {
  font-size: 0.85rem;
  color: #a1a1aa;
  line-height: 1.6;
  margin-bottom: 16px;
}

.project-stack {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-bottom: 16px;
}

.stack-tag {
  padding: 3px 10px;
  background: #27272a;
  border: 1px solid #2a2a30;
  border-radius: 4px;
  font-size: 0.75rem;
  color: #71717a;
}

/* 兴趣与价值观 */
.interests-section {
  padding: 80px 0;
}

.interests-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 60px;
}

.interest-card {
  background: #1e1e24;
  border: 1px solid #2a2a30;
  border-radius: 10px;
  padding: 28px;
  text-align: center;
  transition: all 0.15s;
}

.interest-card:hover {
  border-color: #3f3f46;
}

.interest-icon {
  font-size: 2.5rem;
  margin-bottom: 16px;
}

.interest-name {
  font-size: 1.1rem;
  color: #e4e4e7;
  font-weight: 600;
  margin-bottom: 10px;
}

.interest-desc {
  font-size: 0.85rem;
  color: #a1a1aa;
  line-height: 1.6;
}

.values-quote {
  max-width: 700px;
  margin: 0 auto;
  text-align: center;
}

.values-quote blockquote {
  font-size: 1.1rem;
  color: #d4d4d8;
  line-height: 1.8;
  font-style: italic;
  padding: 24px;
  border-left: 3px solid #3b82f6;
  background: #1e1e24;
  border-radius: 0 10px 10px 0;
}

/* 联系方式 */
.contact-section {
  padding: 80px 0 120px;
}

.contact-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 20px;
  max-width: 800px;
  margin: 0 auto;
}

.contact-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 20px;
  background: #1e1e24;
  border: 1px solid #2a2a30;
  border-radius: 10px;
  transition: all 0.15s;
}

.contact-item:hover {
  border-color: #3f3f46;
}

.contact-icon {
  font-size: 1.8rem;
  width: 48px;
  height: 48px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #27272a;
  border-radius: 10px;
}

.contact-label {
  font-size: 0.8rem;
  color: #71717a;
  margin-bottom: 4px;
}

.contact-value {
  font-size: 0.95rem;
  color: #e4e4e7;
  font-weight: 500;
}

/* 响应式设计 */
@media (max-width: 768px) {
  .timeline::before {
    left: 20px;
  }

  .timeline-item,
  .timeline-item.timeline-right {
    width: 100%;
    margin-left: 0;
    padding-left: 56px;
    padding-right: 0;
  }

  .timeline-item:not(.timeline-right) .timeline-badge,
  .timeline-item.timeline-right .timeline-badge {
    left: 0;
    right: auto;
  }

  .skills-grid,
  .projects-grid,
  .interests-grid {
    grid-template-columns: 1fr;
  }

  .contact-grid {
    grid-template-columns: 1fr;
  }

  .social-links {
    flex-direction: column;
    align-items: center;
  }

  .social-link {
    width: 200px;
    justify-content: center;
  }
}

@media (max-width: 480px) {
  .container {
    padding: 0 16px;
  }

  .avatar {
    width: 100px;
    height: 100px;
    font-size: 3rem;
  }

  .section-title {
    margin-bottom: 32px;
  }
}
</style>
