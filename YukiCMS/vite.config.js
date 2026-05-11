import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue()],
  build: {
    // 禁用文件名混淆，强制带哈希
    rollupOptions: {
      output: {
        // 核心：所有资源自动加唯一哈希，彻底绕开缓存
        entryFileNames: 'assets/[name]-[hash].js',
        chunkFileNames: 'assets/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          const info = assetInfo.name.split('.')
          const ext = info[info.length - 1]
          return `assets/[name]-[hash][extname]`
        },
      },
    },
  },
})
