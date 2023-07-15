<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\cache\driver\Redis as DriverRedis;

class GoodsOrder extends Command
{
    protected function configure()
    {
        $this->setName('auto_cancel_goods_order')->setDescription('auto_cancel_goods_order per 15 minutes');
    }
    /**
     * 商品订单：下单15分钟未支付，自动取消
     */
    protected function execute(Input $input, Output $output)
    {
        $nowTime = time();
        $intervalMinutes = 15;
        $where = [
            'status' => '0',
            'createtime' => ['<',date('Y-m-d H:i:s',$nowTime-$intervalMinutes*60)],
            'is_pay' => 0
        ];
        $list = Db::name('goods_order')->where($where)->select();
        foreach($list as $v){
            if($v['type']==2){
                $extendGoods = Db::name('goods_order_extend')->where('order_id',$v['id'])->find();
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
        }
        $data = ['status'=>'6'];
        Db::name('goods_order')->where($where)->update($data);
    }
}