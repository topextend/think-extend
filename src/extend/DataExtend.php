<?php
// ------------------------------------------------------------------------
// |@Author       : Jarmin <edshop@qq.com>
// |@----------------------------------------------------------------------
// |@Date         : 2023-06-06 14:53:53
// |@----------------------------------------------------------------------
// |@LastEditTime : 2023-06-06 14:53:55
// |@----------------------------------------------------------------------
// |@LastEditors  : Jarmin <jarmin@ladmin.cn>
// |@----------------------------------------------------------------------
// |@Description  : 数据处理扩展
// |@----------------------------------------------------------------------
// |@FilePath     : DataExtend.php
// |@----------------------------------------------------------------------
// |@Copyright (c) 2023 http://www.ladmin.cn   All rights reserved. 
// ------------------------------------------------------------------------
declare (strict_types=1);

namespace think\admin\extend;

/**
 * 数据处理扩展
 * Class DataExtend
 * @package think\admin\extend
 */
class DataExtend
{
    /**
     * 一维数组转多维数据树
     * @param array $its 待处理数据
     * @param string $cid 自己的主键
     * @param string $pid 上级的主键
     * @param string $sub 子数组名称
     * @return array
     */
    public static function arr2tree(array $its, string $cid = 'id', string $pid = 'pid', string $sub = 'children'): array
    {
        [$tree, $its] = [[], array_column($its, null, $cid)];
        foreach ($its as $it) isset($its[$it[$pid]]) ? $its[$it[$pid]][$sub][] = &$its[$it[$cid]] : $tree[] = &$its[$it[$cid]];
        return $tree;
    }

    /**
     * 过滤数组中的空值或假值
     * @param array $array 待过滤的数组
     * @return array 经过过滤后的数组
     */
    public static function arrfilterEmptyValues(array $array): array {
        foreach ($array as $key => &$value) {
            // 如果pid为0则不替换掉
            if ($key === 'pid' && $value === 0) {
                continue;
            }

            // 如果当前值为数组，则递归调用本函数进行进一步处理
            if (is_array($value)) {
                $value = self::arrfilterEmptyValues($value);

                // 去掉处理后的子数组中的空值或假值元素
                if (empty($value)) {
                    unset($array[$key]);
                }
            } elseif (empty($value) && $value !== 0 && $value !== '0') {
                // 如果当前值为字符串或数字，且为假值，则将其删除
                unset($array[$key]);
            }
        }

        return $array;
    }
}