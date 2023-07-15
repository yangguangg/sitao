<?php

namespace app\admin\model\first;

use think\Model;
use traits\model\SoftDelete;

class Add extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'b_goods_add';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text'
    ];
    

    
    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1'), '2' => __('Status 2')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function indexusers()
    {
        return $this->belongsTo('app\admin\model\water\Users', 'b_user_id', 'id', [], 'LEFT')->field('id,mobile,b_name,head_img,true_name');
    }
}
