<?php
// ------------------------------------------------------------------------
// |@Author       : Jarmin <edshop@qq.com>
// |@----------------------------------------------------------------------
// |@Date         : 2023-06-07 15:48:29
// |@----------------------------------------------------------------------
// |@LastEditTime : 2024-09-27 22:48:26
// |@----------------------------------------------------------------------
// |@LastEditors  : Jarmin <jarmin@ladmin.cn>
// |@----------------------------------------------------------------------
// |@Description  : 权限中间件
// |@----------------------------------------------------------------------
// |@FilePath     : Auth.php
// |@----------------------------------------------------------------------
// |@Copyright (c) 2023 http://www.ladmin.cn   All rights reserved. 
// ------------------------------------------------------------------------
declare (strict_types=1);

namespace think\admin\middleware;

/**
 * 权限中间件
 * Class Auth
 * @package think\admin\middleware
 */
class Auth
{
    /**
    * API权限验证中间件
    * @param  \Illuminate\Http\Request  $request
    * @param  \Closure  $next
    * @return mixed
    */
    public function handle($request, \Closure $next)
    {
        // 获取请求头中的token
        $token = $request->header('token');
        // 如果没有获取到token，返回token过期错误
        if (!$token) return json(['code' => 401, 'message' => lang('validate.token_expired')], 401);
        // 从缓存中获取用户信息
        $user = cache(LOGIN_CACHE_KEY . $token);
        // 如果缓存中没有用户信息，返回未登录错误信息
        if (!$user) return json(['code' => 401, 'message' => lang('validate.login_expired')], 401);
        // 将用户信息绑定到请求对象中方便后续使用
        $request->user = $user;
        // 判断是否有操作权限，如果没有，返回权限不足的错误信息
        if($user['id'] != 1){
            $rule = $request->rule()->getRule();
            $type = $request->rule()->getMethod();
            $status = false;
            foreach ($user['api_auth'] as $v){
                if($rule == $v['url'] && $type == $v['type']) {
                    $status = true;
                    break;
                }
            }
            if(!$status){
                return json(['code' => 403, 'message' => lang('validate.no_permission_to_operate')], 403);
            }
        }

        // 继续执行后续请求处理逻辑
        return $next($request);
    }
}