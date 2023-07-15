<?php
namespace app\user\model;

use think\Model;

class TicketOrderModel extends Model
{
    protected $name = 'ticket_order';
    public $append = ['status_text','end_time'];
    public function goods()
    {
        $field = 'id,title,image';
        return $this->belongsTo('GoodsModel','goods_id','id')->field($field);
    }
    public function getStatusTextAttr($value,$data)
    {
        $statusText = '';
        if(isset($data['status'])){
            $statusArr = ['0'=>"待付款",'1'=>"已完成",'2'=>"已关闭"];
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
}