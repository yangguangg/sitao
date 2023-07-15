<?php

namespace app\admin\model\first;

use think\Db;
use think\Model;
use traits\model\SoftDelete;

class GoodsAdd extends Model
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
        'status_text',
        'goods_nums'
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
    public function getGoodsNumsAttr($value,$data)
    {
        $goodsNum = 0;
        if(isset($data['goods_id']) && isset($data['b_user_id'])){
            $where = [
                'b_user_id' => $data['b_user_id'],
                'goods_id' => $data['goods_id']
            ];
            $record = Db::name("b_goods")
                ->where($where)
                ->find();
            if($record){
                $goodsNum = $record['goods_nums'];
            }
        }
        return $goodsNum;
    }

    public function goods()
    {
        return $this->belongsTo('app\admin\model\goods\Goods', 'goods_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }
}
