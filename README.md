# 个人博客系统 - 完整技术文档

## 项目概述

这是一个前后端分离的个人博客系统，前端采用 Vue 3 + Vite 构建，后端使用原生 PHP + MySQL 提供 RESTful API 接口。项目特色包括每日随机壁纸背景、液态玻璃导航栏效果、富文本/HTML 双模式文章编辑器、IP 防重复的点赞/收藏系统等。

---

## 一、技术栈

### 1.1 前端技术栈

| 技术 | 版本 | 用途 |
|------|------|------|
| Vue | ^3.5.13 | 核心框架，Composition API + `<script setup>` |
| vue-router | ^4.6.4 | 路由管理，createWebHistory 模式 |
| Vite | ^6.3.0 | 构建工具 |
| @vitejs/plugin-vue | ^5.2.2 | Vite Vue 插件 |

**前端项目路径**: `d:\blog\my-blog\`

### 1.2 后端技术栈

| 技术 | 用途 |
|------|------|
| PHP | 后端语言（原生 PHP，无框架） |
| MySQL | 关系型数据库 |
| PDO | 数据库操作扩展 |
| Quill.js | 富文本编辑器（管理后台） |

**后端项目路径**: `d:\blog\backend\`

### 1.3 开发环境配置

| 配置项 | 值 |
|--------|------|
| 数据库名 | `` |
| 数据库用户 | `` |
| 数据库密码 | `` |
| 数据库主机 | `localhost` |
| 字符集 | `utf8mb4` |
| 网站域名 | `http://localhost` |
| API 基础地址 | `http://localhost/api` |
| 请求超时时间 | `15000ms` |

---

## 二、前端架构

### 2.1 项目目录结构

```
my-blog/
├── index.html                    # 入口 HTML
├── package.json                  # 项目依赖
├── vite.config.js                # Vite 配置
└── src/
    ├── main.js                   # 应用入口
    ├── App.vue                   # 根组件（壁纸背景逻辑）
    ├── style.css                 # 全局样式
    ├── components/
    │   ├── DokeNav.vue          # 液态玻璃导航栏
    │   └── Typewriter.vue       # 打字机效果组件
    ├── views/
    │   ├── Home.vue             # 首页（文章列表 + 欢迎动画）
    │   ├── Articles.vue         # 文章列表页（筛选 + 分页）
    │   ├── ArticleDetail.vue    # 文章详情页（阅读 + 评论 + 点赞/收藏）
    │   ├── About.vue            # 关于我页面
    │   └── Fun.vue              # 资源推荐页
    ├── router/
    │   └── index.js             # 路由配置
    └── services/
        └── api.js               # API 接口封装
```

### 2.2 路由配置

| 路径 | 组件 | 加载方式 | 说明 |
|------|------|----------|------|
| `/` | Home.vue | 同步加载 | 首页 |
| `/articles` | Articles.vue | 同步加载 | 文章列表页 |
| `/articles/:id` | ArticleDetail.vue | 同步加载 | 文章详情页 |
| `/fun` | Fun.vue | **懒加载** | 资源推荐页 |
| `/about` | About.vue | **懒加载** | 关于我页面 |

**路由特性**：
- `createWebHistory` 模式（无 `#`）
- 路由切换自动滚动到顶部
- 支持 `savedPosition`

### 2.3 API 封装架构

**基础配置** (`src/services/api.js`):

```javascript
const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost/api'
const REQUEST_TIMEOUT = 15000
```

**统一请求函数** `request(url, options)`:
- `fetch` + `AbortController` 超时控制
- 自动添加 `Content-Type: application/json`
- 统一返回 `{ code, message, data }` 格式

**GET/POST 封装**:
- `get(url, params)` — 自动转 query string
- `post(url, data)` — 自动 JSON.stringify

### 2.4 API 接口模块

| 模块 | 前缀 | 方法 | 后端路径 |
|------|------|------|----------|
| 首页 | `homeApi` | `getWelcomeTexts`, `getStats` | `/api/home/` |
| 文章 | `articleApi` | `getList`, `getDetail`, `like`, `collect`, `create`, `update`, `delete` | `/api/articles/` |
| 关于 | `aboutApi` | `getInfo` | `/api/about/` |
| 分类 | `categoryApi` | `getList` | `/api/categories/` |
| 标签 | `tagApi` | `getList` | `/api/tags/` |
| 壁纸 | `wallpaperApi` | `getDaily` | `/api/wallpapers/` |
| 资源 | `resourceApi` | `getList`, `getCategories` | `/api/resources/` |
| 评论 | `commentApi` | `getList`, `create`, `like` | `/api/comments/` |

### 2.5 核心组件说明

#### App.vue（根组件）
- **壁纸背景系统**：调用 `wallpaperApi.getDaily()` 获取每日壁纸
- **缓存策略**：localStorage 缓存当日壁纸 URL
- **背景遮罩**：`rgba(0, 0, 0, 0.15)` 半透明遮罩

