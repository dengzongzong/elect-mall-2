<?php
/**
 * GitHub Webhook 部署触发器
 * 支持通过 setup 模式设置Git Token
 */

// ====== 配置 ======
define('REPO_DIR', dirname(__DIR__));
define('WEBHOOK_SECRET', 'electmall2026');
define('LOG_FILE', REPO_DIR . '/deploy/deploy.log');
define('DEPLOY_SCRIPT', REPO_DIR . '/deploy/deploy.sh');
define('TOKEN_FILE', REPO_DIR . '/deploy/.git_token');
define('GIT_REPO_URL', 'https://github.com/dengzongzong/elect-mall-2.git');

// ====== 日志 ======
function writeLog($message) {
    $time = date('Y-m-d H:i:s');
    $log = "[{$time}] {$message}" . PHP_EOL;
    @file_put_contents(LOG_FILE, $log, FILE_APPEND | LOCK_EX);
}

// ====== Token管理 ======
function getGitToken() {
    if (file_exists(TOKEN_FILE)) {
        return trim(file_get_contents(TOKEN_FILE));
    }
    $cmd = "cd " . escapeshellarg(REPO_DIR) . " && git remote get-url origin 2>/dev/null";
    $url = trim(shell_exec($cmd));
    if (preg_match('/https:\/\/[^:]+:(.+)@/', $url, $m)) {
        return $m[1];
    }
    return '';
}

function saveGitToken($token) {
    $token = trim($token);
    if (empty($token)) return false;
    $dir = dirname(TOKEN_FILE);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    return file_put_contents(TOKEN_FILE, $token) !== false;
}

// ====== 修复Git远程地址 ======
function fixGitRemote() {
    $repoDir = REPO_DIR;
    $token = getGitToken();
    if ($token) {
        $gitUrl = "https://dengzongzong:{$token}@github.com/dengzongzong/elect-mall-2.git";
    } else {
        $gitUrl = GIT_REPO_URL;
    }
    
    $cmd = "cd " . escapeshellarg($repoDir) . " && git remote get-url origin 2>/dev/null";
    $current = trim(shell_exec($cmd));
    
    if ($current !== $gitUrl) {
        writeLog("[FIX] 更新Git远程地址（带token认证）");
        $cmd = "cd " . escapeshellarg($repoDir) . " && git remote set-url origin " . escapeshellarg($gitUrl) . " 2>&1";
        shell_exec($cmd);
        writeLog("[FIX] Git远程地址已更新");
    } else {
        writeLog("[FIX] Git远程地址正确，无需更新");
    }
}

// ====== 清理锁文件 ======
function cleanLockFile() {
    $lockFile = REPO_DIR . '/deploy/deploy.lock';
    if (file_exists($lockFile)) {
        if (time() - filemtime($lockFile) > 300) {
            @unlink($lockFile);
            writeLog("[FIX] 已清理过期锁文件");
        }
    }
}

// ====== 验证签名 ======
function verifySignature($payload, $signatureHeader) {
    if (!WEBHOOK_SECRET) return true;
    if (!$signatureHeader) return false;
    $expected = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);
    return hash_equals($expected, $signatureHeader);
}

// ====== 主流程 ======
$action = $_GET['action'] ?? '';

// 特殊模式：设置Token（通过URL参数一次性传入）
if ($action === 'set_token') {
    $token = $_GET['token'] ?? '';
    if (empty($token)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing token parameter']);
        exit;
    }
    if (saveGitToken($token)) {
        // 立即更新远程地址
        fixGitRemote();
        echo json_encode(['status' => 'ok', 'message' => 'Token saved and git remote updated']);
        writeLog("[SETUP] Token已保存并更新远程地址");
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save token']);
    }
    exit;
}

// 正常Webhook流程
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (!verifySignature($payload, $signature)) {
    http_response_code(403);
    writeLog("[ERROR] 签名验证失败");
    die('Signature verification failed');
}

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

$data = json_decode($payload, true);
$branch = $data['ref'] ?? '';

if (strpos($branch, 'refs/heads/main') === false) {
    writeLog("[SKIP] 忽略分支: {$branch}");
    echo json_encode(['status' => 'skipped', 'message' => "Branch {$branch} ignored"]);
    exit;
}

writeLog("[TRIGGER] 收到 main 分支推送");

// 修复Git远程地址
fixGitRemote();

// 清理过期锁文件
cleanLockFile();

// 使用sudo修复.git目录权限（这是关键：sudo允许nginx用户操作root所有的.git目录）
$gitDir = REPO_DIR . '/.git';
if (is_dir($gitDir)) {
    exec("sudo chmod -R 777 " . escapeshellarg($gitDir) . " 2>&1", $fixOut, $fixCode);
    writeLog("[FIX] sudo chmod .git: " . ($fixCode === 0 ? 'OK' : 'FAILED'));
}

// 异步执行部署脚本
$logFile = LOG_FILE;
$cmd = "nohup bash " . escapeshellarg(DEPLOY_SCRIPT) . " >> {$logFile} 2>&1 &";
exec($cmd);

writeLog("[TRIGGER] 部署脚本已启动");

echo json_encode([
    'status' => 'ok',
    'message' => 'Deployment triggered with git remote fix',
    'branch' => $branch
]);