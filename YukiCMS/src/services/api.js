// ==================== 配置 ====================
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://8.138.148.245/api'
const REQUEST_TIMEOUT = 15000

// ==================== 通用请求函数 ====================
async function request(url, options = {}) {
  const controller = new AbortController()
  const timeoutId = setTimeout(() => controller.abort(), REQUEST_TIMEOUT)

  try {
    const response = await fetch(`${API_BASE_URL}${url}`, {
      ...options,
      signal: controller.signal,
      headers: {
        'Content-Type': 'application/json',
        ...options.headers
      }
    })

    clearTimeout(timeoutId)

    const result = await response.json()
    return result
  } catch (error) {
    clearTimeout(timeoutId)
    console.error('API 请求失败:', error)
    throw error
  }
}

// GET 请求
function get(url, params = {}) {
  const queryString = new URLSearchParams(params).toString()
  const fullUrl = queryString ? `${url}?${queryString}` : url
  return request(fullUrl, { method: 'GET' })
}

// POST 请求
function post(url, data = {}) {
  return request(url, {
    method: 'POST',
    body: JSON.stringify(data)
  })
}

// ==================== 统一响应格式 ====================
// 所有 PHP 接口都应该返回以下格式：
// {
//   "code": 200,              // 状态码：200成功，其他失败
//   "message": "success",      // 提示信息
//   "data": { ... }            // 数据
// }

// ==================== 1. 首页相关 API ====================
export const homeApi = {
  /**
   * 获取首页欢迎文字
   * GET /api/home/welcome
   * 
   * PHP 返回示例：
   * {
   *   "code": 200,
   *   "message": "success",
   *   "data": {
   *     "texts": [
   *       "欢迎来到我的博客✨",
   *       "这里有好玩的功能🎮",
   *       "一起探索吧🚀"
   *     ]
   *   }
   * }
   */
  getWelcomeTexts: async () => {
    try {
      return await get('/home/welcome.php')
    } catch (error) {
      console.error('获取欢迎文字失败:', error)
      return {
        code: 200,
        message: 'success',
        data: {
          texts: []
        }
      }
    }
  },

  /**
   * 获取首页统计数据
   * GET /api/home/stats.php
   * 
   * PHP 返回示例：
   * {
   *   "code": 200,
   *   "message": "success",
   *   "data": {
   *     "articleCount": 42,
   *     "viewCount": 12345,
   *     "likeCount": 567,
   *     "friendCount": 23
   *   }
   * }
   */
  getStats: async () => {
    try {
      return await get('/home/stats.php')
    } catch (error) {
      console.error('获取统计数据失败:', error)
      return {
        code: 200,
        message: 'success',
        data: {
          articleCount: 0,
          viewCount: 0,
          likeCount: 0
        }
      }
    }
  }
}

