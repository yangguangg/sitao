<?php

namespace app\admin\model\order;

use think\Db;
use think\Model;


class Goods extends Model
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
    public function getTypeList()
    {
        //0=商品订单,1=一键送水,2=秒杀订单
        return [0=>__('Type 0'),1=>__('Type 1'),2=>__('Type 2')];
    }

    public function getPayTypeList()
    {
        return ['0' => __('Pay_type 0'), '1' => __('Pay_type 1')];
    }
    public function getStationList()
    {
        $stationList = Db::name("index_users")
            ->field('id,b_name')
            ->where("user_type",1)
            ->where('status',1)
            ->select();
        return $stationList;
    }
    public function getStationsList()
    {
        $stationList = Db::name("index_users")
            ->where("user_type",1)
            ->where('status',1)
            ->column('b_name','id');
        return $stationList;
    }
    public function getWorkerList($stationId)
    {
        $workerList = Db::name("water_workers")
            ->field("id,worker_name")
            ->where('b_user_id',$stationId)
            ->where('deletetime',null)
            ->select();
        return $workerList;
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


    public function useraddress()
    {
        return $this->belongsTo('app\admin\model\users\Address', 'address_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function indexusers()
    {
        return $this->belongsTo('app\admin\model\water\Users', 'b_user_id', 'id', [], 'LEFT')->setEagerlyType(0)->bind('b_name');
    }


    public function usercoupons()
    {
        return $this->belongsTo('app\admin\model\users\Coupons', 'coupons_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
