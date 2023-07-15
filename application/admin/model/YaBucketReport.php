<?php

namespace app\admin\model;

use think\Model;


class YaBucketReport extends Model
{

    

    

    // 表名
    protected $name = 'ya_bucket_report';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [

    ];










    public function indexusers()
    {
        return $this->belongsTo('app\admin\model\users\IndexUsers', 'index_users_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
    public function water()
    {
        return $this->belongsTo('app\admin\model\users\Water', 'water_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    public function bucketcate()
    {
        return $this->belongsTo('BucketCate', 'bucket_cate_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
