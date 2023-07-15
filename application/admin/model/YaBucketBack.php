<?php

namespace app\admin\model;

use think\Model;


class YaBucketBack extends Model
{

    

    

    // 表名
    protected $name = 'ya_bucket_back';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text',
        'confirmtime_text',
        'completetime_text'
    ];
    

    
    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2'), '3' => __('Status 3')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function getConfirmtimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['confirmtime']) ? $data['confirmtime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }


    public function getCompletetimeTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['completetime']) ? $data['completetime'] : '');
        return is_numeric($value) ? date("Y-m-d H:i:s", $value) : $value;
    }

    protected function setConfirmtimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }

    protected function setCompletetimeAttr($value)
    {
        return $value === '' ? null : ($value && !is_numeric($value) ? strtotime($value) : $value);
    }


    public function indexusers()
    {
        return $this->belongsTo('app\admin\model\users\IndexUsers', 'index_users_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function water()
    {
        return $this->belongsTo('app\admin\model\users\IndexUsers', 'water_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function bucketcate()
    {
        return $this->belongsTo('BucketCate', 'bucket_cate_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function yabucket()
    {
        return $this->belongsTo('YaBucket', 'order_sn', 'order_sn', [], 'LEFT')->setEagerlyType(0);
    }
}
