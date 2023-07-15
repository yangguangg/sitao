<?php

namespace app\admin\model\users;

use think\Model;
use traits\model\SoftDelete;

class Address extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'user_address';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'is_checked_text',
        'is_has_elevator_text'
    ];
    

    
    public function getIsCheckedList()
    {
        return ['0' => __('Is_checked 0'), '1' => __('Is_checked 1')];
    }

    public function getIsHasElevatorList()
    {
        return ['0' => __('Is_has_elevator 0'), '1' => __('Is_has_elevator 1')];
    }


    public function getIsCheckedTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_checked']) ? $data['is_checked'] : '');
        $list = $this->getIsCheckedList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsHasElevatorTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_has_elevator']) ? $data['is_has_elevator'] : '');
        $list = $this->getIsHasElevatorList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function indexusers()
    {
        return $this->belongsTo('app\admin\model\users\Customer', 'c_user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
