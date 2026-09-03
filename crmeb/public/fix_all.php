<?php
/**
 * 全自动数据库修复脚本 v2.0
 * 功能：自动扫描所有表，检测并修复双重编码乱码，重置管理员密码
 * 
 * 访问方式：http://你的域名/fix_all.php
 * 使用后请立即删除此文件！
 */

header('Content-Type: text/html; charset=utf-8');

// 加载ThinkPHP环境
$autoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($autoload)) {
    die("❌ 找不到 autoload.php，请确认路径正确");
}
require $autoload;

// 读取.env配置
$envFile = __DIR__ . '/../.env';
$config = parseEnv($envFile);

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>全自动数据库修复工具 v2.0</title>";
echo "<style>
body{font-family:'Microsoft YaHei',sans-serif;max-width:960px;margin:30px auto;padding:20px;line-height:1.8}
h1{color:#2c3e50;border-bottom:3px solid #e74c3c;padding-bottom:10px}
h2{color:#2c3e50;border-left:4px solid #e74c3c;padding-left:10px;margin-top:30px}
.success{color:#090;padding:5px 10px;background:#e8f8e8;border-radius:4px;margin:2px 0}
.error{color:#c00;padding:5px 10px;background:#fde8e8;border-radius:4px;margin:2px 0}
.warn{color:#960;padding:5px 10px;background:#fff3e0;border-radius:4px;margin:2px 0}
.info{color:#036;padding:5px 10px;background:#e3f2fd;border-radius:4px;margin:2px 0}
table{border-collapse:collapse;width:100%;margin:10px 0;font-size:14px}
table td, table th{border:1px solid #ddd;padding:4px 8px;text-align:left;word-break:break-all}
table th{background:#f5f5f5;font-weight:bold}
.summary{font-size:18px;padding:15px;background:#e8f8e8;border-radius:8px;margin:15px 0}
code{background:#f0f0f0;padding:2px 6px;border-radius:3px;font-size:13px}
</style></head><body>";
echo "<h1>🔧 CRMEB 全自动数据库修复工具 v2.0</h1>";

try {
    # ---- 第零步：修复项目文件权限 ----
    echo "<h2>🔧 第零步：修复项目文件权限</h2>";
    $projectDir = __DIR__ . '/../..';
    $gitDir = $projectDir . '/.git';
    if (is_dir($gitDir)) {
        exec("sudo chmod -R 777 {$gitDir} 2>&1", $permOut, $permCode);
        if ($permCode === 0) {
            echo "<div class='success'>✅ .git目录权限已修复（via sudo）</div>\n";
        } else {
            // 尝试不使用sudo
            exec("chmod -R 777 {$gitDir} 2>&1", $permOut2, $permCode2);
            if ($permCode2 === 0) {
                echo "<div class='success'>✅ .git目录权限已修复</div>\n";
            } else {
                echo "<div class='warn'>⚠️ .git目录权限修复失败，需要root权限</div>\n";
            }
        }
    } else {
        echo "<div class='warn'>⚠️ .git目录不存在（{$gitDir}）</div>\n";
    }
    $dsn = "mysql:host={$config['hostname']};port={$config['hostport']};dbname={$config['database']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, character_set_client = utf8mb4, character_set_connection = utf8mb4, character_set_results = utf8mb4",
    ]);
    echo "<div class='success'>✅ 数据库连接成功: {$config['database']}</div>\n";

    $prefix = $config['prefix'];

    // ============================================================
    // 第一步：全面扫描所有表，修复双重编码乱码
    // ============================================================
    echo "<h2>📝 第一步：全面扫描所有表，修复中文乱码</h2>";
    
    // 获取所有表
    $stmt = $pdo->query("SHOW TABLES");
    $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<div class='info'>📋 数据库共 " . count($allTables) . " 个表</div>\n";
    
    $totalFixed = 0;
    $totalGarbled = 0;
    $fixedTables = [];
    
    // 已知的乱码特征字符（UTF-8被当作Latin-1解码后的常见字符）
    $garbledChars = [
        'å', 'æ', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï',
        'ð', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú',
        'û', 'ü', 'ý', 'þ', 'ÿ', 'ā', 'ă', 'ą', 'ć', 'ĉ',
        'ċ', 'č', 'ď', 'đ', 'ē', 'ĕ', 'ė', 'ę', 'ě', 'ĝ',
        'ğ', 'ġ', 'ģ', 'ĥ', 'ħ', 'ĩ', 'ī', 'ĭ', 'į', 'ı',
        'ĳ', 'ĵ', 'ķ', 'ĺ', 'ļ', 'ľ', 'ŀ', 'ł', 'ń', 'ņ',
        'ň', 'ŉ', 'ō', 'ŏ', 'ő', 'œ', 'ŕ', 'ŗ', 'ř', 'ś',
        'ŝ', 'ş', 'š', 'ţ', 'ť', 'ŧ', 'ũ', 'ū', 'ŭ', 'ů',
        'ű', 'ų', 'ŵ', 'ŷ', 'ź', 'ž', 'ſ',
        // 常见的中文乱码特征
        'è®¢', 'å•†', 'å“', 'ç®¡', 'ç†', 'å•†å“', 'è®¢å•',
        'ç”¨', 'æˆ·', 'è®¾', 'ç½®', 'åŠŸ', 'èƒ½', 'æƒ', 'é™',
        'åˆ—', 'è¡¨', 'æ•°', 'æ®', 'é…', 'ç½®', 'è', 'åŠ¡',
        'å•†', 'åŸ', 'åˆ†', 'ç±»', 'æ ‡', 'ç­¾', 'å±•', 'ç¤º',
        'ç®¡ç†', 'ç³»ç»Ÿ', 'è®¢å•ç®¡ç†', 'å•†å“ç®¡ç†',
    ];
    
    // 额外的常见乱码UTF-8字节序列（被双重编码后的特征）
    $garbledBytePatterns = [
        "\xc3\xa5", // å
        "\xc3\xa6", // æ
        "\xc3\xa8", // è
        "\xc3\xa9", // é
        "\xc3\xaa", // ê
        "\xc3\xab", // ë
        "\xc3\xac", // ì
        "\xc3\xad", // í
        "\xc3\xae", // î
        "\xc3\xaf", // ï
        "\xc3\xb1", // ñ
        "\xc3\xb2", // ò
        "\xc3\xb3", // ó
        "\xc3\xb4", // ô
        "\xc3\xb6", // ö
        "\xc3\xb9", // ù
        "\xc3\xba", // ú
        "\xc3\xbc", // ü
    ];
    
    echo "<table><tr><th>#</th><th>表名</th><th>字段</th><th>修复行数</th><th>状态</th></tr>\n";
    $tableIndex = 0;
    
    foreach ($allTables as $tableName) {
        $tableIndex++;
        
        // 获取表的字段信息
        $colStmt = $pdo->query("SHOW COLUMNS FROM `{$tableName}`");
        $columns = $colStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 筛选出需要检查的文本字段（varchar, char, text, mediumtext, longtext, tinytext）
        $textColumns = [];
        foreach ($columns as $col) {
            $type = strtolower($col['Type']);
            $field = $col['Field'];
            // 只处理文本类型字段
            if (preg_match('/^(varchar|char|text|mediumtext|longtext|tinytext)/', $type)) {
                // 排除非文本字段（数字、时间、ID等）
                if (!in_array($field, ['id', 'add_time', 'update_time', 'create_time', 'delete_time', 
                    'status', 'is_del', 'is_show', 'is_hot', 'is_banner', 
                    'sort', 'visit', 'sales', 'stock', 'price', 'cost', 'ot_price',
                    'brokerage', 'brokerage_two', 'integral', 'ficti', 'give_integral',
                    'postage', 'delivery_type', 'spec_type',
                    'password', 'pwd', 'pay_password', 'token', 'api_token',
                    'openid', 'unionid', 'spread_openid', 'routine_openid', 'session_key',
                    'last_ip', 'client_ip', 'ip',
                    'level', 'pid', 'type', 'module',
                    'is_show_path', 'is_header', 'access', 'auth_type',
                    'sort', 'id', 'page', 'limit', 'export',
                ])) {
                    $textColumns[] = $field;
                }
            }
        }
        
        if (empty($textColumns)) {
            continue;
        }
        
        // 检查每个文本字段是否包含乱码
        $tableFixed = false;
        $garbledCount = 0;
        $fixedFields = [];
        
        foreach ($textColumns as $field) {
            try {
                // 不限制行数，检查所有行
                $checkStmt = $pdo->query("SELECT `{$field}` FROM `{$tableName}` WHERE `{$field}` IS NOT NULL AND `{$field}` != '' LIMIT 200");
                $rows = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
                
                $hasGarbled = false;
                foreach ($rows as $val) {
                    // 方法1：检查Latin-1特征字符
                    foreach ($garbledChars as $char) {
                        if (strpos($val, $char) !== false) {
                            $hasGarbled = true;
                            break 2;
                        }
                    }
                    // 方法2：检查字节序列特征
                    if (!$hasGarbled) {
                        foreach ($garbledBytePatterns as $pattern) {
                            if (strpos($val, $pattern) !== false) {
                                $hasGarbled = true;
                                break 2;
                            }
                        }
                    }
                }
                
                if ($hasGarbled) {
                    // 尝试两种修复策略
                    // 策略1: 标准双重编码修复 (UTF-8 -> Latin-1 -> UTF-8 双重编码)
                    $fixSql = "UPDATE `{$tableName}` SET `{$field}` = CONVERT(BINARY CONVERT(CAST(`{$field}` AS CHAR) USING latin1) USING utf8mb4) WHERE `{$field}` IS NOT NULL AND `{$field}` != ''";
                    $count = $pdo->exec($fixSql);
                    
                    if ($count === 0) {
                        // 策略2: 尝试不同的编码组合 (UTF-8 -> GBK -> UTF-8)
                        try {
                            $fixSql2 = "UPDATE `{$tableName}` SET `{$field}` = CONVERT(CAST(CONVERT(CAST(`{$field}` AS CHAR) USING utf8mb4) AS CHAR) USING utf8mb4) WHERE `{$field}` IS NOT NULL AND `{$field}` != ''";
                            $count = $pdo->exec($fixSql2);
                        } catch (Exception $e) {
                            // 忽略
                        }
                    }
                    
                    if ($count > 0) {
                        $garbledCount += $count;
                        $tableFixed = true;
                        $fixedFields[] = $field;
                    }
                }
            } catch (Exception $e) {
                // 跳过出错字段
            }
        }
        
        if ($tableFixed) {
            $totalFixed += $garbledCount;
            $totalGarbled++;
            $fixedTables[] = $tableName;
            echo "<tr><td>{$tableIndex}</td><td><strong>{$tableName}</strong></td><td>" . implode(', ', $fixedFields) . "</td><td>{$garbledCount}</td><td class='success'>✅ 已修复</td></tr>\n";
        }
    }
    
    echo "</table>\n";
    
    echo "<div class='summary'>";
    echo "📊 扫描完成：<br>";
    echo "✅ 修复了 <strong>{$totalGarbled}</strong> 个表中 <strong>{$totalFixed}</strong> 条乱码数据<br>";
    if (!empty($fixedTables)) {
        echo "📋 修复的表：<strong>" . implode('</strong>, <strong>', $fixedTables) . "</strong><br>";
    }
    if ($totalFixed === 0) {
        echo "<div class='warn'>⚠️ 没有找到需要修复的乱码数据。如果问题仍然存在，可能是数据本身没有乱码，而是PHP输出编码问题。</div>\n";
    }
    echo "</div>\n";

    // ============================================================
    // 第二步：针对管理员菜单表进行专项修复
    // ============================================================
    echo "<h2>📝 第二步：菜单表专项修复</h2>";
    
    $menuTable = $prefix . 'system_menus';
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$menuTable}'");
        $tableExists = $stmt->fetch();
        
        if ($tableExists) {
            echo "<div class='info'>📋 找到菜单表: {$menuTable}</div>\n";
            
            // 检查menu_name字段
            $stmt = $pdo->query("SELECT id, menu_name FROM `{$menuTable}` WHERE is_del = 0 ORDER BY pid, sort DESC LIMIT 50");
            $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table><tr><th>ID</th><th>当前值</th><th>修复后</th></tr>\n";
            $menuFixedCount = 0;
            
            foreach ($menus as $menu) {
                $original = $menu['menu_name'];
                $fixed = $original;
                
                // 检查是否乱码
                $isGarbled = false;
                foreach ($garbledChars as $char) {
                    if (strpos($original, $char) !== false) {
                        $isGarbled = true;
                        break;
                    }
                }
                
                if ($isGarbled) {
                    // 尝试修复单个值
                    try {
                        $fixStmt = $pdo->prepare("UPDATE `{$menuTable}` SET `menu_name` = CONVERT(BINARY CONVERT(CAST(`menu_name` AS CHAR) USING latin1) USING utf8mb4) WHERE id = ?");
                        $fixStmt->execute([$menu['id']]);
                        $menuFixedCount++;
                        
                        // 重新读取修复后的值
                        $checkStmt = $pdo->prepare("SELECT menu_name FROM `{$menuTable}` WHERE id = ?");
                        $checkStmt->execute([$menu['id']]);
                        $fixed = $checkStmt->fetchColumn();
                        
                        echo "<tr><td>{$menu['id']}</td><td class='error'>" . htmlspecialchars($original) . "</td><td class='success'>" . htmlspecialchars($fixed) . "</td></tr>\n";
                    } catch (Exception $e) {
                        echo "<tr><td>{$menu['id']}</td><td class='error'>" . htmlspecialchars($original) . "</td><td class='error'>修复失败: " . $e->getMessage() . "</td></tr>\n";
                    }
                }
            }
            echo "</table>\n";
            
            if ($menuFixedCount > 0) {
                echo "<div class='success'>✅ 菜单表修复完成，共修复 {$menuFixedCount} 条菜单数据</div>\n";
            } else {
                echo "<div class='info'>ℹ️ 菜单数据没有发现乱码，无需修复</div>\n";
            }
            
            // 也修复header字段
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM `{$menuTable}` WHERE header IS NOT NULL AND header != ''");
                $hasHeader = $stmt->fetchColumn();
                if ($hasHeader > 0) {
                    $fixStmt = $pdo->exec("UPDATE `{$menuTable}` SET `header` = CONVERT(BINARY CONVERT(CAST(`header` AS CHAR) USING latin1) USING utf8mb4) WHERE `header` IS NOT NULL AND `header` != ''");
                    if ($fixStmt > 0) {
                        echo "<div class='success'>✅ 菜单header字段修复完成，共修复 {$fixStmt} 条</div>\n";
                    }
                }
            } catch (Exception $e) {
                // header字段可能不存在，忽略
            }
        } else {
            echo "<div class='warn'>⚠️ 未找到菜单表 {$menuTable}</div>\n";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ 菜单表检查出错: " . htmlspecialchars($e->getMessage()) . "</div>\n";
    }

    // ============================================================
    // 第三步：检查并修复系统配置表中的中文数据
    // ============================================================
    echo "<h2>📝 第三步：系统配置专项修复</h2>";
    
    $configTables = [
        $prefix . 'system_config',
        $prefix . 'system_config_tab',
    ];
    
    foreach ($configTables as $configTable) {
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE '{$configTable}'");
            if (!$stmt->fetch()) continue;
            
            echo "<div class='info'>📋 检查配置表: {$configTable}</div>\n";
            
            $colStmt = $pdo->query("SHOW COLUMNS FROM `{$configTable}`");
            $columns = $colStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($columns as $col) {
                $field = $col['Field'];
                $type = strtolower($col['Type']);
                
                if (preg_match('/^(varchar|char|text|mediumtext|longtext)/', $type)) {
                    if (in_array($field, ['id', 'sort', 'status', 'type', 'config_tab_id', 'input_type', 'upload_type', 'required'])) continue;
                    
                    try {
                        $checkStmt = $pdo->query("SELECT `{$field}` FROM `{$configTable}` WHERE `{$field}` IS NOT NULL AND `{$field}` != '' LIMIT 100");
                        $rows = $checkStmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        $hasGarbled = false;
                        foreach ($rows as $val) {
                            foreach ($garbledChars as $char) {
                                if (strpos($val, $char) !== false) {
                                    $hasGarbled = true;
                                    break 2;
                                }
                            }
                        }
                        
                        if ($hasGarbled) {
                            $fixSql = "UPDATE `{$configTable}` SET `{$field}` = CONVERT(BINARY CONVERT(CAST(`{$field}` AS CHAR) USING latin1) USING utf8mb4) WHERE `{$field}` IS NOT NULL AND `{$field}` != ''";
                            $count = $pdo->exec($fixSql);
                            if ($count > 0) {
                                echo "<div class='success'>✅ {$configTable}.{$field}: 修复 {$count} 条</div>\n";
                            }
                        }
                    } catch (Exception $e) {
                        // 忽略
                    }
                }
            }
        } catch (Exception $e) {
            // 忽略
        }
    }

    // ============================================================
    // 第四步：检查并重置管理员密码
    // ============================================================
    echo "<h2>🔐 第四步：管理后台账号检查</h2>";
    
    $adminTable = $prefix . 'system_admin';
    $stmt = $pdo->query("SELECT id, account, real_name, status, is_del, level FROM `{$adminTable}` LIMIT 10");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($admins)) {
        echo "<div class='error'>❌ 未找到任何管理员账号！</div>\n";
    } else {
        echo "<div class='info'>📋 找到 " . count($admins) . " 个管理员账号：</div>\n";
        echo "<table><tr><th>ID</th><th>账号</th><th>姓名</th><th>状态</th><th>是否删除</th></tr>";
        
        foreach ($admins as $admin) {
            $statusLabel = $admin['status'] ? '<span class="success">正常</span>' : '<span class="error">禁用</span>';
            $delLabel = $admin['is_del'] ? '<span class="error">已删除</span>' : '<span class="success">正常</span>';
            echo "<tr><td>{$admin['id']}</td><td>{$admin['account']}</td><td>{$admin['real_name']}</td><td>$statusLabel</td><td>$delLabel</td></tr>";
        }
        echo "</table>";
        
        // 重置第一个管理员密码
        if (!empty($admins)) {
            $admin = $admins[0];
            $newPwd = 'admin123';
            $hashedPwd = password_hash($newPwd, PASSWORD_BCRYPT);
            
            $updateStmt = $pdo->prepare("UPDATE `{$adminTable}` SET pwd = ?, status = 1, is_del = 0 WHERE id = ?");
            $updateStmt->execute([$hashedPwd, $admin['id']]);
            
            echo "<div class='success'>✅ 管理员账号已重置：</div>\n";
            echo "<div class='info'>📌 账号: <strong>{$admin['account']}</strong></div>\n";
            echo "<div class='info'>📌 密码: <strong>{$newPwd}</strong></div>\n";
        }
    }

    // ============================================================
    // 第五步：清除缓存
    // ============================================================
    echo "<h2>🧹 第五步：清除系统缓存</h2>";
    
    $cachePaths = [
        __DIR__ . '/../runtime/cache/',
        __DIR__ . '/../runtime/cache/',
        __DIR__ . '/../runtime/log/',
        __DIR__ . '/../runtime/temp/',
    ];
    
    foreach ($cachePaths as $cachePath) {
        if (is_dir($cachePath)) {
            $files = glob($cachePath . '*');
            $count = 0;
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                    $count++;
                }
            }
            echo "<div class='success'>✅ 已清除 {$cachePath} ({$count} 个文件)</div>\n";
        }
    }
    
    // 尝试通过ThinkPHP清除缓存
    try {
        if (class_exists('\think\facade\Cache')) {
            \think\facade\Cache::clear();
            echo "<div class='success'>✅ ThinkPHP缓存已清除</div>\n";
        }
    } catch (Exception $e) {
        echo "<div class='warn'>⚠️ ThinkPHP缓存清除失败: " . $e->getMessage() . "</div>\n";
    }
    
    echo "<hr>";
    echo "<div class='summary'>🎉 所有修复操作已完成！</div>\n";
    echo "<div class='info'>💡 如果菜单仍然显示乱码，请尝试清除浏览器缓存或按 Ctrl+F5 强制刷新</div>\n";
    echo "<div class='warn'>⚠️ 请立即删除此文件（fix_all.php），防止被他人利用！</div>\n";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ 错误: " . htmlspecialchars($e->getMessage()) . "</div>\n";
    echo "<div class='error'>位置: " . $e->getFile() . ":" . $e->getLine() . "</div>\n";
    echo "<pre>" . $e->getMessage() . "</pre>\n";
}

echo "</body></html>";

/**
 * 解析.env文件
 */
function parseEnv($file) {
    $defaults = [
        'hostname' => '127.0.0.1',
        'database' => 'crmeb31',
        'username' => 'root',
        'password' => 'root',
        'hostport' => '3306',
        'charset' => 'utf8',
        'prefix' => 'eb_',
    ];
    
    if (!file_exists($file)) return $defaults;
    
    $content = file_get_contents($file);
    $lines = preg_split('/\r\n|\r|\n/', $content);
    $section = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        if (strpos($line, '[') === 0 && strpos($line, ']') !== false) {
            $section = strtolower(trim($line, '[]'));
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = strtolower(trim($key));
            $value = trim($value);
            
            if ($section === 'database') {
                switch ($key) {
                    case 'hostname': $defaults['hostname'] = $value; break;
                    case 'database': $defaults['database'] = $value; break;
                    case 'username': $defaults['username'] = $value; break;
                    case 'password': $defaults['password'] = $value; break;
                    case 'hostport': $defaults['hostport'] = $value; break;
                    case 'charset': $defaults['charset'] = $value; break;
                    case 'prefix': $defaults['prefix'] = $value; break;
                }
            }
        }
    }
    
    return $defaults;
}
?>