#### DokeNav.vue（导航栏）
- **液态玻璃效果**：SVG `<filter>` + Canvas 位移贴图
- **活动指示器**：白色 pill 滑块跟随路由动画移动
- **Tab 项**：首页、文章、资源、关于

#### Typewriter.vue（打字机）
- 逐字打字 + 删除动画
- 支持多行文字循环播放
- 所有文字从 API 动态获取，组件内无默认值

---

## 三、后端架构

### 3.1 目录结构

```
backend/
├── config.php                    # 全局配置（数据库 + CORS + 响应格式）
├── db.php                        # 数据库连接工具
├── database.sql                  # 完整数据库表结构（19 张表）
├── blog.sql                      # 纯净表结构（无数据，用于初始化）
├── api/                          # API 接口目录
│   ├── about/
│   │   └── index.php            # 获取关于我完整数据
│   ├── articles/
│   │   ├── index.php            # 文章列表（分页 + 筛选）
│   │   ├── view.php             # 文章详情（浏览量 +1）
│   │   ├── create.php           # 创建文章
│   │   ├── update.php           # 更新文章
│   │   ├── delete.php           # 删除文章
│   │   ├── like.php             # 点赞（IP 防重复）
│   │   ├── collect.php          # 收藏（IP 防重复）
│   │   ├── comments.php         # 获取评论
│   │   ├── comment.php          # 发表评论
│   │   └── upload.php           # 图片/封面图上传
│   ├── categories/
│   │   └── index.php            # 分类列表
│   ├── comments/
│   │   ├── index.php            # 评论列表
│   │   ├── create.php           # 发表评论
│   │   └── like.php             # 评论点赞
│   ├── home/
│   │   ├── welcome.php          # 欢迎文字
│   │   └── stats.php            # 首页统计
│   ├── resource_categories/
│   │   └── index.php            # 资源分类列表
│   ├── resources/
│   │   ├── index.php            # 资源列表
│   │   ├── create.php           # 创建资源
│   │   ├── update.php           # 更新资源
│   │   └── delete.php           # 删除资源
│   ├── tags/
│   │   └── index.php            # 标签列表
│   └── wallpapers/
│       ├── daily.php            # 每日壁纸
│       ├── upload.php           # 壁纸上传
│       └── index.php            # 壁纸列表
├── admin/                        # 管理后台
│   ├── index.php                # 后台首页
│   ├── editor.php               # 文章编辑器（Quill + HTML 双模式）
│   ├── comments.php             # 评论管理
│   ├── wallpapers.php           # 壁纸管理
│   ├── resources.php            # 资源管理
│   └── iconfont.css             # 图标字体
└── uploads/                      # 文件上传目录
```

### 3.2 配置文件

#### config.php
```php
DB_HOST = 'localhost'
DB_NAME = ''
DB_USER = ''
DB_PASS = ''
DB_CHARSET = 'utf8mb4'
SITE_URL = 'http://localhost/'
```

**统一响应函数** `jsonResponse($code, $message, $data)`:
- `Content-Type: application/json; charset=utf-8`
- CORS 头：`Access-Control-Allow-Origin: *`
- `JSON_UNESCAPED_UNICODE` 中文不转义

### 3.3 API 接口规范

**统一返回格式**：
```json
{
  "code": 200,
  "message": "success",
  "data": { ... }
}
```

**状态码**：
| code | 含义 |
|------|------|
| 200 | 成功 |
| 400 | 参数错误 |
| 404 | 资源不存在 |
| 500 | 服务器错误 |

### 3.4 核心 API 详解

#### 文章列表 `GET /api/articles/index.php`
**参数**：`page`, `pageSize`, `category_id`, `keyword`
**返回**：`{ list: [...], total, page, pageSize }`

#### 文章详情 `GET /api/articles/view.php?id=:id`
**返回**：完整文章信息 + `category` + `tags` + `collectCount` + `isCollected`
**逻辑**：自动 `view_count + 1`，返回当前 IP 是否已收藏

#### 创建文章 `POST /api/articles/create.php`
**请求体**：`title`, `description`, `content`, `cover_image`, `category_id`, `icon`, `color`, `tags`, `is_published`
**逻辑**：事务处理，自动创建标签，插入关联记录

#### 点赞/收藏 `POST /api/articles/like.php|collect.php`
**逻辑**：基于 IP 防重复，自动更新计数，支持取消操作

#### 每日壁纸 `GET /api/wallpapers/daily.php`
**逻辑**：
1. 检查今日是否已有选定壁纸
2. 无则随机选择（7 天内未使用优先）
3. 记录到 `daily_wallpapers` 表

---

## 四、数据库设计

### 4.1 表清单（19 张表）

