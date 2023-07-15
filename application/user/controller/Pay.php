<?php
namespace app\user\controller;

use app\admin\model\YaBucket;
use app\common\controller\BaseApi;
use app\user\action\PayAction;

class Pay extends BaseApi
{
    private $payAction;
    public function __construct()
    {
        parent::__construct();
        $this->payAction = new PayAction();
    }
    /**
     * 增加桶数--购买--已添加至接口文档
     */
    public function adding_barrel_pay()
    {
        $userId = $this->userId;
        $openId = $this->openId;
        $barrelNums = input('barrel_nums',0);
        $res = $this->payAction->adding_barrel_pay($userId,$openId,$barrelNums);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 水票--立即购买（生成订单）--已添加至接口文档
     */
    public function create_ticket_order()
    {
        $userId = $this->userId;
        $ticketId = input('ticket_id',0);
        $userCouponsId = input('user_coupons_id');
        $res = $this->payAction->create_ticket_order($userId,$ticketId,$userCouponsId,$this->vip);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 水票--支付--已添加至接口文档
     */
    public function ticket_package_pay()
    {
        $userId = $this->userId;
        $openId = $this->openId;
        $ticketOrderId = input('ticket_order_id',0);
        $user_cou_id = input('user_coupons_id',0);//2022年7月23日18:32:52添加
        $res = $this->payAction->ticket_package_pay($userId,$openId,$ticketOrderId,$user_cou_id);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }

    /**
     * 20220510 新增
     * 支付桶押金
     * order_sn 订单号
     */
    public function ya_pay()
    {
        $water_id = $this->userId;
        $order_sn = input('order_sn',0);

        $res = $this->payAction->ya_pay($water_id,$order_sn,$this->openId); if($res['code']==0){
        return commonReturnError($res['msg']);
    }
        return commonReturnSuccess($res['data'],$res['msg']);
    }

//vip_package_pay

    /**
     * 水票--支付--已添加至接口文档
     */
    public function vip_package_pay()
    {
        $userId = $this->userId;
        $openId = $this->openId;
        $ticketOrderId = input('order_sn',0);
        $res = $this->payAction->vip_package_pay($userId,$openId,$ticketOrderId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 商品支付--下单页面（上级页面：购物车列表页）--已添加至接口文档
     */
    public function carts_pay()
    {
        $userId = $this->userId;
        $openId = $this->openId;
        $params = request()->param();
        $res = $this->payAction->carts_pay($openId,$userId,$params,$this->vip);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 商品支付--下单页面（上级页面：商品详情页，触发跳转方式：点击 详情页立即购买）
     * --已添加至接口文档
     */
    public function goods_detail_pay()
    {
        $userId = $this->userId;
        $openId = $this->openId;
        $params = request()->param();
        $res = $this->payAction->goods_detail_pay($userId,$openId,$params,$this->vip);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 商品订单--详情或列表项--立即支付--已添加至接口文档
     */
    public function goods_order_pay()
    {
        $userId = $this->userId;
        $openId = $this->openId;
        $orderId = input('order_id');
        $res = $this->payAction->goods_order_pay($userId,$openId,$orderId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 一键送水--立即支付--已添加至接口文档
     */
    public function sending_water_pay()
    {
        $params = request()->param();
        $userId = $this->userId;
        $res = $this->payAction->sending_water_pay($userId,$params);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        if($res['code']==2){
            return commonReturnError($res['msg'],405);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 秒杀下单支付--已添加至接口文档
     */
    public function seconds_kill_pay()
    {
        $userId = $this->userId;
        $openId = $this->openId;
        $params = request()->param();
        $res = $this->payAction->seconds_kill_pay($userId,$openId,$params);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
}