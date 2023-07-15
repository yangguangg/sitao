<?php

namespace app\admin\model\users;

use think\Model;

class Water extends Model
{
    // 表名
    protected $name = 'index_users';
    // 追加属性
    protected $append = [
        'vip_deadtime_text'
    ];
    public function getVipDeadtimeTextAttr($value, $data)
    {
        $value = $value ? $value : $data['vip_deadtime'];
        return date('Y-m-d H:i:s',$value);
    }
}