// ==================== 2. 文章相关 API ====================
export const articleApi = {
  /**
   * 获取文章列表
   * GET /api/articles
   * 
   * 请求参数：
   * - page: 页码，默认 1
   * - pageSize: 每页数量，默认 10
   * - category: 分类（可选）
   * - keyword: 搜索关键词（可选）
   * 
   * PHP 返回示例：
   * {
   *   "code": 200,
   *   "message": "success",
   *   "data": {
   *     "list": [
   *       {
   *         "id": 1,
   *         "title": "Vue 3 组合式 API 入门指南",
   *         "description": "从零开始学习 Vue 3 的 Composition API...",
   *         "category": "技术",
   *         "date": "2024-01-15",
   *         "tags": ["Vue", "JavaScript", "前端"],
   *         "icon": "💻",
   *         "color": "linear-gradient(135deg, #667eea 0%, #764ba2 100%)",
   *         "viewCount": 123,
   *         "likeCount": 45
   *       }
   *     ],
   *     "total": 100,
   *     "page": 1,
   *     "pageSize": 10
   *   }
   * }
   */
  getList: async (params = {}) => {
    try {
      return await get('/articles/index.php', params)
    } catch (error) {
      console.error('获取文章列表失败:', error)
      return {
        code: 200,
        message: 'success',
        data: {
          list: [],
          total: 0,
          page: params.page || 1,
          pageSize: params.pageSize || 10
        }
      }
    }
  },

  /**
   * 获取文章详情
   * GET /api/articles/view.php?id=:id
   * 
   * PHP 返回示例：
   * {
   *   "code": 200,
   *   "message": "success",
   *   "data": {
   *     "id": 1,
   *     "title": "Vue 3 组合式 API 入门指南",
   *     "content": "文章内容...",
   *     "category": "技术",
   *     "createdAt": "2024-01-15 10:30:00",
   *     "updatedAt": "2024-01-16 15:20:00",
   *     "tags": ["Vue", "JavaScript", "前端"],
   *     "viewCount": 123,
   *     "likeCount": 45
   *   }
   * }
   */
  getDetail: async (id) => {
    try {
      return await get('/articles/view.php', { id })
    } catch (error) {
      console.error('获取文章详情失败:', error)
      return {
        code: 200,
        message: 'success',
        data: null
      }
    }
  },

  /**
   * 点赞文章
   * POST /api/articles/like.php
   * 
   * PHP 返回示例：
   * {
   *   "code": 200,
   *   "message": "点赞成功",
   *   "data": {
   *     "likeCount": 46
   *   }
   * }
   */
  like: async (id) => {
    try {
      return await post('/articles/like.php', { id })
    } catch (error) {
      console.error('点赞失败:', error)
      return {
        code: 500,
        message: '点赞失败',
        data: null
      }
    }
  },

  collect: async (id) => {
    try {
      return await post('/articles/collect.php', { id })
    } catch (error) {
      console.error('收藏失败:', error)
      return {
        code: 500,
        message: '收藏失败',
        data: null
      }
    }
  },

  create: async (data) => {
    try {
      return await post('/articles/create.php', data)
    } catch (error) {
      console.error('创建文章失败:', error)
      return { code: 500, message: '创建失败', data: null }
    }
  },

  update: async (id, data) => {
    try {
      return await request(`/articles/update.php?id=${id}`, {
        method: 'PUT',
        body: JSON.stringify(data)
      })
    } catch (error) {
      console.error('更新文章失败:', error)
      return { code: 500, message: '更新失败', data: null }
    }
  },

  delete: async (id) => {
    try {
      return await request(`/articles/delete.php?id=${id}`, { method: 'DELETE' })
    } catch (error) {
      console.error('删除文章失败:', error)
      return { code: 500, message: '删除失败', data: null }
    }
  }
}

// ==================== 关于模块 ====================
export const aboutApi = {
  /**
   * 获取关于信息
   * GET /api/about/index.php
   * 
   * PHP 返回示例：
   * {
   *   "code": 200,
   *   "message": "success",
   *   "data": {
   *     "name": "博主昵称",
   *     "bio": "一个热爱技术、喜欢折腾的人...",
   *     "avatar": "/uploads/avatar.jpg",
   *     "email": "hello@example.com",
   *     "github": "https://github.com/xxx",
   *     "links": [
   *       { "name": "GitHub", "icon": "🐙", "url": "https://github.com/xxx" },
   *       { "name": "邮箱", "icon": "📧", "url": "mailto:hello@example.com" }
   *     ]
   *   }
   * }
   */
  getInfo: async () => {
    try {
      return await get('/about/index.php')
    } catch (error) {
      console.error('获取关于信息失败:', error)
      return {
        code: 200,
        message: 'success',
        data: null
      }
    }
  }
}

// ==================== 5. 分类相关 API ====================
export const categoryApi = {
  /**
   * 获取分类列表
   * GET /api/categories/index.php
   * 
   * PHP 返回示例：
   * {
   *   "code": 200,
   *   "message": "success",
   *   "data": [
   *     { "id": 1, "name": "技术", "count": 20 },
   *     { "id": 2, "name": "生活", "count": 15 }
   *   ]
   * }
   */
  getList: async () => {
    try {
      return await get('/categories/index.php')
    } catch (error) {
      console.error('获取分类列表失败:', error)
      return {
        code: 200,
        message: 'success',
        data: []
      }
    }
  }
}

