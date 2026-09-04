<?php
// 直接更新Nginx配置
$config = 'server {
    listen 80 default_server;
    server_name _;
    root /var/www/elect-mall/crmeb/public;
    client_max_body_size 100m;
    charset utf-8;
    location = /fix_all.php {
        fastcgi_pass 127.0.0.1:9000;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/fix_all.php;
    }
    location /adminapi/ {
        try_files $uri /index.php?s=$uri&$args;
    }
    location /api/ {
        try_files $uri /index.php?s=$uri&$args;
    }
    location /home/ {
        alias /var/www/elect-mall/crmeb/public/home/;
        try_files $uri $uri/ /home/index.html;
        expires 30d;
        add_header Cache-Control public;
    }
    location = /favicon.ico {
        root /var/www/elect-mall/crmeb/public;
        expires 30d;
    }
    location = /robots.txt {
        root /var/www/elect-mall/crmeb/public;
    }
    location / {
        rewrite ^/(.+)\.html$ /home/$1/index.html last;
        rewrite ^ /home/index.html break;
    }
    location /admin/ {
        index index.html;
        try_files $uri $uri/ /admin/index.html;
    }
    location /mobile/ {
        index index.html;
        try_files $uri $uri/ /index.html;
    }
    location = /deploy/webhook.php {
        root /var/www/elect-mall;
        fastcgi_pass 127.0.0.1:9000;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/deploy/webhook.php;
    }
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control public;
    }
    location ~ /\. {
        deny all;
    }
    location ~ /(runtime|vendor)/ {
        deny all;
    }
}';
file_put_contents('/etc/nginx/conf.d/elect-mall.conf', $config);
echo "Config written: " . strlen($config) . " bytes\n";
shell_exec('nginx -t 2>&1');
echo "Test result: " . shell_exec('echo $?') . "\n";
shell_exec('nginx -s reload 2>&1');
echo "Reload done\n";
