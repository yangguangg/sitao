<?php
namespace app\user\action;

use addons\epay\library\Service;
use app\admin\model\YaBucket;
use app\user\model\GoodsCartModel;
use app\user\model\GoodsModel;
use Exception;
use think\cache\driver\Redis as DriverRedis;
use think\Controller;
use think\Db;
use think\Log;
use think\Validate;

class PayAction extends Controller
{
    private function pay($params)
    {
        $rules = [
            'amount' => 'require',
            'orderid' => 'require',
            'type' => 'require',
            'title' => 'require',
            'notifyurl' => 'require',
            'returnurl' => 'require',
            'method' => 'require',
            'openid' => 'require'
        ];
        $msgs = [
            'amount.require' => '订单金额不能为空',
            'orderid.require' => '订单编号不能为空',
            'type.require' => '支付类型不能为空',
            'title.require' => '订单标题不能为空',
            'notifyurl.require' => '回调地址不能为空',
            'returnurl.require' => '返回地址不能为空',
            'method.require' => '支付方法不能为空',
            'openid.require' => '用户openId不能为空'
        ];
        $validate = new Validate($rules,$msgs);
        $res = $validate->check($params);
        if(!$res){
            return ['code'=>0,'msg'=>$validate->getError(),'data'=>[]];
        }
        //$params['amount'] = 0.01;
        $resObj = Service::submitOrder($params);
        return ['code'=>1,'msg'=>'操作成功','data'=>$resObj];
    }
    /**
     * 20220510 新增
     * 支付桶押金
     * order_sn 订单号
     */
    public function ya_pay($water_id,$order_sn,$openId)
    {
        $model = new YaBucket();
        $where = [
            'order_sn' => $order_sn,
            'index_users_id' => $water_id,
        ];
        $info = $model->where($where)->find();
        if(empty($info)){

            return ['code'=>0,'msg'=>'未查询到该押桶','data'=>[]];
        }
        if($info['status'] != 1){
            return ['code'=>0,'msg'=>'该押桶不能进行过支付操作','data'=>[]];
        }

        if($info){
            //进行支付
            $returnUrl = 'http://haoyah.mx1991.com/pages/mine/mineOther/buyRecord';
            $params = [
                'amount'=>$info['total_price'],
                'orderid'=>$info['order_sn'],
                'type'=>'wechat',
                'title'=>'押桶支付',
                'notifyurl'=>config('site.host_address').'/user/Notify/ya_notify',
                'returnurl'=>$returnUrl,
                'method'=>"miniapp",
                'openid'=>$openId
            ];
            $res = $this->pay($params);
            return $res;
        }
        return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
    }
    /**
     * 购买桶
     */
    public function adding_barrel_pay($userId,$openId,$barrelNums)
    {
        if($barrelNums<=0){
            return ['code'=>0,'msg'=>'请选择桶的下单数量','data'=>[]];
        }
        $orderSn = get_order_sn();
        $amount = $barrelNums*floatval(config('site.barrel_price'));
        $orderTitle = "新增桶".$barrelNums.'个';
        //插入订单
        $data = [
            'order_sn' => $orderSn,
            'barrel_nums' => $barrelNums,
            'amount' => $amount,
            'c_user_id' => $userId,
            'order_title' => $orderTitle,
            'createtime' => date("Y-m-d H:i:s")
        ];
        Db::name('barrel_order')->strict(false)->insert($data);
        $returnUrl = 'http://haoyah.mx1991.com';
        $params = [
            'amount'=>$amount,
            'orderid'=>$orderSn,
            'type'=>'wechat',
            'title'=>$orderTitle,
            'notifyurl'=>config('site.host_address').'/user/Notify/bought_barrel_notify',
            'returnurl'=>$returnUrl,
            'method'=>"miniapp",
            'openid'=>$openId
        ];
        $res = $this->pay($params);
        return $res;
    }

   
    /**
     * 水票--立即购买（生成订单）
     */
    public function create_ticket_order($userId,$ticketId,$userCouponsId,$vip=1)
    {
        if(!$ticketId){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        $orderSn = get_order_sn();
        $new_where = [
            'id' => $ticketId,
            'status' => ['neq',3],//非vip增票可以买
        ];
        $ticketInfo = Db::name("goods_tickets")->where($new_where)->find();
        if(!$ticketInfo){
            return ['code'=>0,'msg'=>'传参错误','data'=>[]];
        }
        $buyNums = $ticketInfo['buy_nums'];
        $giveNums = $ticketInfo['give_nums'];
        $gettingNums = $buyNums + $giveNums;
        $goodsId = $ticketInfo['goods_id'];
        $goodsInfo = Db::name("goods")->field('title,sale_price')->where('id',$goodsId)->find();
        $goodsTitle = $goodsInfo['title'];
        $orderTitle = '购入 '.$goodsTitle.' '.$gettingNums.'张水票';
        $amount = $goodsInfo['sale_price'] * $buyNums;
        if($vip == 2){
            $amount = $amount * config('site.vip_rate') * 0.01;
        }

        Db::startTrans();
        try{
            if($userCouponsId){
                $info = $this->deduct_coupons($amount,$userCouponsId,$userId);
                $amount = $info['money'];
                $userCouponsId = $info['coupons_id'];
            }
            //插入订单表
            $data = [
                'c_user_id' => $userId,
                'ticket_id' => $ticketId,
                'createtime' => date("Y-m-d H:i:s"),
                'goods_id' => $goodsId,
                'order_sn' => $orderSn,
                'order_title' => $orderTitle,
                'amount' => $amount,
                'getting_nums' => $gettingNums,
                'ticket_title' => $ticketInfo['title'],
                'coupons_id' => $userCouponsId
            ];
            $orderId = Db::name('ticket_order')->strict(false)->insertGetId($data);
            Db::commit();
        }catch(Exception $e){
            Db::rollback();
            return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
        }  
        return ['code'=>1,'msg'=>'success','data'=>['order_id'=>$orderId]];
    }
    /**
     * 水票--支付
     */
    public function ticket_package_pay($userId,$openId,$ticketOrderId,$user_cou_id)
    {
        $where = [
            'c_user_id' => $userId,
            'id' => $ticketOrderId
        ];
        $orderInfo = Db::name('ticket_order')->where($where)->find();
        if($orderInfo){
            if ($orderInfo['is_pay'] != 0) {
                return ['code'=>0,'msg'=>'订单信息错误1','data'=>[]];
                //throw Exception('抢购太火爆, 请重试...');
            }
            
            
            
            try{
                
                //进行支付
                Db::startTrans();
                $sumMoney = $orderInfo['amount'];
                if($user_cou_id){
                    $ucinfo = $this->deduct_coupons($sumMoney,$user_cou_id,$userId);
                    if($ucinfo['err_code'] == 1){
                        throw Exception('优惠券未找到');
                    }
                    if($ucinfo['err_code'] == 2){
                        throw Exception('没有达到使用门槛');
                    }
                    $sumMoney = $ucinfo['money'];
                    $user_cou_id = $ucinfo['coupons_id'];
                }
                $savedata = [
                    'actual_money' => $sumMoney,
                    'youhui_money' => $orderInfo['amount'] - $sumMoney,
                    'coupons_id' => $user_cou_id
                ];
                //保存会员优惠券id
                $ressave = Db::name('ticket_order')->where($where)->update($savedata);
                if(!$ressave){
                    throw Exception('抢购太火爆, 请重试...');
                }
                
                $returnUrl = 'http://haoyah.mx1991.com/pages/mine/mineOther/buyRecord';
                $params = [
                    'amount'=>$sumMoney,
                    'orderid'=>$orderInfo['order_sn'],
                    'type'=>'wechat',
                    'title'=>$orderInfo['order_title'],
                    'notifyurl'=>config('site.host_address').'/user/Notify/bought_ticket_notify',
                    'returnurl'=>$returnUrl,
                    'method'=>"miniapp",
                    'openid'=>$openId
                ];
                Db::commit();
                $res = $this->pay($params);
                return $res;
            }catch(Exception $e){
                Db::rollback();
                return ['code'=>0,'msg'=>$e->getMessage()];
            }
            
            
            
        }else{
            return ['code'=>0,'msg'=>'订单信息错误','data'=>[]];
        }
        return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
    }

    /**
     * VIp--支付
     */
    public function vip_package_pay($userId,$openId,$ticketOrderId)
    {
        $where = [
            'user_id' => $userId,
            'order_sn' => $ticketOrderId
        ];
        $orderInfo = Db::name('vip')->where($where)->find();
        if($orderInfo){
            //进行支付
            $returnUrl = 'http://haoyah.mx1991.com/pages/mine/mine/mine';
            $params = [
                'amount'=>$orderInfo['price'],
                'orderid'=>$orderInfo['order_sn'],
                'type'=>'wechat',
                'title'=>'充值会员',
                'notifyurl'=>config('site.host_address').'/user/Notify/vip_notify',
                'returnurl'=>$returnUrl,
                'method'=>"miniapp",
                'openid'=>$openId
            ];
            $res = $this->pay($params);
            return $res;
        }
        return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
    }
    /**
     * 商品支付--上级页面：购物车列表
     */
    public function carts_pay($openId,$userId,$params,$vip = 0)
    {
        //收货地址 校验
        $addressId = $params['address_id'];
        if(!$addressId){
            return ['code'=>0,'msg'=>'请选择收货地址','data'=>[]];
        }
        //送达时间 校验
        $arriveTime = $params['arrive_time'];
        if(!$arriveTime){
            return ['code'=>0,'msg'=>'请选择收货时间段','data'=>[]];
        }
        //匹配最近的水站
        $stationId = $this->matching_shop($addressId);
        if(!$stationId){
            return ['code'=>0,'msg'=>'未匹配到合适的水站','data'=>[]];
        }
        $sumMoney = 0.00;
        $sumNums = 0;
        $extendDatas = [];
        //获取购物车所有商品
        $cartType = 0;
        $where = [
            'c_user_id' => $userId,
            'cart_type' => $cartType
        ];
        $field = 'id,goods_id,goods_nums';
        $list = GoodsCartModel::with('goods')->field($field)->where($where)->select();
        if(empty($list)){
            return ['code'=>0,'msg'=>'购物车商品为空','data'=>[]];
        }
        $saleRatio = 1;
        if($vip == 2){
            $saleRatio = config('site.vip_rate') * 0.01;
        }
        Db::startTrans();
        try{
            $cartsIdArr = [];
            foreach($list as $v){
                $sumNums += $v['goods_nums'];
                $v['goods']['sale_price'] = $v['goods']['sale_price'] * $saleRatio;
                $sumMoney += round($v['goods_nums']*$v['goods']['sale_price'],2);
                $data = [
                    'goods_id' => $v['goods_id'],
                    'goods_nums' => $v['goods_nums'],
                    'goods_title' => $v['goods']['title'],
                    'goods_image' => $v['goods']['image'],
                    'sale_price' => $v['goods']['sale_price']
                ];
                $extendDatas[] = $data;
                $cartsIdArr[] = $v['id'];
            }
            //删除购物车商品
            Db::name('goods_cart')->where('id','in',$cartsIdArr)->delete();
            $userCouponsId = $params['user_coupons_id'];
            if($userCouponsId){
                $info = $this->deduct_coupons($sumMoney,$userCouponsId,$userId);
                $sumMoney = $info['money'];
                $userCouponsId = $info['coupons_id'];
            }
            //插入订单表
            $orderTitle = '购入商品 '.$sumNums.'件';
            $orderSn = get_order_sn();
            $mainData = [
                'order_sn' => $orderSn,
                'is_use_ticket' => 1,
                'createtime' => date("Y-m-d H:i:s"),
                'c_user_id' => $userId,
                'sum_money' => $sumMoney,
                'sum_nums' => $sumNums,
                'b_user_id' => $stationId,
                'address_id' => $addressId,
                'arrive_time' => $arriveTime,
                'order_title' => $orderTitle,
                'coupons_id' => $userCouponsId,
                'type' => 0
            ];
            $mainOrderId = Db::name('goods_order')->strict(false)->insertGetId($mainData);
            foreach($extendDatas as &$v){
                $v['order_id'] = $mainOrderId;
            }
            Db::name('goods_order_extend')->strict(false)->insertAll($extendDatas);
            //进行支付
            $returnUrl = 'http://haoyah.mx1991.com/pages/bill/bill';
            $params = [
                'amount'=>$sumMoney,
                'orderid'=>$orderSn,
                'type'=>'wechat',
                'title'=>$orderTitle,
                'notifyurl'=>config('site.host_address').'/user/Notify/goods_pay_notify',
                'returnurl'=>$returnUrl,
                'method'=>"miniapp",
                'openid'=>$openId
            ];
            Db::commit();
            $res = $this->pay($params);
        }catch(Exception $e){
            Db::rollback();
            return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
        }
        return $res;
    }
    /**
     * 商品支付--上级页面--商品详情-立即购买
     */
    public function goods_detail_pay($userId,$openId,$params,$vip = 0)
    {
        if(!isset($params['goods_id']) && !$params['goods_id']){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        if($params['goods_num'] <= 0){
            return ['code'=>0,'msg'=>'请选择要购买的数量','data'=>[]];
        }
        //收货地址 校验
        $addressId = $params['address_id'];
        if(!$addressId){
            return ['code'=>0,'msg'=>'请选择收货地址','data'=>[]];
        }
        //送达时间 校验
        $arriveTime = $params['arrive_time'];
        if(!$arriveTime){
            return ['code'=>0,'msg'=>'请选择收货时间段','data'=>[]];
        }
        //匹配最近的水站
        $stationId = $this->matching_shop($addressId);
        if(!$stationId){
            return ['code'=>0,'msg'=>'未匹配到合适的水站','data'=>[]];
        }
        Db::startTrans();
        try{
            $sumMoney = 0.00;
            $sumNums = $params['goods_num'];
            $goodsId = $params['goods_id'];
            $goodsModel = new GoodsModel();
            $goodsInfo = $goodsModel->where('id',$goodsId)->find();
            $saleRatio = 1;
            if($vip == 2){
                $saleRatio = config('site.vip_rate') * 0.01;
            }
            $goodsInfo['sale_price'] = $goodsInfo['sale_price'] * $saleRatio;
            $sumMoney = round($sumNums*$goodsInfo['sale_price'],2);
            $userCouponsId = $params['user_coupons_id'];
            if($userCouponsId){
                $info = $this->deduct_coupons($sumMoney,$userCouponsId,$userId);
                if($info['err_code'] == 1){
                        throw Exception('优惠券未找到');
                    }
                    if($info['err_code'] == 2){
                        throw Exception('没有达到使用门槛');
                    }
                $sumMoney = $info['money'];
                $userCouponsId = $info['coupons_id'];
            }
            //插入订单表
            $orderTitle = '购入商品 '.$sumNums.'件';
            $orderSn = get_order_sn();
            $mainData = [
                'order_sn' => $orderSn,
                'is_use_ticket' => 1,
                'createtime' => date("Y-m-d H:i:s"),
                'c_user_id' => $userId,
                'sum_money' => $sumMoney,
                'sum_nums' => $sumNums,
                'b_user_id' => $stationId,
                'address_id' => $addressId,
                'arrive_time' => $arriveTime,
                'order_title' => $orderTitle,
                'coupons_id' => $userCouponsId,
                'type' => 0
            ];
            $mainOrderId = Db::name('goods_order')->strict(false)->insertGetId($mainData);
            $extendDatas = [];
            $extendDatas[] = [
                'order_id' => $mainOrderId,
                'goods_id' => $goodsId,
                'goods_nums' => $sumNums,
                'goods_title' => $goodsInfo['title'],
                'goods_image' => $goodsInfo['image'],
                'sale_price' => $goodsInfo['sale_price']
            ];
            Db::name('goods_order_extend')->strict(false)->insertAll($extendDatas);
            //进行支付
            $returnUrl = 'http://haoyah.mx1991.com/pages/bill/bill';
            $params = [
                'amount'=>$sumMoney,
                'orderid'=>$orderSn,
                'type'=>'wechat',
                'title'=>$orderTitle,
                'notifyurl'=>config('site.host_address').'/user/Notify/goods_pay_notify',
                'returnurl'=>$returnUrl,
                'method'=>"miniapp",
                'openid'=>$openId
            ];
            Db::commit();
            $res = $this->pay($params);
        }catch(Exception $e){
            Db::rollback();
            return ['code'=>0,'msg'=>$e->getMessage()];
        }
        return $res;
    }
    /**
     * 商品订单--详情支付
     */
    public function goods_order_pay($userId,$openId,$orderId)
    {
        if(!$orderId){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        $where = [
            'c_user_id' => $userId,
            'id' => $orderId
        ];
        $orderInfo = Db::name('goods_order')->where($where)->find();
        //进行支付
        $returnUrl = 'http://haoyah.mx1991.com/pages/bill/bill';
        $params = [
            'amount'=>$orderInfo['sum_money'],
            'orderid'=>$orderInfo['order_sn'],
            'type'=>'wechat',
            'title'=>$orderInfo['order_title'],
            'notifyurl'=>config('site.host_address').'/user/Notify/goods_pay_notify',
            'returnurl'=>$returnUrl,
            'method'=>"miniapp",
            'openid'=>$openId
        ];
        $res = $this->pay($params);
        return $res;
    }
    /**
     * 一键送水--立即支付
     */
    public function sending_water_pay($userId,$params)
    {
        //收货地址 校验
        $addressId = $params['address_id'];
        if(!$addressId){
            return ['code'=>0,'msg'=>'请选择收货地址','data'=>[]];
        }
        //送达时间 校验
        $arriveTime = $params['arrive_time'];
        if(!$arriveTime){
            return ['code'=>0,'msg'=>'请选择收货时间段','data'=>[]];
        }
         //匹配最近的水站
        $stationId = $this->matching_shop($addressId);
        if(!$stationId){
            return ['code'=>0,'msg'=>'未匹配到合适的水站','data'=>[]];
        }
        //水票支付
        Db::startTrans();
        try{
            $sumMoney = 0.00;
            $sumNums = 0;
            $extendDatas = [];
            //获取购物车所有商品
            $cartType = 1;
            $where = [
                'c_user_id' => $userId,
                'cart_type' => $cartType
            ];
            $list = GoodsCartModel::with('goods')->where($where)->select();
            if(empty($list)){
                return ['code'=>0,'msg'=>'请选择要购买的商品','data'=>[]];
            }
            $cartsIdArr = [];
            //水票余额校验
            $res = true;
            foreach($list as $v){
                $where = [
                    'goods_id' => $v['goods_id'],
                    'c_user_id' => $userId
                ];
                $unusedTickets = Db::name('user_tickets')->where($where)->value('unused_tickets');
                if($unusedTickets < $v['goods_nums']){
                    $res = false;
                    break;
                }
                //01.扣除水票余额
                Db::name('user_tickets')->where($where)->setDec('unused_tickets',$v['goods_nums']);
                Db::name('user_tickets')->where($where)->setInc('used_tickets',$v['goods_nums']);
                //02.商品表增加销量
                Db::name('goods')->where('id',$v['goods_id'])->setInc('sale_sums',$v['goods_nums']);
                //03.生成订单信息
                $sumNums += $v['goods_nums'];
                $sumMoney += round($v['goods_nums']*$v['goods']['sale_price'],2);
                $data = [
                    'goods_id' => $v['goods_id'],
                    'goods_nums' => $v['goods_nums'],
                    'goods_title' => $v['goods']['title'],
                    'goods_image' => $v['goods']['image'],
                    'sale_price' => $v['goods']['sale_price']
                ];
                $extendDatas[] = $data;
                $cartsIdArr[] = $v['id'];
                //减少水站库存
                // $where = [
                //     'b_user_id' => $stationId,
                //     'goods_id' => $v['goods_id']
                // ];
                // Db::name("b_goods")
                //     ->where($where)
                //     ->setDec('goods_nums',$v['goods_nums']);
            }
            //水票余额校验
            if(!$res){
                return ['code'=>2,'msg'=>'其中一项商品水票余额不足','data'=>[]];
            }
            //删除购物车商品
            Db::name('goods_cart')->where('id','in',$cartsIdArr)->delete();
            //插入订单表
            $orderTitle = '购入商品 '.$sumNums.'件';
            $orderSn = get_order_sn();
            $nowTime = date("Y-m-d H:i:s");
            $mainData = [
                'order_sn' => $orderSn,
                'is_use_ticket' => 1,
                'createtime' => $nowTime,
                'c_user_id' => $userId,
                'sum_money' => $sumMoney,
                'sum_nums' => $sumNums,
                'b_user_id' => $stationId,
                'address_id' => $addressId,
                'arrive_time' => $arriveTime,
                'order_title' => $orderTitle,
                'type' => 1,
                'status' => '1'
            ];
            $mainOrderId = Db::name('goods_order')->strict(false)->insertGetId($mainData);
            foreach($extendDatas as &$v){
                $v['order_id'] = $mainOrderId;
            }
            Db::name('goods_order_extend')->strict(false)->insertAll($extendDatas);

            //04.分佣，赠送优惠券
            //check_is_first($userId,$sumMoney);
            //发送模版消息
            send_msg_to_station($nowTime,$stationId,$orderSn,$addressId,$arriveTime);
            Db::commit();
        }catch(Exception $e){
            Db::rollback();
            return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
        }
        return ['code'=>1,'msg'=>'购买成功，请等待配送','data'=>[]];
    }
    /**
     * 秒杀下单支付
     */
    public function seconds_kill_pay($userId,$openId,$params)
    {
        $killGoodsId = $params['kill_goods_id'];
        if(!$killGoodsId){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        $killGoodsInfo = Db::name('goods_seconds_kill')->where('id',$killGoodsId)->find();
        if(!$killGoodsInfo){
            return ['code'=>0,'msg'=>'秒杀商品不存在','data'=>[]];
        }
        $endDateTime = $killGoodsInfo['end_time'];
        $endTime = strtotime($endDateTime);
        $nowTime = time();
        if($nowTime > $endTime){
            return ['code'=>0,'msg'=>'该商品秒杀活动已结束','data'=>[]];
        }
        //判断有无库存
        $options = [
            'host'       => '127.0.0.1',
            'port'       => 6379
        ];
        $redisObj = new DriverRedis($options);
        $key = 'goods_store_'.$killGoodsId;
        $dataList = $redisObj->get($key);
        if(is_array($dataList) && !empty($dataList)){
            $res = array_pop($dataList);
            if($res==1){
                //更新缓存
                $redisObj->set($key,$dataList,$endTime-$nowTime);
            }
        }else{
            $redisObj->set($key,null);
            return ['code'=>0,'msg'=>'商品已售罄','data'=>[]];
        }
        //收货地址 校验
        $addressId = $params['address_id'];
        if(!$addressId){
            return ['code'=>0,'msg'=>'请选择收货地址','data'=>[]];
        }
        //送达时间 校验
        $arriveTime = $params['arrive_time'];
        if(!$arriveTime){
            return ['code'=>0,'msg'=>'请选择收货时间段','data'=>[]];
        }
        $stationId = $this->matching_shop($addressId);
        if(!$stationId){
            return ['code'=>0,'msg'=>'未匹配到合适的水站','data'=>[]];
        }
        //下单操作
        Db::startTrans();
        try{
            //生成订单
            $goodsId = $killGoodsInfo['goods_id'];
            $goodsModel = new GoodsModel();
            $belongGoodsInfo = $goodsModel->where('id',$goodsId)->find();
            $sumMoney = $killGoodsInfo['kill_price'];
            $sumNums = 1;
            //插入订单表
            $orderTitle = '购入商品 '.$sumNums.'件';
            $orderSn = get_order_sn();
            $mainData = [
                'order_sn' => $orderSn,
                'is_use_ticket' => 1,
                'createtime' => date("Y-m-d H:i:s"),
                'c_user_id' => $userId,
                'sum_money' => $sumMoney,
                'sum_nums' => $sumNums,
                'b_user_id' => $stationId,
                'address_id' => $addressId,
                'arrive_time' => $arriveTime,
                'order_title' => $orderTitle,
                'type' => 2
            ];
            $mainOrderId = Db::name('goods_order')->strict(false)->insertGetId($mainData);
            $extendDatas = [];
            $extendDatas[] = [
                'order_id' => $mainOrderId,
                'goods_id' => $goodsId,
                'goods_nums' => $sumNums,
                'goods_title' => $belongGoodsInfo['title'],
                'goods_image' => $belongGoodsInfo['image'],
                'sale_price' => $killGoodsInfo['kill_price'],
                'kill_goods_id' => $killGoodsId
            ];
            Db::name('goods_order_extend')->strict(false)->insertAll($extendDatas);
            //进行支付
            $returnUrl = 'http://haoyah.mx1991.com/pages/bill/bill';
            $params = [
                'amount'=>$sumMoney,
                'orderid'=>$orderSn,
                'type'=>'wechat',
                'title'=>$orderTitle,
                'notifyurl'=>config('site.host_address').'/user/Notify/goods_pay_notify',
                'returnurl'=>$returnUrl,
                'method'=>"miniapp",
                'openid'=>$openId
            ];
            Db::commit();
            $res = $this->pay($params);
        }catch(Exception $e){
            Db::rollback();
            return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
        }
        return $res;
    }
    /**
     * 匹配最近的水站
     */
    private function matching_shop($addressId)
    {
        //匹配最近的水站
        $limit = 1;
        $addressInfo = Db::name('user_address')->field('final_longitude,final_latitude')->where('id',$addressId)->find();
        $sql = "SELECT  `id`,( 6371 * acos (  
            cos ( radians(".$addressInfo['final_latitude'].") )  
            * cos( radians( b_lat ) )  
            * cos( radians( b_lng ) - radians(".$addressInfo['final_longitude'].") )  
            + sin ( radians(".$addressInfo['final_latitude'].") )  
            * sin( radians( b_lat ) )  
            )  
        ) AS distance  
        FROM fa_index_users
        WHERE `status` = '1' and `user_type` = 1
        HAVING distance < ".floatval(config('site.longest_distance'))." 
        ORDER BY distance ASC 
        LIMIT ".$limit;
        $list = Db::query($sql);
        if(empty($list)){
            $stationId = null;
        }else{
            $stationId = $list[0]['id'];
        }
        return $stationId;
    }
    /**
     * 订单金额--扣除优惠券
     */
    private function deduct_coupons($oldOrderMoney,$userCouponsId,$userId)
    {
        $where = [
            'c_user_id' => $userId,
            'id' => $userCouponsId,
            'status' => '0'
        ];
        $userCouponsInfo = Db::name('user_coupons')->where($where)->find();
        if(!$userCouponsInfo){
            return ['money'=>$oldOrderMoney,'coupons_id'=>0,'err_code'=>1];
        }
        $couponsValue = $userCouponsInfo['coupons_value'];
        $thresholdPrice = $userCouponsInfo['threshold_price'];
        if($thresholdPrice>$oldOrderMoney){
            //没有达到使用门槛
            return ['money'=>$oldOrderMoney,'coupons_id'=>0,'err_code'=>2];
        }
        $finalOrderMoney = $oldOrderMoney - $couponsValue;
        if($finalOrderMoney <= 0){
            $finalOrderMoney = 0.01;
        }
        //更新记录状态
        $data = [
            'status' => 1,
            'usedtime' => date("Y-m-d H:i:s")
        ];
        $where = [
            'c_user_id' => $userId,
            'id' => $userCouponsId
        ];
        Db::name('user_coupons')->where($where)->update($data);
        return ['money'=>$finalOrderMoney,'coupons_id'=>$userCouponsId,'err_code'=>0];
    }
}