<?php
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// 如果请求的是/import_categories.php，直接返回
if ($path === '/import_categories.php') {
    return false;
}

// 静态文件
if (preg_match('/\.(?:png|jpg|jpeg|gif|js|css|ico|svg|woff|woff2|ttf|eot)$/', $path)) {
    return false;
}

// /admin/ 开头的路由
if (strpos($path, '/admin/') === 0) {
    $file = __DIR__ . '/admin' . ($path === '/admin/' ? '/index.html' : $path);
    if (file_exists($file)) {
        return false;
    }
    $_SERVER['REQUEST_URI'] = '/admin/index.html';
    return false;
}

// API路由 -> 转发到index.php
$_SERVER['REQUEST_URI'] = '/index.php?s=' . $uri . '&' . $_SERVER['QUERY_STRING'];
return false;
