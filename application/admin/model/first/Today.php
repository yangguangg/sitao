<?php

namespace app\admin\model\first;

use think\Model;


class Today extends Model
{

    

    

    // 表名
    protected $name = 'goods_order';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'is_pay_text',
        'is_use_ticket_text',
        'status_text',
        'arrive_time_text',
        'pay_type_text'
    ];
    

    
    public function getIsPayList()
    {
        return ['0' => __('Is_pay 0'), '1' => __('Is_pay 1')];
    }

    public function getIsUseTicketList()
    {
        return ['0' => __('Is_use_ticket 0'), '1' => __('Is_use_ticket 1')];
    }

    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3'), '4' => __('Status 4'), '5' => __('Status 5'), '6' => __('Status 6')];
    }

    public function getPayTypeList()
    {
        return ['0' => __('Pay_type 0'), '1' => __('Pay_type 1')];
    }


    public function getIsPayTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_pay']) ? $data['is_pay'] : '');
        $list = $this->getIsPayList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsUseTicketTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_use_ticket']) ? $data['is_use_ticket'] : '');
        $list = $this->getIsUseTicketList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getArriveTimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['arrive_time']) ? $data['arrive_time'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getPayTypeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['pay_type']) ? $data['pay_type'] : '');
        $list = $this->getPayTypeList();
        return isset($list[$value]) ? $list[$value] : '';
    }

    protected function setArriveTimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


}
