<?php
namespace Core;
/**
 * Log
 *
 * @author : Cyw
 * @email  : rose2099.c@gmail.com
 * @created: 16/6/23 下午7:49
 * @logs   :
 *
 */
class Log
{
    const TRACE = 'TRACE'; //流程追踪
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARN = 'WARN';
    const ERROR = 'ERROR';
    const ALERT = 'ALERT';
    const RECORD = 'RECORD';

    /**
     * **必须**立刻采取行动
     *
     * 例如：在整个网站都垮掉了、数据库不可用了或者其他的情况下
     * 应该发送一条警报短信把你叫醒。
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public static function alert($message, array $context = array())
    {
        self::write(static::ALERT, $message, $context);
    }

    /**
     * 运行时出现的错误，不需要立刻采取行动，但必须记录下来以备检测。
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public static function error($message, array $context = array())
    {
        self::write(static::ERROR, $message, $context);
    }

    /**
     * 出现非错误性的异常。
     *
     * 例如：使用了被弃用的API、错误地使用了API或者非预想的不必要错误。
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public static function warn($message, array $context = array())
    {
        self::write(static::WARN, $message, $context);
    }

    /**
     * 流程追踪。
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public static function trace($message, array $context = array())
    {
        self::write(static::TRACE, $message, $context);
    }

    /**
     * 重要事件
     *
     * 例如：用户登录和SQL记录。
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public static function info($message, array $context = array())
    {
        self::write(static::INFO, $message, $context);
    }

    /**
     * debug 详情
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public static function debug($message, array $context = array())
    {
        self::write(static::DEBUG, $message, $context);
    }

    /**
     * 记录日志 详情
     *
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public static function record($action = '', $data = [], $uid = 0, $devid = '')
    {
        $record = [
            '_ld_'    => date('Y-m-d H:i:s'),
            '_a_'     => $action,
            '_uid_'   => (int)$uid,
            '_devid_' => $devid
        ];

        $record = array_merge($data, $record);
        $record = json_decode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        self::write(static::RECORD, $record, []);
    }

    /**
     * 任意等级的日志记录
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     *
     * @return null
     */
    public static function write($level, $message, array $context = [])
    {
        if (is_object($message)) {
            $message = json_decode(json_encode($message), true);
        }

        if (is_array($message)) {
            $message = json_encode($message, 256);
        }

        foreach ($context as $key => $val) {
            $message = str_replace('{' . $key . '}', $val, $message);
        }

        $trace = debug_backtrace();

        $caller = isset($trace[1]) ? $trace[1] : [];

        if (isset($caller['file'])) {
            $file = pathinfo($caller['file'], PATHINFO_BASENAME);
            $line = $caller['line'];
        } else {
            $file = $line = '';
        }

        self::printConsoleLog($message, $level, $file, $line);
    }

    public static function printConsoleLog($message, $level = 'TRACE', $file = '', $line = 0)
    {
        $message = sprintf('[%s][%s][%s][%s]%s', date('Y-m-d H:i:s'), $level, $file, $line, $message);
        //记录到一个文件....
        error_log($message . PHP_EOL, 3, APP_LOG . date('Y-m-d') . sprintf('-%s.log', strtolower($level)));
    }
}
