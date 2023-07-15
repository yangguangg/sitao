<?php
namespace app\user\controller;

use app\admin\model\users\Vip;
use app\common\controller\BaseApi;
use app\user\action\OrderAction;

class Order extends BaseApi
{
    public $orderAction;
    public function __construct()
    {
        parent::__construct();
        //$this->checkSign();
        $this->orderAction = new OrderAction();
    }
    /**
     * 获取 默认收货地址--已添加至接口文档
     * return array
     */
    public function get_checked_address()
    {
        $userId = $this->userId;
        $info = $this->orderAction->get_checked_address($userId);
        return commonReturnSuccess($info);
    }
    /**
     * 新增收货地址--已添加至接口文档
     * return array
     */
    public function add_user_address()
    {
        $userId = $this->userId;
        $params = request()->param();
        $res = $this->orderAction->add_user_address($userId,$params);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 收货地址--批量删除--已添加至接口文档
     */
    public function delete_many_address()
    {
        $addressJson = input('address_json');
        $userId = $this->userId;
        $addressArr = json_decode($addressJson,true);
        $res = $this->orderAction->delete_many_address($addressArr,$userId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 加入购物车--已添加至接口文档
     */
    public function add_into_cart()
    {
        $goodsId = input('goods_id');
        $goodsNums = input('goods_nums');
        $cartType = input('cart_type',0);
        $userId = $this->userId;
        $res = $this->orderAction->add_into_cart($goodsId,$goodsNums,$cartType,$userId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 购物车编辑 -- 单项 数量 增 减--已添加至接口文档
     */
    public function act_one_cart()
    {
        $goodsId = input('goods_id');
        $actType = input('act_type',0);//0：新增1，1：减1 
        $cartType = input('cart_type',0);
        $userId = $this->userId;
        $res = $this->orderAction->act_one_cart($goodsId,$actType,$cartType,$userId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 获取购物车列表--已添加至接口文档
     */
    public function get_cart_list()
    {
        $userId = $this->userId;
        $cartType = input('cart_type',0);
        $list = $this->orderAction->get_cart_list($userId,$cartType,$this->vip);
        return commonReturnSuccess($list);
    }
    /**
     * 购物车编辑 批量删除--已添加至接口文档
     */
    public function delete_cart_options()
    {
        $userId = $this->userId;
        $cartJson = input('cart_ids_json');
        $cartType = input('cart_type',0);
        $cartArr = json_decode($cartJson,true);
        $res = $this->orderAction->delete_cart_options($cartArr,$userId,$cartType);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 获取收货地址列表--已添加至接口文档
     */
    public function get_address_list()
    {
        $userId = $this->userId;
        $list = $this->orderAction->get_address_list($userId);
        return commonReturnSuccess($list);
    }
    /**
     * 水票订单列表--已添加至接口文档
     */
    public function get_ticket_orders()
    {
        $userId = $this->userId;
        $page = input('page',1);
        $limit = input('limit',10);
        $list = $this->orderAction->get_ticket_orders($userId,$page,$limit);
        return commonReturnSuccess($list);
    }
    /**
     * 水票订单详情--已添加至接口文档
     */
    public function get_ticket_order_detail()
    {
        $orderId = input('order_id',0);
        $userId = $this->userId;
        $res = $this->orderAction->get_ticket_order_detail($userId,$orderId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 水订单列表--已添加至接口文档
     */
    public function get_goods_order_list()
    {
        $userId = $this->userId;
        $status = input('status','');
        $page = input('page',1);
        $limit = input('limit',10);
        $list = $this->orderAction->get_goods_order_list($userId,$status,$page,$limit);
        return commonReturnSuccess($list);
    }
    /**
     * 取消商品订单--已添加至接口文档
     */
    public function cancel_goods_order()
    {
        $userId = $this->userId;
        $orderId = input('order_id',0);
        $res = $this->orderAction->cancel_goods_order($userId,$orderId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 订单--去评价--获取订单商品列表--已添加至接口文档
     */
    public function get_order_goods_list()
    {
        $orderId = input('order_id',0);
        $res = $this->orderAction->get_order_goods_list($orderId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 订单--添加评价--已添加至接口文档
     */
    public function add_goods_comment()
    {
        // return commonReturnSuccess('测试评论');
        $userId = $this->userId;
        $params = request()->param();
        $res = $this->orderAction->add_comment($userId,$params);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 订单--确认收货--已添加至接口文档
     */
    public function confirm_got_goods()
    {
        $userId = $this->userId;
        $orderId = input('order_id');
        $res = $this->orderAction->confirm_got_goods($userId,$orderId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 水订单--详情--已添加至接口文档
     */
    public function get_goods_order_detail()
    {
        $userId = $this->userId;
        $orderId = input('order_id');
        $res = $this->orderAction->get_goods_order_detail($userId,$orderId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 获取我的优惠券列表--已添加至接口文档
     */
    public function get_my_coupons_list()
    {
        $userId = $this->userId;
        $list = $this->orderAction->get_my_coupons_list($userId);
        return commonReturnSuccess($list);
    }
    /**
     * 获取我未领取的优惠券列表
     */
    public function get_un_coupons_list()
    {
        $userId = $this->userId;
        $list = $this->orderAction->get_un_coupons_list($userId);
        return commonReturnSuccess($list);
    }
    /**
     * 一键送水--选择商品--已添加至接口文档
     */
    public function get_sendding_waters()
    {
        $brandId = input('brand_id',0);
        $page = input('page',1);
        $limit = input('limit',10);
        $userId = $this->userId;
        $list = $this->orderAction->get_sendding_waters($userId,$brandId,$page,$limit);
        return commonReturnSuccess($list);
    }
    /**
     * 获取地址信息
     */
    public function get_address_info()
    {
        $addressId = input('address_id');
        $data = $this->orderAction->get_address_info($addressId);
        return commonReturnSuccess($data);
    }

    /**
     * 购买会员
     */
    public function buy_vip(){
        $vipModel = new Vip();
        $r = $vipModel->createorder($this->userId);
        if($r['code'] == 1){
            return commonReturnSuccess($r['data']);
        }else{
            return commonReturnError($r['msg']);
        }
    }
}