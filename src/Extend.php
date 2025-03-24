<?php
// ------------------------------------------------------------------------
// |@Author       : Jarmin <edshop@qq.com>
// |@----------------------------------------------------------------------
// |@Date         : 2022-06-30 19:21:46
// |@----------------------------------------------------------------------
// |@LastEditTime : 2024-09-27 08:40:54
// |@----------------------------------------------------------------------
// |@LastEditors  : Jarmin <jarmin@ladmin.cn>
// |@----------------------------------------------------------------------
// |@Description  : 模块注册服务
// |@----------------------------------------------------------------------
// |@FilePath     : Extend.php
// |@----------------------------------------------------------------------
// |@Copyright (c) 2022 http://www.ladmin.cn   All rights reserved. 
// ------------------------------------------------------------------------
declare (strict_types = 1);

namespace think\admin;

use think\facade\Route;
use think\admin\listener\system;
use think\admin\extend\ConstExtend;
use think\middleware\LoadLangPack;
use think\admin\middleware\MultiApp;
use think\admin\middleware\Cors;
use think\Service;
use think\Request;
use think\Response;

/**
 * 模块注册服务
 * Class Extend
 * @package think\admin
 */
class Extend extends Service
{
    public function register()
    {
        // 加载系统语言包
        $this->app->lang->load(__DIR__ . '/lang/zh-cn.php', 'zh-cn');
        $this->app->lang->load(__DIR__ . '/lang/en-us.php', 'en-us');
        // 注册定义常量
        ConstExtend::init();
    }

    /**
     * 启动服务
     */
    public function boot()
    {
        // 请求初始化处理事件监听器注册
        $this->app->event->listen('HttpRun', function (Request $request) {
            // 注册多应用中间键
            $this->app->middleware->add(MultiApp::class);
            // 注册跨域中间键
            $this->app->middleware->add(Cors::class);
            // 注册语言包中间键
            $this->app->middleware->add(LoadLangPack::class);
        });
        // 请求结束后处理事件监听器注册
        $this->app->event->listen('HttpEnd', function (Request $request) {
            //可用于添加请求结束后的资源清理、日志记录等相关逻辑
        });
    }
}