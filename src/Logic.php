<?php
// ------------------------------------------------------------------------
// |@Author       : Jarmin <edshop@qq.com>
// |@----------------------------------------------------------------------
// |@Date         : 2024-09-16 21:56:53
// |@----------------------------------------------------------------------
// |@LastEditTime : 2024-10-03 01:18:20
// |@----------------------------------------------------------------------
// |@LastEditors  : Jarmin <jarmin@ladmin.cn>
// |@----------------------------------------------------------------------
// |@Description  : 逻辑基类
// |@----------------------------------------------------------------------
// |@FilePath     : Logic.php
// |@----------------------------------------------------------------------
// |@Copyright (c) 2024 http://www.ladmin.cn   All rights reserved. 
// ------------------------------------------------------------------------
declare (strict_types=1);

namespace think\admin;

use stdClass;
use think\App;
use think\helper\Str;
use think\exception\HttpResponseException;

/**
 * 逻辑基类
 * Class Logic
 * @package think\admin
 */
abstract class Logic extends stdClass
{
    /**
     * 应用容器
     * @var App
     */
    protected App $app;

    /**
     * 构造函数，初始化一些变量并执行自动验证逻辑
     * @param App $app 服务容器实例
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->data = $this->filter();
    }
    
    /**
     * 过滤请求参数的方法
     *
     * 该方法用于获取当前请求的参数，并检查参数中是否存在 'sign' 键。
     * 如果存在，将其从参数数组中移除，然后返回过滤后的参数数组。
     *
     * @return array 过滤后的请求参数数组
     */
    public function filter()
    {
        // 获取当前请求的参数
        $data = $this->app->request->param();
        // 检查参数数组中是否存在 'sign' 键，如果存在则将其移除
        if (isset($data['sign'])) unset($data['sign']);
        // 返回过滤后的参数数组
        return $data;
    }

    /**
     * 构建一个包含状态码、消息和数据的 JSON 格式数据数组。
     *
     * @param int    $code    状态码，默认为SUCCESS常量（如果定义了SUCCESS常量的话），用于表示请求或操作的状态。
     * @param string $message 消息字符串，用于描述操作相关的信息，例如操作成功或失败的原因等。
     * @param array  $data    可选参数，一个数组，用于包含需要返回的数据。默认为空数组。
     *
     * @return array 返回一个关联数组，其结构符合常见的 API 响应格式，包含以下键值：
     *               - 'code': 状态码。
     *               - 'message': 消息。
     *               - 'data': 数据数组（可能为空）。
     */
    function json(int $code = SUCCESS, string $message = null, array $data = []) {
        return [
            'code' => $code,
            'message' => $message,
            'data' => $data
        ];
    }
    
    /**
     * 魔术方法，当尝试访问不存在的类属性时自动调用。
     * 主要用于动态地获取相关类的实例，如逻辑类、服务类、验证器类、模型类等。
     *
     * @param string $name 要获取的属性名。
     * @return mixed 返回获取到的类实例，如果没有找到对应的类则抛出异常。
     * @throws HttpResponseException 如果没有找到对应的类，则抛出此异常，并给出相应的错误提示信息。
     */
    public function __get($name)
    {
        // 从属性名中提取前缀，例如从 logicUser 中提取出 logic
        $prefix = strtolower(trim(preg_split('/[A-Z]/', $name, 2, PREG_SPLIT_NO_EMPTY)[0], '_'));
        // 判断前缀是否是允许的类型（logic、service、validate、model）
        if (in_array($prefix, ['logic', 'service', 'validate', 'model'])) {
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
        return null;
    }
}