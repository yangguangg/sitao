<?php
namespace app\user\action;

use app\user\model\GoodsCartModel;
use app\user\model\GoodsModel;
use app\user\model\GoodsOrderModel;
use app\user\model\TicketOrderModel;
use Exception;
use think\Controller;
use think\Db;
use think\Validate;
use think\cache\driver\Redis as DriverRedis;

class OrderAction extends Controller
{
    /**
     * 获取 默认收货地址
     * return array
     */
    public function get_checked_address($userId)
    {
        $info = Db::name('user_address')
            ->field('id,final_address,user_name,mobile')
            ->where('is_checked',1)
            ->where('c_user_id',$userId)
            ->where('deletetime',null)
            ->find();
        return $info;
    }
    /**
     * 新增收货地址
     * return array
     */
    public function add_user_address($userId,$params)
    {
        //普通参数校验
        $rules = [
            'door' => 'require',
            'is_checked' => 'require',
            'final_address' => 'require',
            'final_longitude' => 'require',
            'final_latitude' => 'require',
            'user_name' => 'require',
            'is_has_elevator' => 'require',
            'mobile' => 'require|^1\d{10}$'
        ];
        $msgs = [
            'door.require' => '请输入详细地址',
            'is_checked.require' => '请选择是否默认',
            'final_address.require' => '请选择地址定位',
            'final_longitude.require' => '经度不能为空',
            'final_latitude.require' => '纬度不能为空',
            'user_name.require' => '收货人不能为空',
            'is_has_elevator.require' => '电梯选项不能为空',
            'mobile.require' => '联系电话不能为空',
            'mobile' => '电话格式不正确'
        ];
        $validate = new Validate($rules,$msgs);
        $res = $validate->check($params);
        if(!$res){
            return ['code'=>0,'msg'=>$validate->getError(),'data'=>[]];
        }
        $params['c_user_id'] = $userId;
        if(!isset($params['id'])){
            //新增
            $record = Db::name('user_address')->where('c_user_id',$userId)->find();
            Db::startTrans();
            try{
                if(!$record){
                    $params['is_checked'] = 1;
                }else{
                    if($params['is_checked']==1){
                        //将之前记录的默认状态取消
                        Db::name('user_address')
                            ->where('c_user_id',$userId)
                            ->where('is_checked',1)
                            ->update(['is_checked'=>0]);
                    }
                }
                $params['createtime'] = time();
                $id = Db::name('user_address')->strict(false)->insertGetId($params);
                $params['id'] = $id;
                Db::commit();
            }catch(Exception $e){
                Db::rollback();
                return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
            }
        }else{
            Db::startTrans();
            try{
                $id = $params['id'];
                if($params['is_checked']==1){
                    //将之前记录的默认状态取消
                    Db::name('user_address')
                        ->where('c_user_id',$userId)
                        ->where('is_checked',1)
                        ->update(['is_checked'=>0]);
                }
                Db::name('user_address')->strict(false)->where('id',$id)->update($params);
                Db::commit();
            }catch(Exception $e){
                Db::rollback();
                return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
            }
        }
        return ['code'=>1,'msg'=>'操作成功!','data'=>$params];
    }
    public function get_address_info($addressId)
    {
        $field = 'id,final_address,final_longitude,final_latitude,user_name,mobile,is_checked,door';
        $addressInfo = Db::name('user_address')->field($field)->where('id',$addressId)->find();
        return $addressInfo;
    }
    /**
     * 加入购物车
     */
    public function add_into_cart($goodsId,$goodsNums,$cartType,$userId)
    {
        if(!$goodsId){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        if($goodsNums<=0){
            return ['code'=>0,'msg'=>'请选择商品数量','data'=>[]];
        }
        //记录校验
        $where = [
            'goods_id' => $goodsId,
            'c_user_id' => $userId,
            'cart_type' => $cartType
        ];
        $cartModel = new GoodsCartModel();
        $record = $cartModel->get($where);
        if($record){
            //该商品 购物车 记录 已存在 更新
            $cartModel->where($where)->setInc('goods_nums',$goodsNums);
        }else{
            //该商品 购物车 记录 不存在 新增
            $data = [
                'goods_id' => $goodsId,
                'goods_nums' => $goodsNums,
                'c_user_id' => $userId,
                'cart_type' => $cartType,
                'createtime' => date('Y-m-d H:i:s')
            ];
            $cartModel->allowField(true)->create($data);
        }
        return ['code'=>1,'msg'=>'添加成功','data'=>[]];
    }
    /**
     * 购物车操作 单项 增 或 减
     * cart_type 0:商品购买,1:一键送水
     * act_type 0:增加,1:减少
     */
    public function act_one_cart($goodsId,$actType,$cartType,$userId)
    {
        if(!$goodsId){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        //记录校验 减操作 剩余数量必须大于0
        $where = [
            'goods_id' => $goodsId,
            'c_user_id' => $userId,
            'cart_type' => $cartType
        ];
        $cartModel = new GoodsCartModel();
        $record = $cartModel->where($where)->find();
        if($cartType == 0 && $actType == 1 && $record['goods_nums'] <= 1){
            return ['code'=>0,'msg'=>'最少购买1件哦','data'=>[]];
        }
        if($cartType == 1 && $actType == 1 && $record['goods_nums'] <= 1){
            $cartModel->where($where)->delete();
            return ['code'=>1,'msg'=>'操作成功','data'=>[]];
        }
        if($actType==0){
            $cartModel->where($where)->setInc('goods_nums');
        }else{
            $cartModel->where($where)->setDec('goods_nums');
        }
        return ['code'=>1,'msg'=>'操作成功','data'=>[]];
    }
    /**
     * 获取购物车列表
     */
    public function get_cart_list($userId,$cartType,$vip=0)
    {
        $where = [
            'c_user_id' => $userId,
            'cart_type' => $cartType
        ];
        $field = 'id,goods_id,goods_nums,c_user_id';
        $list = GoodsCartModel::with('goods')->field($field)->where($where)->select();
        $sumMoney = 0.00;
        $sumNums = 0;
        foreach($list as &$v){
            $sumNums += $v['goods_nums'];
            if($cartType == 1){
                $where = [];
                $where['goods_id'] = $v['goods_id'];
                $where['c_user_id'] = $v['c_user_id'];
                $havingTickets= Db::name('user_tickets')->where($where)->value('unused_tickets');
                if(!$havingTickets){
                    $havingTickets = 0;
                }
                $v['having_tickets'] = $havingTickets;
            }
            $saleRatio = 1;
            if($vip == 2){
                $saleRatio = config('site.vip_rate') * 0.01;
            }
            $v['goods']['sale_price'] = $v['goods']['sale_price'] * $saleRatio;
            $sumMoney+=($v['goods_nums']*$v['goods']['sale_price']);
        }
        $datas = ['sum_money'=>$sumMoney,'list'=>$list,'sum_nums'=>$sumNums];
        return $datas;
    }
    /**
     * 购物车编辑 -- 批量删除
     */
    public function delete_cart_options($cartArr,$userId,$cartType)
    {
        if(empty($cartArr)){
            return ['code'=>0,'msg'=>'请选择要删除的商品','data'=>[]];
        }
        $cartModel = new GoodsCartModel();
        $cartIdsArr = [];
        foreach($cartArr as $v){
            $cartIdsArr[] = $v['cart_id'];
        }
        $where['id'] = ['in',$cartIdsArr];
        $where['c_user_id'] = $userId;
        $where['cart_type'] = $cartType;
        $res = $cartModel->where($where)->delete();
        if(!$res){
            return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
        }
        return ['code'=>1,'msg'=>'操作成功','data'=>[]];
    }
    /**
     * 获取收货地址列表
     */
    public function get_address_list($userId)
    {
        $list = Db::name('user_address')
                    ->field('id,final_address,door,user_name,mobile,is_checked')
                    ->where('c_user_id',$userId)
                    ->where('deletetime',null)
                    ->select();
        return $list;
    }
    /**
     * 批量删除--收货地址
     */
    public function delete_many_address($addressArr,$userId)
    {
        if(empty($addressArr)){
            return ['code'=>0,'msg'=>'请选择要删除的地址','data'=>[]];
        }
        $addressIdsArr = [];
        foreach($addressArr as $v){
            $addressIdsArr[] = $v['address_id'];
        }
        $res = Db::name('user_address')
            ->where('id','in',$addressIdsArr)
            ->where('c_user_id',$userId)
            ->update(['deletetime'=>time()]);
        if($res){
            return ['code'=>1,'msg'=>'操作成功','data'=>[]];
        }
    }
    /**
     * 水票订单列表
     */
    public function get_ticket_orders($userId,$page,$limit)
    {
        $where['c_user_id'] = $userId;
        $field = 'id,goods_id,getting_nums,amount,ticket_title,status,createtime';
        $list = TicketOrderModel::with('goods')
                    ->field($field)
                    ->where($where)
                    ->page($page,$limit)
                    ->order('id','desc')
                    ->select();
        return $list;
    }
    /**
     * 水票订单详情
     */
    public function get_ticket_order_detail($userId,$orderId)
    {
        if(!$orderId){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        $where = [
            'c_user_id' => $userId,
            'id' => $orderId
        ];
        $field = 'id,goods_id,getting_nums,amount,ticket_title,status,order_sn';
        $info = TicketOrderModel::with('goods')
                    ->field($field)
                    ->where($where)
                    ->find();
        return ['code'=>1,'msg'=>'success','data'=>$info];
    }
    /**
     * 订单列表
     */
    public function get_goods_order_list($userId,$status,$page,$limit)
    {
        // $orderStatus = $status-1;
        $where = [
            'c_user_id' => $userId
        ];
        if($status!==''){
            $where['status'] = strval($status);
        }else{
            $where['status'] = ['in',['0','1','2','3','4']];
        }
        $list = GoodsOrderModel::with('extend')
                    ->field('id,sum_money,sum_nums,status,createtime,order_sn,is_use_ticket')
                    ->where($where)
                    ->page($page,$limit)
                    ->order('id','desc')
                    ->select();
        return $list;
    }
    /**
     * 取消订单
     */
    public function cancel_goods_order($userId,$orderId)
    {
        $goodsOrderModel = new GoodsOrderModel();
        $where = [
            'c_user_id' => $userId,
            'id' => $orderId
        ];
        $orderInfo = $goodsOrderModel->get($where);
        if(!$orderInfo){
            return ['code'=>0,'msg'=>'您没有权限操作哦','data'=>[]];
        }
        if($orderInfo['status'] >=1){
            return ['code'=>0,'msg'=>'您不能取消订单哦','data'=>[]];
        }
        $orderInfo->status = 6;
        $res = $orderInfo->save();
        if($orderInfo['type']==2){
            $extendGoods = Db::name('goods_order_extend')->where('order_id',$orderId)->find();
            $killGoodsId = $extendGoods['kill_goods_id'];
            $killGoodsInfo = Db::name('goods_seconds_kill')->where('id',$killGoodsId)->find();
            $endDateTime = $killGoodsInfo['end_time'];
            $endTime = strtotime($endDateTime);
            $nowTime = time();
            if($nowTime <= $endTime){
                $options = [
                    'host'       => '127.0.0.1',
                    'port'       => 6379
                ];
                $redisObj = new DriverRedis($options);
                $key = 'goods_store_'.$killGoodsId;
                $dataList = $redisObj->get($key);
                if(is_array($dataList)){
                    array_push($dataList,1);
                }else{
                    $dataList = [1];
                }
                //更新缓存
                $redisObj->set($key,$dataList,$endTime-$nowTime);
            }
        }
        if(!$res){
            return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
        }
        return ['code'=>1,'msg'=>'取消成功','data'=>[]];
    }
     /**
     * 订单--去评价--获取订单商品列表 --已添加至接口文档
     */
    public function get_order_goods_list($orderId)
    {
        if(!$orderId){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        $goodsList = Db::name('goods_order_extend')->where('order_id',$orderId)->select();
        foreach($goodsList as &$v){
            $v['goods_image'] = cdnurl($v['goods_image'],true);
        }
        return ['code'=>1,'msg'=>'success','data'=>$goodsList];
    }
    /**
     * 订单--添加评价
     */
    public function add_comment($userId,$params)
    {
        if(!isset($params['order_id']) && !$params['order_id']){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        if(!isset($params['goods_id']) && !$params['goods_id']){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        $orderId = $params['order_id'];
        $where = [
            'goods_id' => $params['goods_id'],
            'c_user_id' => $userId,
            'order_id' => $orderId
        ];
        $record = Db::name('user_comment')->where($where)->find();
        if($record){
            return ['code'=>0,'msg'=>'请勿重复评价','data'=>[]];
        }
        $data = [
            'comment' => $params['comment'],
            'goods_id' => $params['goods_id'],
            'order_id' => $orderId,
            'images' => $params['images'],
            'c_user_id' => $userId,
            'level_status' => $params['level_status'],
            'createtime' => time()
        ];
        Db::startTrans();
        try{
            //插入评价表
            Db::name('user_comment')->strict(false)->insert($data);
            //更改订单状态
            Db::name('goods_order')->where('id',$orderId)->update(['status'=>'5']);
            Db::commit();
        }catch(Exception $e){
            Db::rollback();
            return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
        }
        return ['code'=>1,'msg'=>'评价成功','data'=>[]];
    }
    /**
     * 订单--确认收货
     */
    public function confirm_got_goods($userId,$orderId)
    {
        if(!$orderId){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        $orderModel = new GoodsOrderModel();
        $where = [
            'c_user_id' => $userId,
            'id' => $orderId
        ];
        $orderInfo = $orderModel->get($where);
        if(!$orderInfo){
            return ['code'=>0,'msg'=>'您没有权限操作哦','data'=>[]];
        }
        $orderInfo->status = '4';
        $res = $orderInfo->save();
        if(!$res){
            return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
        }
        return ['code'=>1,'msg'=>'操作成功','data'=>[]];
    }
    /**
     * 订单--详情
     */
    public function get_goods_order_detail($userId,$orderId)
    {
        if(!$orderId){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        $where = [
            'c_user_id' => $userId,
            'id' => $orderId
        ];
        $field = 'id,order_sn,is_use_ticket,status,createtime,address_id,arrive_time,sum_money,sum_nums,worker_id';
        $orderInfo = GoodsOrderModel::with(['extend','address'])
                        ->field($field)
                        ->where($where)
                        ->find();
        $workerInfo = null;
        if($orderInfo['worker_id']){
            $field = '';
            $workerInfo = Db::name('water_workers')
                            ->field('id,worker_name,worker_mobile')
                            ->where('id',$orderInfo['worker_id'])
                            ->find();
        }
        $orderInfo['worker_info'] = $workerInfo;
        return ['code'=>1,'msg'=>'success','data'=>$orderInfo];
    }
    /**
     * 获取我的优惠券列表
     */
    public function get_my_coupons_list($userId)
    {
        $where = [
            'c_user_id' => $userId,
            'status' => '0'
        ];
        $field = 'id user_coupons_id,coupons_value,threshold_price,end_time';
        $list = Db::name('user_coupons')->field($field)->where($where)->select();
        $nowTime = time();
        foreach ($list as $k => $v){
            if(strtotime($v['end_time'].' 23:59:59')<$nowTime){
                unset($list[$k]);
            }
        }
        return array_values($list);
    }
    /**
     * 获取我未领取的优惠券列表
     */
    public function get_un_coupons_list($userId)
    {
        //先获取领取过的优惠券
        $where = [
            'c_user_id' => $userId,
            'type' => '2'
            // 'status' => '0'
        ];
        $ids = Db::name('user_coupons')->where($where)->column('coupons_id');
// var_dump($ids);
        $new_where = [
            // 'status' => '2',//前台领取的才能显示
        ];
        if(count($ids)>1){
            $new_where['id'] = ['not in',$ids];
        }
        // else{
        //     $new_where['id'] = ['not in','-1'];
        // }
        
        $uinfo = Db::name('index_users')->where('id', $userId)->find();
        if($uinfo['vip'] != 2){
            $list = Db::name('coupons')->where($new_where)
            ->where('deletetime',null)
            ->where('status','1')
            ->select();
        }else{
            $list = Db::name('coupons')->where($new_where)
            ->where('deletetime',null)
            ->order('status asc')
            ->select();
        }


        
//        echo Db::name('coupons')->getLastSql();
//
//        dump($list);
//        dump(($ids));
//        dump(count($ids));
//        exit;
        return $list;
//        $nowTime = time();
//        foreach ($list as $k => $v){
//            if(strtotime($v['end_time'].' 23:59:59')<$nowTime){
//                unset($list[$k]);
//            }
//        }
//        return array_values($list);
    }
    /**
     * 一键送水--选择商品
     */
    public function get_sendding_waters($userId,$brandId,$page,$limit)
    {
        $limit = 30;
        $where = [
            'cate_id' => 1,
            'deletetime' => null,
            'status' => '1',
            'goods_nums' => ['>',0]
        ];
        if($brandId){
            $where['brand_id'] = $brandId;
        }
        $goodsModel = new GoodsModel();
        $field = 'id,title,image,tag_ids,original_price,sale_price,sale_sums,is_intro,is_avoid';
        $list = $goodsModel
                    ->field($field)
                    ->where($where)
                    ->page($page,$limit)
                    ->select();
        foreach($list as &$v){
            $where = [
                'goods_id' => $v['id'],
                'c_user_id' => $userId
            ];
            $havingTickets = Db::name('user_tickets')->where($where)->value('unused_tickets');
            if(!$havingTickets){
                $havingTickets = 0;
            }
            $v['having_tickets'] = $havingTickets;
        }
        return $list;
    }
}