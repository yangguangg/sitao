<?php

namespace app\user\controller;

use app\admin\model\users\Vip;
use app\admin\model\YaBucket;
use app\common\controller\Publics;
use Exception;
use think\Db;

class Notify extends Publics
{
    /**
     * 购买桶--回调
     */
    public function bought_barrel_notify()
    {
        $xml = file_get_contents("php://input");
        file_put_contents(PHP_EOL . 'wx_pay_notify_barrel.txt', $xml, FILE_APPEND);
        $jsonxml = json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA));
        $result = json_decode($jsonxml, true);//转成数组，
        if ($result) {
            //如果成功返回了
            $outTradeNo = $result['out_trade_no'];
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                $orderObj = Db::name('barrel_order');
                $where['order_sn'] = $outTradeNo;
                $orderInfo = $orderObj->where($where)->find();
                if ($orderInfo["is_pay"] == 1) {
                    echo '<xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>';
                    exit("已完成支付!");
                }
                Db::startTrans();
                try {
                    //更新订单状态
                    $data = [
                        'status' => 1,
                        'pay_time' => date('Y-m-d H:i:s'),
                        "pay_type" => 0,
                        "is_pay" => 1
                    ];
                    $orderObj->where($where)->update($data);
                    //个人账户增加桶的数量
                    $barrelNums = $orderInfo['barrel_nums'];
                    $userId = $orderInfo['c_user_id'];
                    Db::name('index_users')->where('id', $userId)->setInc('unused_barrel', $barrelNums);
                    Db::commit();
                } catch (Exception $e) {
                    file_put_contents(PHP_EOL . 'wx_pay_barrel_error.txt', $e->getMessage(), FILE_APPEND);
                    Db::rollback();
                }
            }
        }
    }

    /**
     * 购买水票--回调
     */
    public function bought_ticket_notify()
    {
        $xml = file_get_contents("php://input");
        file_put_contents(PHP_EOL . 'wx_pay_notify_ticket.txt', $xml, FILE_APPEND);
        $jsonxml = json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA));
        $result = json_decode($jsonxml, true);//转成数组，
        if ($result) {
            //如果成功返回了
            $outTradeNo = $result['out_trade_no'];
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                $where['order_sn'] = $outTradeNo;
                $orderInfo = Db::name('ticket_order')->where($where)->find();
                if ($orderInfo["is_pay"] == 1) {
                    echo '<xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>';
                    exit("已完成支付!");
                }
                Db::startTrans();
                try {
                    //01.更新订单状态
                    $data = [
                        'status' => 1,
                        'pay_time' => date('Y-m-d H:i:s'),
                        "is_pay" => 1
                    ];
                    Db::name('ticket_order')->where($where)->update($data);
                    //02.增加用户水票余额
                    $gettingNums = $orderInfo['getting_nums'];
                    $where = [
                        'c_user_id' => $orderInfo['c_user_id'],
                        'goods_id' => $orderInfo['goods_id']
                    ];
                    $record = Db::name('user_tickets')->where($where)->find();
                    if ($record) {
                        Db::name('user_tickets')->where($where)->setInc('unused_tickets', $gettingNums);
                        Db::name('user_tickets')->where($where)->update(['updatetime' => date('Y-m-d H:i:s')]);
                    } else {
                        $data = [
                            'goods_id' => $orderInfo['goods_id'],
                            'unused_tickets' => $gettingNums,
                            'c_user_id' => $orderInfo['c_user_id'],
                            'updatetime' => date('Y-m-d H:i:s')
                        ];
                        Db::name('user_tickets')->strict(false)->insert($data);
                    }
                    Db::commit();
                } catch (Exception $e) {
                    file_put_contents(PHP_EOL . 'wx_pay_ticket_error.txt', $e->getMessage(), FILE_APPEND);
                    Db::rollback();
                }
                //03.分佣，赠送优惠券
                check_is_first($orderInfo['c_user_id'], $orderInfo['amount']);
            }
        }
    }

    /**
     * 商品--支付--回调
     */
    public function goods_pay_notify()
    {
        $xml = file_get_contents("php://input");
        file_put_contents(PHP_EOL . 'wx_pay_notify_ticket.txt', $xml, FILE_APPEND);
        $jsonxml = json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA));
        $result = json_decode($jsonxml, true);//转成数组，
        if ($result) {
            //如果成功返回了
            $outTradeNo = $result['out_trade_no'];
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                $orderObj = Db::name('goods_order');
                $where['order_sn'] = $outTradeNo;
                $orderInfo = $orderObj->where($where)->find();
                if ($orderInfo["is_pay"] == 1) {
                    echo '<xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>';
                    exit("已完成支付!");
                }
                Db::startTrans();
                try {
                    //01.更新订单状态
                    $data = [
                        'status' => 1,
                        'pay_time' => date('Y-m-d H:i:s'),
                        "pay_type" => 0,
                        "is_pay" => 1
                    ];
                    $orderObj->where($where)->update($data);
                    $list = Db::name('goods_order_extend')->where('order_id', $orderInfo['id'])->select();
                    foreach ($list as $v) {
                        if ($orderInfo['type'] == 0) {
                            //02.商品表
                            //增加销量
                            Db::name('goods')->where('id', $v['goods_id'])->setInc('sale_sums', $v['goods_nums']);
                            //减少库存
                            // $where = [
                            //     'b_user_id' => $orderInfo['b_user_id'],
                            //     'goods_id' => $v['goods_id']
                            // ];
                            // Db::name("b_goods")
                            //     ->where($where)
                            //     ->setDec('goods_nums', $v['goods_nums']);
                        }
                        if ($orderInfo['type'] == 2) {
                            // $where = [
                            //     'b_user_id' => $orderInfo['b_user_id'],
                            //     'goods_id' => $v['goods_id']
                            // ];
                            // Db::name("b_goods")
                            //     ->where($where)
                            //     ->setDec('goods_nums', $v['goods_nums']);
                            Db::name('goods_seconds_kill')->where('id', $v['kill_goods_id'])->setDec('nums', $v['goods_nums']);
                        }
                    }
                    //05发送模版消息
                    send_msg_to_station($orderInfo['createtime'], $orderInfo['b_user_id'], $orderInfo['order_sn'], $orderInfo['address_id'], $orderInfo['arrive_time']);
                    Db::commit();
                } catch (Exception $e) {
                    file_put_contents(PHP_EOL . 'wx_pay_ticket_error.txt', $e->getMessage(), FILE_APPEND);
                    Db::rollback();
                }
                //04.分佣，赠送优惠券
                check_is_first($orderInfo['c_user_id'], $orderInfo['sum_money']);
            }
        }
    }
    /**
     * 发送模版消息
     */

    /**
     * vip购买回调
     */
    public function vip_notify()
    {
        $xml = file_get_contents("php://input");
        file_put_contents(PHP_EOL . 'wx_pay_notify_vip.txt', $xml, FILE_APPEND);
        $jsonxml = json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA));
        $result = json_decode($jsonxml, true);//转成数组，
        if ($result) {
            //如果成功返回了
            $outTradeNo = $result['out_trade_no'];
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                Db::startTrans();
                try {
                    $model = new Vip();
                    $r = $model->payorder($outTradeNo);
                    if ($r['code'] != 1) {
                        throw new  \think\Exception($r['msg']);
                    }
                    Db::commit();
                    echo '<xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>';
                    exit("已完成支付!");
                } catch (Exception $e) {
                    file_put_contents(PHP_EOL . 'wx_pay_vip_error.txt', $e->getMessage(), FILE_APPEND);
                    Db::rollback();
                }
            }
        }
    }

     /**
     * 押桶回调
     */
    public function ya_notify()
    {
        $xml = file_get_contents("php://input");
        file_put_contents(PHP_EOL . 'wx_pay_notify_ya.txt', $xml, FILE_APPEND);
        $jsonxml = json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA));
        $result = json_decode($jsonxml, true);//转成数组，
        // file_put_contents(PHP_EOL . 'wx_pay_notify_ya.txt', jsone_encode($result), FILE_APPEND);
        if ($result) {
            //如果成功返回了
            $outTradeNo = $result['out_trade_no'];
            file_put_contents(PHP_EOL . 'wx_pay_notify_ya_r.txt', json_encode($result), FILE_APPEND);
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                Db::startTrans();
                try {
                    file_put_contents(PHP_EOL . 'wx_pay_notify_ya_r.txt', '---------------------------------', FILE_APPEND);
                    $model = new YaBucket();
                    file_put_contents(PHP_EOL . 'wx_pay_notify_ya_r.txt', '--------111------------', FILE_APPEND);
                    $r = $model->pay_ya($outTradeNo);
                    file_put_contents(PHP_EOL . 'wx_pay_notify_ya_r.txt', '--------222------------', FILE_APPEND);
                    file_put_contents(PHP_EOL . 'wx_pay_notify_ya_r.txt', json_encode($r), FILE_APPEND);
                    if ($r['code'] != 200) {
                        throw new  \think\Exception($r['msg']);
                    }
                    Db::commit();
                    echo '<xml> <return_code><![CDATA[SUCCESS]]></return_code> <return_msg><![CDATA[OK]]></return_msg> </xml>';
                    exit("已完成支付!");
                } catch (Exception $e) {
                    file_put_contents(PHP_EOL . 'wx_pay_ya_error.txt', $e->getMessage(), FILE_APPEND);
                    Db::rollback();
                }
            }
        }
    }
}