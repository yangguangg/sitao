<?php

namespace app\admin\model\water;

use think\Model;
use traits\model\SoftDelete;

class Worker extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'water_workers';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'is_checked_text'
    ];
    

    
    public function getIsCheckedList()
    {
        return ['0' => __('Is_checked 0'), '1' => __('Is_checked 1')];
    }


    public function getIsCheckedTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_checked']) ? $data['is_checked'] : '');
        $list = $this->getIsCheckedList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function indexusers()
    {
        return $this->belongsTo('app\admin\model\water\Users', 'b_user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
