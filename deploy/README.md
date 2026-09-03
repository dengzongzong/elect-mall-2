# 自动部署指南

通过 GitHub Webhook 实现「提交代码 → 自动部署到服务器」

## 架构

```
你推送代码到 GitHub
        ↓
GitHub 发送 Webhook → 服务器 webhook.php
        ↓
webhook.php 验证签名后，触发 deploy.sh
        ↓
deploy.sh 执行：
  1. git pull 拉取最新代码
  2. composer install 安装 PHP 依赖
  3. npm run generate 构建 PC 前端
  4. npm run build 构建管理后台
  5. 同步文件到 Web 目录
  6. 重启 Nginx/PHP-FPM
```

## 一、服务器配置

### 前置条件

服务器需要安装以下环境：

```bash
# 基础环境
sudo apt update
sudo apt install -y git nginx php-fpm php-mysql php-curl php-gd php-mbstring php-xml composer

# Node.js (>=16)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

### 1. 在服务器上克隆代码

```bash
# 以 www-data 用户运行，确保 Web 服务器有权限
sudo mkdir -p /var/www/elect-mall
sudo chown www-data:www-data /var/www/elect-mall
sudo -u www-data git clone https://github.com/dengzongzong/elect-mall-2.git /var/www/elect-mall
```

### 2. 配置 GitHub 访问权限（免密拉取）

```bash
# 生成 SSH 密钥（用于 GitHub 免密访问）
sudo -u www-data ssh-keygen -t ed25519 -C "deploy@elect-mall" -f /var/www/.ssh/id_ed25519 -N ""

# 查看公钥，添加到 GitHub 仓库的 Deploy Keys
sudo cat /var/www/.ssh/id_ed25519.pub
```

> 将公钥添加到 GitHub 仓库：`Settings` → `Deploy keys` → `Add deploy key`

### 3. 设置目录权限

```bash
# 确保 Web 服务器用户有写入权限
sudo chown -R www-data:www-data /var/www/elect-mall
sudo chmod -R 755 /var/www/elect-mall/deploy
sudo chmod +x /var/www/elect-mall/deploy/deploy.sh
```

### 4. 配置 Nginx

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/elect-mall/public;

    index index.php index.html;

    # 后台管理
    location /admin {
        alias /var/www/elect-mall/template/admin/dist;
        index index.html;
        try_files $uri $uri/ /admin/index.html;
    }

    # PC 前端
    location /home {
        alias /var/www/elect-mall/template/pc/dist;
        index index.html;
        try_files $uri $uri/ /home/index.html;
    }

    # Webhook 部署端点
    location /deploy/webhook.php {
        root /var/www/elect-mall;
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }

    # PHP 后端 API
    location /api {
        try_files $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
    }
}
```

### 5. 测试部署脚本

```bash
sudo -u www-data bash /var/www/elect-mall/deploy/deploy.sh
```

## 二、GitHub Webhook 配置

1. 打开 GitHub 仓库：`https://github.com/dengzongzong/elect-mall-2`
2. 进入 `Settings` → `Webhooks` → `Add webhook`
3. 填写以下信息：

| 字段 | 值 |
|------|-----|
| **Payload URL** | `http://你的服务器IP/deploy/webhook.php` |
| **Content type** | `application/json` |
| **Secret** | `electmall2026`（与 `webhook.php` 中的 `WEBHOOK_SECRET` 一致） |
| **SSL verification** | 如无 HTTPS 则选 `Disable` |
| **Which events?** | `Just the push event` |
| **Active** | 勾选 |

4. 点击 `Add webhook` 保存

## 三、安全加固（建议）

### 1. 配置防火墙

```bash
# 只允许 GitHub 的 IP 访问 Webhook
sudo ufw allow from 192.30.252.0/22 to any port 80
sudo ufw allow from 185.199.108.0/22 to any port 80
sudo ufw allow from 140.82.112.0/20 to any port 80
```

### 2. 修改 Webhook 密钥

编辑 `deploy/webhook.php`，将 `WEBHOOK_SECRET` 改为一个随机字符串，然后在 GitHub Webhook 设置中更新 Secret。

### 3. 配置 HTTPS（推荐）

使用 Let's Encrypt 免费证书：

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com
```

## 四、手动触发部署

如果不想通过 Webhook，也可以 SSH 登录服务器后手动执行：

```bash
sudo -u www-data bash /var/www/elect-mall/deploy/deploy.sh
```

## 五、查看部署日志

```bash
tail -f /var/www/elect-mall/deploy/deploy.log
```

## 六、常见问题

### Q: Webhook 返回 403
A: 检查 `webhook.php` 中的 `WEBHOOK_SECRET` 是否与 GitHub 上设置的一致。

### Q: git pull 需要输入密码
A: 没有配置 SSH Deploy Key，请按上面步骤配置。

### Q: 前端构建失败
A: 检查 Node.js 版本是否 >= 16，或手动运行 `npm install` 查看详细错误。

### Q: 权限不足
A: 确保 `www-data` 用户对项目目录有读写权限。