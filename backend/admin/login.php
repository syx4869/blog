<?php
session_start();

// 如果已登录，跳转到后台首页
if (isset($_SESSION['admin_id']) && isset($_SESSION['admin_token'])) {
    header('Location: index.php');
    exit;
}

$error = '';

// 处理登录表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $remember = isset($_POST['remember']) ? true : false;
    
    if (empty($username) || empty($password)) {
        $error = '用户名和密码不能为空';
    } else {
        require_once '../db.php';
        try {
            $pdo = getDB();
            
            // 查询用户
            $stmt = $pdo->prepare("SELECT id, username, password, nickname, avatar, role, is_active FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                if (!$admin['is_active']) {
                    $error = '账号已被禁用，请联系超级管理员';
                } else {
                    // 生成 Token
                    $token = bin2hex(random_bytes(32));
                    $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days'));
                    
                    // 保存 Token
                    $stmt = $pdo->prepare("INSERT INTO admin_tokens (admin_id, token, expires_at, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([
                        $admin['id'],
                        $token,
                        $expiresAt,
                        $_SERVER['REMOTE_ADDR'] ?? null,
                        $_SERVER['HTTP_USER_AGENT'] ?? null
                    ]);
                    
                    // 设置 Session
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_nickname'] = $admin['nickname'];
                    $_SESSION['admin_avatar'] = $admin['avatar'];
                    $_SESSION['admin_role'] = $admin['role'];
                    $_SESSION['admin_token'] = $token;
                    
                    // 记住登录
                    if ($remember) {
                        setcookie('admin_token', $token, time() + 7 * 24 * 3600, '/', '', false, true);
                    }
                    
                    // 更新最后登录时间
                    $stmt = $pdo->prepare("UPDATE admins SET last_login_at = NOW(), last_login_ip = ? WHERE id = ?");
                    $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? null, $admin['id']]);
                    
                    header('Location: index.php');
                    exit;
                }
            } else {
                $error = '用户名或密码错误';
            }
        } catch (Exception $e) {
            $error = '系统错误: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>后台登录 - 博客管理系统</title>

  <style>
    @font-face {
      font-family: 'PingFang HK';
      src: url('PingFang HK.ttf') format('truetype');
      font-weight: normal;
      font-style: normal;
      font-display: swap;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'PingFang HK', -apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif;
      background: #18181b;
      color: #d4d4d8;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .login-container {
      width: 100%;
      max-width: 420px;
      padding: 0 20px;
    }

    .login-box {
      background: #1e1e24;
      border: 1px solid #2a2a30;
      border-radius: 16px;
      padding: 40px 36px;
    }

    .login-header {
      text-align: center;
      margin-bottom: 32px;
    }

    .login-header .logo {
      width: 64px;
      height: 64px;
      background: #27272a;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      font-size: 1.8rem;
    }

    .login-header h1 {
      font-size: 1.4rem;
      color: #e4e4e7;
      font-weight: 600;
      margin-bottom: 6px;
    }

    .login-header p {
      font-size: 0.85rem;
      color: #71717a;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      font-size: 0.85rem;
      color: #a1a1aa;
      margin-bottom: 8px;
      font-weight: 500;
    }

    .input-wrapper {
      position: relative;
    }

    .form-input {
      width: 100%;
      padding: 12px 14px;
      background: #27272a;
      border: 1px solid #3a3a40;
      border-radius: 10px;
      color: #e4e4e7;
      font-size: 0.95rem;
      font-family: inherit;
      transition: all 0.2s;
      outline: none;
    }

    .form-input:focus {
      border-color: #3b82f6;
      background: #2a2a30;
    }

    .form-input::placeholder {
      color: #52525b;
    }

    .form-options {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 24px;
    }

    .remember-me {
      display: flex;
      align-items: center;
      gap: 8px;
      cursor: pointer;
      font-size: 0.85rem;
      color: #a1a1aa;
    }

    .remember-me input[type="checkbox"] {
      width: 16px;
      height: 16px;
      accent-color: #3b82f6;
      cursor: pointer;
    }

    .forgot-link {
      font-size: 0.85rem;
      color: #3b82f6;
      text-decoration: none;
      transition: color 0.2s;
    }

    .forgot-link:hover {
      color: #60a5fa;
    }

    .btn-login {
      width: 100%;
      padding: 13px;
      background: #3b82f6;
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 0.95rem;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-login:hover {
      background: #2563eb;
    }

    .btn-login:active {
      transform: scale(0.98);
    }

    .error-message {
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid rgba(239, 68, 68, 0.2);
      color: #ef4444;
      padding: 12px 16px;
      border-radius: 10px;
      font-size: 0.85rem;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .login-footer {
      text-align: center;
      margin-top: 24px;
      font-size: 0.8rem;
      color: #52525b;
    }

    @media (max-width: 480px) {
      .login-box {
        padding: 32px 24px;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-box">
      <div class="login-header">
        <div class="logo">&#128274;</div>
        <h1>博客管理系统</h1>
        <p>请登录以继续访问后台</p>
      </div>

      <?php if ($error): ?>
        <div class="error-message">
          <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label class="form-label">用户名</label>
          <div class="input-wrapper">
            <input type="text" name="username" class="form-input" placeholder="请输入用户名" required
                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">密码</label>
          <div class="input-wrapper">
            <input type="password" name="password" class="form-input" placeholder="请输入密码" required>
          </div>
        </div>

        <div class="form-options">
          <label class="remember-me">
            <input type="checkbox" name="remember" checked>
            <span>记住我</span>
          </label>
        </div>

        <button type="submit" class="btn-login">登 录</button>
      </form>

      <div class="login-footer">
        默认账号: admin / 密码: admin123
      </div>
    </div>
  </div>
</body>
</html>
