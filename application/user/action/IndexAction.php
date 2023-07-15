<?php

namespace app\user\action;

use app\user\model\BannersModel;
use app\user\model\CommentModel;
use app\user\model\GoodsModel;
use app\user\model\KillGoodsModel;
use think\Controller;
use think\Db;

class IndexAction extends Controller
{
    /**
     * 首页轮播图
     */
    public function get_banners_list()
    {
        $bannerModel = new BannersModel();
        $list = $bannerModel
            ->field('id,banner_image')
            ->order('weigh', 'desc')
            ->select();
        return $list;
    }

    /**
     * 轮播图详情
     */
    public function get_banner_detail($bannerId)
    {
        if (!$bannerId) {
            return ['code' => 0, 'msg' => '关键参数缺失', 'data' => []];
        }
        $detail = Db::name('index_banners')
            ->field('id,intro_content')
            ->where('id', $bannerId)
            ->find();
        return ['code' => 1, 'msg' => '查询成功', 'data' => $detail];
    }

    /**
     * 获取商品分类
     */
    public function get_goods_cates()
    {
        $list = Db::name('goods_cates')
            ->field('id,name')
            ->where('deletetime', null)
            ->select();
        return $list;
    }

    /**
     * 获取商品列表
     */
    public function get_goods_list($page, $limit, $token, $cateId, $isIntro, $vip = 0)
    {
        $goodsModel = new GoodsModel();
        $where = [
            'status' => '1',
            'goods_nums' => ['>', 0],
            // 'tickets.status' => ['neq',3],
            'deletetime' => null
        ];
        if ($cateId) {
            $where['cate_id'] = $cateId;
        }
        if ($isIntro == 1) {
            $where['is_intro'] = '1';
        }
        $goodsList = $goodsModel
            ->with(['tickets'=>function($q){
                $q->where('status','neq',3);
            }])
            ->field('id,title,original_price,sale_price,tag_ids,is_avoid,image,sale_sums,is_intro')
            ->where($where)
            // ->where('tickets.status','neq',3)
            ->page($page, $limit)
            ->select();
        if ($token && cache($token)) {
            $userInfo = cache($token);
            $userId = $userInfo['id'];
            $goodsIdList = Db::name('user_sales')->where('c_user_id', $userId)->column('goods_id');
            foreach ($goodsList as &$v) {
                $saleRatio = 1;
                $v['vip_price'] = 0;
                if (in_array($v['id'], $goodsIdList)) {
                    $saleRatio = Db::name('user_sales')
                        ->where('c_user_id', $userId)
                        ->where('goods_id', $v['id'])
                        ->value('sale_ratio');
                    $saleRatio = $saleRatio / 100;
                } elseif ($vip == 2) {
                    $saleRatio = config('site.vip_rate') * 0.01;
                }
                $v['vip_price'] = round($v['sale_price'] * $saleRatio, 2);
            }
        }
        return $goodsList;
    }

    /**
     * 获取商品详情
     */
    public function get_goods_detail($goodsId, $token, $vip = 0)
    {
        if (!$goodsId) {
            return ['code' => 0, 'msg' => '关键参数缺失', 'data' => []];
        }
        $goodsModel = new GoodsModel();
        $field = 'id,title,original_price,sale_price,tag_ids,is_avoid,images,sale_sums,goods_content,is_intro';
        $detail = $goodsModel->with(['tickets'])->field($field)->where('id', $goodsId)->find();
        $detail['vip_price'] = 0;
        if ($token && cache($token)) {
            $userInfo = cache($token);
            $userId = $userInfo['id'];
            $goodsIdList = Db::name('user_sales')->where('c_user_id', $userId)->column('goods_id');
            $saleRatio = 1;
            if (in_array($detail['id'], $goodsIdList)) {
                $saleRatio = Db::name('user_sales')
                    ->where('c_user_id', $userId)
                    ->where('goods_id', $detail['id'])
                    ->value('sale_ratio');
                $saleRatio = $saleRatio / 100;
            } elseif ($vip == 2) {
                $saleRatio = config('site.vip_rate') * 0.01;
            }
            $detail['vip_price'] = round($detail['sale_price'] * $saleRatio, 2);
            
        }
        $detail['icp'] = config('site.beian');
        $detail['hot_phone'] = config('site.service_phone');
        return ['code' => 1, 'msg' => '查询成功', 'data' => $detail];
    }

