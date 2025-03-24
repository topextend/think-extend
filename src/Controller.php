<?php
// ------------------------------------------------------------------------
// |@Author       : Jarmin <edshop@qq.com>
// |@----------------------------------------------------------------------
// |@Date         : 2023-06-04 14:52:14
// |@----------------------------------------------------------------------
// |@LastEditTime : 2024-09-27 18:50:06
// |@----------------------------------------------------------------------
// |@LastEditors  : Jarmin <jarmin@ladmin.cn>
// |@----------------------------------------------------------------------
// |@Description  : 控制器基类
// |@----------------------------------------------------------------------
// |@FilePath     : Controller.php
// |@----------------------------------------------------------------------
// |@Copyright (c) 2023 http://www.ladmin.cn   All rights reserved. 
// ------------------------------------------------------------------------
declare (strict_types=1);

namespace think\admin;

use stdClass;
use think\App;
use think\Request;
use think\facade\Env;
use ReflectionMethod;
use think\helper\Str;
use think\exception\HttpResponseException;
use think\Response;

/**
 * 控制器基类
 * Class Controller
 * @package think\admin
 */
abstract class Controller extends stdClass
{
    /**
     * 应用容器
     * 用于存储应用容器实例，方便获取其他服务和配置等相关操作
     * @var App
     */
    protected App $app;

    /**
     * 请求对象
     * 保存当前请求的相关信息，如请求参数、请求方法等
     * @var Request
     */
    protected Request $request;

    /**
     * 控制器名称
     * 存储当前控制器的名称
     * @var string
     */
    protected string $controller;

    /**
     * 模块名称
     * 存储当前模块的名称
     * @var string
     */
    protected string $module;

    /**
     * 操作名称
     * 存储当前正在执行的操作（方法）名称
     * @var string
     */
    protected string $action;

    /**
     * 构造函数，初始化一些变量并执行自动验证逻辑
     *
     * @param App $app 服务容器实例，用于获取其他服务和配置信息
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;

        // 解析请求信息，获取控制器、模块和操作名称
        $this->controller = class_basename(static::class);
        $this->module     = $this->app->http->getName();
        $this->action     = $this->request->action();

        // 执行自动签名校验，根据请求参数和方法注释，自动进行签名校验
        $this->autoSign();

        // 执行自动验证，根据请求参数和方法注释，自动进行参数校验
        $this->autoValidate();
    }

    /**
     * 自动进行签名校验
     *
     * 如果请求方法的注释中存在 @sign true，则需要进行签名校验。
     * 签名校验需要提供 salt 参数，因此该方法也会自动校验用户是否已经登录。
     *
     * @return void
     */
    public function autoSign()
    {
        // 如果是 GET 请求，则不需要进行签名校验
        if ($this->request->isGet()) {
            return;
        }

        // 使用反射获取当前方法的类反射对象并获取当前方法的注释
        $reflectionMethod = $this->getReflectionMethod();
        $methodDocComment = $reflectionMethod->getDocComment();

        // 如果方法注释中不存在 @sign true，则不需要进行签名校验
        if ($methodDocComment === false || strpos($methodDocComment, '@sign true') === false) {
            return;
        }

        // 从请求中获取所有参数
        $param = $this->request->param();

        // 过滤掉空值字段，去除无效参数
        $param = array_filter($param, function ($value) {
            return $value!== "" && $value!== null;
        });

        // 如果不存在 sign 参数，则说明请求非法
        if (!array_key_exists('sign', $param)) {
            $this->error(PARAM_INVALID, lang('validate.missing_sign_parameter'));
        }

        // 从参数中删除 sign 参数
        unset($param['sign']);

        // 如果未登录，则要求重新登录
        if (!$this->request->user) {
            $this->error(SESSION_TIMEOUT, lang('validate.login_expired'));
        }

        // 添加 salt 参数，并对参数进行自然排序
        $param['salt'] = $this->request->user['salt']?? '';
        ksort($param, SORT_NATURAL);

        // 遍历参数，对数组和布尔型进行特殊处理
        foreach ($param as &$v) {
            if (is_array($v)) {
                $v = str_replace('\/', '/', json_encode($v, JSON_UNESCAPED_UNICODE));
            }
            if (is_bool($v)) {
                $v = $v? "true" : "false";
            }
        }

        // 将参数拼接成字符串，并计算签名
        $sign = md5(join(',', $param));
        $clientSign = $this->request->param('sign');

        // 检查客户端提供的签名是否正确
        if ($clientSign!== $sign) {
            $this->error(PARAM_INVALID, lang('validate.invalid_parameter'));
        }
    }

