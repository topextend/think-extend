<?php
// ------------------------------------------------------------------------
// |@Author       : Jarmin <edshop@qq.com>
// |@----------------------------------------------------------------------
// |@Date         : 2024-09-25 06:37:04
// |@----------------------------------------------------------------------
// |@LastEditTime : 2024-09-25 12:13:45
// |@----------------------------------------------------------------------
// |@LastEditors  : Jarmin <jarmin@ladmin.cn>
// |@----------------------------------------------------------------------
// |@Description  : 跨域中间件
// |@----------------------------------------------------------------------
// |@FilePath     : Cors.php
// |@----------------------------------------------------------------------
// |@Copyright (c) 2024 http://www.ladmin.cn   All rights reserved. 
// ------------------------------------------------------------------------
declare (strict_types=1);

namespace think\admin\middleware;

use Closure;
use think\Config;
use think\Request;
use think\Response;
use think\facade\Route;

/**
 * 跨域中间件
 * Class Cors
 * @package think\admin\middleware
 */
class Cors
{
    /**
     * @var string[] 定义允许的路径数组
     */
    protected $paths = [];

    /**
     * @var string[] 定义允许的源地址数组
     */
    protected $allowedOrigins = [];

    /**
     * @var string[] 定义允许的源地址模式数组
     */
    protected $allowedOriginsPatterns = [];

    /**
     * @var string[] 定义允许的请求方法数组
     */
    protected $allowedMethods = [];

    /**
     * @var string[] 定义允许的请求头数组
     */
    protected $allowedHeaders = [];

    /**
     * @var string[] 定义暴露的响应头数组
     */
    private $exposedHeaders = [];

    /**
     * @var bool 是否支持凭证（如 Cookie ）
     */
    protected $supportsCredentials = false;

    /**
     * @var int 预检请求的最大缓存时间（秒）
     */
    protected $maxAge = 0;

    /**
     * @var bool 是否允许所有源地址
     */
    protected $allowAllOrigins = false;

    /**
     * @var bool 是否允许所有请求方法
     */
    protected $allowAllMethods = false;

    /**
     * @var bool 是否允许所有请求头
     */
    protected $allowAllHeaders = false;

    /**
     * 构造函数，从配置中获取 CORS 相关选项
     * @param Config $config 配置对象
     */
    public function __construct(Config $config)
    {
        $options = $config->get('cors', []);

        $this->paths = $options['paths']?? $this->paths;
        $this->allowedOrigins = $options['allowed_origins']?? $this->allowedOrigins;
        $this->allowedOriginsPatterns = $options['allowed_origins_patterns']?? $this->allowedOriginsPatterns;
        $this->allowedMethods = $options['allowed_methods']?? $this->allowedMethods;
        $this->allowedHeaders = $options['allowed_headers']?? $this->allowedHeaders;
        $this->exposedHeaders = $options['exposed_headers']?? $this->exposedHeaders;
        $this->supportsCredentials = $options['supports_credentials']?? $this->supportsCredentials;

        $maxAge = $this->maxAge;
        if (array_key_exists('max_age', $options)) {
            $maxAge = $options['max_age'];
        }
        $this->maxAge = $maxAge === null? null : (int) $maxAge;

        // 标准化处理：将请求头名称转换为小写，请求方法转换为大写
        $this->allowedHeaders = array_map('strtolower', $this->allowedHeaders);
        $this->allowedMethods = array_map('strtoupper', $this->allowedMethods);

        // 将 ['*'] 转换为布尔值 true
        $this->allowAllOrigins = in_array('*', $this->allowedOrigins);
        $this->allowAllHeaders = in_array('*', $this->allowedHeaders);
        $this->allowAllMethods = in_array('*', $this->allowedMethods);

        // 将通配符模式转换为正则表达式模式
        if (!$this->allowAllOrigins) {
            foreach ($this->allowedOrigins as $origin) {
                if (strpos($origin, '*')!== false) {
                    $this->allowedOriginsPatterns[] = $this->convertWildcardToPattern($origin);
                }
            }
        }
    }

    /**
     * 处理请求的中间件方法
     * @param Request $request 请求对象
     * @param Closure $next 下一个中间件或处理函数
     * @return Response 响应对象
     */
    public function handle($request, Closure $next)
    {
        // 如果请求路径不匹配，直接调用下一个处理函数
        if (!$this->hasMatchingPath($request)) {
            // 预置处理MISS全局路由
            Route::miss(function () use ($request) {
                if (!$request->isOptions()) {
                    return json(['code' => 404,'message' => lang('common.api_not_found')]);
                }
            });
            return $next($request);
        }

        // 如果是预检请求
        if ($this->isPreflightRequest($request)) {
            return $this->handlePreflightRequest($request);
        }

        // 调用下一个处理函数获取响应
        /** @var Response $response */
        $response = $next($request);

        // 为响应添加预检请求相关的头信息
        return $this->addPreflightRequestHeaders($response, $request);
    }

    /**
     * 为响应添加预检请求相关的头信息
     * @param Response $response 响应对象
     * @param Request $request 请求对象
     * @return Response 处理后的响应对象
     */
    protected function addPreflightRequestHeaders(Response $response, Request $request): Response
    {
        $this->configureAllowedOrigin($response, $request);

        if ($response->getHeader('Access-Control-Allow-Origin')) {
            $this->configureAllowCredentials($response, $request);
            $this->configureAllowedMethods($response, $request);
            $this->configureAllowedHeaders($response, $request);
            $this->configureExposedHeaders($response, $request);
            $this->configureMaxAge($response, $request);
        }

        return $response;
    }

