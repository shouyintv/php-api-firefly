<?php
/**
 * index
 *
 * @author : Cyw
 * @email  : rose2099.c@gmail.com
 * @created: 16/12/30 下午7:28
 * @logs   :
 *
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

use Phalcon\Db\Adapter\Pdo\Mysql as MysqlAdapter;
use Phalcon\Di\FactoryDefault;
use Phalcon\Mvc\Model\MetaData\Memory as MemoryMetaData;
use Phalcon\Mvc\Model\MetaData\Redis as RedisMetaData;
use Core\FireReturn;
use Core\Log;

define('APP_NAME', 'api_firefly'); // 设置项目名称,给redis加前缀
define('APP_ENV', getenv('SY_APPLICATION_ENV')); // 环境变量［local,prerelease,master］
define('APP_PATH', __DIR__ . '/../app');
define('BASE_PATH', __DIR__ . '/..');

function is_online()
{
    return APP_ENV === 'master';
}

function is_prerelease()
{
    return APP_ENV === 'prerelease';
}

function is_local()
{
    return APP_ENV === 'local' || APP_ENV === 'local_qm';
}

// XSS 过滤
function safe_replace($string)
{
    $string = str_replace('%20', '', $string);
    $string = str_replace('%27', '', $string);
    $string = str_replace('%2527', '', $string);

    /*$string = str_replace('"', '&quot;', $string);
    $string = str_replace("'", '', $string);
    $string = str_replace('"', '', $string);
    $string = str_replace(';', '', $string);
    $string = str_replace('<', '&lt;', $string);
    $string = str_replace('>', '&gt;', $string);
    $string = str_replace('\\', '', $string);*/

    return $string;
}

function safe_filter($str)
{
    $filter = null;
    if (is_array($str)) {
        foreach ($str as $key => $val) {
            $filter[safe_replace($key)] = safe_filter($val);
        }
    } else {
        $filter = safe_replace($str);
    }

    return $filter;
}

function getComposerNameSpace()
{
    $psr4map = include BASE_PATH . '/vendor/composer/autoload_psr4.php';
    $temp1 = [];
    foreach ($psr4map as $ns => $path) {
        $temp1[substr($ns, 0, -1)] = $path;
    }
    $nssMap = include BASE_PATH . '/vendor/composer/autoload_namespaces.php';
    $temp2 = [];
    foreach ($nssMap as $ns => $path) {
        $ns = substr($ns, 0, -1);
        $temp2[$ns] = $path[0] . '/' . str_replace('\\', '/', $ns);
    }
    $loader = new \Phalcon\Loader();
    $loader->registerNamespaces(array_merge($temp2, $temp1));
    $loader->register();
}

if ($_GET) {
    $_GET = safe_filter($_GET);
}
if ($_POST) {
    $_POST = safe_filter($_POST);
}
if ($_REQUEST) {
    $_REQUEST = safe_filter($_REQUEST);
}
if ($_COOKIE) {
    $_COOKIE = safe_filter($_COOKIE);
}

// 加载项目选项
$project = 'public';
$controllerDir = APP_PATH . '/controllers/public/';
$uri = $_SERVER['REQUEST_URI'];

// 路由分组
// 可以被公共访问
if (strpos($uri, 'public') === 1) {
    $project = 'public';
    $controllerDir = APP_PATH . '/controllers/public/';
}

// 必须授权
if (strpos($uri, 'user') === 1) {
    $project = 'user';
    $controllerDir = APP_PATH . '/controllers/user/';
}

if (strpos($uri, 'auth') === 1) {
    $project = 'auth';
    $controllerDir = APP_PATH . '/controllers/auth/';
}

// loader
getComposerNameSpace();
$loader = new \Phalcon\Loader();

// 根据命名空间前缀加载
$loader->registerNamespaces([
    'Core'   => APP_PATH . '/library/core/',
    'Model'  => APP_PATH . '/library/model/',
    'Vendor' => APP_PATH . '/library/vendor/',
]);

/**
 * Register Files, composer autoloader
 */
$autoloadFiles = BASE_PATH . '/vendor/composer/autoload_files.php';
if (file_exists($autoloadFiles)) {
    $filesMap = include $autoloadFiles;
    $filesInc = array_values($filesMap);
    $loader->registerFiles($filesInc);
}

$loader->registerDirs([
    $controllerDir,
])->register();

// Di
$di = new FactoryDefault();

