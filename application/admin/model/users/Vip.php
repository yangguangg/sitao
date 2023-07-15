<?php

namespace app\admin\model\users;

use app\admin\model\goods\Tickets;
use app\admin\model\water\Users;
use think\Db;
use think\Exception;
use think\Model;


class Vip extends Model
{


    // 表名
    protected $name = 'vip';

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    // 追加属性
    protected $append = [
        'status_text'
    ];


    public function getStatusList()
    {
        return ['1' => __('Status 1'), '2' => __('Status 2')];
    }


    public function getStatusTextAttr($value, $data)
    {
        $value = $value ? $value : (isset($data['status']) ? $data['status'] : '');
        $list = $this->getStatusList();
        return isset($list[$value]) ? $list[$value] : '';
    }


    public function indexusers()
    {
        return $this->belongsTo('app\admin\model\users\IndexUsers', 'user_id', 'id', [], 'LEFT')->setEagerlyType(0);
    }


    /**
     * 订单生成
     * @param $user_id
     * @return array
     */
    public function createorder($user_id)
    {
        $userinfo = (new Users())->where(['id'=>$user_id])->find();
        $price = config('site.vip_price');
        $days = config('site.vip_days');
        $now = time();
        if(empty($userinfo) || $userinfo['vip_deadtime'] > $now){
            return [
                'code' => 0,
                'msg' => '您的会员未到期，请勿重复升级',
            ];
        }
        $data = [
            'order_sn' => date('YmdHis') . mt_rand(1000, 9999),
            'user_id' => $user_id,
            'price' => $price,
            'days' => $days,
            'status' => 1,
            'createtime' => $now,
            'updatetime' => $now,
        ];
        $r = self::insert($data);
        if (!$r) {
            return [
                'code' => 0,
                'msg' => '订单生成失败',
            ];
        } else {
            return [
                'code' => 1,
                'msg' => '生成成功，去支付',
                'data' => $data,
            ];
        }
    }

    /**
     * 支付回调
     * @param $user_id
     * @return array
     */
    public function payorder($order_sn)
    {
        $info = self::where(['order_sn' => $order_sn])->find();

        if (empty($info)) {
            return [
                'code' => 0,
                'msg' => '未查询到该订单'
            ];
        }
        if ($info['status'] != 1) {
            return [
                'code' => '0',
                'msg' => '该订单已处理过',
            ];
        }
        $now = time();
        $data = [
            'status' => 2,
            'updatetime' => $now,
        ];
        Db::startTrans();
        try {
            $r = self::where(['order_sn' => $order_sn])->update($data);

            if (!$r) {
                throw new Exception('周期处理失败');
            }
            //处理会员的周期
            $model = new IndexUsers();
            $user = $model->where(['id' => $info['user_id']])->find();
            if (empty($user)) {
                throw new Exception('未查询到用户信息');
            }
            $tmp_time = $now > $user['vip_deadtime'] ? $now : $user['vip_deadtime'];
            $end_time = $tmp_time + ($info['days'] * 24 * 60 * 60);
            $r = $user->save([
                'vip' => 2,
                'vip_deadtime' => $end_time,
            ]);
            if (!$r) {
                throw new Exception('周期处理失败');
            }

            //赠送优惠券
            $vipModel = new \app\admin\model\indexs\Coupons();
            $vipModel->give_coupon($info['user_id']);
            //送水票
                $tickModel = new Tickets();
                $tickModel->vip_ticket($info['user_id']);
            //赠送水桶
        } catch (Exception $ee) {
            Db::rollback();

            return [
                'code' => '0',
                'msg' => $ee->getMessage(),
            ];
        }
        Db::commit();
        return [
            'code' => '1',
            'msg' => '回调成功',
        ];

    }
}
