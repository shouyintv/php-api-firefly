<?php
/**
 * config.local
 * 开发分支-develop
 *
 * @author : Cyw
 * @email  : rose2099.c@gmail.com
 * @created: 16/6/9 下午4:20
 * @logs   :
 *
 */

return new \Phalcon\Config([
    'debug'            => true,
    'databases'        => [
        'db_config' => [
            'host'     => '192.168.1.234',
            'username' => 'root',
            'password' => '123456',
            'port'     => '3306',
            'dbname'   => 'db_config',
            'options'  => [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4',
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]
        ]
    ],
    'redis'            => array(
        'User'    => array(
            'host'     => '192.168.1.234',
            'port'     => 6379,
            'readHost' => '192.168.1.234',
            'readPort' => 6379
        ),
        'Cache'   => array(
            'host' => '192.168.1.234',
            'port' => 6379,
        ),
        'Session' => array(
            'host' => '192.168.1.234',
            'port' => 6379,
        ),
        'Storage' => array(
            'host' => '192.168.1.234',
            'port' => 6379,
        )
    ),
    // 表结构缓存设置
    'DATA_CACHE_META'  => [
        'adapter'    => 'Redis',
        'host'       => '192.168.1.234',
        'port'       => 6379,
        'persistent' => false,
        'prefix'     => 'SYMETA',
        'lifetime'   => 86400
    ],
    // 数据库查询缓存设置
    'DATA_CACHE_MODEL' => [
        'host'       => '192.168.1.234',
        'port'       => 6379,
        'timeout'    => 1,
        'persistent' => false
    ],
    'logPath'          => '/tmp/logs/'
]);