    /**
     * 配置允许的源地址头信息
     * @param Response $response 响应对象
     * @param Request $request 请求对象
     */
    protected function configureAllowedOrigin(Response $response, Request $request): void
    {
        if ($this->allowAllOrigins === true &&!$this->supportsCredentials) {
            $response->header(['Access-Control-Allow-Origin' => '*']);
        } elseif ($this->isSingleOriginAllowed()) {
            $response->header(['Access-Control-Allow-Origin' => array_values($this->allowedOrigins)[0]]);
        } else {
            if ($this->isCorsRequest($request) && $this->isOriginAllowed($request)) {
                $response->header(['Access-Control-Allow-Origin' => (string) $request->header('Origin')]);
            }
        }
    }

    /**
     * 配置是否支持凭证头信息
     * @param Response $response 响应对象
     * @param Request $request 请求对象
     */
    protected function configureAllowCredentials(Response $response, Request $request): void
    {
        if ($this->supportsCredentials) {
            $response->header(['Access-Control-Allow-Credentials' => 'true']);
        }
    }

    /**
     * 配置允许的请求方法头信息
     * @param Response $response 响应对象
     * @param Request $request 请求对象
     */
    protected function configureAllowedMethods(Response $response, Request $request): void
    {
        if ($this->allowAllMethods === true) {
            $allowMethods = strtoupper((string) $request->header('Access-Control-Request-Method'));
        } else {
            $allowMethods = implode(', ', $this->allowedMethods);
        }

        $response->header(['Access-Control-Allow-Methods' => $allowMethods]);
    }

    /**
     * 配置允许的请求头头信息
     * @param Response $response 响应对象
     * @param Request $request 请求对象
     */
    protected function configureAllowedHeaders(Response $response, Request $request): void
    {
        if ($this->allowAllHeaders === true) {
            $allowHeaders = (string) $request->header('Access-Control-Request-Headers');
        } else {
            $allowHeaders = implode(', ', $this->allowedHeaders);
        }
        $response->header(['Access-Control-Allow-Headers' => $allowHeaders]);
    }

    /**
     * 配置暴露的响应头头信息
     * @param Response $response 响应对象
     * @param Request $request 请求对象
     */
    protected function configureExposedHeaders(Response $response, Request $request): void
    {
        if ($this->exposedHeaders) {
            $exposeHeaders = implode(', ', $this->exposedHeaders);
            $response->header(['Access-Control-Expose-Headers' => $exposeHeaders]);
        }
    }

    /**
     * 配置预检请求的最大缓存时间头信息
     * @param Response $response 响应对象
     * @param Request $request 请求对象
     */
    protected function configureMaxAge(Response $response, Request $request): void
    {
        if ($this->maxAge!== null) {
            $response->header(['Access-Control-Max-Age' => (string) $this->maxAge]);
        }
    }

    /**
     * 处理预检请求
     * @param Request $request 请求对象
     * @return Response 响应对象
     */
    protected function handlePreflightRequest(Request $request)
    {
        $response = response('', 204);

        return $this->addPreflightRequestHeaders($response, $request);
    }

    /**
     * 判断是否为 CORS 请求
     * @param Request $request 请求对象
     * @return bool 是否为 CORS 请求
     */
    protected function isCorsRequest(Request $request)
    {
        return!!$request->header('Origin');
    }

    /**
     * 判断是否为预检请求
     * @param Request $request 请求对象
     * @return bool 是否为预检请求
     */
    protected function isPreflightRequest(Request $request)
    {
        return $request->method() === 'OPTIONS' && $request->header('Access-Control-Request-Method');
    }

    /**
     * 判断是否只有一个允许的源地址
     * @return bool 是否只有一个允许的源地址
     */
    protected function isSingleOriginAllowed(): bool
    {
        if ($this->allowAllOrigins === true || count($this->allowedOriginsPatterns) > 0) {
            return false;
        }

        return count($this->allowedOrigins) === 1;
    }

    /**
     * 判断请求的源地址是否被允许
     * @param Request $request 请求对象
     * @return bool 请求的源地址是否被允许
     */
    protected function isOriginAllowed(Request $request): bool
    {
        if ($this->allowAllOrigins === true) {
            return true;
        }

        $origin = (string) $request->header('Origin');

        if (in_array($origin, $this->allowedOrigins)) {
            return true;
        }

        foreach ($this->allowedOriginsPatterns as $pattern) {
            if (preg_match($pattern, $origin)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 判断请求路径是否匹配
     * @param Request $request 请求对象
     * @return bool 请求路径是否匹配
     */
    protected function hasMatchingPath(Request $request)
    {
        $url = $request->pathInfo();
        $url = trim($url, '/');
        if ($url === '') {
            $url = '/';
        }

        $paths = $this->getPathsByHost($request->host(true));

        foreach ($paths as $path) {
            if ($path!== '/') {
                $path = trim($path, '/');
            }

            if ($path === $url) {
                return true;
            }

            $pattern = $this->convertWildcardToPattern($path);

            if (preg_match($pattern, $url) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * 根据主机获取允许的路径
     * @param string $host 主机名
     * @return array 允许的路径数组
     */
    protected function getPathsByHost($host)
    {
        $paths = $this->paths;

        if (isset($paths[$host])) {
            return $paths[$host];
        }

        return array_filter($paths, function ($path) {
            return is_string($path);
        });
    }

    /**
     * 将通配符模式转换为正则表达式模式
     * @param string $pattern 通配符模式
     * @return string 转换后的正则表达式模式
     */
    protected function convertWildcardToPattern($pattern)
    {
        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace('\*', '.*', $pattern);
        return '#^'. $pattern. '\z#u';
    }
}