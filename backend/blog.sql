-- ============================================
-- 博客数据库完整表结构 (blog.sql)
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ============================================
-- 1. 分类表
-- ============================================
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '分类名称',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='分类表';

-- ============================================
-- 2. 标签表
-- ============================================
CREATE TABLE IF NOT EXISTS `tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL COMMENT '标签名称',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='标签表';

-- ============================================
-- 3. 文章表
-- ============================================
CREATE TABLE IF NOT EXISTS `articles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL COMMENT '文章标题',
  `description` varchar(500) DEFAULT NULL COMMENT '文章描述',
  `content` text DEFAULT NULL COMMENT '文章内容',
  `cover_image` varchar(500) DEFAULT NULL COMMENT '封面图URL',
  `category_id` int(11) DEFAULT NULL COMMENT '分类ID',
  `icon` varchar(50) DEFAULT '?' COMMENT '图标',
  `view_count` int(11) DEFAULT 0 COMMENT '浏览量',
  `like_count` int(11) DEFAULT 0 COMMENT '点赞数',
  `collect_count` int(11) DEFAULT 0 COMMENT '收藏数',
  `is_published` tinyint(1) DEFAULT 1 COMMENT '是否发布',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `is_published` (`is_published`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章表';

-- ============================================
-- 4. 文章标签关联表
-- ============================================
CREATE TABLE IF NOT EXISTS `article_tags` (
  `article_id` int(11) NOT NULL,
  `tag_id` int(11) NOT NULL,
  PRIMARY KEY (`article_id`, `tag_id`),
  KEY `tag_id` (`tag_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章标签关联表';

-- ============================================
-- 5. 点赞记录表
-- ============================================
CREATE TABLE IF NOT EXISTS `likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL COMMENT '文章ID',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) DEFAULT NULL COMMENT '用户代理',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `article_ip` (`article_id`, `ip_address`),
  KEY `article_id` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='点赞记录表';

-- ============================================
-- 6. 文章收藏表
-- ============================================
CREATE TABLE IF NOT EXISTS `collects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL COMMENT '文章ID',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) DEFAULT NULL COMMENT '用户代理',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `article_ip` (`article_id`, `ip_address`),
  KEY `article_id` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='文章收藏记录表';

-- ============================================
-- 7. 评论表
-- ============================================
CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL COMMENT '文章ID',
  `parent_id` int(11) DEFAULT NULL COMMENT '父评论ID（回复时使用）',
  `reply_to` varchar(100) DEFAULT NULL COMMENT '回复对象昵称',
  `nickname` varchar(100) NOT NULL COMMENT '昵称',
  `email` varchar(200) DEFAULT NULL COMMENT '邮箱',
  `content` text NOT NULL COMMENT '评论内容',
  `like_count` int(11) DEFAULT 0 COMMENT '点赞数',
  `is_author` tinyint(1) DEFAULT 0 COMMENT '是否作者',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) DEFAULT NULL COMMENT '用户代理',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`),
  KEY `parent_id` (`parent_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论表';

-- ============================================
-- 8. 评论点赞表
-- ============================================
CREATE TABLE IF NOT EXISTS `comment_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment_id` int(11) NOT NULL COMMENT '评论ID',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) DEFAULT NULL COMMENT '用户代理',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `comment_ip` (`comment_id`, `ip_address`),
  KEY `comment_id` (`comment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='评论点赞记录表';

-- ============================================
-- 9. 关于信息表
-- ============================================
CREATE TABLE IF NOT EXISTS `about_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '名称',
  `title` varchar(100) DEFAULT '全栈开发工程师' COMMENT '职业头衔',
  `bio` text DEFAULT NULL COMMENT '简介',
  `avatar` varchar(500) DEFAULT NULL COMMENT '头像',
  `email` varchar(200) DEFAULT NULL COMMENT '邮箱',
  `github` varchar(500) DEFAULT NULL COMMENT 'GitHub',
  `wechat` varchar(100) DEFAULT NULL COMMENT '微信号',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='关于信息表';

-- ============================================
-- 10. 关于我-技能表
-- ============================================
CREATE TABLE IF NOT EXISTS `about_skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL COMMENT '技能分类',
  `name` varchar(100) NOT NULL COMMENT '技能名称',
  `level` int(11) DEFAULT 0 COMMENT '熟练度 0-100',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='关于我-技能表';

-- ============================================
-- 11. 关于我-经历表
-- ============================================
CREATE TABLE IF NOT EXISTS `about_timeline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(20) NOT NULL COMMENT '类型: work/education',
  `date` varchar(50) NOT NULL COMMENT '时间范围',
  `title` varchar(100) NOT NULL COMMENT '标题',
  `organization` varchar(100) NOT NULL COMMENT '机构/公司',
  `description` text DEFAULT NULL COMMENT '描述',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='关于我-经历表';

-- ============================================
-- 12. 关于我-项目表
-- ============================================
CREATE TABLE IF NOT EXISTS `about_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '项目名称',
  `icon` varchar(20) DEFAULT '?' COMMENT '图标',
  `description` text DEFAULT NULL COMMENT '项目描述',
  `stack` varchar(255) DEFAULT NULL COMMENT '技术栈，逗号分隔',
  `stars` int(11) DEFAULT 0 COMMENT 'Star数',
  `views` varchar(50) DEFAULT '0' COMMENT '浏览量',
  `demo_url` varchar(255) DEFAULT NULL COMMENT '演示链接',
  `repo_url` varchar(255) DEFAULT NULL COMMENT '源码链接',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='关于我-项目表';

-- ============================================
-- 13. 关于我-兴趣表
-- ============================================
CREATE TABLE IF NOT EXISTS `about_interests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `icon` varchar(20) DEFAULT '?' COMMENT '图标',
  `name` varchar(50) NOT NULL COMMENT '兴趣名称',
  `description` text DEFAULT NULL COMMENT '描述',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sort` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='关于我-兴趣表';

-- ============================================
-- 14. 关于我-引言表
-- ============================================
CREATE TABLE IF NOT EXISTS `about_quotes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quote` text DEFAULT NULL COMMENT '引言内容',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='关于我-引言表';

-- ============================================
-- 15. 壁纸表
-- ============================================
CREATE TABLE IF NOT EXISTS `wallpapers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL COMMENT '壁纸标题',
  `filename` varchar(255) NOT NULL COMMENT '文件名',
  `url` varchar(500) NOT NULL COMMENT '访问URL',
  `width` int(11) DEFAULT 0 COMMENT '图片宽度',
  `height` int(11) DEFAULT 0 COMMENT '图片高度',
  `file_size` int(11) DEFAULT 0 COMMENT '文件大小(字节)',
  `mime_type` varchar(50) DEFAULT NULL COMMENT 'MIME类型',
  `category` varchar(50) DEFAULT 'default' COMMENT '分类',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否启用',
  `used_count` int(11) DEFAULT 0 COMMENT '使用次数',
  `last_used_date` date DEFAULT NULL COMMENT '最后使用日期',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `is_active` (`is_active`),
  KEY `category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='壁纸表';

-- ============================================
-- 16. 每日壁纸记录表
-- ============================================
CREATE TABLE IF NOT EXISTS `daily_wallpapers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` date NOT NULL COMMENT '日期',
  `wallpaper_id` int(11) NOT NULL COMMENT '壁纸ID',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `date` (`date`),
  KEY `wallpaper_id` (`wallpaper_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='每日壁纸记录表';

-- ============================================
-- 17. 资源分类表
-- ============================================
CREATE TABLE IF NOT EXISTS `resource_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(50) NOT NULL COMMENT '分类标识',
  `name` varchar(50) NOT NULL COMMENT '分类名称',
  `icon` varchar(255) DEFAULT NULL COMMENT '图标',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否启用',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='资源分类表';

-- ============================================
-- 18. 资源表
-- ============================================
CREATE TABLE IF NOT EXISTS `resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '资源名称',
  `description` varchar(500) DEFAULT NULL COMMENT '描述',
  `url` varchar(255) NOT NULL COMMENT '链接',
  `icon` varchar(20) DEFAULT '' COMMENT '图标',
  `type` varchar(50) DEFAULT 'tutorial' COMMENT '类型',
  `category` varchar(50) DEFAULT '' COMMENT '子分类',
  `level` varchar(20) DEFAULT 'beginner' COMMENT '难度',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否启用',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='资源表';

-- ============================================
-- 19. 首页欢迎文字表
-- ============================================
CREATE TABLE IF NOT EXISTS `welcome_texts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `text` varchar(200) NOT NULL COMMENT '欢迎文字',
  `sort` int(11) DEFAULT 0 COMMENT '排序',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='首页欢迎文字表';

-- ============================================
-- 20. 管理员表
-- ============================================
CREATE TABLE IF NOT EXISTS `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL COMMENT '用户名',
  `password` varchar(255) NOT NULL COMMENT '密码哈希',
  `nickname` varchar(100) DEFAULT NULL COMMENT '昵称',
  `avatar` varchar(500) DEFAULT NULL COMMENT '头像URL',
  `role` varchar(20) DEFAULT 'admin' COMMENT '角色: admin/super',
  `is_active` tinyint(1) DEFAULT 1 COMMENT '是否启用',
  `last_login_at` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(45) DEFAULT NULL COMMENT '最后登录IP',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表';

-- ============================================
-- 21. 管理员登录日志表
-- ============================================
CREATE TABLE IF NOT EXISTS `admin_login_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) DEFAULT NULL COMMENT '用户代理',
  `status` varchar(20) DEFAULT 'success' COMMENT '状态: success/failed',
  `message` varchar(255) DEFAULT NULL COMMENT '备注信息',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员登录日志表';

-- ============================================
-- 22. 管理员Token表
-- ============================================
CREATE TABLE IF NOT EXISTS `admin_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) NOT NULL COMMENT '管理员ID',
  `token` varchar(64) NOT NULL COMMENT 'Token',
  `expires_at` datetime NOT NULL COMMENT '过期时间',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP地址',
  `user_agent` varchar(500) DEFAULT NULL COMMENT '用户代理',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `admin_id` (`admin_id`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员Token表';

-- 插入默认管理员账号 (密码: admin123)
INSERT IGNORE INTO `admins` (`username`, `password`, `nickname`, `role`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '管理员', 'super');

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
