<?php
/**
 * PHP输出编码修复工具
 * 功能：修复数据库连接编码 + 修改.env配置 + 清除缓存
 * 使用后请立即删除此文件！
 */
header('Content-Type: text/html; charset=utf-8');
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>PHP输出编码修复工具</title>";
echo "<style>
body{font-family:'Microsoft YaHei',sans-serif;max-width:800px;margin:30px auto;padding:20px;line-height:1.8}
h1{color:#2c3e50;border-bottom:3px solid #e74c3c;padding-bottom:10px}
.success{color:#090;padding:5px 10px;background:#e8f8e8;border-radius:4px;margin:2px 0}
.error{color:#c00;padding:5px 10px;background:#fde8e8;border-radius:4px;margin:2px 0}
.warn{color:#960;padding:5px 10px;background:#fff3e0;border-radius:4px;margin:2px 0}
.info{color:#036;padding:5px 10px;background:#e3f2fd;border-radius:4px;margin:2px 0}
pre{background:#f5f5f5;padding:10px;border-radius:4px;font-size:13px}
</style></head><body>";
echo "<h1>🔧 PHP输出编码修复工具</h1>";

echo "<h2>📋 当前环境信息</h2>";
echo "<table border='1' cellpadding='6' cellspacing='0' style='border-collapse:collapse;width:100%'>";
echo "<tr><td>PHP版本</td><td>" . phpversion() . "</td></tr>";
echo "<tr><td>PHP default_charset</td><td>" . (ini_get('default_charset') ?: '未设置') . "</td></tr>";
echo "<tr><td>PHP mbstring.func_overload</td><td>" . (ini_get('mbstring.func_overload') ?: '0') . "</td></tr>";
echo "</table><br>";

// 1. 修改 .env 文件，将 CHARSET 改为 utf8mb4
echo "<h2>1️⃣ 修改 .env 数据库连接编码</h2>";
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $content = file_get_contents($envFile);
    $original = $content;
    
    // 将 CHARSET = utf8 改为 CHARSET = utf8mb4
    $content = preg_replace('/CHARSET\s*=\s*utf8(\s*)/i', 'CHARSET = utf8mb4$1', $content);
    $content = preg_replace('/charset\s*=\s*utf8(\s*)/i', 'charset = utf8mb4$1', $content);
    
    if ($content !== $original) {
        if (file_put_contents($envFile, $content)) {
            echo "<div class='success'>✅ .env 文件已修改: CHARSET = utf8 → CHARSET = utf8mb4</div>";
        } else {
            echo "<div class='error'>❌ 无法写入 .env 文件，请检查权限</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ .env 文件中 CHARSET 已经是 utf8mb4 或无需修改</div>";
    }
    
    echo "<pre>" . htmlspecialchars(file_get_contents($envFile)) . "</pre>";
} else {
    echo "<div class='error'>❌ 找不到 .env 文件</div>";
}

// 2. 修改 config/database.php 强制使用 utf8mb4
echo "<h2>2️⃣ 检查并修复数据库配置</h2>";
$dbConfigFile = __DIR__ . '/../config/database.php';
if (file_exists($dbConfigFile)) {
    $dbContent = file_get_contents($dbConfigFile);
    $dbOriginal = $dbContent;
    
    // 确保 charset 使用 utf8mb4
    $dbContent = preg_replace(
        "/'charset'\s*=>\s*Env::get\('database\.charset',\s*'utf8'\)/",
        "'charset'         => Env::get('database.charset', 'utf8mb4')",
        $dbContent
    );
    
    if ($dbContent !== $dbOriginal) {
        if (file_put_contents($dbConfigFile, $dbContent)) {
            echo "<div class='success'>✅ database.php 已修改，默认 charset 改为 utf8mb4</div>";
        } else {
            echo "<div class='error'>❌ 无法写入 database.php</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ database.php 已使用 utf8mb4，无需修改</div>";
    }
}

// 3. 创建一个中间件来强制设置响应编码
echo "<h2>3️⃣ 创建全局编码中间件</h2>";
$middlewareContent = '<?php
/**
 * 全局编码中间件 - 强制UTF-8响应
 */
namespace app\http\middleware;

use app\Request;
use crmeb\interfaces\MiddlewareInterface;
use think\Response;

class CharsetMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, \Closure $next)
    {
        $response = $next($request);
        
        // 确保所有响应都使用 UTF-8 编码
        if (method_exists($response, \'header\')) {
            $response->header([\'Content-Type\' => \'application/json; charset=utf-8\']);
        }
        
        return $response;
    }
}
';

$middlewareFile = __DIR__ . '/../app/http/middleware/CharsetMiddleware.php';
if (!file_exists($middlewareFile)) {
    if (file_put_contents($middlewareFile, $middlewareContent)) {
        echo "<div class='success'>✅ 已创建 CharsetMiddleware</div>";
    } else {
        echo "<div class='error'>❌ 无法创建 CharsetMiddleware</div>";
    }
} else {
    echo "<div class='info'>ℹ️ CharsetMiddleware 已存在</div>";
}

// 4. 修改 route middleware 配置
echo "<h2>4️⃣ 注册编码中间件到路由</h2>";
$commonRoute = __DIR__ . '/../app/adminapi/route/common.php';
if (file_exists($commonRoute)) {
    $routeContent = file_get_contents($commonRoute);
    $routeOriginal = $routeContent;
    
    // 在 middleware 数组末尾添加 CharsetMiddleware
    $routeContent = str_replace(
        "\\app\\adminapi\\middleware\\AdminLogMiddleware::class",
        "\\app\\adminapi\\middleware\\AdminLogMiddleware::class,\n    \\app\\http\\middleware\\CharsetMiddleware::class",
        $routeContent
    );
    
    if ($routeContent !== $routeOriginal) {
        if (file_put_contents($commonRoute, $routeContent)) {
            echo "<div class='success'>✅ 已注册 CharsetMiddleware 到路由</div>";
        } else {
            echo "<div class='error'>❌ 无法写入路由配置</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ CharsetMiddleware 已注册，无需修改</div>";
    }
}

// 5. 检查 Nginx 配置
echo "<h2>5️⃣ Nginx 编码配置检查</h2>";
$nginxConfig = '/etc/nginx/conf.d/elect-mall.conf';
if (file_exists($nginxConfig)) {
    $nginxContent = file_get_contents($nginxConfig);
    echo "<pre>" . htmlspecialchars($nginxContent) . "</pre>";
    
    if (strpos($nginxContent, 'charset utf-8') !== false) {
        echo "<div class='success'>✅ Nginx 已配置 charset utf-8</div>";
    } else {
        echo "<div class='warn'>⚠️ Nginx 缺少 charset utf-8 配置</div>";
    }
} else {
    echo "<div class='warn'>⚠️ Nginx 配置文件不存在（可能路径不同）</div>";
}

// 6. 清除缓存
echo "<h2>6️⃣ 清除系统缓存</h2>";
$cachePaths = [
    __DIR__ . '/../runtime/cache/',
    __DIR__ . '/../runtime/temp/',
];
foreach ($cachePaths as $path) {
    if (is_dir($path)) {
        $files = glob($path . '*');
        $count = 0;
        foreach ($files as $file) {
            if (is_file($file)) @unlink($file);
            $count++;
        }
        echo "<div class='success'>✅ 已清除 {$path} ({$count} 个文件)</div>";
    }
}

echo "<hr>";
echo "<div class='summary' style='font-size:16px;padding:15px;background:#e8f8e8;border-radius:8px;margin:15px 0'>";
echo "🎉 修复完成！请按以下步骤验证：<br>";
echo "1️⃣ 如果修改了 .env 文件，需要重启 PHP-FPM 才能生效：<br>";
echo "<pre>systemctl reload php-fpm</pre>";
echo "2️⃣ 退出管理后台，重新登录<br>";
echo "3️⃣ 按 Ctrl+F5 强制刷新浏览器<br>";
echo "4️⃣ 如果问题仍然存在，请访问 <a href='check_encoding.php' target='_blank'>check_encoding.php</a> 查看详细诊断信息";
echo "</div>";
echo "<div class='warn'>⚠️ 请删除此文件（fix_output_charset.php）和 check_encoding.php</div>";

echo "</body></html>";
?>