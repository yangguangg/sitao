<?php

namespace app\admin\model\indexs;

use app\admin\model\first\UserCoupons;
use think\Db;
use think\Model;
use traits\model\SoftDelete;

class Coupons extends Model
{

    use SoftDelete;

    

    // 表名
    protected $name = 'coupons';
    
    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = false;
    protected $deleteTime = 'deletetime';

    // 追加属性
    protected $append = [
        'status_text',
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


    /**
     * 赠送优惠券
     * @param $user_id
     */
    public function give_coupon($user_id){
        $ids = Db::name('user_coupons')->where(['c_user_id' => $user_id])->column('id');

        $model = new self();
        $where = [
            'status' => 3,//赠券
        ];
        if(count($ids) > 0){
            $where['id'] =['not in',$ids];
        }
        $list = $model->where($where)->select();
        if(count($list) >0){
            foreach($list as $vo){
                $data = [
                    'c_user_id' => $user_id,
                    'coupons_value' => $vo['coupons_value'],
                    'threshold_price' => $vo['threshold_price'],
                    'createtime' => date('Y-m-d H:i:s'),
                    'end_time' => date('Y-m-d',strtotime('+'.config('site.vip_coupon_days').' days')),
                    'status' => 0,
                    'coupons_id' => $vo['id'],
                    'type' => 2,
                ];
                Db::name('user_coupons')->insert($data);
            }
        }
    }





}
