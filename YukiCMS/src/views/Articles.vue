<template>
  <div class="articles-page">
    <svg class="liquid-svg" xmlns="http://www.w3.org/2000/svg" width="0" height="0">
      <defs>
        <filter :id="searchFilterId" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
          <feImage :id="searchMapId" />
          <feDisplacementMap
            in="SourceGraphic"
            :in2="searchMapId"
            xChannelSelector="R"
            yChannelSelector="G"
            :scale="searchDisplacementScale"
          />
        </filter>
        <filter :id="selectFilterId" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
          <feImage :id="selectMapId" />
          <feDisplacementMap
            in="SourceGraphic"
            :in2="selectMapId"
            xChannelSelector="R"
            yChannelSelector="G"
            :scale="selectDisplacementScale"
          />
        </filter>
        <filter :id="dropdownFilterId" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
          <feImage :id="dropdownMapId" />
          <feDisplacementMap
            in="SourceGraphic"
            :in2="dropdownMapId"
            xChannelSelector="R"
            yChannelSelector="G"
            :scale="dropdownDisplacementScale"
          />
        </filter>
      </defs>
    </svg>
    <canvas ref="searchCanvasRef" style="display: none;"></canvas>
    <canvas ref="selectCanvasRef" style="display: none;"></canvas>
    <canvas ref="dropdownCanvasRef" style="display: none;"></canvas>

    <div class="container">
      <div class="page-header">
        <h1 class="page-title">文章列表</h1>
        <div class="search-filter">
          <div class="liquid-glass-search" ref="searchBoxRef" :style="searchGlassStyle">
            <div class="search-box">
              <input
                v-model="searchKeyword"
                type="text"
                placeholder="搜索文章..."
                @keyup.enter="handleSearch"
              />
              <button class="search-btn" @click="handleSearch"><i class="iconfont icon-search"></i></button>
            </div>
          </div>
          <div class="custom-select" ref="selectRef">
            <div class="liquid-glass-select" ref="selectBoxRef" :style="selectGlassStyle">
              <div class="select-trigger" @click="toggleDropdown">
                <span class="select-value">{{ selectedCategoryName }}</span>
                <span class="select-arrow" :class="{ open: isDropdownOpen }">▼</span>
              </div>
            </div>
            <div class="select-dropdown" v-show="isDropdownOpen" ref="dropdownRef" :style="dropdownGlassStyle">
              <div
                class="select-option"
                :class="{ active: selectedCategory === '' }"
                @click="selectCategory('')"
              >
                全部分类
              </div>
              <div
                class="select-option"
                v-for="cat in categories"
                :key="cat.id"
                :class="{ active: selectedCategory === cat.id.toString() }"
                @click="selectCategory(cat.id.toString())"
              >
                {{ cat.name }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="articles-grid" v-if="!loading && articles.length > 0">
        <article
          class="article-card"
          v-for="article in articles"
          :key="article.id"
          @click="goToDetail(article.id)"
        >
          <div class="article-image" :style="{ backgroundImage: article.cover_image ? `url(${article.cover_image})` : article.color || 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)' }">
            <div v-if="!article.cover_image" class="article-icon"><i class="iconfont icon-bianjiwenjian"></i></div>
          </div>
          <div class="article-content">
            <div class="article-meta">
              <span class="article-category">{{ article.category }}</span>
              <span class="article-date">{{ article.date }}</span>
              <span class="article-views"><i class="iconfont icon-icon-"></i> {{ article.viewCount || 0 }}</span>
            </div>
            <h3 class="article-title">{{ article.title }}</h3>
            <p class="article-desc">{{ article.description }}</p>
            <div class="article-tags">
              <span class="tag" v-for="tag in article.tags" :key="tag">{{ tag }}</span>
            </div>
          </div>
        </article>
      </div>

      <div class="empty-state" v-if="!loading && articles.length === 0">
        <div class="empty-icon">📭</div>
        <p class="empty-text">暂无文章</p>
      </div>

      <div class="loading-state" v-if="loading">
        <div class="loading-spinner"></div>
        <p>加载中...</p>
      </div>

      <div class="pagination" v-if="!loading && totalPages > 1">
        <button
          class="page-btn"
          :disabled="currentPage === 1"
          @click="goToPage(currentPage - 1)"
        >
          上一页
        </button>
        <span class="page-info">{{ currentPage }} / {{ totalPages }}</span>
        <button
          class="page-btn"
          :disabled="currentPage === totalPages"
          @click="goToPage(currentPage + 1)"
        >
          下一页
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { articleApi, categoryApi } from '../services/api'

const router = useRouter()

const articles = ref([])
const categories = ref([])
const loading = ref(false)
const currentPage = ref(1)
const pageSize = ref(10)
const totalPages = ref(1)
const total = ref(0)
const searchKeyword = ref('')
const selectedCategory = ref('')
const isDropdownOpen = ref(false)
const selectRef = ref(null)

const searchBoxRef = ref(null)
const selectBoxRef = ref(null)
const dropdownRef = ref(null)
const searchCanvasRef = ref(null)
const selectCanvasRef = ref(null)
const dropdownCanvasRef = ref(null)

const searchFilterId = 'lg-search-' + Math.random().toString(36).substr(2, 9)
const searchMapId = searchFilterId + '-map'
const searchDisplacementScale = ref(30)

const selectFilterId = 'lg-select-' + Math.random().toString(36).substr(2, 9)
const selectMapId = selectFilterId + '-map'
const selectDisplacementScale = ref(30)

const dropdownFilterId = 'lg-dropdown-' + Math.random().toString(36).substr(2, 9)
const dropdownMapId = dropdownFilterId + '-map'
const dropdownDisplacementScale = ref(30)

let searchCanvasCtx = null
let selectCanvasCtx = null
let dropdownCanvasCtx = null
let searchAnimId = null
let selectAnimId = null
const canvasDPI = 1

const selectedCategoryName = computed(() => {
  if (!selectedCategory.value) return '全部分类'
  const cat = categories.value.find(c => c.id.toString() === selectedCategory.value)
  return cat ? cat.name : '全部分类'
})

const searchGlassStyle = computed(() => ({
  backdropFilter: `url(#${searchFilterId}) blur(0.25px) brightness(1.5) saturate(1.1)`,
  WebkitBackdropFilter: `url(#${searchFilterId}) blur(0.25px) brightness(1.5) saturate(1.1)`
}))

const selectGlassStyle = computed(() => ({
  backdropFilter: `url(#${selectFilterId}) blur(0.25px) brightness(1.5) saturate(1.1)`,
  WebkitBackdropFilter: `url(#${selectFilterId}) blur(0.25px) brightness(1.5) saturate(1.1)`
}))

const dropdownGlassStyle = computed(() => ({
  backdropFilter: `url(#${dropdownFilterId}) blur(0.25px) brightness(1.5) saturate(1.1)`,
  WebkitBackdropFilter: `url(#${dropdownFilterId}) blur(0.25px) brightness(1.5) saturate(1.1)`
}))

function smoothStep(a, b, t) {
  t = Math.max(0, Math.min(1, (t - a) / (b - a)))
  return t * t * (3 - 2 * t)
}

function len(x, y) {
  return Math.sqrt(x * x + y * y)
}

function roundedRectSDF(x, y, width, height, radius) {
  const qx = Math.abs(x) - width + radius
  const qy = Math.abs(y) - height + radius
  return Math.min(Math.max(qx, qy), 0) + len(Math.max(qx, 0), Math.max(qy, 0)) - radius
}

function updateShader(canvasRef, ctx, filterId, mapId, scaleRef, wrapperRef) {
  if (!canvasRef || !ctx || !wrapperRef) return

  const canvas = canvasRef
  const w = canvas.width
  const h = canvas.height
  const data = new Uint8ClampedArray(w * h * 4)

  let maxScale = 0
  const rawValues = []

  for (let i = 0; i < data.length; i += 4) {
    const x = (i / 4) % w
    const y = Math.floor(i / 4 / w)
    const uv = { x: x / w, y: y / h }

    const ix = uv.x - 0.5
    const iy = uv.y - 0.5
    const distanceToEdge = roundedRectSDF(ix, iy, 0.3, 0.2, 0.6)
    const displacement = smoothStep(0.8, 0, distanceToEdge - 0.15)
    const scaled = smoothStep(0, 1, displacement)

    const posX = ix * scaled + 0.5
    const posY = iy * scaled + 0.5

    const dx = posX * w - x
    const dy = posY * h - y
    maxScale = Math.max(maxScale, Math.abs(dx), Math.abs(dy))
    rawValues.push(dx, dy)
  }

  maxScale *= 0.5
  scaleRef.value = maxScale / canvasDPI

  let index = 0
  for (let i = 0; i < data.length; i += 4) {
    const r = rawValues[index++] / maxScale + 0.5
    const g = rawValues[index++] / maxScale + 0.5
    data[i] = r * 255
    data[i + 1] = g * 255
    data[i + 2] = 0
    data[i + 3] = 255
  }

  ctx.putImageData(new ImageData(data, w, h), 0, 0)

  const feImage = document.getElementById(mapId)
  if (feImage) {
    feImage.setAttributeNS('http://www.w3.org/1999/xlink', 'href', canvas.toDataURL())
  }
}

function initLiquidGlass() {
  if (searchBoxRef.value && searchCanvasRef.value) {
    const rect = searchBoxRef.value.getBoundingClientRect()
    const cw = rect.width || 400
    const ch = rect.height || 50
    searchCanvasRef.value.width = cw * canvasDPI
    searchCanvasRef.value.height = ch * canvasDPI
    searchCanvasCtx = searchCanvasRef.value.getContext('2d')

    const filter = document.getElementById(searchFilterId)
    if (filter) {
      filter.setAttribute('x', '0')
      filter.setAttribute('y', '0')
      filter.setAttribute('width', searchCanvasRef.value.width.toString())
      filter.setAttribute('height', searchCanvasRef.value.height.toString())
    }
    const feImg = document.getElementById(searchMapId)
    if (feImg) {
      feImg.setAttribute('width', searchCanvasRef.value.width.toString())
      feImg.setAttribute('height', searchCanvasRef.value.height.toString())
    }

    updateShader(searchCanvasRef.value, searchCanvasCtx, searchFilterId, searchMapId, searchDisplacementScale, searchBoxRef.value)
  }

  if (selectBoxRef.value && selectCanvasRef.value) {
    const rect = selectBoxRef.value.getBoundingClientRect()
    const cw = rect.width || 180
    const ch = rect.height || 50
    selectCanvasRef.value.width = cw * canvasDPI
    selectCanvasRef.value.height = ch * canvasDPI
    selectCanvasCtx = selectCanvasRef.value.getContext('2d')

    const filter = document.getElementById(selectFilterId)
    if (filter) {
      filter.setAttribute('x', '0')
      filter.setAttribute('y', '0')
      filter.setAttribute('width', selectCanvasRef.value.width.toString())
      filter.setAttribute('height', selectCanvasRef.value.height.toString())
    }
    const feImg = document.getElementById(selectMapId)
    if (feImg) {
      feImg.setAttribute('width', selectCanvasRef.value.width.toString())
      feImg.setAttribute('height', selectCanvasRef.value.height.toString())
    }

    updateShader(selectCanvasRef.value, selectCanvasCtx, selectFilterId, selectMapId, selectDisplacementScale, selectBoxRef.value)
  }
}

function initDropdownLiquidGlass() {
  if (!dropdownRef.value || !dropdownCanvasRef.value) return

  nextTick(() => {
    const rect = dropdownRef.value.getBoundingClientRect()
    const cw = rect.width || 180
    const ch = rect.height || 200
    dropdownCanvasRef.value.width = cw * canvasDPI
    dropdownCanvasRef.value.height = ch * canvasDPI
    dropdownCanvasCtx = dropdownCanvasRef.value.getContext('2d')

    const filter = document.getElementById(dropdownFilterId)
    if (filter) {
      filter.setAttribute('x', '0')
      filter.setAttribute('y', '0')
      filter.setAttribute('width', dropdownCanvasRef.value.width.toString())
      filter.setAttribute('height', dropdownCanvasRef.value.height.toString())
    }
    const feImg = document.getElementById(dropdownMapId)
    if (feImg) {
      feImg.setAttribute('width', dropdownCanvasRef.value.width.toString())
      feImg.setAttribute('height', dropdownCanvasRef.value.height.toString())
    }

    updateShader(dropdownCanvasRef.value, dropdownCanvasCtx, dropdownFilterId, dropdownMapId, dropdownDisplacementScale, dropdownRef.value)
  })
}

function toggleDropdown() {
  isDropdownOpen.value = !isDropdownOpen.value
  if (isDropdownOpen.value) {
    initDropdownLiquidGlass()
  }
}

function selectCategory(id) {
  selectedCategory.value = id
  isDropdownOpen.value = false
  handleCategoryChange()
}

function handleClickOutside(event) {
  if (selectRef.value && !selectRef.value.contains(event.target)) {
    isDropdownOpen.value = false
  }
}

onMounted(async () => {
  await loadCategories()
  await loadArticles()
  document.addEventListener('click', handleClickOutside)

  await nextTick()
  initLiquidGlass()
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
  if (searchAnimId) cancelAnimationFrame(searchAnimId)
  if (selectAnimId) cancelAnimationFrame(selectAnimId)
})

async function loadCategories() {
  try {
    const result = await categoryApi.getList()
    if (result.code === 200 && result.data) {
      categories.value = result.data
    }
  } catch (error) {
    console.error('加载分类失败:', error)
  }
}

async function loadArticles() {
  loading.value = true
  try {
    const params = {
      page: currentPage.value,
      pageSize: pageSize.value
    }
    if (searchKeyword.value) {
      params.keyword = searchKeyword.value
    }
    if (selectedCategory.value) {
      params.category = selectedCategory.value
    }

    const result = await articleApi.getList(params)
    if (result.code === 200 && result.data) {
      articles.value = result.data.list || []
      total.value = result.data.total || 0
      totalPages.value = Math.ceil(total.value / pageSize.value) || 1
    }
  } catch (error) {
    console.error('加载文章列表失败:', error)
  } finally {
    loading.value = false
  }
}

function handleSearch() {
  currentPage.value = 1
  loadArticles()
}

function handleCategoryChange() {
  currentPage.value = 1
  loadArticles()
}

function goToPage(page) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  loadArticles()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function goToDetail(id) {
  router.push(`/articles/${id}`)
}
</script>

<style scoped>
.articles-page {
  width: 100%;
  min-height: 100vh;
  padding: 100px 0 60px;
  position: relative;
}

.liquid-svg {
  position: fixed;
  top: 0;
  left: 0;
  pointer-events: none;
  z-index: 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

.page-header {
  margin-bottom: 40px;
}

.page-title {
  font-size: clamp(2rem, 4vw, 3rem);
  color: white;
  margin-bottom: 20px;
  text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
}

.search-filter {
  display: flex;
  gap: 15px;
  flex-wrap: wrap;
}

.liquid-glass-search {
  flex: 1;
  min-width: 250px;
  border-radius: 150px;
  overflow: hidden;
  box-shadow:
    0 4px 8px rgba(0, 0, 0, 0.25),
    0 -10px 25px inset rgba(0, 0, 0, 0.15),
    0 -1px 4px 1px inset rgba(255, 255, 255, 0.74);
}

.search-box {
  display: flex;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 150px;
  overflow: hidden;
}

.search-box input {
  flex: 1;
  padding: 14px 20px;
  background: transparent;
  border: none;
  color: #000000;
  font-size: 1rem;
  outline: none;
  font-family: inherit;
}

.search-box input::placeholder {
  color: #000000;
}

.search-btn {
  padding: 14px 20px;
  background: rgba(255, 255, 255, 0.15);
  border: none;
  cursor: pointer;
  font-size: 1.4rem;
  transition: background 0.3s ease;
  color: #000000;
  display: flex;
  align-items: center;
  justify-content: center;
}

.search-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

.custom-select {
  position: relative;
  min-width: 180px;
  z-index: 100;
}

.liquid-glass-select {
  border-radius: 150px;
  overflow: hidden;
  box-shadow:
    0 4px 8px rgba(0, 0, 0, 0.25),
    0 -10px 25px inset rgba(0, 0, 0, 0.15),
    0 -1px 4px 1px inset rgba(255, 255, 255, 0.74);
}

.select-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 150px;
  color: #000000;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  user-select: none;
}

.select-trigger:hover {
  background: rgba(255, 255, 255, 0.15);
}

.select-value {
  flex: 1;
}

.select-arrow {
  font-size: 0.7rem;
  transition: transform 0.3s ease;
  opacity: 0.7;
  color: #000000;
}

.select-arrow.open {
  transform: rotate(180deg);
}

.select-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  left: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  overflow: hidden;
  box-shadow:
    0 4px 8px rgba(0, 0, 0, 0.25),
    0 -10px 25px inset rgba(0, 0, 0, 0.15),
    0 -1px 4px 1px inset rgba(255, 255, 255, 0.74);
  animation: dropdownFadeIn 0.2s ease;
}

