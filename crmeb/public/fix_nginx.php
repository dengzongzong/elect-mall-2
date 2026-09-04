<?php
/**
 * 直接在服务器上更新Nginx配置 - 修复.html路由404问题
 * 使用方法: 访问 http://134.175.246.242/crmeb/public/fix_nginx.php
 */
header('Content-Type: text/plain; charset=utf-8');

echo "=== 开始更新Nginx配置 ===\n\n";

// 完整的新配置
$newConfig = <<<'EOT'
server {
    listen 80 default_server;
    server_name _;
    root /var/www/elect-mall/crmeb/public;
    client_max_body_size 100m;
    charset utf-8;

    # 修复工具（临时）
    location = /fix_all.php {
        fastcgi_pass 127.0.0.1:9000;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/fix_all.php;
    }

    # 管理后台API 路由
    location /adminapi/ {
        try_files $uri /index.php?s=$uri&$args;
    }

    # 旧版API 路由
    location /api/ {
        try_files $uri /index.php?s=$uri&$args;
    }

    # PC 前端静态资源（/home/xxx.js 等）
    location /home/ {
        alias /var/www/elect-mall/crmeb/public/home/;
        try_files $uri $uri/ /home/index.html;
        expires 30d;
        add_header Cache-Control public;
    }

    # 根目录静态文件（favicon.ico 等）
    location = /favicon.ico {
        root /var/www/elect-mall/crmeb/public;
        expires 30d;
    }
    location = /robots.txt {
        root /var/www/elect-mall/crmeb/public;
    }

    # PC 前端 - 路由处理
    # 注意：不要用 try_files $uri $uri/，因为 public/index.html（旧uni-app页面）会优先匹配
    location / {
        # 处理 .html 路由（如 /brand_list.html → /home/brand_list/index.html）
        rewrite ^/(.+)\.html$ /home/$1/index.html last;
        # 其他所有请求都走 Nuxt.js SPA 入口
        rewrite ^ /home/index.html break;
    }

    # 管理后台
    location /admin/ {
        index index.html;
        try_files $uri $uri/ /admin/index.html;
    }

    # H5 移动端
    location /mobile/ {
        index index.html;
        try_files $uri $uri/ /index.html;
    }

    # Webhook 部署
    location = /deploy/webhook.php {
        root /var/www/elect-mall;
        fastcgi_pass 127.0.0.1:9000;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/deploy/webhook.php;
    }

    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }

    # 静态资源缓存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control public;
    }

    # 禁止访问
    location ~ /\. {
        deny all;
    }
    location ~ /(runtime|vendor)/ {
        deny all;
    }
}
EOT;

$dst = '/etc/nginx/conf.d/elect-mall.conf';

echo "正在写入配置到: {$dst}\n";
$result = file_put_contents($dst, $newConfig);

if ($result !== false) {
    echo "✓ 写入成功，大小: {$result} 字节\n\n";
} else {
    echo "✗ 直接写入失败，尝试使用sudo...\n\n";
}

// 测试配置
echo "[1/2] 测试Nginx配置...\n";
ob_flush();
flush();
exec('sudo nginx -t 2>&1', $output, $code);
echo implode("\n", $output) . "\n";

if ($code === 0) {
    echo "✓ 配置测试通过\n\n";
} else {
    echo "✗ 配置测试失败\n\n";
}

// 重载Nginx
echo "[2/2] 重载Nginx...\n";
ob_flush();
flush();
exec('sudo systemctl reload nginx 2>&1', $output, $code);
echo implode("\n", $output) . "\n";

if ($code === 0) {
    echo "✓ Nginx重载成功\n\n";
} else {
    echo "✗ Nginx重载失败\n\n";
}

echo "=== 完成 ===\n";
echo "现在请测试访问以下三个页面:\n";
echo "  http://134.175.246.242/brand_list.html\n";
echo "  http://134.175.246.242/bom_copy.html\n";
echo "  http://134.175.246.242/authorized_dealer.html\n";
?>