// config
if (file_exists(APP_PATH . '/config/config.' . APP_ENV . '.php')) {
    $config = include APP_PATH . '/config/config.' . APP_ENV . '.php';
    $di->set('config', $config, true);
}

// request
class Request extends Phalcon\Http\Request
{
    public function getClientAddress($trustForwardedHeader = null)
    {
        $ip = parent::getClientAddress();
        if (isset($_SERVER['HTTP_USERIP'])) {
            $ip = $_SERVER['HTTP_USERIP'];
        } elseif (isset($_SERVER['HTTP_ALI_CDN_REAL_IP'])) {
            $ip = $_SERVER['HTTP_ALI_CDN_REAL_IP'];
        } elseif (isset($_SERVER['HTTP_CDN_SRC_IP'])) {
            $ip = $_SERVER['HTTP_CDN_SRC_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            && $_SERVER['HTTP_X_FORWARDED_FOR']
        ) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if ($ip) {
            $ip = safe_filter($ip);
        }

        $ip = explode(',', $ip);
        $ip = trim($ip[0]);

        return $ip;
    }
}
$di->set('request', function () {
    return new Request;
});

// 设置数据库服务实例 return $app->db_config
foreach ((array)$di->get('config')->databases as $key => $database) {
    $di->set($key, function () use ($database) {
        return new MysqlAdapter($database->toArray());
    });
}
// 设置redis return $app->redisCache
foreach ((array)$di->get('config')->redis as $k => $v) {
    $di->set('redis' . $k, function () use ($v) {
        $objRedis = new \Core\FireRedis($v->toArray());

        return $objRedis;
    });
}

$di->set(
    'modelsMetadata',
    function () use ($di) {
        if ($di->get('config')->debug) {
            $metaData = new MemoryMetaData();
        } else {
            $metaData = new RedisMetaData($di->get('config')->DATA_CACHE_META->toArray());
        }

        return $metaData;
    },
    true
);

$di->set(
    'modelsCache',
    function () use ($di) {
        if ($di->get('config')->debug) {
            return new \Phalcon\Cache\Backend\Memory(new \Phalcon\Cache\Frontend\None());
        }
        $frontend = new \Phalcon\Cache\Frontend\Data([
            'lifetime' => 86400,
        ]);

        return new \Phalcon\Cache\Backend\Redis($frontend, $di->get('config')->DATA_CACHE_MODEL->toArray());
    },
    true
);

$di->set(
    'cookies',
    function () {
        $cookies = new Phalcon\Http\Response\Cookies();
        $cookies->useEncryption(false); //禁用加密
        return $cookies;
    });

// APP
$app = new \Phalcon\Mvc\Micro($di);

// 加载路由
include APP_PATH . '/config/route.' . $project . '.php';

$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, "Not Found")->sendHeaders();
    echo 'This is crazy, but this page was not found!';
    exit;
});

define('APP_LOG', $di->getConfig()->logPath);

set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($app, $di) {
    $app->response->setContentType('application/json', 'UTF-8')->sendHeaders();
    $errorMsg = "Error:{$errno} {$errstr} File>>{$errfile}:{$errline}";
    $err = is_online() ? '系统错误' : $errorMsg;
    if (is_online()) {
        Log::error($errorMsg);
    }
    echo FireReturn::makeJson($errno, $err);
    exit;
});

set_exception_handler(function (Exception $e) use ($app) {
    $logMsg = sprintf('Exception:code=%d, message=%s, file=%s:%s',
        $e->getCode(),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine()
    );

    $app->response->setContentType('application/json', 'UTF-8')->sendHeaders();

    if (is_online()) {
        Log::error($logMsg);
        $msg = sprintf('系统异常:code=%d', $e->getCode());
    } else {
        $msg = $logMsg;
    }

    echo FireReturn::makeJson($e->getCode(), $msg);
    exit;
});

register_shutdown_function(function () use ($app, $di) {
    $app->response->setContentType('application/json', 'UTF-8')->sendHeaders();
    $last_error = error_get_last();

    $errorMsg = '致命错误：' . $last_error['message'] . ' ' . $last_error['file'] . ' ' . $last_error['line'];
    if (!is_null($last_error)) {
        $errstr = is_online() ? '系统错误' : $errorMsg;
        $strRtn = FireReturn::makeJson(1000, $errstr);

        if (is_online()) {
            Log::error($errorMsg);
        }
        echo $strRtn;
        exit;
    }
});

$app->handle();
