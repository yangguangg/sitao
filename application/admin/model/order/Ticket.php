<?php

namespace app\admin\model\order;

use think\Model;


class Ticket extends Model
{

    

    

    // 表名
    protected $name = 'ticket_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'is_pay_text'
    ];
    

    
    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2')];
    }

    public function getIsPayList()
    {
        return ['0' => __('Is_pay 0'), '1' => __('Is_pay 1')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsPayTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_pay']) ? $data['is_pay'] : '');
        $list = $this->getIsPayList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function indexusers()
    {
        return $this->belongsTo('app\admin\model\users\Customer', 'c_user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function usercoupons()
    {
        return $this->belongsTo('app\admin\model\users\Coupons', 'coupons_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
