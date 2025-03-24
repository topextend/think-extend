<?php
// ------------------------------------------------------------------------
// |@Author       : Jarmin <edshop@qq.com>
// |@----------------------------------------------------------------------
// |@Date         : 2023-06-05 16:01:32
// |@----------------------------------------------------------------------
// |@LastEditTime : 2024-09-27 22:36:56
// |@----------------------------------------------------------------------
// |@LastEditors  : Jarmin <jarmin@ladmin.cn>
// |@----------------------------------------------------------------------
// |@Description  : 常量管理扩展
// |@----------------------------------------------------------------------
// |@FilePath     : ConstExtend.php
// |@----------------------------------------------------------------------
// |@Copyright (c) 2023 http://www.ladmin.cn   All rights reserved. 
// ------------------------------------------------------------------------
namespace think\admin\extend;

/**
 * 常量管理扩展
 * Class ConstExtend
 * @package think\admin\extend
 */
class ConstExtend
{
    /**
     * 初始化
     */
    public static function init()
    {
        // 调用状态常量
        ConstExtend::statusConst();
        // 调用系统常量
        ConstExtend::sysConst();
    }
    
    /**
     * 状态常量
     */
    public static function statusConst()
    {
        // 成功
        define('SUCCESS', 200);
        // 失败
        define('ERROR', 201);
        // 无效
        define('INVALID', -1);
        // 数据库保存错误
        define('DB_SAVE_ERROR', -2);
        // 数据库读取错误
        define('DB_READ_ERROR', -3);
        // 缓存保存错误
        define('CACHE_SAVE_ERROR', -4);
        // 缓存读取错误
        define('CACHE_READ_ERROR', -5);
        // 文件保存错误
        define('FILE_SAVE_ERROR', -6);
        // 登录失败
        define('LOGIN_ERROR', -7);
        // 不存在
        define('NOT_EXISTS', -8);
        // 解析JSON数据失败
        define('JSON_PARSE_FAIL', -9);
        // 类型错误
        define('TYPE_ERROR', -10);
        // 数字匹配错误
        define('NUMBER_MATCH_ERROR', -11);
        // 缺少参数
        define('EMPTY_PARAMS', -12);
        // 数据已经存在
        define('DATA_EXISTS', -13);
        // 授权失败
        define('AUTH_ERROR', -14);
        // 其他登录
        define('OTHER_LOGIN', -16);
        // 版本不支持
        define('VERSION_INVALID', -17);
        // CURL错误
        define('CURL_ERROR', -18);
        // 记录未找到
        define('RECORD_NOT_FOUND', -19);
        // 删除失败
        define('DELETE_FAILED', -20);
        // 添加记录失败
        define('ADD_FAILED', -21);
        // 更新记录失败
        define('UPDATE_FAILED', -22);
        // 字符串匹配错误
        define('STRING_MATCH_ERROR', -23);
        // 参数无效
        define('PARAM_INVALID', -995);
        // 访问令牌过期
        define('ACCESS_TOKEN_TIMEOUT', -996);
        // 会话超时
        define('SESSION_TIMEOUT', -997);
        // 未知错误
        define('UNKNOWN', -998);
        // 异常错误
        define('EXCEPTION', -999);
    }

    /**
     * 系统常量
     */
    public static function sysConst()
    {
        // 用户登录缓存键
        define('LOGIN_CACHE_KEY', 'user_');
    }
}