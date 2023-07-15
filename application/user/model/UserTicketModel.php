<?php
namespace app\user\model;

use think\Model;

class UserTicketModel extends Model
{
    protected $name = 'user_tickets';
    public function goods()
    {
        $field = 'id,title,image';
        return $this->belongsTo('GoodsModel','goods_id','id')->field($field);
    }
}