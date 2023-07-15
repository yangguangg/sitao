<?php

namespace app\admin\model\goods;

use think\Model;
use traits\model\SoftDelete;

class Goods extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'goods';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text',
        'is_intro_text',
        'is_avoid_text'
    ];
    

    
    public function getStatusList()
    {
        return ['0' => __('Status 0'), '1' => __('Status 1')];
    }

    public function getIsIntroList()
    {
        return ['0' => __('Is_intro 0'), '1' => __('Is_intro 1')];
    }

    public function getIsAvoidList()
    {
        return ['0' => __('Is_avoid 0'), '1' => __('Is_avoid 1')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsIntroTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_intro']) ? $data['is_intro'] : '');
        $list = $this->getIsIntroList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getIsAvoidTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['is_avoid']) ? $data['is_avoid'] : '');
        $list = $this->getIsAvoidList();
        return isset($list[$value]) ? $list[$value] : '';
    }




    public function goodsbrands()
    {
        return $this->belongsTo('app\admin\model\goods\Brands', 'brand_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function goodscates()
    {
        return $this->belongsTo('app\admin\model\goods\Cates', 'cate_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function goodstags()
    {
        return $this->belongsTo('app\admin\model\goods\Tags', 'tag_ids', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
