<?php

namespace Crmeb\Easypay\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;

/**
 * 基于文件存储的PSR-3日志实现类
 */
class FileLogger implements LoggerInterface
{
    /**
     * 日志存储目录
     * @var string
     */
    private $logDir;

    /**
     * 日志文件权限（Linux下有效）
     * @var int
     */
    private $filePermission = 0644;

    /**
     * 单个日志文件最大大小（字节）
     * @var int
     */
    private $maxFileSize = 2 * 1024 * 1024; // 10MB = 10*1024*1024字节

    /**
     * 日志级别映射（对应PSR-3标准级别）
     * @var array
     */
    private $logLevels = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT     => 1,
        LogLevel::CRITICAL  => 2,
        LogLevel::ERROR     => 3,
        LogLevel::WARNING   => 4,
        LogLevel::NOTICE    => 5,
        LogLevel::INFO      => 6,
        LogLevel::DEBUG     => 7,
    ];

    /**
     * 当前启用的日志级别（低于该级别的日志不记录）
     * @var string
     */
    private $currentLevel;

    /**
     * 构造函数
     * @param string $logDir 日志存储目录（默认：项目根目录下的logs文件夹）
     * @param string $logLevel 日志级别（默认：DEBUG，记录所有级别日志）
     * @throws InvalidArgumentException 日志目录创建失败或日志级别无效时抛出异常
     */
    public function __construct(string $logDir = '', string $logLevel = LogLevel::DEBUG)
    {
        // 初始化日志目录
        $dateDir = date('Y/m');
        $this->logDir = empty($logDir) ? dirname(__DIR__, 2) . '/logs/' . $dateDir : rtrim($logDir, '/') . '/' . $dateDir;

        if (!is_dir($this->logDir)) {
            $mkdirResult = mkdir($this->logDir, 0755, true);
            if (!$mkdirResult) {
                throw new InvalidArgumentException("日志目录创建失败：{$this->logDir}");
            }
        }

        // 验证并设置日志级别
        if (!isset($this->logLevels[$logLevel])) {
            throw new InvalidArgumentException("无效的日志级别：{$logLevel}，支持的级别：" . implode(', ', array_keys($this->logLevels)));
        }
        $this->currentLevel = $logLevel;
    }

    /**
     * 记录紧急日志（系统不可用）
     * @param string $message 日志消息
     * @param array $context 上下文参数（用于替换消息中的占位符）
     * @return void
     */
    public function emergency($message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * 记录警报日志（必须立即处理）
     * @param string $message 日志消息
     * @param array $context 上下文参数
     * @return void
     */
    public function alert($message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * 记录严重日志（严重错误）
     * @param string $message 日志消息
     * @param array $context 上下文参数
     * @return void
     */
    public function critical($message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * 记录错误日志（运行时错误）
     * @param string $message 日志消息
     * @param array $context 上下文参数
     * @return void
     */
    public function error($message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * 记录警告日志（异常情况但非错误）
     * @param string $message 日志消息
     * @param array $context 上下文参数
     * @return void
     */
    public function warning($message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * 记录通知日志（正常但重要的事件）
     * @param string $message 日志消息
     * @param array $context 上下文参数
     * @return void
     */
    public function notice($message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * 记录信息日志（普通信息）
     * @param string $message 日志消息
     * @param array $context 上下文参数
     * @return void
     */
    public function info($message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    /**
     * 记录调试日志（详细调试信息）
     * @param string $message 日志消息
     * @param array $context 上下文参数
     * @return void
     */
    public function debug($message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    /**
     * 核心日志记录方法（所有日志级别最终调用此方法）
     * @param string $level 日志级别
     * @param string $message 日志消息
     * @param array $context 上下文参数
     * @return void
     * @throws InvalidArgumentException 无效日志级别时抛出异常
     */
    public function log($level, $message, array $context = []): void
    {
        // 验证日志级别有效性
        if (!isset($this->logLevels[$level])) {
            throw new InvalidArgumentException("无效的日志级别：{$level}");
        }

        // 判断当前日志级别是否需要记录（低于当前启用级别的日志不记录）
        if ($this->logLevels[$level] > $this->logLevels[$this->currentLevel]) {
            return;
        }

        // 处理日志消息（替换占位符）
        $formattedMessage = $this->interpolateMessage($message, $context);

        // 构建日志内容
        $logContent = $this->buildLogContent($level, $formattedMessage, $context);

        // 获取日志文件名（按日期分文件，格式：YYYY-MM-DD.log）
        $logFile = $this->logDir . '/' . date('d') . '.log';

        $this->checkAndRotateLog($logFile);


        // 写入日志文件（追加模式，文件不存在则自动创建）
        $writeResult = file_put_contents(
            $logFile,
            $logContent . PHP_EOL,
            FILE_APPEND | LOCK_EX // LOCK_EX 避免多进程同时写入冲突
        );

        // 写入失败时抛出警告（不中断程序，避免日志功能影响主业务）
        if ($writeResult === false) {
            trigger_error("日志写入失败：{$logFile}", E_USER_WARNING);
        }

        // 设置日志文件权限（仅首次创建文件时生效）
        if (!file_exists($logFile) || fileperms($logFile) !== $this->filePermission) {
            @chmod($logFile, $this->filePermission);
        }
    }

    /**
     * 处理日志消息中的占位符（遵循PSR-3规范：{占位符}）
     * @param string $message 原始日志消息
     * @param array $context 上下文参数
     * @return string 处理后的日志消息
     */
    private function interpolateMessage(string $message, array $context): string
    {
        // 构建占位符替换数组
        $replace = [];
        foreach ($context as $key => $value) {
            // 仅处理字符串或可转换为字符串的类型
            if (is_scalar($value) || (is_object($value) && method_exists($value, '__toString'))) {
                $replace["{{$key}}"] = $value;
            } elseif (is_object($value)) {
                $replace["{{$key}}"] = get_class($value) . ' object';
            } else {
                $replace["{{$key}}"] = gettype($value);
            }
        }

        // 替换占位符并返回
        return strtr($message, $replace);
    }

    /**
     * 构建完整的日志内容
     * @param string $level 日志级别
     * @param string $message 处理后的日志消息
     * @param array $context 上下文参数
     * @return string 完整日志内容
     */
    private function buildLogContent(string $level, string $message, array $context): string
    {
        // 日志时间（精确到毫秒）
        $microtime = microtime(true);
        $timestamp = date('Y-m-d H:i:s', (int)$microtime) . '.' . sprintf('%03d', ($microtime - (int)$microtime) * 1000);

        // 客户端IP（CLI环境为CLI，Web环境为客户端IP）
        $ip = php_sapi_name() === 'cli' ? 'CLI' : ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN');

        // 日志内容格式：[时间] [级别] [IP] 消息 [上下文（JSON格式）]
        $contextStr = !empty($context) ? ' [' . json_encode($context, JSON_UNESCAPED_UNICODE) . ']' : '';

        return sprintf("[%s] [%s] [%s] %s%s", $timestamp, strtoupper($level), $ip, $message, $contextStr);
    }

    /**
     * 检查并切割日志文件
     * @param string $logFile 日志文件路径
     */
    private function checkAndRotateLog(string $logFile): void
    {
        // 1. 检查文件是否存在且是常规文件
        if (!file_exists($logFile) || !is_file($logFile)) {
            return;
        }

        // 2. 检查文件大小（使用clearstatcache确保获取最新大小）
        clearstatcache(true, $logFile);
        $fileSize = filesize($logFile);

        // 3. 大小检测容错处理（避免filesize返回false的情况）
        if ($fileSize === false) {
            trigger_error("无法获取日志文件大小：{$logFile}", E_USER_WARNING);
            return;
        }

        // 4. 超过最大尺寸则切割
        if ($fileSize >= $this->maxFileSize) {
            $this->rotateLog($logFile);
        }
    }

    /**
     * 执行日志切割
     * @param string $logFile 日志文件路径
     */
    private function rotateLog(string $logFile): void
    {
        // 1. 解析源文件信息（分离文件名和后缀）
        $fileDir = dirname($logFile); // 日志文件所在目录
        $fileName = basename($logFile); // 源文件名（如：log.log）
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION); // 源文件后缀（如：log）
        $fileBaseName = pathinfo($fileName, PATHINFO_FILENAME); // 源文件无后缀名称（如：log）

        // 2. 生成随机数字（可自定义随机数长度，此处为6位纯数字）
        $randomNum = mt_rand(100000, 999999); // 6位随机整数，也可使用 rand(1000, 9999) 生成4位

        // 3. 构建切割后的文件名：源文件名_随机数字.后缀（如：log_123456.log）
        $rotatedFileName = "{$fileBaseName}_{$randomNum}.{$fileExt}";
        $rotatedFile = $fileDir . DIRECTORY_SEPARATOR . $rotatedFileName;

        // 4. 避免随机数重复（可选：如果需要严格唯一，可循环检测文件是否存在）
        while (file_exists($rotatedFile)) {
            $randomNum = mt_rand(100000, 999999);
            $rotatedFileName = "{$fileBaseName}_{$randomNum}.{$fileExt}";
            $rotatedFile = $fileDir . DIRECTORY_SEPARATOR . $rotatedFileName;
        }

        // 5. 加锁确保切割原子性，避免多进程冲突
        $handle = fopen($logFile, 'a+');
        if (flock($handle, LOCK_EX)) {
            // 二次检查文件大小，防止多进程并发导致重复切割
            clearstatcache(true, $logFile);
            if (filesize($logFile) >= $this->maxFileSize) {
                // 重命名实现切割
                if (rename($logFile, $rotatedFile)) {
                    // 重新打开原文件（自动创建空文件）
                    $handle = fopen($logFile, 'a+');
                    if ($handle) {
                        // 写入切割标记日志
                        fwrite($handle, '');
                        fclose($handle);
                    }
                } else {
                    trigger_error("日志切割失败：无法重命名 {$logFile} 到 {$rotatedFile}", E_USER_WARNING);
                }
            }
            flock($handle, LOCK_UN); // 释放锁
        } else {
            trigger_error("日志切割失败：无法获取文件锁 {$logFile}", E_USER_WARNING);
        }
        fclose($handle);

        // 6. 设置切割后文件的权限
        if (file_exists($rotatedFile)) {
            @chmod($rotatedFile, $this->filePermission);
        }
    }

    /**
     * 获取当前日志存储目录
     * @return string
     */
    public function getLogDir(): string
    {
        return $this->logDir;
    }

    /**
     * 获取当前启用的日志级别
     * @return string
     */
    public function getCurrentLogLevel(): string
    {
        return $this->currentLevel;
    }
}