// ==================== 6. 标签相关 API ====================
export const tagApi = {
  /**
   * 获取标签列表
   * GET /api/tags/index.php
   * 
   * PHP 返回示例：
   * {
   *   "code": 200,
   *   "message": "success",
   *   "data": [
   *     { "id": 1, "name": "Vue", "count": 10 },
   *     { "id": 2, "name": "JavaScript", "count": 15 }
   *   ]
   * }
   */
  getList: async () => {
    try {
      return await get('/tags/index.php')
    } catch (error) {
      console.error('获取标签列表失败:', error)
      return {
        code: 200,
        message: 'success',
        data: []
      }
    }
  }
}

// ==================== 7. 每日壁纸 API ====================
export const wallpaperApi = {
  /**
   * 获取每日壁纸
   * GET /api/wallpapers/daily.php
   * 
   * PHP 返回示例：
   * {
   *   "code": 200,
   *   "message": "success",
   *   "data": {
   *     "id": 1,
   *     "title": "每日壁纸",
   *     "url": "http://.../wallpaper.jpg",
   *     "width": 1920,
   *     "height": 1080,
   *     "date": "2024-01-01"
   *   }
   * }
   */
  getDaily: async () => {
    try {
      return await get('/wallpapers/daily.php')
    } catch (error) {
      console.error('获取每日壁纸失败:', error)
      return {
        code: 200,
        message: 'success',
        data: null
      }
    }
  }
}

// ==================== 8. 资源推荐 API ====================
export const resourceApi = {
  /**
   * 获取资源列表
   * GET /api/resources/index.php?type=tutorial
   * 
   * PHP 返回示例：
   * {
   *   "code": 200,
   *   "message": "success",
   *   "data": [
   *     {
   *       "id": 1,
   *       "name": "Vue.js 官方文档",
   *       "description": "全面系统的 Vue 3 学习文档...",
   *       "url": "https://cn.vuejs.org/",
   *       "icon": "",
   *       "type": "tutorial",
   *       "category": "前端基础",
   *       "level": "beginner"
   *     }
   *   ]
   * }
   */
  getList: async (params = {}) => {
    try {
      return await get('/resources/index.php', params)
    } catch (error) {
      console.error('获取资源列表失败:', error)
      return {
        code: 200,
        message: 'success',
        data: []
      }
    }
  },

  /**
   * 获取资源分类列表
   * GET /api/resource_categories/index.php
   * 
   * PHP 返回示例：
   * {
   *   "code": 200,
   *   "message": "success",
   *   "data": [
   *     { "key": "all", "name": "全部", "icon": "" },
   *     { "key": "tutorial", "name": "教程", "icon": "" }
   *   ]
   * }
   */
  getCategories: async () => {
    try {
      return await get('/resource_categories/index.php')
    } catch (error) {
      console.error('获取资源分类失败:', error)
      return {
        code: 200,
        message: 'success',
        data: []
      }
    }
  }
}

// ==================== 9. 评论 API ====================
export const commentApi = {
  /**
   * 获取评论列表
   * GET /api/comments/index.php?article_id=1
   */
  getList: async (articleId) => {
    try {
      return await get('/comments/index.php', { article_id: articleId })
    } catch (error) {
      console.error('获取评论列表失败:', error)
      return { code: 200, message: 'success', data: [] }
    }
  },

  /**
   * 发表评论
   * POST /api/comments/create.php
   */
  create: async (data) => {
    try {
      return await post('/comments/create.php', data)
    } catch (error) {
      console.error('发表评论失败:', error)
      return { code: 500, message: '发表评论失败' }
    }
  },

  /**
   * 评论点赞
   * POST /api/comments/like.php
   */
  like: async (commentId) => {
    try {
      return await post('/comments/like.php', { comment_id: commentId })
    } catch (error) {
      console.error('评论点赞失败:', error)
      return { code: 500, message: '点赞失败' }
    }
  }
}

export default {
  homeApi,
  articleApi,
  aboutApi,
  categoryApi,
  tagApi,
  wallpaperApi,
  resourceApi,
  commentApi
}
