<?php
namespace app\water\controller;

use app\admin\model\BucketCate;
use app\common\controller\StationBase;
use app\user\model\GoodsModel;
use app\water\action\GoodsAction;
use think\Db;

class Goods extends StationBase
{
    private $goodsAction;
    public function __construct()
    {
        parent::__construct();
        $this->goodsAction = new GoodsAction();
    }
    /**
     * 库存列表--已添加至接口文档
     */
    public function get_station_goods_list()
    {
        $userId = $this->userId;
        $page = input('page',1);
        $limit = input('limit',10);
        $list = $this->goodsAction->get_station_goods_list($userId,$page,$limit);
        $where = [
            'b_user_id' => $userId,
        ];
        $totalnum = Db::name('b_goods')->where($where)->sum('goods_nums');
        $totalnum = $totalnum > 0 ? $totalnum : 0;
        $data = [
            'list' => $list,
            'totalnum' => $totalnum,
        ];
        return commonReturnSuccess($data);
    }
    /**
     * 补货--已添加至接口文档
     */
    public function add_goods_num()
    {
        $userId = $this->userId;
        $goodsId = input('goods_id');
        $addNum = input('add_num');
        $res = $this->goodsAction->add_goods_num($userId,$goodsId,$addNum);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }
    /**
     * 库存商品详情--已添加至接口文档
     */
    public function get_goods_detail()
    {
        $userId = $this->userId;
        $goodsId = input('goods_id');
        $res = $this->goodsAction->get_goods_detail($userId,$goodsId);
        if($res['code']==0){
            return commonReturnError($res['msg']);
        }
        return commonReturnSuccess($res['data'],$res['msg']);
    }


    /**
     * 获取桶类型
     */
    public function get_bucket_list()
    {
        $model = new BucketCate();
        $list = $model->where(['status'=>1])->order('id desc')->field('id,name,price')->select();
        return commonReturnSuccess($list);
    }
}