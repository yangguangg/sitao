<?php
namespace app\water\controller;

use app\common\controller\StationBase;
use app\water\action\OrderAction;

class Order extends StationBase
{
    private $orderAction;
    public function __construct()
    {
        parent::__construct();
        $this->orderAction = new OrderAction();
    }
    /**
     * 订单列表--已添加至接口文档
     */
    public function get_order_list()
    {
        $status = input('status',0);
        $userId = $this->userId;
        $page = input('page',1);
        $limit = input('limit',10);
        $list = $this->orderAction->get_order_list($status,$userId,$page,$limit);
        return commonReturnSuccess($list);
    }
    /**
     * 拒接订单--已添加至接口文档
     */
    public function refuse_getting_order()
    {
        $userId = $this->userId;
        $orderId = input('order_id',0);
        $res = $this->orderAction->refuse_getting_order($userId,$orderId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 获取配送人员列表--已添加至接口文档
     */
    public function get_station_workers()
    {
        $userId = $this->userId;
        $list = $this->orderAction->get_station_workers($userId);
        return commonReturnSuccess($list);
    }
    /**
     * 新增配送人员--已添加至接口文档
     */
    public function add_water_worker()
    {
        $userId = $this->userId;
        $params = request()->param();
        $res = $this->orderAction->add_water_worker($userId,$params);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 批量删除配送人员--已添加至接口文档
     */
    public function delete_batch_workers()
    {
        $userId = $this->userId;
        $workerIdsJson = input('worker_ids_json','');
        $res = $this->orderAction->delete_batch_workers($userId,$workerIdsJson);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 确认接单--已添加至接口文档
     */
    public function sure_get_order()
    {
        $userId = $this->userId;
        $orderId = input('order_id');
        $workerId = input('worker_id');
        $res = $this->orderAction->sure_get_order($userId,$orderId,$workerId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 开始配送--已添加至接口文档
     */
    public function start_send_water()
    {
        $userId = $this->userId;
        $orderId = input('order_id');
        $res = $this->orderAction->start_send_water($userId,$orderId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 确认送达--已添加至接口文档
     */
    public function sure_goods_arrived()
    {
        $userId = $this->userId;
        $orderId = input('order_id');
        $res = $this->orderAction->sure_goods_arrived($userId,$orderId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 水站端订单详情
     */
    public function get_goods_order_detail()
    {
        $userId = $this->userId;
        $orderId = input('order_id');
        $result = $this->orderAction->get_goods_order_detail($userId,$orderId);
        return commonReturnSuccess($result['data'],$result['msg']);
    }
    
}