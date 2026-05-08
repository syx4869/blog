<template>
  <div class="nav-container">
    <div class="liquid-glass-wrapper" ref="wrapperRef" :style="wrapperStyle">
      <div class="tabs" ref="tabsRef">
        <div class="pill" :style="pillStyle"></div>
        <div
          v-for="(tab, index) in tabItems"
          :key="tab.name"
          :class="['tab', { active: currentRoute === tab.route }]"
          @click="navigateTo(tab.route)"
        >
          {{ tab.name }}
        </div>
      </div>
    </div>
    <svg class="liquid-filter" xmlns="http://www.w3.org/2000/svg" width="0" height="0">
      <defs>
        <filter :id="filterId" filterUnits="userSpaceOnUse" color-interpolation-filters="sRGB">
          <feImage :id="mapId" />
          <feDisplacementMap
            in="SourceGraphic"
            :in2="mapId"
            xChannelSelector="R"
            yChannelSelector="G"
            :scale="displacementScale"
          />
        </filter>
      </defs>
    </svg>
    <canvas ref="canvasRef" style="display: none;"></canvas>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'

const router = useRouter()
const route = useRoute()

const tabItems = ref([
  { name: '首页', route: '/' },
  { name: '文章', route: '/articles' },
  { name: '资源', route: '/fun' },
  { name: '关于', route: '/about' }
])

const currentRoute = ref('/')
const tabsRef = ref(null)
const wrapperRef = ref(null)
const canvasRef = ref(null)
const pillLeft = ref(0)
const pillWidth = ref(0)

const filterId = 'liquid-filter-' + Math.random().toString(36).substr(2, 9)
const mapId = filterId + '-map'
const displacementScale = ref(30)

let canvasCtx = null
const canvasDPI = 1

const pillStyle = computed(() => ({
  left: pillLeft.value + 'px',
  width: pillWidth.value + 'px'
}))

const wrapperStyle = computed(() => ({
  backdropFilter: `url(#${filterId}) blur(0.25px) brightness(1.5) saturate(1.1)`,
  WebkitBackdropFilter: `url(#${filterId}) blur(0.25px) brightness(1.5) saturate(1.1)`
}))

function smoothStep(a, b, t) {
  t = Math.max(0, Math.min(1, (t - a) / (b - a)))
  return t * t * (3 - 2 * t)
}

function length(x, y) {
  return Math.sqrt(x * x + y * y)
}

function roundedRectSDF(x, y, width, height, radius) {
  const qx = Math.abs(x) - width + radius
  const qy = Math.abs(y) - height + radius
  return Math.min(Math.max(qx, qy), 0) + length(Math.max(qx, 0), Math.max(qy, 0)) - radius
}

function updatePill() {
  if (!tabsRef.value) return
  const activeIndex = tabItems.value.findIndex(tab => tab.route === currentRoute.value)
  const activeTab = tabsRef.value.children[activeIndex + 1]
  if (activeTab) {
    pillLeft.value = activeTab.offsetLeft
    pillWidth.value = activeTab.offsetWidth
  }
}

function navigateTo(routePath) {
  currentRoute.value = routePath
  router.push(routePath)
  updatePill()
}

function updateRouteFromPath() {
  currentRoute.value = route.path
  updatePill()
}

function updateShader() {
  if (!canvasRef.value || !canvasCtx) return

  const canvas = canvasRef.value
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
  displacementScale.value = maxScale / canvasDPI

  let index = 0
  for (let i = 0; i < data.length; i += 4) {
    const r = rawValues[index++] / maxScale + 0.5
    const g = rawValues[index++] / maxScale + 0.5
    data[i] = r * 255
    data[i + 1] = g * 255
    data[i + 2] = 0
    data[i + 3] = 255
  }

  canvasCtx.putImageData(new ImageData(data, w, h), 0, 0)

  const feImage = document.getElementById(mapId)
  if (feImage) {
    feImage.setAttributeNS('http://www.w3.org/1999/xlink', 'href', canvas.toDataURL())
  }
}

