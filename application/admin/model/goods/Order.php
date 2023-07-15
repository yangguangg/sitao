<?php

namespace app\admin\model\goods;

use think\Model;


class Order extends Model
{

    

    

    // 表名
    protected $name = 'goods_order_extend';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = false;

    // 定义时间戳字段名
    protected $createTime = false;
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







    public function goodsorder()
    {
        return $this->belongsTo('app\admin\model\GoodsOrder', 'order_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
