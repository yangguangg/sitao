<?php

namespace app\admin\model\users;

use think\Model;


class Sales extends Model
{

    

    

    // 表名
    protected $name = 'user_sales';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];
    

    







    public function goods()
    {
        return $this->belongsTo('app\admin\model\goods\Goods', 'goods_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function indexusers()
    {
        return $this->belongsTo('app\admin\model\users\Customer', 'c_user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
