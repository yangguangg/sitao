<?php
namespace app\user\model;

use think\Model;

class GoodsOrderModel extends Model
{
    protected $name = 'goods_order';
    protected $append = ['user_status_text','water_status_text','end_time'];
    public function extend()
    {
        return $this->hasMany('GoodsOrderExtendModel','order_id','id');
    }
    public function getUserStatusTextAttr($value,$data)
    {
        $statusText = '';
        if(isset($data['status'])){
            $statusArr = ["待付款","待接单","待配送","配送中","待评价","已评价","已关闭"];
            $statusText = $statusArr[$data['status']];
        }
        return $statusText;
    }
    public function getEndTimeAttr($value,$data)
    {
        $endTime = '';
        if(isset($data['createtime'])){
            $endTime = date("Y-m-d H:i:s",strtotime($data['createtime'])+15*60);
        }
        return $endTime;
    }
    public function getWaterStatusTextAttr($value,$data)
    {
        $statusText = '';
        if(isset($data['status'])){
            $statusArr = ["待付款","待接单","待上门","待完成","已完成","已完成","已关闭"];
            $statusText = $statusArr[$data['status']];
        }
        return $statusText;
    }
    public function address()
    {
        return $this->belongsTo('AddressModel','address_id','id')->field('id,final_address,user_name,mobile,door');
    }
}