@keyframes dropdownFadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.select-option {
  padding: 12px 20px;
  color: #000000;
  cursor: pointer;
  transition: all 0.2s ease;
  user-select: none;
}

.select-option:hover {
  background: rgba(0, 0, 0, 0.06);
  color: #000000;
}

.select-option.active {
  background: rgba(102, 126, 234, 0.15);
  color: #000000;
  font-weight: 500;
}

.articles-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 30px;
  margin-bottom: 40px;
}

.article-card {
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(20px);
  border-radius: 20px;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.15);
  cursor: pointer;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.article-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.article-image {
  height: 200px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;
}

.article-icon {
  font-size: 5rem;
}

.article-content {
  padding: 25px;
}

.article-meta {
  display: flex;
  gap: 12px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.article-category {
  background: rgba(255, 255, 255, 0.2);
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  color: white;
}

.article-date,
.article-views {
  color: rgba(255, 255, 255, 0.6);
  font-size: 0.85rem;
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.article-title {
  font-size: 1.4rem;
  color: white;
  margin-bottom: 10px;
  line-height: 1.4;
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

.empty-state,
.loading-state {
  text-align: center;
  padding: 80px 20px;
}

.empty-icon {
  font-size: 5rem;
  margin-bottom: 20px;
}

.empty-text {
  font-size: 1.2rem;
  color: rgba(255, 255, 255, 0.7);
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

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
}

.page-btn {
  padding: 12px 24px;
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

.page-btn:hover:not(:disabled) {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
}

.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.page-info {
  color: rgba(255, 255, 255, 0.8);
  font-size: 1rem;
}

@media (max-width: 768px) {
  .articles-grid {
    grid-template-columns: 1fr;
  }

  .search-filter {
    flex-direction: column;
  }

  .liquid-glass-search {
    min-width: 100%;
  }

  .custom-select {
    width: 100%;
  }
}
</style>