    /**gfd
     * 获取商品评价列表
     */
    public function get_goods_comments($goodsId, $page, $limit)
    {
        if (!$goodsId) {
            return ['code' => 0, 'msg' => '关键参数缺失', 'data' => []];
        }
        $list = CommentModel::with('users')
            ->field('comment,images,c_user_id,level_status')
            ->where('goods_id', $goodsId)
            ->where('deletetime', null)
            ->page($page, $limit)
            ->select();
        return ['code' => 1, 'msg' => '查询成功', 'data' => $list];
    }

    /**
     * 获取 水票 列表 $isHot=0 全部，=1 热销
     */
    public function get_tickets_list($isHot, $page, $limit, $vip = 1)
    {
        $where = [
            'is_has_ticket' => 1,
            'deletetime' => null,
            'cate_id' => 1
        ];
        $goodsIdList = Db::name('goods')->where($where)->page($page, $limit)->column('id');
        $list = [];
        $field = 'id ticket_id,title,buy_nums,give_nums,status,goods_id';
        foreach ($goodsIdList as $v) {
            $where = [
                'deletetime' => null,
                'goods_id' => $v,
                'status' => ['neq', 3],//非vip增票可以买
            ];
            if ($isHot == 1) {
                $where['is_hot'] = 1;
            }
            $ticketList = Db::name('goods_tickets')->field($field)->where($where)->order('buy_nums', 'asc')->select();
            if (!empty($ticketList)) {
                $data = [];
                $goodsModel = new GoodsModel();
                $data['goods_info'] = $goodsModel->field('id goods_id,title,image,sale_price')->where('id', $v)->find();
                $data['goods_info']['vip_price'] = 0;
                if ($vip == 2) {
                    $saleRatio = config('site.vip_rate') * 0.01;
                    $data['goods_info']['vip_price'] = round($data['goods_info']['sale_price'] * $saleRatio,2);
                }
                $data['ticket_list'] = $ticketList;
                $list[] = $data;
            }
        }
        return $list;
    }

    /**
     * 获取品牌列表
     */
    public function get_brand_list()
    {
        $field = 'id,brand_name';
        $list = Db::name('goods_brands')
            ->field($field)
            ->where('deletetime', null)
            ->select();
        return $list;
    }

    /**
     * 水票列表--点击-购买--获取水票规格
     */
    public function get_ticket_detail($goodsId, $isHot,$vip=1)
    {
        if (!$goodsId) {
            return ['code' => 0, 'msg' => '关键参数缺失', 'data' => []];
        }
        $goodsModel = new GoodsModel();
        $goodsInfo = $goodsModel->field('id,title,image,sale_price')->where('id', $goodsId)->find();
        $where = [
            'goods_id' => $goodsId,
            'deletetime' => null,
            'status' => ['neq', 3],//非vip增票可以买
        ];
        if ($isHot == 1) {
            $where['is_hot'] = 1;
        }
        $field = 'id ticket_id,title,buy_nums,give_nums';
        $packageList = Db::name('goods_tickets')->field($field)->where($where)->order('buy_nums', 'asc')->select();
        $goodsInfo['vip_price'] = 0;
        if ($vip == 2) {
            $saleRatio = config('site.vip_rate') * 0.01;
            $goodsInfo['vip_price'] = round($goodsInfo['sale_price'] * $saleRatio,2);
        }
        $goodsInfo['vip'] = $vip;
        $data = [
            'goods_info' => $goodsInfo,
            'package_list' => $packageList
        ];
        return ['code' => 1, 'msg' => 'success', 'data' => $data];
    }

    /**
     * 限时秒杀--商品列表
     */
    public function get_seconds_kill_list($page, $limit)
    {
        $where = [
            'status' => '0',
            'end_time' => ['>', date("Y-m-d H:i:s")]
        ];
        $field = 'id,goods_id,nums,start_time,end_time,kill_price';
        $list = KillGoodsModel::with('goods')
            ->field($field)
            ->where($where)
            ->page($page, $limit)
            ->select();
        return $list;
    }

    /**
     * 限时秒杀--商品详情
     */
    public function get_kill_goods_detail($killGoodsId)
    {
        if (!$killGoodsId) {
            return ['code' => 0, 'msg' => '关键参数缺失', 'data' => []];
        }
        $field = 'id,goods_id,nums,start_time,end_time,kill_price';
        $detail = KillGoodsModel::with('detail')
            ->field($field)
            ->where('id', $killGoodsId)
            ->find();
        return ['code' => 1, 'msg' => 'success', 'data' => $detail];
    }
}