### **yafframework**
> 在原生yaf框架的基础上，做了封装优化，集成了数据库、缓存、日志、curl请求等，根据平时的经验不断完善，提供简单快速的开发框架

#### **一、文件说明**

```
application         // 应用目录
    actions             // action
    controllers         // 控制器 action的映射层
    library             // 项目库文件存放位置
    models
        dao                 // 数据库操作
        service
            data                // 数据处理层 （第三方接口及数据库操作）
            page                // 业务逻辑层
    modules             // modules 需要到 application.ini 下配置
    plugins             // 插件目录
    views               // 模板文件
    script              // 脚本文件
    Bootstrap.php       // 框架初始化设置，路由、自动加载等
conf                // 配置文件
data                // 本地数据/缓存
log                 // 日志
public              // 入口目录 index.php，及存放静态文件目录
thirdparty          // 第三方插件目录 例如smarty插件
```

#### **二、基类说明**
1.基类位置
```
application/library/comm/base/
```
2.基类说明
```
application/library/comm/base/Action.php            action 继承的基类 （包含登录校验、权限校验、数据渲染等...）
application/library/comm/base/Adapter.php           smarty基类        
application/library/comm/base/Dao.php               数据库操作dao层基类
application/library/comm/base/DataService.php       数据处理层基类
application/library/comm/base/PageService.php       业务逻辑层基类 (包含参数接收处理、错误处理等)
application/library/comm/base/Script.php            脚本基类
```

#### **三、php配置文件说明**
1.按照yaf扩展 yaf-2.3.5 版本
2.php.ini
```
; 加载yaf扩展
extension=yaf.so

; yaf配置
[Yaf]
yaf.use_namespace=off
yaf.environ="product"
yaf.lowcase_path=on
yaf.name_separator="_"
yaf.name_suffix=0
```

#### **四、nginx配置**
```
server
{
    listen       80;
    server_name  xxx.songrd.com;

    index index.html index.htm index.php;
    root  /home/www/web/xxx;

    access_log /home/www/log/yaf_access.log;
    error_log  /home/www/log/yaf_error.log error;

    # 静态文件
    location ~* .*\.(gif|jpg|jpeg|png|bmp|swf|ico|otf|ttf)$ {
        expires      30d;
        try_files $uri =404;
    }

    # css js 文件
    location ~* .*\.(js|css)?$ {
        expires      1d;
        try_files $uri =404;
    }

    # 重点注意这里
    location  / {
        rewrite  "^/(.*)" /public/index.php/$1 break;        
        fastcgi_pass unix:/tmp/php-cgi.sock;
        fastcgi_index  index.php;
        fastcgi_split_path_info     ^(.+?\.php)(.*)$;
        fastcgi_param  SCRIPT_FILENAME      $document_root$fastcgi_script_name;
        fastcgi_param  PATH_INFO            $fastcgi_path_info;
        include fastcgi_params;
    }
}
```

#### **五、数据库使用**
[数据库使用](https://github.com/songrd/yafframework/wiki/%E6%95%B0%E6%8D%AE%E5%BA%93%E6%93%8D%E4%BD%9C)
#### **六、日志使用**
[日志使用](https://github.com/songrd/yafframework/wiki/%E6%97%A5%E5%BF%97%E4%BD%BF%E7%94%A8)
#### **七、curl使用**
[curl使用](https://github.com/songrd/yafframework/wiki/curl)
#### **八、redis使用**
[redis使用](https://github.com/songrd/yafframework/wiki/redis)
