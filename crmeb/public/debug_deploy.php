<?php
header('Content-Type: text/plain; charset=utf-8');
$base = '/var/www/elect-mall';

echo "=== Git HEAD ===\n";
$cmd = "cd $base && git log --oneline -5 2>&1";
passthru($cmd);

echo "\n=== Git remote ===\n";
$cmd = "cd $base && git remote -v 2>&1";
passthru($cmd);

echo "\n=== Deploy.log 最后30行 ===\n";
$log = "$base/deploy/deploy.log";
if (file_exists($log)) {
    $lines = file($log);
    $last = array_slice($lines, -30);
    echo implode('', $last);
} else {
    echo "LOG file not found\n";
}

echo "\n=== Deploy.lock ===\n";
$lock = "$base/deploy/deploy.lock";
echo file_exists($lock) ? "LOCK EXISTS\n" : "LOCK NOT FOUND\n";

echo "\n=== Pages目录 ===\n";
$pages = "$base/template/pc/pages";
if (is_dir($pages)) {
    $files = scandir($pages);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') {
            echo "  $f\n";
        }
    }
} else {
    echo "pages dir not found\n";
}

echo "\n=== Home目录 (dist) ===\n";
$home = "$base/crmeb/public/home";
if (is_dir($home)) {
    $files = scandir($home);
    $dirs = [];
    $js = 0;
    foreach ($files as $f) {
        if ($f === '.' || $f === '..') continue;
        if (is_dir("$home/$f")) $dirs[] = $f;
        elseif (strpos($f, '.js') !== false) $js++;
    }
    echo "Total items: " . count($files) . "\n";
    echo "JS files: $js\n";
    echo "Generated route dirs: " . implode(', ', $dirs) . "\n";
} else {
    echo "home dir not found\n";
}