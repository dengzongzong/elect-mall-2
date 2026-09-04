<?php
/**
 * 部署状态诊断页 - 查看服务器上的 git HEAD 和部署日志
 * 安全提醒：验证后请删除本文件
 */
header('Content-Type: text/html; charset=utf-8');
$proj = '/var/www/elect-mall';
echo '<pre style="background:#111;color:#0f0;padding:15px;font-size:13px">';

echo "===== 当前 git HEAD =====" . PHP_EOL;
$out = [];
exec("cd $proj && git log -1 --oneline 2>&1", $out);
echo implode(PHP_EOL, $out) . PHP_EOL . PHP_EOL;

echo "===== git remote =====" . PHP_EOL;
$out = [];
exec("cd $proj && git remote get-url origin 2>&1", $out);
echo implode(PHP_EOL, $out) . PHP_EOL . PHP_EOL;

echo "===== import_categories.php 是否为新版(含9大分类标题) =====" . PHP_EOL;
$f = "$proj/crmeb/public/import_categories.php";
if (file_exists($f)) {
    $c = file_get_contents($f);
    echo strpos($c, '电子元器件9大分类') !== false ? "新版(9大分类) ✓" : "旧版(仅陶瓷贴片电容) ✗";
} else {
    echo "文件不存在";
}
echo PHP_EOL . PHP_EOL;

echo "===== deploy.log 末尾 40 行 =====" . PHP_EOL;
$log = "$proj/deploy/deploy.log";
if (file_exists($log)) {
    $lines = file($log, FILE_IGNORE_NEW_LINES);
    echo implode(PHP_EOL, array_slice($lines, -40));
} else {
    echo "无日志文件";
}
echo PHP_EOL . PHP_EOL;

echo "===== deploy.lock 是否存在 =====" . PHP_EOL;
$lock = "$proj/deploy/deploy.lock";
echo file_exists($lock) ? "存在（部署可能仍在进行/卡住）" : "不存在";
echo PHP_EOL;

echo "</pre>";