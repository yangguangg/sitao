<?php
namespace app\user\model;

use think\Model;

class GoodsCartModel extends Model
{
    protected $name = 'goods_cart';
    public function goods()
    {
        $field = 'id,title,image,original_price,sale_price,tag_ids,is_avoid,is_intro';
        return $this->belongsTo('GoodsModel','goods_id','id')->field($field);
    }
}