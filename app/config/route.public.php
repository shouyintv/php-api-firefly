<?php
// 路由
$routers = [
    '/public/test' => [
        'controller' => 'IndexController',
        'action'     => 'test',
        'method'     => ['get', 'post'],
    ],
];

// 注入DI
$map_methods = ['get', 'post'];
foreach ($routers as $uri => $router) {
    $methods = is_array($router['method']) ? $router['method'] : (array)$router['method'];
    foreach ((array)$methods as $method) {
        $method = strtolower($method);
        if (!in_array($method, $map_methods)) {
            continue;
        }
        $app->$method($uri, function () use ($app, $router) {
            $di = $app->getDI();
            $di->set($router['controller'], function () use ($router) {
                return new $router['controller'];
            });

            return call_user_func_array([$di->get($router['controller']), $router['action']], func_get_args());
        });
    }
}