    /**
     * 自动验证当前请求参数，根据控制器方法中的注释信息进行参数校验
     *
     * 如果方法注释中不存在 @validate true，则说明该方法不需要进行自动参数验证；
     * 如果对应的验证器类不存在，则抛出 404 错误。
     * 如果请求参数校验不通过，则抛出 400 错误。
     * 如果方法注释中存在 @mixed validate true，将自动判断当前操作是否为新建（无 id 参数）或编辑（有 id 参数），并为验证器指定不同的验证场景；
     * 如果方法注释中存在 @password validate true，将自动判断当前操作是否为批量操作（无 password 参数）或单个操作（有 password 参数），并为验证器指定不同的验证场景；
     * 如果参数验证不通过，则会直接报错并终止当前请求。
     *
     * @return void
     * @throws \think\exception\HttpResponseException
     */
    protected function autoValidate()
    {
        // 如果是 GET 请求，则不需要进行参数校验
        if ($this->request->isGet()) {
            return;
        }

        // 使用反射获取当前方法的类反射对象并获取当前方法的注释
        $reflectionMethod = $this->getReflectionMethod();
        $methodDocComment = $reflectionMethod->getDocComment();

        // 如果方法注释不存在或注释中没有 @validate true，则说明该方法不需要进行自动参数校验
        if ($methodDocComment === false || strpos($methodDocComment, '@validate true') === false) {
            return;
        }

        // 根据控制器名称，自动解析验证器类名称，并判断类是否存在
        $param = $this->request->param();
        $validateClass = $this->app->parseClass('validate', $this->controller);
        if (!class_exists($validateClass)) {
            $this->error(NOT_EXISTS, lang('validate.class_not_exists', [$validateClass]));
        }

        // 实例化验证器类，根据方法注释中的 @mixed validate 和 @password validate 设置不同的校验场景
        $validate = $this->app->make($validateClass);
        $scene = ucfirst($this->action);
        if (str_contains($methodDocComment, '@mixed validate true')) {
            $scene = empty($param['id'])? "{$this->action}Add" : "{$this->action}Edit";
        }
        if (str_contains($methodDocComment, '@password validate true')) {
            $scene = empty($param['password'])? "{$this->action}Batch" : "{$this->action}";
        }
        if (!$validate->hasScene($scene)) {
            $this->error(NOT_EXISTS, lang('validate.scene_not_exists', [$validateClass, "scene{$scene}"]));
        }

        // 调用验证器对象的指定场景进行校验，如果校验不通过，则报错
        if (!$validate->scene($scene)->check($param)) {
            $this->error(EMPTY_PARAMS, $validate->getError());
        }
    }

    /**
     * 获取当前请求的反射方法
     *
     * @return ReflectionMethod
     */
    protected function getReflectionMethod(): ReflectionMethod
    {
        $controllerClass = "app\\{$this->module}\\controller\\". Str::studly($this->controller);
        return new ReflectionMethod($controllerClass, $this->action);
    }

    /**
     * 分页响应
     *
     * @param array $list 数据列表
     * @param int $total 总数
     * @param int $currentPage 当前页
     * @param int $pageSize 每页数量
     * @return Response
     */
    protected function paginate($list, $total, $currentPage, $pageSize)
    {
        $result = [
            'code' => 200,
            'message' => lang('common.success'),
            'data' => [
                'list' => $list,
                'total' => $total,
                'current_page' => $currentPage,
                'page_size' => $pageSize
            ]
        ];

        // 抛出包含 JSON 格式响应数据的 HttpResponseException 异常
        throw new HttpResponseException(json($result));
    }

    /**
     * 权限不足错误响应
     *
     * @param string $message 错误消息，默认为'权限不足'
     * @return Response
     */
    protected function permissionError(string $message = null)
    {
        if ($message === null) {
            $message = lang('common.insufficient_permissions');
        }

        $result = [
            'code' => 403,
            'message' => $message,
            'data' => []
        ];

        // 抛出包含 JSON 格式响应数据的 HttpResponseException 异常
        throw new HttpResponseException(json($result));
    }

