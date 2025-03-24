<?php
// ------------------------------------------------------------------------
// |@Author       : Jarmin <edshop@qq.com>
// |@----------------------------------------------------------------------
// |@Date         : 2023-06-04 14:52:24
// |@----------------------------------------------------------------------
// |@LastEditTime : 2024-10-03 02:53:54
// |@----------------------------------------------------------------------
// |@LastEditors  : Jarmin <jarmin@ladmin.cn>
// |@----------------------------------------------------------------------
// |@Description  : 模型基类
// |@----------------------------------------------------------------------
// |@FilePath     : Model.php
// |@----------------------------------------------------------------------
// |@Copyright (c) 2023 http://www.ladmin.cn   All rights reserved. 
// ------------------------------------------------------------------------
declare (strict_types=1);

namespace think\admin;

/**
 * 模型基类
 * Class Model
 * @package app\model
 */
class Model extends \think\Model
{
    /**
     * 获取单条数据
     * @param array $where 查询条件
     * @param mixed $fields 需要获取的字段
     * @return array 配置信息数组，如果未找到则返回空数组
     */
    public function getOne(array $where, string $fields = '*')
    {
        return $this->where($where)->field($fields)->find();
    }
 
    /**
     * 统计符合条件的数据数量的通用方法
     * @param array $where 统计条件数组
     * @return int 符合条件的数据数量
     */
    public function total(array $where)
    {
        return $this->where($where)->count();
    }

    /**
     * 获取数据列表，支持 where, whereIn, whereLike 条件
     * @param array $where 查询条件
     * @param mixed $fields 需要获取的字段
     * @param string $order 排序
     * @param int $page 分页
     * @param int $pageSize 分页大小
     * @return \think\Collection|array  返回的数据列表
     */
    public function getList(array $where = [], string $fields = '*', string $order = '', int $page = 0, int $pageSize = 20)
    {
        $query = $this->where(function ($query) use ($where) {
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    // 如果值是数组，使用 whereIn
                    $query->whereIn($key, $value);
                } elseif (strpos($value, '%') !== false) {
                    // 如果值中包含百分号，使用 whereLike
                    $query->whereLike($key, $value);
                } else {
                    // 普通条件
                    $query->where($key, $value);
                }
            }
        });

        if ($order) {
            $query->order($order);
        }
        if ($page && $pageSize) {
            $paginate = $query->paginate([
                'pageSize' => $pageSize,
                'page' => $page,
            ]);
            $data = [
                'total' => $paginate->total(),
                'page' => $paginate->currentPage(),
                'pageSize' => $paginate->listRows(),
                'rows' => $paginate->items()
            ];

            return $data;
        } else {
            return $query->select();
        }
    }

    /**
     * 获取符合条件的多个字段值数组
     * @param array $where 查询条件数组
     * @param string $field 要返回的字段名，默认为 'id'
     * @return array 查询结果的字段值数组
     */
    public function getColumn(array $where, string $field = 'id')
    {
        return $this->where(function ($query) use ($where) {
            foreach ($where as $key => $value) {
                if (is_array($value)) {
                    // 如果值是数组，使用 whereIn
                    $query->whereIn($key, $value);
                } else {
                    // 普通条件
                    $query->where($key, $value);
                }
            }
        })->column($field);
    }

    /**
     * 获取配置的值
     * @param array $where 条件数组，用于指定要获取的配置
     * @return mixed 配置的值，如果配置信息不存在则返回空数组
     */
    public function getConfigValue(array $where)
    {
        return ($config_info = $this->getOne($where)) &&!empty($config_info)? $config_info['value'] : [];
    }

    /**
     * 设置配置信息
     * @param array $where 条件数组，用于指定要操作的记录
     * @param array $data 包含配置数据的数组
     * @return bool|int 操作结果，成功时返回影响的行数或插入的自增主键，失败时返回 false
     */
    public function setConfig(array $where, array $data)
    {
        $info = $this->getOne($where);
        if (empty($info)) {
            $data = array_merge($where, $data);
            $data[ 'create_time' ] = time();
            $res = $this->create($data);
        } else {
            $data[ 'update_time' ] = time();
            $res = $this->where($where)->save($data);
        }
        return $res;
    }

    /**
     * 更新 JSON 字段中的特定键值
     * @param array $where 条件数组，用于指定要更新的记录
     * @param string $key JSON 字段中的键
     * @param string $value 要设置的新值
     * @return bool 是否更新成功
     */
    public function updateJsonValue(array $where, string $key, string $value)
    {
        // 先获取符合条件的记录
        $record = $this->where($where)->find();
        if ($record) {
            // 获取原始的 JSON 数据
            $jsonData = $record->getData('value');
            // 解析 JSON 数据为数组
            $jsonArray = json_decode($jsonData, true);
            // 更新指定键的值
            $jsonArray[$key] = $value;
            // 将更新后的数组转换回 JSON 字符串
            $updatedJson = json_encode($jsonArray);
            // 更新数据库中的记录
            $record->value = $updatedJson;
            return $record->save();
        }
        return false;
    }
    
    /**
     * 保存数据并返回自增 ID
     * @param array $data 要保存的数据
     * @param boolean $returnId 是否返回自增 ID，默认 false
     * @return mixed 保存成功返回自增 ID 或操作结果，失败返回 false
     */
    public function saveData(array $data, array $where = [])
    {
        if (!empty($where)) {
            // 如果有条件，进行更新操作
            $result = $this->where($where)->update($data);
            if ($result) {
                return $result;
            }
        } else {
            // 确保插入时不包含主键字段
            if (isset($data[$this->pk])) {
                unset($data[$this->pk]);
            }
            // 无条件，则插入数据
            $this->save($data);
            return $this->id;
        }
    }

    /**
     * 通用删除数据方法，智能处理普通条件和 whereIn 条件
     * @param array $conditions 删除的条件，支持普通条件和 whereIn 方式
     * @return int 删除的记录数
     * @throws \Exception
     */
    public function deleteData(array $where = [])
    {
        try {
            $query = $this->where(function ($query) use ($where) {
                foreach ($where as $key => $value) {
                    if (is_array($value)) {
                        // 使用 whereIn 处理数组条件
                        $query->whereIn($key, $value);
                    } else {
                        // 普通 where 条件
                        $query->where($key, $value);
                    }
                }
            });
            return $query->delete();
        } catch (Exception $e) {
            // 记录错误日志或进行其他错误处理
            // 这里可以根据您的需求添加具体的错误处理逻辑，比如返回特定的错误码或消息
            return false;
        }
    }
}