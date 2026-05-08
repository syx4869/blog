<script setup>
import { ref, onMounted } from 'vue'
import DokeNav from './components/DokeNav.vue'
import { wallpaperApi } from './services/api'

const wallpaperUrl = ref('')
const wallpaperLoaded = ref(false)

onMounted(async () => {
  // 始终从 API 获取最新壁纸（后台已处理每日去重逻辑）
  const result = await wallpaperApi.getDaily()
  if (result.code === 200 && result.data && result.data.url) {
    wallpaperUrl.value = result.data.url
    wallpaperLoaded.value = true
  } else {
    wallpaperLoaded.value = true
  }
})
</script>

<template>
  <div class="app-container">
    <!-- 壁纸背景层 -->
    <div class="wallpaper-layer" :class="{ loaded: wallpaperLoaded }">
      <img
        v-if="wallpaperUrl"
        :src="wallpaperUrl"
        alt="每日壁纸"
        class="wallpaper-image"
        @load="wallpaperLoaded = true"
      />
      <div class="wallpaper-overlay"></div>
    </div>

    <!-- 内容层 -->
    <DokeNav />
    <main class="main-content">
      <router-view />
    </main>
  </div>
</template>

<style scoped>
.app-container {
  width: 100%;
  min-height: 100vh;
  position: relative;
}

/* 壁纸背景层 */
.wallpaper-layer {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 0;
  opacity: 0;
  transition: opacity 1s ease;
}

.wallpaper-layer.loaded {
  opacity: 1;
}

.wallpaper-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
}

/* 暗色遮罩，确保文字可读 */
.wallpaper-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.15);
  backdrop-filter: blur(0.5px);
}

/* 内容层 */
.main-content {
  position: relative;
  z-index: 1;
  width: 100%;
  min-height: 100vh;
}
</style>