    /**
     * 数据验证错误响应
     *
     * @param array $errors 错误信息
     * @param string $message 消息，默认为'数据验证失败'
     * @return Response
     */
    protected function validationError($errors, string $message = null)
    {
        if ($message === null) {
            $message = lang('common.data_validation_failed');
        }

        $result = [
            'code' => 422,
            'message' => $message,
            'data' => [
                'errors' => $errors
            ]
        ];

        // 抛出包含 JSON 格式响应数据的 HttpResponseException 异常
        throw new HttpResponseException(json($result));
    }

    /**
     * 处理异常
     *
     * @param \Exception $e
     * @return Response
     */
    protected function handleException(\Exception $e)
    {
        $message = $e->getMessage();
        $code = $e->getCode();

        if ($code < 100 || $code > 599) {
            $code = 500;
        }

        return $this->error($message, $code);
    }
    
    /**
     * 响应处理函数
     *
     * @param array|object $data 返回的数据，默认为空。如果不为对象且包含'code'键，将根据'code'的值进行不同的处理
     * @param string $message 响应的提示信息，默认为空。如果为空且需要根据'code'处理时，会有默认提示信息
     * @param int $code 响应的状态码，默认为SUCCESS常量。如果需要根据'code'处理时，会使用$data中的'code'值
     * @return void 此函数不直接返回值，而是根据$data中的'code'值调用相应的成功或错误处理函数
     */
    public function response(array|object $data = [], string $message = null, int $code = SUCCESS)
    {
        if (!is_object($data) && isset($data['code'])) {
            if ($data['code'] === SUCCESS) {
                $this->success($data['data'], $data['message'], $data['code']);
            } else {
                $this->error($data['code'], $data['message'], $data['data']);
            }
        } else {
            $this->success($data, $message, $code);
        }
    }

    /**
     * 成功的返回
     *
     * @param array $data 返回的数据
     * @param string $message 成功提示信息，默认为'操作成功'
     * @param int $code 返回码，默认为 0
     * @return Response 返回的响应对象
     */
    public function success(array|object $data = [], string $message = null, int $code = SUCCESS): Response
    {
        if ($message === null) {
            $message = lang('common.operation_successful');
        }
        
        $return = [
            'code' => $code,
            'message' => $message,
            'data' => $data
        ];

        // 抛出包含 JSON 格式响应数据的 HttpResponseException 异常
        throw new HttpResponseException(json($return));
    }

    /**
     * 错误的返回
     *
     * @param int $code 错误码
     * @param string $message 错误信息，默认为'操作失败'
     * @param array $data 错误数据
     * @return Response 返回的响应对象
     */
    public function error(int $code, string $message = null, array|object $data = []): Response
    {
        if ($message === null) {
            $message = lang('common.operation_failed');
        }

        $return = [
            'code' => $code,
            'message' => $message,
            'data' => $data
        ];

        // 抛出包含 JSON 格式响应数据的 HttpResponseException 异常
        throw new HttpResponseException(json($return));
    }

    /**
     * 魔术方法，当访问不存在的属性时会被调用。
     * 此方法主要用于在逻辑层中动态获取相关逻辑类的实例。
     *
     * @param string $name 尝试获取的属性名称。
     * @return mixed 如果成功找到并实例化对应的逻辑类则返回实例，否则返回 null 或者抛出异常。
     * @throws HttpResponseException 如果没有找到对应的逻辑类，则抛出包含错误信息的异常。
     */
    public function __get(string $name)
    {
        // 检查属性名是否以 'logic' 开头
        if (Str::startsWith($name, 'logic')) {
            // 优先查询 think\admin\logic 目录下的服务
            $class = "\\think\\admin\\logic\\" . Str::studly(str_replace('logic', '', $name));
            // 如果类存在，则通过服务容器获取实例
            if (class_exists($class)) {
                return app($class);
            }
            // 如果在 think\admin\logic 目录下不存在，则尝试在所有已注册命名空间下的 logic 目录查找服务类
            $class = $this->app->parseClass('logic', Str::studly(str_replace('logic', '', $name)));
            // 如果构建的类存在，则通过服务容器获取实例
            if (class_exists($class)) {
                return app($class);
            }
            // 未找到对应的类时，抛出异常，错误信息从语言包中获取
            throw new HttpResponseException(json([
                'code' => NOT_EXISTS,
                'message' => lang('common.layer_file_not_created', ['prefix' => 'logic', 'class' => Str::studly(str_replace('logic', '', $name))])
            ]));
        }

        return null;
    }
}