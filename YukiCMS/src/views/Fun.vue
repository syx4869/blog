<template>
  <div class="resources-page">
    <div class="container">
      <h1 class="page-title">资源推荐</h1>
      <p class="page-desc">精选前端开发资源，助力你的技术成长</p>

      <div class="filter-bar">
        <button
          v-for="cat in categories"
          :key="cat.key"
          :class="['filter-btn', { active: activeCategory === cat.key }]"
          @click="activeCategory = cat.key"
        >
          <span class="filter-icon">{{ cat.icon }}</span>
          <span>{{ cat.name }}</span>
        </button>
      </div>

      <div class="resources-grid">
        <div
          v-for="item in filteredResources"
          :key="item.name"
          class="resource-card"
        >
          <div class="card-header">
            <div class="resource-icon">{{ item.icon }}</div>
            <div class="resource-tags">
              <span class="tag" :class="item.type">{{ typeLabel(item.type) }}</span>
            </div>
          </div>
          <h3 class="resource-name">{{ item.name }}</h3>
          <p class="resource-desc">{{ item.description }}</p>
          <div class="resource-footer">
            <div class="resource-meta">
              <span v-if="item.level" class="meta-item" :class="'level-' + item.level">
                {{ levelLabel(item.level) }}
              </span>
              <span v-if="item.category" class="meta-item">🏷️ {{ item.category }}</span>
            </div>
            <a :href="item.url" class="resource-link" target="_blank" rel="noopener">
              访问 →
            </a>
          </div>
        </div>
      </div>

      <div class="page-footer">
        <p> 持续更新中，欢迎推荐更多优质资源</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { resourceApi } from '../services/api'

const activeCategory = ref('all')
const categories = ref([])
const resources = ref([])
const loading = ref(true)

const filteredResources = computed(() => {
  if (activeCategory.value === 'all') return resources.value
  return resources.value.filter(r => r.type === activeCategory.value)
})

const typeLabels = ref({})
const levelLabels = ref({})

function typeLabel(type) {
  return typeLabels.value[type] || type
}

function levelLabel(level) {
  return levelLabels.value[level] || level
}

async function loadData() {
  loading.value = true
  try {
    const [categoriesRes, resourcesRes] = await Promise.all([
      resourceApi.getCategories(),
      resourceApi.getList()
    ])

    if (categoriesRes.code === 200 && categoriesRes.data) {
      categories.value = categoriesRes.data
      // 动态构建类型标签映射
      const tLabels = {}
      categoriesRes.data.forEach(cat => {
        if (cat.key && cat.key !== 'all') {
          tLabels[cat.key] = cat.name
        }
      })
      typeLabels.value = tLabels
    }

    if (resourcesRes.code === 200 && resourcesRes.data) {
      resources.value = resourcesRes.data
      // 动态构建难度标签映射
      const lLabels = {}
      resourcesRes.data.forEach(r => {
        if (r.level) {
          const levelMap = {
            beginner: '入门',
            intermediate: '进阶',
            advanced: '高级'
          }
          lLabels[r.level] = levelMap[r.level] || r.level
        }
      })
      levelLabels.value = lLabels
    }
  } catch (error) {
    console.error('加载资源数据失败:', error)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.resources-page {
  width: 100%;
  min-height: 100vh;
  padding-top: 80px;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
}

.page-title {
  font-size: clamp(1.8rem, 4vw, 2.5rem);
  color: #e4e4e7;
  font-weight: 700;
  margin-bottom: 8px;
  text-align: center;
}

.page-desc {
  color: #000000;
  text-align: center;
  margin-bottom: 36px;
  font-size: 1rem;
}

.filter-bar {
  display: flex;
  gap: 10px;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 36px;
}

.filter-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 18px;
  background: #1e1e24;
  border: 1px solid #2a2a30;
  border-radius: 8px;
  color: #a1a1aa;
  cursor: pointer;
  transition: all 0.15s;
  font-size: 0.85rem;
  font-family: inherit;
}

.filter-btn:hover {
  background: #27272a;
  color: #e4e4e7;
}

.filter-btn.active {
  background: #3b82f6;
  border-color: #3b82f6;
  color: white;
}

.filter-icon {
  font-size: 1.1rem;
}

.resources-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 20px;
}

.resource-card {
  background: #1e1e24;
  border: 1px solid #2a2a30;
  border-radius: 10px;
  padding: 22px;
  transition: all 0.15s;
}

.resource-card:hover {
  border-color: #3f3f46;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 14px;
}

.resource-icon {
  font-size: 2rem;
}

.resource-tags {
  display: flex;
  gap: 6px;
}

.tag {
  padding: 3px 10px;
  border-radius: 4px;
  font-size: 0.72rem;
  font-weight: 500;
}

.tag.tutorial { background: #1e3a5f; color: #60a5fa; }
.tag.tool { background: #3b1f5e; color: #c084fc; }
.tag.framework { background: #1a3d2e; color: #4ade80; }
.tag.community { background: #4a2d1a; color: #fb923c; }
.tag.design { background: #4a1a2d; color: #f472b6; }

.resource-name {
  font-size: 1.05rem;
  color: #e4e4e7;
  font-weight: 600;
  margin-bottom: 8px;
}

.resource-desc {
  font-size: 0.85rem;
  color: #a1a1aa;
  line-height: 1.6;
  margin-bottom: 18px;
}

.resource-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.resource-meta {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.meta-item {
  font-size: 0.75rem;
  color: #52525b;
}

.meta-item.level-beginner { color: #4ade80; }
.meta-item.level-intermediate { color: #facc15; }
.meta-item.level-advanced { color: #f87171; }

.resource-link {
  padding: 6px 14px;
  background: transparent;
  border: 1px solid #2a2a30;
  border-radius: 6px;
  color: #3b82f6;
  text-decoration: none;
  font-size: 0.8rem;
  transition: all 0.15s;
}

.resource-link:hover {
  background: #27272a;
  border-color: #3b82f6;
}

.page-footer {
  text-align: center;
  padding: 48px 0 80px;
  color: #000000;
  font-size: 0.9rem;
}

@media (max-width: 768px) {
  .resources-grid {
    grid-template-columns: 1fr;
  }

  .filter-bar {
    justify-content: flex-start;
    overflow-x: auto;
    flex-wrap: nowrap;
    padding-bottom: 8px;
  }

  .filter-btn {
    white-space: nowrap;
    flex-shrink: 0;
  }
}

@media (max-width: 480px) {
  .container {
    padding: 0 16px;
  }
}
</style>