function initLiquidGlass() {
  if (!canvasRef.value || !wrapperRef.value) return

  const rect = wrapperRef.value.getBoundingClientRect()
  const canvasWidth = rect.width || 600
  const canvasHeight = rect.height || 100
  canvasRef.value.width = canvasWidth * canvasDPI
  canvasRef.value.height = canvasHeight * canvasDPI
  canvasCtx = canvasRef.value.getContext('2d')

  const filter = document.getElementById(filterId)
  if (filter) {
    filter.setAttribute('x', '0')
    filter.setAttribute('y', '0')
    filter.setAttribute('width', canvasRef.value.width.toString())
    filter.setAttribute('height', canvasRef.value.height.toString())
  }

  const feImage = document.getElementById(mapId)
  if (feImage) {
    feImage.setAttribute('width', canvasRef.value.width.toString())
    feImage.setAttribute('height', canvasRef.value.height.toString())
  }

  updateShader()
}

function handleResize() {
  updatePill()
  initLiquidGlass()
}

let unwatchRoute = null

onMounted(async () => {
  await nextTick()
  updateRouteFromPath()
  updatePill()

  initLiquidGlass()

  unwatchRoute = watch(() => route.path, () => {
    updateRouteFromPath()
  })

  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  if (unwatchRoute) {
    unwatchRoute()
  }
  window.removeEventListener('resize', handleResize)
})
</script>

<style scoped>
.nav-container {
  position: relative;
  display: flex;
  justify-content: center;
  padding: 20px;
  z-index: 10;
}

.liquid-glass-wrapper {
  position: relative;
  border-radius: 150px;
  overflow: hidden;
  box-shadow:
    0 4px 8px rgba(0, 0, 0, 0.25),
    0 -10px 25px inset rgba(0, 0, 0, 0.15),
    0 -1px 4px 1px inset rgba(255, 255, 255, 0.74);
}

.tabs {
  position: relative;
  display: flex;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 150px;
  padding: 8px;
  gap: 4px;
}

.pill {
  position: absolute;
  top: 8px;
  height: calc(100% - 16px);
  background: rgba(255, 255, 255, 0.95);
  border-radius: 150px;
  box-shadow:
    0 2px 8px rgba(0, 0, 0, 0.15),
    0 0 0 1px rgba(255, 255, 255, 0.5);
  transition:
    left 0.5s cubic-bezier(0.34, 1.56, 0.64, 1),
    width 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
  pointer-events: none;
  z-index: 1;
}

.tab {
  position: relative;
  z-index: 2;
  padding: 12px 28px;
  font-size: 15px;
  font-weight: 500;
  letter-spacing: 0.05em;
  cursor: pointer;
  border-radius: 150px;
  white-space: nowrap;
  transition: all 0.3s ease;
  user-select: none;
  background: linear-gradient(
    90deg,
    #ff6b6b,
    #ffa500,
    #ffd93d,
    #6bcb77,
    #4d96ff,
    #9b59b6,
    #ff6b6b
  );
  background-size: 300% 100%;
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  animation: rainbowFlow 4s linear infinite;
}

.tab.active {
  background: linear-gradient(
    90deg,
    #667eea,
    #764ba2,
    #f093fb,
    #4facfe,
    #667eea
  );
  background-size: 300% 100%;
  -webkit-background-clip: text;
  background-clip: text;
  -webkit-text-fill-color: transparent;
  animation: rainbowFlow 3s linear infinite;
  font-weight: 600;
}

.tab:not(.active):hover {
  animation-duration: 1.5s;
  filter: brightness(1.3);
}

@keyframes rainbowFlow {
  0% {
    background-position: 0% 50%;
  }
  100% {
    background-position: 300% 50%;
  }
}

.liquid-filter {
  position: fixed;
  top: 0;
  left: 0;
  pointer-events: none;
  z-index: 0;
}
</style>
