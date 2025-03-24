# think-extend
    ├── src
    │   ├── extend                    --扩展目录
    │   │   ├── CodeConstExtend.php   --常量管理扩展
    │   │   ├── CodeExtend.php        --随机数码扩展
    │   │   └── DataExtend.php        --数据处理扩展
    │   ├── middleware                --中间件目录
    │   │   ├── Auth.php              --权限中间件
    │   │   ├── Cors.php              --跨域中间件
    │   │   └── MultiApp.php          --多应用中间件
    │   ├── lang                      --语言包目录
    │   │   ├── en-us.php             --英文语言包
    │   │   └── zh-cn.php             --中文语言包
    │   ├── Common.php                --通用函数库
    │   ├── Controller.php            --控制器基类
    │   ├── Extend.php                --启动加载相关
    │   ├── Logic.php                 --逻辑基类
    │   ├── Model.php                 --模型基类
    │   └── Service.php               --服务基类
    └── composer.json                 --配置文件

# 多层分离

### controller 控制器层
接收请求数据，并与logic 逻辑层交互，获取结果返回给视图层
### logic      逻辑层
处理业务逻辑，可与其他logic、service、model、validate层进行交互后返回给控制器层
### service    服务层
处理服务业务，可与其他service、model、validate层进行交互后返回给逻辑层，如：文件上传，下载，图片处理，存储，处理日志，错误处理，邮件，授权，队列，计划任务，支付，验证，加密，短信等第三方业务
### model      模型层
处理数据库业务，封装通用数据库操作方法，一个model对应一个数据集合，包含了数据的字段信息、关联关系、读写设置，其他与数据库操作无关的东西应该考虑独立存放。
### validate   验证层
处理请求数据验证

# 跨域配置
在config下创建一个cors配置文件，代码如下：

```
return [
    'paths'                    => [],
    'allowed_origins'          => ['*'],
    'allowed_origins_patterns' => [],
    'allowed_methods'          => ['*'],
    'allowed_headers'          => ['*'],
    'exposed_headers'          => [],
    'max_age'                  => 0,
    'supports_credentials'     => false,
];
```
跨域需求可自行配置