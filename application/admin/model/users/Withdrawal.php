<?php

namespace app\admin\model\users;

use think\Model;


class Withdrawal extends Model
{

    

    

    // 表名
    protected $name = 'withdrawal_record';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'get_way_text'
    ];
    

    
    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2')];
    }

    public function getGetWayList()
    {
        return ['0' => __('Get_way 0'), '1' => __('Get_way 1')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getGetWayTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['get_way']) ? $data['get_way'] : '');
        $list = $this->getGetWayList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function indexusers()
    {
        return $this->belongsTo('app\admin\model\users\Customer', 'c_user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