| 序号 | 表名 | 说明 |
|------|------|------|
| 1 | `categories` | 文章分类 |
| 2 | `tags` | 标签 |
| 3 | `articles` | 文章（含 `collect_count`） |
| 4 | `article_tags` | 文章标签关联 |
| 5 | `likes` | 文章点赞记录 |
| 6 | `collects` | 文章收藏记录 |
| 7 | `comments` | 评论 |
| 8 | `comment_likes` | 评论点赞记录 |
| 9 | `about_info` | 关于信息 |
| 10 | `about_skills` | 技能 |
| 11 | `about_timeline` | 经历 |
| 12 | `about_projects` | 项目 |
| 13 | `about_interests` | 兴趣 |
| 14 | `about_quotes` | 引言 |
| 15 | `wallpapers` | 壁纸 |
| 16 | `daily_wallpapers` | 每日壁纸记录 |
| 17 | `resource_categories` | 资源分类 |
| 18 | `resources` | 资源 |
| 19 | `welcome_texts` | 首页欢迎文字 |

### 4.2 核心表结构

#### articles（文章表）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT PK | 主键 |
| title | VARCHAR(200) | 标题 |
| description | VARCHAR(500) | 描述 |
| content | TEXT | 内容（HTML） |
| cover_image | VARCHAR(500) | 封面图 URL |
| category_id | INT | 分类 ID |
| icon | VARCHAR(50) | 图标 |
| view_count | INT | 浏览量 |
| like_count | INT | 点赞数 |
| collect_count | INT | 收藏数 |
| is_published | TINYINT | 是否发布 |
| created_at | TIMESTAMP | 创建时间 |
| updated_at | TIMESTAMP | 更新时间 |

#### collects（收藏记录表）
| 字段 | 类型 | 说明 |
|------|------|------|
| id | INT PK | 主键 |
| article_id | INT | 文章 ID |
| ip_address | VARCHAR(45) | IP 地址 |
| user_agent | VARCHAR(500) | UA |
| created_at | TIMESTAMP | 时间 |
| **唯一索引** | `(article_id, ip_address)` | 同 IP 同文章只能收藏一次 |

---

## 五、管理后台

### 5.1 文章编辑器（editor.php）

**特色功能**：
- **可视化 / HTML 双模式切换**：Quill 富文本编辑器 + HTML 代码编辑
- **双向同步**：切换标签时内容自动同步
- **封面图上传**：拖拽上传，自动校验尺寸
- **标签管理**：回车添加，点击删除
- **图标选择**：iconfont 图标输入

**字体**：全局使用 `PingFang HK`（自定义字体文件）

### 5.2 其他管理页面

| 页面 | 功能 |
|------|------|
| `index.php` | 后台首页，数据统计 |
| `comments.php` | 评论管理，删除操作 |
| `wallpapers.php` | 壁纸上传、启用/禁用、设为今日 |
| `resources.php` | 资源增删改查，分类动态获取 |

---

## 六、关键特性实现

### 6.1 液态玻璃效果

**实现方式**：
- SVG `<filter>` + `<feDisplacementMap>` 位移贴图
- Canvas 生成位移纹理（roundedRectSDF + smoothStep）
- CSS `backdrop-filter` 应用滤镜

**使用位置**：
- `DokeNav.vue` — 导航栏背景
- `Articles.vue` — 搜索框、下拉选择框

### 6.2 IP 防重复机制

**点赞/收藏/评论点赞**均采用相同机制：
```sql
UNIQUE KEY (article_id/comment_id, ip_address)
```
- 同一 IP 只能操作一次
- 再次操作自动取消（切换状态）
- 记录 `user_agent` 辅助识别

### 6.3 无默认值设计

**前端**：
- 所有 `ref` 初始值为 `[]` 或 `null`
- API catch 块返回空数据或 `null`
- 无硬编码的 mock 数据、渐变颜色、图标

**后端**：
- 管理页面分类标签从数据库动态获取
- 无写死的类型映射（`$typeLabels` 已改为查询 `resource_categories` 表）

---

## 七、部署说明

### 7.1 数据库初始化

```bash
# 导入纯净表结构
mysql -u username -p database_name < backend/blog.sql
```

### 7.2 前端部署

```bash
cd my-blog
npm install
npm run build
# 将 dist/ 目录部署到 Web 服务器
```

### 7.3 后端部署

```bash
# 确保 PHP 支持 PDO + MySQL
# 配置数据库连接（config.php）
# 确保 uploads/ 和 wallpapers/ 目录可写
# 确保安装fileinfo扩展
```

### 7.4 Nginx 配置（Vue History 模式支持）

由于前端使用 Vue Router 的 `createWebHistory` 模式，Nginx 需要配置伪静态支持，否则刷新非首页会出现 404。

**Nginx 配置示例：**

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/your/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    # API 代理（如果前后端在同一域名）
    location /api/ {
        proxy_pass http://localhost:8080/api/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

**关键配置说明：**

| 配置 | 作用 |
|------|------|
| `try_files $uri $uri/ /index.html` | 先尝试匹配文件/目录，都找不到则返回 `index.html`，由 Vue Router 处理路由 |

**宝塔面板用户**：在网站设置 → 伪静态 → 选择 `vue`，或手动添加上述 `location` 配置。

**Apache 用户**：在项目根目录创建 `.htaccess` 文件：

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On
  RewriteBase /
  RewriteRule ^index\.html$ - [L]
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule . /index.html [L]
</IfModule>
```
