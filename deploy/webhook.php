<?php
/**
 * GitHub Webhook 部署触发器
 * 
 * 用法：
 * 1. 将此文件放在服务器上，如 /var/www/elect-mall/deploy/webhook.php
 * 2. 在 GitHub 仓库设置中添加 Webhook：
 *    - URL: http://你的服务器IP/deploy/webhook.php
 *    - Content type: application/json
 *    - Secret: 自己设置一个密钥（下面 WEBHOOK_SECRET 要一致）
 *    - Events: 勾选 "Push events"
 * 
 * 3. 确保 www-data 用户有权限执行 deploy/deploy.sh
 */

// ====== 配置 ======
// 仓库根目录（服务器上代码存放的绝对路径）
define('REPO_DIR', dirname(__DIR__));

// Webhook 密钥（与 GitHub 上设置的 Secret 一致）
define('WEBHOOK_SECRET', 'electmall2026');

// 日志文件路径
define('LOG_FILE', REPO_DIR . '/deploy/deploy.log');

// 部署脚本路径
define('DEPLOY_SCRIPT', REPO_DIR . '/deploy/deploy.sh');

// ====== 日志函数 ======
function writeLog($message) {
    $time = date('Y-m-d H:i:s');
    $log = "[{$time}] {$message}" . PHP_EOL;
    file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
}

// ====== 验证签名 ======
function verifySignature($payload, $signatureHeader) {
    if (!WEBHOOK_SECRET) {
        return true; // 未设置密钥，跳过验证（不推荐）
    }
    if (!$signatureHeader) {
        return false;
    }
    $expected = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);
    return hash_equals($expected, $signatureHeader);
}

// ====== 获取请求体 ======
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

// 验证签名
if (!verifySignature($payload, $signature)) {
    http_response_code(403);
    writeLog("[ERROR] 签名验证失败");
    die('Signature verification failed');
}

// 解析事件
$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? 'ping';

if ($event === 'ping') {
    writeLog("[PING] GitHub 连接测试成功");
    echo json_encode(['status' => 'ok', 'message' => 'pong']);
    exit;
}

if ($event !== 'push') {
    writeLog("[SKIP] 忽略事件类型: {$event}");
    echo json_encode(['status' => 'skipped', 'message' => "Event {$event} ignored"]);
    exit;
}

// 解析推送数据
$data = json_decode($payload, true);
$branch = $data['ref'] ?? '';
$commits = $data['commits'] ?? [];

// 只处理 main 分支
if (strpos($branch, 'refs/heads/main') === false) {
    writeLog("[SKIP] 忽略分支: {$branch}");
    echo json_encode(['status' => 'skipped', 'message' => "Branch {$branch} ignored"]);
    exit;
}

writeLog("[TRIGGER] 收到 main 分支推送，共 " . count($commits) . " 个提交");

// 异步执行部署脚本（不阻塞 Webhook 响应）
$logFile = LOG_FILE;
$cmd = "nohup bash " . escapeshellarg(DEPLOY_SCRIPT) . " >> {$logFile} 2>&1 &";
exec($cmd);

echo json_encode([
    'status' => 'ok',
    'message' => 'Deployment triggered',
    'branch' => $branch,
    'commits' => count($commits)
]);