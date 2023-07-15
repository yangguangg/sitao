<?php

namespace app\admin\model\water;

use think\Model;


class Add extends Model
{

    

    

    // 表名
    protected $name = 'water_add';
    
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
        return $this->belongsTo('app\admin\model\water\Users', 'b_user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
