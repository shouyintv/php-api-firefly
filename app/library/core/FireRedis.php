<?php
namespace Core;

use Redis;
use Exception;

class FireRedis
{
    private $masterWeight = 10;

    private $master = null;

    private $slave = null;
    private $readOnlyCommands = [
        'info',
        'smembers',
        'hlen',
        'hmget',
        'srandmember',
        'hvals',
        'randomkey',
        'strlen',
        'dbsize',
        'keys',
        'ttl',
        'lindex',
        'type',
        'llen',
        'dump',
        'scard',
        'echo',
        'lrange',
        'zcount',
        'exists',
        'sdiff',
        'zrange',
        'mget',
        'zrank',
        'get',
        'getbit',
        'getrange',
        'zrevrange',
        'zrevrangebyscore',
        'hexists',
        'object',
        'sinter',
        'zrevrank',
        'hget',
        'zscore',
        'hgetall',
        'sismember'
    ];

    public function __construct($option = [])
    {
        if (!class_exists('Redis')) {
            throw new Exception('redis not suport!');
        }
        if (!is_array($option)) {
            $option = [];
        }
        $option['host'] = isset($option['host']) ? $option['host'] : '127.0.0.1';
        $option['port'] = isset($option['port']) ? $option['port'] : 6379;
        $option['timeout'] = isset($option['timeout']) ? $option['timeout'] : 0;
        $option['persistent'] = isset($option['persistent']) ? (bool)$option['persistent'] : false;
        $option['auth'] = isset($option['auth']) ? $option['auth'] : '';
        $option['reads'] = isset($option['reads']) && is_array($option['reads']) ? $option['reads'] : [];

        $this->masterWeight = isset($option['readWeight']) && $option['reads'] ? (int)$option['readWeight'] : 10;

        $this->master = $this->connect($option['host'], $option['port'], $option['timeout'], $option['persistent']);
        $option['auth'] and $this->master->auth($option['auth']);

        if ($option['reads']) {
            shuffle($option['reads']);
            $config = array_shift($option['reads']);
            $host = isset($config['host']) ? $config['host'] : '127.0.0.1';
            $port = isset($config['port']) ? $config['port'] : 6379;
            $timeout = isset($config['timeout']) ? $config['timeout'] : 0;
            $persistent = isset($config['persistent']) ? (bool)$config['persistent'] : false;
            $this->slave = $this->connect($host, $port, $timeout, $persistent);

            isset($config['auth']) and $this->slave->auth($config['auth']);
        }
    }

    /**
     * 连接
     *
     * @param string $host
     * @param int $port
     * @param float $timeout
     * @param bool $persistent
     *
     * @return Redis
     * @throws Exception
     */
    public function connect($host = '127.0.0.1', $port = 6379, $timeout = 0.0, $persistent = false)
    {
        $connect = new Redis();
        $ret = $persistent ? $connect->pconnect($host, $port, $timeout) : $connect->connect($host, $port, $timeout);
        if (!$ret) {
            throw new Exception(sprintf('read redis connet fail at %s:%s', $host, $port));
        }

        return $connect;
    }

    /**
     * unix domain socket
     *
     * @param string $host
     *
     * @return bool
     */
    public function isSockHost($host = '')
    {
        return strpos($host, 'sock') !== false;
    }

    /**
     * 调用处理
     *
     * @param $funcName
     * @param $param
     *
     * @return mixed
     */
    public function __call($funcName, ...$param)
    {
        $param = array_shift($param);
        $key = array_shift($param);
        if ($this->masterWeight < rand(1, 10) && $this->slave && in_array(strtolower($funcName),
                $this->readOnlyCommands)
        ) {
            return $key ? $this->slave->$funcName($key, ...$param) : $this->slave->$funcName();
        }

        return $key ? $this->master->$funcName($key, ...$param) : $this->master->$funcName();
    }

    /**
     * 获取主库对象
     *
     * @return null
     */
    public function getMaster()
    {
        return $this->master;
    }

    /**
     * 获取从库对象
     *
     * @return null
     */
    public function getSlave()
    {
        return $this->slave;
    }

    /**
     * 析构
     */
    public function __destruct()
    {
        if ($this->master) {
            $this->master->close();
            $this->master = null;
        }
        if ($this->slave) {
            $this->slave->close();
            $this->slave = null;
        }
    }
}
