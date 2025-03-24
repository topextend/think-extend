<?php
// ------------------------------------------------------------------------
// |@Author       : Jarmin <edshop@qq.com>
// |@----------------------------------------------------------------------
// |@Date         : 2023-06-06 19:31:34
// |@----------------------------------------------------------------------
// |@LastEditTime : 2024-09-16 22:07:27
// |@----------------------------------------------------------------------
// |@LastEditors  : Jarmin <jarmin@ladmin.cn>
// |@----------------------------------------------------------------------
// |@Description  : 英文语言包，设置应用的英文语言规则
// |@----------------------------------------------------------------------
// |@FilePath     : en-us.php
// |@----------------------------------------------------------------------
// |@Copyright (c) 2023 http://www.ladmin.cn   All rights reserved. 
// ------------------------------------------------------------------------
return [
    'common'      =>    [
        'success'                          => 'Success',
        'fail'                             => 'Fail',
        'operation_successful'             => 'Operation successful',
        'operation_failed'                 => 'Operation failed',
        'data_validation_failed'           => 'Data validation failed',
        'insufficient_permissions'         => 'Insufficient permissions',
        'layer_file_not_created'           => ':prefix layer file :class not created',
        'api_not_found'                    => 'API interface does not exist',
    ],
    'validate'    =>    [
        'class_not_exists'                 => 'Class : %s does not exist',
        'scene_not_exists'                 => 'Scene : %s of class : %s does not exist',
        'missing_sign_parameter'           => 'Missing sign parameter',
        'invalid_parameter'                => 'Invalid parameter',
        'token_expired'                    => 'Token has expired',
        'login_expired'                    => 'Login has expired',
        'no_permission_to_operate'         => 'No permission to operate',
    ],
];