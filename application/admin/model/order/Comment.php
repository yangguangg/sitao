<?php

namespace app\admin\model\order;

use think\Model;
use traits\model\SoftDelete;

class Comment extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'user_comment';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'level_status_text'
    ];
    

    
    public function getLevelStatusList()
    {
        return ['0' => __('Level_status 0'), '1' => __('Level_status 1'), '2' => __('Level_status 2')];
    }


    public function getLevelStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['level_status']) ? $data['level_status'] : '');
        $list = $this->getLevelStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function goods()
    {
        return $this->belongsTo('app\admin\model\goods\Goods', 'goods_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
