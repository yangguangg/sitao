<?php
namespace app\water\action;

use app\user\model\GoodsOrderModel;
use Exception;
use think\Controller;
use think\Db;
use think\Validate;

class OrderAction extends Controller
{
    /**
     * 订单列表
     */
    public function get_order_list($status,$userId,$page,$limit)
    {
        $where['b_user_id'] = $userId;
        //状态:0=未支付,1=待接单,2=待配送,3=配送中,4=待评价,5=已评价,6=已取消
        switch($status){
            case 0 :
                $where['status'] = ['in',['1','2','3','4','5']];
                break;
            case 1:
                $where['status'] = '1';
                break;
            case 2:
                $where['status'] = '2';
                break;
            case 3:
                $where['status'] = '3';
                break;
            case 4:
                $where['status'] = ['in',['4','5']];
                break;
        }
        $list = GoodsOrderModel::with(['extend','address'])
                    ->field('order_sn,arrive_time,id,sum_money,sum_nums,status,address_id,is_use_ticket')
                    ->where($where)
                    ->page($page,$limit)
                    ->order('id','desc')
                    ->select();
        return $list;
    }
    /**
     * 水站端订单详情
     */
    public function get_goods_order_detail($userId,$orderId)
    {
        $where = [
            'b_user_id' => $userId,
            'id' => $orderId
        ];
        $model = new GoodsOrderModel();
        $goodsDetail = $model::where($where)
                            ->with(['extend','address'])
                            ->find();
        return ['code'=>1,'msg'=>'查询成功','data'=>$goodsDetail];
    }
    /**
     * 拒接订单
     */
    public function refuse_getting_order($userId,$orderId)
    {
        if(!$orderId){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        $mainOrderInfo = Db::name('goods_order')->where('id',$orderId)->find();
        $addressId = $mainOrderInfo['address_id'];
        $oldStationId = $mainOrderInfo['b_user_id'];
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
         WHERE `status` = '1' and `user_type` = 1 and `id` != $oldStationId
         HAVING distance < ".floatval(config('site.longest_distance'))." 
         ORDER BY distance ASC 
         LIMIT ".$limit;
         $list = Db::query($sql);
         if(empty($list)){
             $stationId = 0;
         }else{
             $stationId = $list[0]['id'];
         }
         if(!$stationId){
            return ['code'=>0,'msg'=>'没有匹配到合适的水站','data'=>[]];
         }
         $data = ['b_user_id' => $stationId];
         $res = Db::name('goods_order')->where('id',$orderId)->update($data);
         send_msg_to_station($mainOrderInfo['createtime'],$stationId,$mainOrderInfo['order_sn'],$addressId,$mainOrderInfo['arrive_time']);
         if(!$res){
             return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
         }
         return ['code'=>1,'msg'=>'操作成功','data'=>[]];
    }
    /**
     * 获取配送人员列表
     */
    public function get_station_workers($userId)
    {
        $where = [
            'b_user_id' => $userId,
            'deletetime' => null
        ];
        $field = 'id,worker_name,worker_mobile,is_checked';
        $workerList = Db::name('water_workers')->field($field)->where($where)->order('is_checked','desc')->select();
        return $workerList;
    }
    /**
     * 新增配送人员
     */
    public function add_water_worker($userId,$params)
    {
        $rules = [
            'worker_name' => 'require',
            'worker_mobile' => 'require|^1\d{10}$',
        ];
        $msgs = [
            'worker_name.require' => '配送人员姓名不能为空',
            'worker_mobile.require' => '配送电话不能为空',
            'worker_mobile' => '电话格式不正确'
        ];
        $validate = new Validate($rules,$msgs);
        $res = $validate->check($params);
        if(!$res){
            return ['code'=>0,'msg'=>$validate->getError(),'data'=>[]];
        }
        $data = [
            'worker_name' => $params['worker_name'],
            'worker_mobile' => $params['worker_mobile'],
            'b_user_id' => $userId,
            'createtime' => time(),
            'is_checked' => $params['is_checked']
        ];
        $where = [
            'b_user_id' => $userId,
            'deletetime' => null
        ];
        Db::startTrans();
        try{
            $record = Db::name('water_workers')->where($where)->find();
            if(!$record){
                $data['is_checked'] = 1;
            }else{
                if($params['is_checked']==1){
                    Db::name('water_workers')->where($where)->update(['is_checked'=>0]);
                }
            }
            Db::name('water_workers')->strict(false)->insert($data);
            Db::commit();
        }catch(Exception $e){
            Db::rollback();
            return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
        }
        return ['code'=>1,'msg'=>'新增成功','data'=>[]];
    }
    /**
     * 批量删除配送人员
     */
    public function delete_batch_workers($userId,$workerIdsJson)
    {
        $workerIdsArr = json_decode($workerIdsJson,true);
        if(!$workerIdsArr || empty($workerIdsArr)){
            return ['code'=>0,'msg'=>'请选择配送人员','data'=>[]];
        }
        $workerIds = [];
        foreach($workerIdsArr as $v){
            $workerIds[] = $v['worker_id'];
        }
        $where['b_user_id'] = $userId;
        $where['id'] = ['in',$workerIds];
        $data = [
            'deletetime' => time()
        ];
        $res = Db::name('water_workers')->where($where)->update($data);
        if(!$res){
            return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
        }
        return ['code'=>1,'msg'=>'操作成功','data'=>[]];
    }
    /**
     * 确认接单
     */
    public function sure_get_order($userId,$orderId,$workerId)
    {
        if(!$orderId){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        if(!$workerId){
            return ['code'=>0,'msg'=>'请选择配送人员','data'=>[]];
        }
        $where = [
            'id' => $orderId,
            'b_user_id' => $userId
        ];
        $data = [
            'status' => '2',
            'worker_id' => $workerId
        ];
        $res = Db::name('goods_order')->where($where)->update($data);
        if(!$res){
            return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
        }
        return ['code'=>1,'msg'=>'操作成功','data'=>[]];
    }
    /**
     * 开始配送
     */
    public function start_send_water($userId,$orderId)
    {
        // return ['code'=>0,'msg'=>'错误提示, 测试用','data'=>[]];
        if(!$orderId){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        $where = [
            'id' => $orderId,
            'b_user_id' => $userId
        ];
        $orderinfo = Db::name('goods_order')->where($where)->find();
        if(!$orderinfo){
            return ['code'=>0,'msg'=>'未找到订单','data'=>[]];
        }
        if($orderinfo['status'] != 2){
            return ['code'=>0,'msg'=>'订单状态已更改,请刷新重试','data'=>[]];
        }
        
        $where_order_extend = [
                'order_id' => $orderId
            ];
        Db::startTrans();
                try {
                    $order_extend_list = Db::name('goods_order_extend')->where($where_order_extend)->select();
                    foreach ($order_extend_list as $key1 => $value1) {
                        $user_kucun_nums = Db::name('b_goods')
                        ->where('b_user_id',$userId)
                        ->where('goods_id',$value1['goods_id'])
                        ->value('goods_nums');
                        if($user_kucun_nums < $value1['goods_nums']){
                            // return ['code'=>0,'msg'=>"{$value1['goods_title']}库存不足, 需要{$value1['goods_nums']},剩余{$user_kucun_nums}",'data'=>[]];
                            throw Exception("{$value1['goods_title']}库存不足, 需要{$value1['goods_nums']},剩余{$user_kucun_nums}");
                        }
                        $res1 = Db::name('b_goods')
                        ->where('b_user_id',$userId)
                        ->where('goods_id',$value1['goods_id'])
                        ->setDec('goods_nums', $value1['goods_nums']);
                        if(!$res1){
                            // return ['code'=>0,'msg'=>'水站库存更新失败,请新重试','data'=>[]];
                            throw Exception("水站库存更新失败,请新重试");
                        }
                    }
                    
                    
                    $data = [
                        'status' => '3'
                    ];
                    $res = Db::name('goods_order')->where($where)->update($data);
                    if(!$res){
                        return ['code'=>0,'msg'=>'请勿重复操作','data'=>[]];
                    }
                    Db::commit();
                    send_msg_to_user($orderId);
                }catch (Exception $e) {
                    Db::rollback();
                    // $this->error($e->getMessage());
                    return ['code'=>0,'msg'=>$e->getMessage(),'data'=>[]];
                    
                }
        
        return ['code'=>1,'msg'=>'操作成功','data'=>[]];
    }
    /**
     * 确认送达
     */
    public function sure_goods_arrived($userId,$orderId)
    {
        if(!$orderId){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        $where = [
            'id' => $orderId,
            'b_user_id' => $userId
        ];
        $data = [
            'status' => '4'
        ];
        $res = Db::name('goods_order')->where($where)->update($data);
        if(!$res){
            return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
        }
        return ['code'=>1,'msg'=>'操作成功','data'=>[]];
    }
}