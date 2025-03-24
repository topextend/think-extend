<?php
// ------------------------------------------------------------------------
// |@Author       : Jarmin <edshop@qq.com>
// |@----------------------------------------------------------------------
// |@Date         : 2022-06-30 19:18:20
// |@----------------------------------------------------------------------
// |@LastEditTime : 2024-09-28 08:01:00
// |@----------------------------------------------------------------------
// |@LastEditors  : Jarmin <jarmin@ladmin.cn>
// |@----------------------------------------------------------------------
// |@Description  : 公用函数
// |@----------------------------------------------------------------------
// |@FilePath     : Common.php
// |@----------------------------------------------------------------------
// |@Copyright (c) 2022 http://www.ladmin.cn   All rights reserved. 
// ------------------------------------------------------------------------
declare (strict_types = 1);

if (!function_exists('version')) {
    /**
     * 当前版本号的函数
     * @return string 返回当前应用的版本号，版本号为 1.3.0
     */
    function version()
    {
        return ['version' => '1.3.0'];
    }
}
if (!function_exists('calcu_expires')) {
    /**
     * 计算剩余过期时间的函数
     * @param int $time 缓存设置的初始时间戳（以秒为单位）
     * @param int $expires 缓存的过期时间长度（以秒为单位）
     * @return int 返回剩余的过期时间（以秒为单位），如果为负数则返回 0
     */
    function calcu_expires(int $time, int $expires)
    {
        $currentTime = time();
        $remainingTime = $time + $expires - $currentTime;
    
        if ($remainingTime < 0) {
            $remainingTime = 0;
        }
    
        return $remainingTime;
    }
}