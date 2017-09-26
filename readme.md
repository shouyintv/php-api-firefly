## FireFly Framework

FireFly is a PHP micro-framework that helps you quickly write simple yet powerful web applications and APIs.

## Feature

1. fast
2. simple

## How To Install

1. add php ext [phalcon](https://phalconphp.com/zh/)
2. composer install
3. config nginx

````
server {
        listen       80;
        server_name api.firefly.com;
        index index.html index.htm index.php;
        set $root_path '/Users/vanilla/git/lostelk/firefly/public/';
        root $root_path;

        try_files $uri $uri/ /index.php?_url=$uri&$args;
        client_max_body_size 50m;

        proxy_read_timeout 300;
        proxy_connect_timeout 300;
        proxy_redirect off;

        error_log /usr/local/var/log/nginx/api.firefly.error.log;
        access_log /usr/local/var/log/nginx/api.firefly.access.log;

        location ~ \.php {
            fastcgi_pass 127.0.0.1:9000;
            #fastcgi_pass unix:/usr/local/var/run/php5-fpm.sock;
            fastcgi_buffer_size 16k;
            fastcgi_buffers 4 16k;
            fastcgi_index /index.php;
            include        fastcgi_params;
            fastcgi_split_path_info       ^(.+\.php)(/.+)$;
            fastcgi_param PATH_INFO       $fastcgi_path_info;
            fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            fastcgi_param SY_APPLICATION_ENV       local;
        }
}
````
4. restart nginx php-fpm and open //xxxx/public/test