<?php
namespace app\user\model;

use think\Model;

class KillGoodsModel extends Model
{
    protected $name = 'goods_seconds_kill';
    public function goods()
    {
        return $this->belongsTo('GoodsModel','goods_id','id')->field('id,title,image,images,sale_sums,original_price,is_intro,is_avoid,tag_ids');
    }
    public function detail()
    {
        return $this->belongsTo('GoodsModel','goods_id','id')->field('id,title,image,images,sale_sums,original_price,is_intro,is_avoid,tag_ids,goods_content');
    }
}