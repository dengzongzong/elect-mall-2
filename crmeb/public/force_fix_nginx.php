<?php
/**
 * 强制修复 Nginx 配置 - 直接替换整个文件
 */
header('Content-Type: text/plain; charset=utf-8');

// 完整修复后的 Nginx 配置
$config = <<<'EOT'
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
    # 修复：使用 root 而不是 alias，因为 root 已经是 /var/www/elect-mall/crmeb/public
    location /home/ {
        root /var/www/elect-mall/crmeb/public;
        try_files $uri $uri/ /home/index.html;
        expires 30d;
        add_header Cache-Control public;
    }

    # 根目录静态文件
    location = /favicon.ico {
        root /var/www/elect-mall/crmeb/public;
        expires 30d;
    }
    location = /robots.txt {
        root /var/www/elect-mall/crmeb/public;
    }

    # PC 前端 - 路由处理
    # 修复：使用 break 替代 last，确保不会重新匹配
    location / {
        # 处理 .html 路由（如 /brand_list.html → /home/brand_list/index.html）
        rewrite ^/(.+)\.html$ /home/$1/index.html break;
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

echo "=== 开始强制修复 Nginx 配置 ===\n\n";

$dst = '/etc/nginx/conf.d/elect-mall.conf';

// 直接写入
$ret = @file_put_contents($dst, $config);
if ($ret !== false) {
    echo "✓ 直接写入成功: {$dst} ({$ret} bytes)\n\n";
} else {
    echo "✗ 直接写入失败，使用 sudo\n\n";
    $tmp = tempnam(sys_get_temp_dir(), 'nginx_conf');
    file_put_contents($tmp, $config);
    $cmd = "sudo cp {$tmp} {$dst} 2>&1";
    echo "执行: {$cmd}\n";
    exec($cmd, $out, $code);
    echo implode("\n", $out) . "\n\n";
    unlink($tmp);
    if ($code !== 0) {
        echo "✗ sudo cp 失败\n";
        exit(1);
    }
    echo "✓ sudo cp 成功\n\n";
}

// 测试配置
echo "[1/2] 测试 Nginx 配置语法...\n";
exec('sudo nginx -t 2>&1', $out, $code);
echo implode("\n", $out) . "\n";
if ($code !== 0) {
    echo "✗ 配置语法错误\n";
    exit(1);
}
echo "✓ 配置测试通过\n\n";

// 重载 Nginx
echo "[2/2] 重载 Nginx...\n";
exec('sudo systemctl reload nginx 2>&1', $out, $code);
echo implode("\n", $out) . "\n";
if ($code !== 0) {
    echo "✗ 重载失败\n";
    exit(1);
}
echo "✓ 重载成功\n\n";

echo "=== 修复完成！请测试访问 ===\n";
echo "  http://134.175.246.242/brand_list.html\n";
echo "  http://134.175.246.242/bom_copy.html\n";
echo "  http://134.175.246.242/authorized_dealer.html\n";
?>