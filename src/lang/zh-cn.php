<?php
// ------------------------------------------------------------------------
// |@Author       : Jarmin <edshop@qq.com>
// |@----------------------------------------------------------------------
// |@Date         : 2023-06-24 15:40:35
// |@----------------------------------------------------------------------
// |@LastEditTime : 2024-09-28 00:56:22
// |@----------------------------------------------------------------------
// |@LastEditors  : Jarmin <jarmin@ladmin.cn>
// |@----------------------------------------------------------------------
// |@Description  : 中文语言包，设置应用的中文语言规则
// |@----------------------------------------------------------------------
// |@FilePath     : zh-cn.php
// |@----------------------------------------------------------------------
// |@Copyright (c) 2023 http://www.ladmin.cn   All rights reserved. 
// ------------------------------------------------------------------------
return [
    'common'      =>    [
        'success'                          => '成功',
        'fail'                             => '失败',
        'operation_successful'             => '操作成功',
        'operation_failed'                 => '操作失败',
        'data_validation_failed'           => '数据验证失败',
        'insufficient_permissions'         => '权限不足',
        'layer_file_not_created'           => '{:prefix} 层文件 {:class} 未创建',
        'api_not_found'                    => 'API接口不存在',
    ],
    'validate'    =>    [
        'class_not_exists'                 => '验证器 %s 不存在',
        'scene_not_exists'                 => '验证器 %s 中不存在 %s 验证场景，请检查',
        'missing_sign_parameter'           => '缺少签名参数',
        'invalid_parameter'                => '非法参数提交',
        'token_expired'                    => 'token已过期',
        'login_expired'                    => '登录已过期',
        'no_permission_to_operate'         => '没有权限操作',
    ],
];