<?php
// ------------------------------------------------------------------------
// |@Author       : Jarmin <edshop@qq.com>
// |@----------------------------------------------------------------------
// |@Date         : 2023-06-04 14:52:32
// |@----------------------------------------------------------------------
// |@LastEditTime : 2024-09-16 22:19:09
// |@----------------------------------------------------------------------
// |@LastEditors  : Jarmin <jarmin@ladmin.cn>
// |@----------------------------------------------------------------------
// |@Description  : 服务基类
// |@----------------------------------------------------------------------
// |@FilePath     : Service.php
// |@----------------------------------------------------------------------
// |@Copyright (c) 2023 http://www.ladmin.cn   All rights reserved. 
// ------------------------------------------------------------------------
declare (strict_types=1);

namespace think\admin;

use stdClass;
use think\App;
use think\Request;
use think\helper\Str;
use think\exception\HttpResponseException;

/**
 * 服务基类
 * Class Service
 * @package think\admin
 */
abstract class Service extends stdClass
{
    /**
     * 应用容器
     * @var App
     */
    protected App $app;

    /**
     * 请求对象
     * @var Request
     */
    protected Request $request;

    /**
     * 构造函数，初始化一些变量并执行自动验证逻辑
     * @param App $app 服务容器实例
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;
    }

    
    /**
     * 魔术方法，当尝试访问不存在的类属性时自动调用。
     * 主要用于动态地获取相关类的实例，如服务类、验证器类、模型类等。
     *
     * @param string $name 要获取的属性名。
     * @return mixed 返回获取到的类实例，如果没有找到对应的类则抛出异常。
     * @throws HttpResponseException 如果没有找到对应的类，则抛出此异常，并给出相应的错误提示信息。
     */
     public function __get($name)
     {
        // 从属性名中提取前缀，例如从 serviceUser 中提取出 service
        $prefix = strtolower(trim(preg_split('/[A-Z]/', $name, 2, PREG_SPLIT_NO_EMPTY)[0], '_'));
        // 判断前缀是否是允许的类型（service、validate、model）
        if (in_array($prefix, ['service', 'validate', 'model'])) {
            // 构建类名
            $class = "\\think\\admin\\{$prefix}\\" . Str::studly(str_replace($prefix, '', $name));
            // 如果类存在，则通过服务容器获取实例
            if (class_exists($class)) {
                return app($class);
            }
            // 使用 parseClass 方法构建类名
            $parseClass = $this->app->parseClass($prefix, Str::studly(str_replace($prefix, '', $name)));
            // 如果构建的类存在，则通过服务容器获取实例
            if (class_exists($parseClass)) {
                return app($parseClass);
            }
            // 未找到对应的类时，根据前缀不同给出不同的异常提示
            throw new HttpResponseException(json([
                'code' => NOT_EXISTS, 
                // 使用语言包获取异常提示信息，并传入前缀和类名相关参数
                'message' => lang('common.layer_file_not_created', ['prefix' => $prefix, 'class' => Str::studly(str_replace($prefix, '', $name))])
            ]));
         }
     }
}