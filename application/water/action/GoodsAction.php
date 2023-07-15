<?php
namespace app\water\action;

use app\user\model\GoodsModel;
use think\Controller;
use think\Db;

class GoodsAction extends Controller
{
    /**
     * 获取库存列表
     */
    public function get_station_goods_list($userId,$page,$limit)
    {
        // $limit = 20;
        $where = [
            'status' => '1',
            'goods_nums' => ['>',0],
            'deletetime' => null
        ];
        $field = 'id,title,sale_price,tag_ids,is_avoid,image,is_intro';
        $goodsModel = new GoodsModel();
        $list = $goodsModel->field($field)->where($where)->page($page,$limit)->select();
        foreach($list as &$v){
            $where = [
                'b_user_id' => $userId,
                'goods_id' => $v['id']
            ];
            $goodsNums = Db::name('b_goods')->where($where)->value('goods_nums');
            if(!$goodsNums){
                $goodsNums = 0;
            }
            $v['goods_nums'] = $goodsNums;
        }
        return $list;
    }
    /**
     * 增加库存
     */
    public function add_goods_num($userId,$goodsId,$addNum)
    {
        if(!$goodsId){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        if($addNum<=0){
            return ['code'=>0,'msg'=>'请选择补货数量','data'=>[]];
        }
        $data = [
            'goods_id' => $goodsId,
            'add_num' => $addNum,
            'createtime' => time(),
            'b_user_id' => $userId
        ];
        $res = Db::name("b_goods_add")->strict(false)->insert($data);
        if(!$res){
            return ['code'=>0,'msg'=>'系统错误，请稍后再试','data'=>[]];
        }
        return ['code'=>1,'msg'=>'请耐心等待审核哦','data'=>[]];
    }
    /**
     * 商品库存详情
     */
    public function get_goods_detail($userId,$goodsId)
    {
        if(!$goodsId){
            return ['code'=>0,'msg'=>'关键参数缺失','data'=>[]];
        }
        $field = 'id,title,sale_price,tag_ids,is_avoid,image,is_intro';
        $goodsModel = new GoodsModel();
        $where = ['id'=>$goodsId];
        $detail = $goodsModel->field($field)->where($where)->find();
        $where = [
            'b_user_id' => $userId,
            'goods_id' => $goodsId
        ];
        $goodsNums = Db::name('b_goods')->where($where)->value('goods_nums');
        if(!$goodsNums){
            $goodsNums = 0;
        }
        $detail['goods_nums'] = $goodsNums;
        return ['code'=>1,'msg'=>'查询成功','data'=>$detail];
    }
}