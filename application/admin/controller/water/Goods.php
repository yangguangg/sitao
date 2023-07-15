<?php

namespace app\admin\controller\water;

use app\common\controller\Backend;
use think\Db;

/**
 * 水站库存管理
 *
 * @icon fa fa-circle-o
 */
class Goods extends Backend
{
    
    /**
     * Goods模型对象
     * @var \app\admin\model\water\Goods
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\water\Goods;

    }

    public function import()
    {
        parent::import();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/admin/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */
    

    /**
     * 查看
     */
    public function index()
    {
        //当前是否为关联查询
        $this->relationSearch = true;
        $bUserId = input('b_user_id');
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $list = Db::name('b_goods')->alias('goods')
                ->join('goods b','goods.goods_id = b.id','left')
                ->field('goods.id,goods.goods_nums,b.title,b.sale_price,b.image')
                ->where('goods.b_user_id',$bUserId)
                ->where($where)
                ->order($sort, $order)
                ->paginate($limit);

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 提醒发货
     */
    public function add_remind()
    {
        $id = input('id');
        $info = Db::name('b_goods')->where('id',$id)->find();
        $bUserId = $info['b_user_id'];
        $goodsId = $info['goods_id'];
        $goodsTitle = Db::name("goods")->where('id',$goodsId)->value('title');
        $openId = Db::name('index_users')->where('id',$bUserId)->value('open_id');
        //发送模版消息
        $template_id = "N0KNHvNru_WYOyMyC5qxyENcwlZv_mx_MMvSw98_uR0";
        $url = 'pages/serveStock/serveStock';
        $data = [
            "touser"=>$openId,
            "template_id"=>$template_id,
            "page"=>$url,
            "data"=>[
                "thing1"=> [
                    "value"=>$goodsTitle
                ],
                "date4"=>[
                    "value"=>date("Y-m-d H:i")
                ],
                "thing5"=> [
                    "value"=>"商品库存不足,请及时申请补货"
                ]
            ]
        ];
        $str = json_encode($data);
        $access_token = get_wx_access_token();
        $res = _request('https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token='.$access_token,true,'post', $str);
        $res = json_decode($res,true);
        if($res['errcode']==43101){
            $this->error('目标用户没有订阅通知功能');
        }
        $this->success('补货提醒已发送','','',1000000);
    }
}
