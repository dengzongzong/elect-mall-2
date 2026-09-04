# 部署经验总结

> 记录本项目从第一次部署到稳定运行过程中踩过的坑和解决方案。
> 每次遇到新问题，请更新此文档。

---

## 目录

1. [服务器网络与连接](#1-服务器网络与连接)
2. [Git 权限与认证](#2-git-权限与认证)
3. [Webhook 自动化部署](#3-webhook-自动化部署)
4. [数据库脚本执行](#4-数据库脚本执行)
5. [文件同步与权限](#5-文件同步与权限)
6. [Nginx 配置](#6-nginx-配置)
7. [数据导入最佳实践](#7-数据导入最佳实践)
8. [检查清单](#8-检查清单)

---

## 1. 服务器网络与连接

### 1.1 确认服务器 IP 和端口

**问题：** SSH 连接超时，无法登录服务器。
**原因：** 一开始使用了错误的服务器 IP，或者 SSH 端口不是默认的 22。
**解决：** 先确认服务器 IP 正确，然后用 `curl http://<IP>/` 测试 Web 服务是否可达，再排查 SSH 端口。

**排查命令：**

```bash
# 测试 Web 服务是否可达
curl -s -o /dev/null -w "%{http_code}" --connect-timeout 10 http://<SERVER_IP>/

# 测试 SSH 端口是否开放
ssh -v -p 22 root@<SERVER_IP>
```

### 1.2 Web 端兜底操作

当 SSH 无法连接但 Web 服务正常时，可以通过 PHP 脚本在 Web 端执行操作：

```bash
# 通过 Web 访问 PHP 工具脚本
curl 'http://<SERVER_IP>/web_exec.php?action=check'
curl 'http://<SERVER_IP>/check_categories.php'
```

> **注意：** 这些工具脚本是临时性的，执行完毕后应立即删除，防止安全风险。

---

## 2. Git 权限与认证

### 2.1 私有仓库认证

**问题：** 服务器上 `git pull` 需要认证，但无法交互式输入密码。
**原因：** 私有仓库的 HTTPS 地址没有携带 token。
**解决：** 在远程 URL 中嵌入 access token：

```bash
git remote set-url origin https://<username>:<token>@github.com/<owner>/<repo>.git
```

### 2.2 `.git` 目录权限

**问题：** `git pull` 报错 "failed to open ... Permission denied"。
**原因：** `.git` 目录被 root 用户所有，Web 服务器用户（nginx）没有写入权限。
**解决：** 使用 `sudo` 修复权限，并在 deploy.sh 中自动执行：

```bash
sudo chmod -R 777 /var/www/elect-mall/.git
```

> **关键：** 如果 `.git` 目录设置了不可变属性（`chattr +i`），需要先移除：
> ```bash
> sudo chattr -R -i /var/www/elect-mall/.git 2>/dev/null
> ```

---

## 3. Webhook 自动化部署

### 3.1 签名验证失败

**问题：** GitHub Webhook 触发时返回 403 "Signature verification failed"。
**原因：** `webhook.php` 中的 `WEBHOOK_SECRET` 与 GitHub Webhook 设置中的 Secret 不匹配。
**解决：** 确保两端 Secret 一致，都设置为 `electmall2026`（或自定义的更安全的值）。

**手动触发测试：**

```bash
PAYLOAD='{"ref":"refs/heads/main","repository":{"full_name":"dengzongzong/elect-mall-2"}}'
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "electmall2026" | cut -d' ' -f2)

curl -X POST http://<SERVER_IP>/deploy/webhook.php \
  -H "Content-Type: application/json" \
  -H "X-Hub-Signature-256: sha256=$SIGNATURE" \
  -H "X-GitHub-Event: push" \
  -d "$PAYLOAD"
```

### 3.2 Webhook 配置要点

在 GitHub 仓库 `Settings → Webhooks → Add webhook` 中填写：

| 字段 | 值 |
|------|-----|
| Payload URL | `http://<服务器IP>/deploy/webhook.php` |
| Content type | `application/json` |
| Secret | 与 `webhook.php` 中的 `WEBHOOK_SECRET` 一致 |
| SSL verification | 无 HTTPS 则选 `Disable` |
| Which events | `Just the push event` |

### 3.3 锁文件机制

`deploy.sh` 使用锁文件防止并发部署。如果部署意外中断，锁文件可能残留：

```bash
# 手动清理锁文件
rm -f /var/www/elect-mall/deploy/deploy.lock
```

锁文件超过 10 分钟会自动视为过期并被清理。

---

## 4. 数据库脚本执行

### 4.1 MySQL 子查询错误

**错误信息：**

```
SQLSTATE[HY000]: General error: 1093 You can't specify target table 'X' for update in FROM clause
```

**原因：** MySQL 不允许在 `DELETE` 或 `UPDATE` 的子查询中直接引用目标表。
**解决：** 使用变量或 JOIN 代替子查询：

```sql
-- 错误写法
DELETE FROM eb_store_category WHERE id IN (SELECT id FROM eb_store_category WHERE ...);

-- 正确写法：先用变量存 ID
SELECT id INTO @tmp_id FROM eb_store_category WHERE cate_name = 'xxx' AND pid = 0 LIMIT 1;
DELETE FROM eb_store_category WHERE id = @tmp_id OR pid = @tmp_id;
```

### 4.2 PHP 执行 SQL 时跳过 SET 语句

**问题：** `deploy.sh` 中用 PHP 执行 SQL 时，部分语句被跳过。
**原因：** 正则表达式 `/^(SELECT|SET)\s/i` 错误地把 `SET` 语句也过滤掉了。
**修复：** 只跳过 `SELECT`，不跳过 `SET`：

```php
// 错误：跳过了 SET 语句
if (preg_match("/^(SELECT|SET)\s/i", $stmt)) continue;

// 正确：只跳过 SELECT
if (preg_match("/^(SELECT)\s/i", $stmt)) continue;
```

### 4.3 数据库配置读取

**问题：** 部署脚本中数据库配置读取失败，使用了默认值导致连接错误。
**原因：** `.env` 文件中的配置键名与 `grep` 搜索的键名不一致。
**解决：** 确认 `.env` 文件的键名格式：

```bash
# .env 文件中的键名示例
HOSTNAME = 127.0.0.1
DATABASE = crmeb31
USERNAME = root
PASSWORD = root
HOSTPORT = 3306
```

### 4.4 推荐：直接使用 PHP 脚本导入数据

相比 SQL 文件，PHP 脚本更灵活、更可靠：

```php
// import_categories.php 核心流程
// 1. 使用 PDO 连接数据库
// 2. 逐条执行 INSERT 语句（有详细日志）
// 3. 执行完毕后验证结果
// 4. 显示友好的 HTML 报告
```

**优点：** 错误处理更完善，日志更详细，可重复执行。

---

## 5. 文件同步与权限

### 5.1 PHP 工具文件 404

**问题：** 访问 `import_categories.php`、`check_categories.php` 等文件返回 404。
**原因：** `deploy.sh` 中文件同步的路径不对，工具文件没有复制到 Web 根目录。
**解决：** 使用 `find` 命令确保所有 PHP 文件都被同步：

```bash
find "${PROJECT_DIR}/crmeb/public" -maxdepth 1 -name "*.php" -exec cp {} "${WEB_ROOT}/" \;
```

### 5.2 目录权限设置

```bash
# Web 目录权限
sudo chown -R nginx:nginx /var/www/elect-mall/crmeb/public
sudo chmod -R 755 /var/www/elect-mall/crmeb/runtime

# 部署脚本可执行权限
sudo chmod +x /var/www/elect-mall/deploy/deploy.sh
```

---

## 6. Nginx 配置

### 6.1 配置写入权限

**问题：** `deploy.sh` 无法写入 `/etc/nginx/conf.d/elect-mall.conf`。
**解决：** 使用 `sudo cp` 代替直接写入，并设置 nginx 用户的 sudo 免密权限：

```bash
# 在 /etc/sudoers 中添加（已配置）
nginx ALL=(ALL) NOPASSWD: ALL
```

### 6.2 配置重载

修改 Nginx 配置后需要重载服务：

```bash
sudo nginx -t && sudo systemctl reload nginx
```

### 6.3 关键配置项

```nginx
# 管理后台API路由（必须配置，否则adminapi接口404）
location /adminapi/ {
    try_files $uri /index.php?s=$uri&$args;
}

# 修复工具路由（临时使用后删除）
location = /fix_all.php {
    fastcgi_pass 127.0.0.1:9000;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root/fix_all.php;
}
```

---

## 7. 数据导入最佳实践

### 7.1 推荐流程

1. **准备数据文件**：使用 PHP 脚本（非 SQL 文件），带完整错误处理和日志
2. **上传到服务器**：通过 Git 提交，自动同步到 Web 目录
3. **通过 Web 访问执行**：访问 `http://<IP>/import_categories.php`
4. **验证结果**：访问 `http://<IP>/check_categories.php`
5. **清理临时文件**：执行完毕后立即删除工具脚本

### 7.2 CRMEB 分类数据结构

```sql
-- 一级分类：pid = 0
-- 二级分类：pid = 一级分类的 ID
-- 关键字段：pid, cate_name, sort, is_show, add_time
```

### 7.3 重复执行安全

导入脚本应该设计为可重复执行：
- 插入前先检查是否已存在，存在则先删除
- 使用 `REPLACE INTO` 或先 `DELETE` 再 `INSERT`
- 执行完毕后备份 SQL 文件，防止重复执行

---

## 8. 检查清单

### 每次提交代码前

- [ ] 代码能否正常编译/构建
- [ ] 数据库变更是否有对应的 SQL 或 PHP 脚本
- [ ] 是否需要同步工具文件到 Web 目录

### 部署后验证

- [ ] Web 首页是否正常访问
- [ ] 后台管理页面是否正常
- [ ] 数据库变更是否生效
- [ ] 新功能是否正常工作

### 遇到部署失败时

1. 查看部署日志：`/var/www/elect-mall/deploy/deploy.log`
2. 检查 Webhook 触发记录
3. 手动执行 `deploy.sh` 测试
4. 通过 Web 工具脚本检查数据库状态

---

> **最后更新：** 2026-09-04
> **维护人：** 项目团队