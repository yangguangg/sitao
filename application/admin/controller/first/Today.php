<?php

namespace app\admin\controller\first;

use app\common\controller\Backend;
use think\Db;

/**
 * 用户买水订单管理
 *
 * @icon fa fa-circle-o
 */
class Today extends Backend
{
    
    /**
     * Today模型对象
     * @var \app\admin\model\first\Today
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\first\Today;
        $this->view->assign("isPayList", $this->model->getIsPayList());
        $this->view->assign("isUseTicketList", $this->model->getIsUseTicketList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("payTypeList", $this->model->getPayTypeList());
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
        $this->relationSearch = false;
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        $todayDate = date('Y-m-d');
        $whereOther = [
            'createtime' => ['between time',[$todayDate.' 00:00:01',$todayDate.' 23:59:59']]
        ];
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();

            $list = $this->model
                    ->where($whereOther)
                    ->where($where)
                    ->order($sort, $order)
                    ->paginate($limit);

            foreach ($list as $row) {
                $row->visible(['id','order_sn','status','createtime','sum_money','sum_nums']);
                
            }

            $result = array("total" => $list->total(), "rows" => $list->items());

            return json($result);
        }
        $sumNums = Db::name('goods_order')->where($whereOther)->count('id');
        $whereOther['is_pay'] = '1';
        $sumMoney = Db::name('goods_order')->where($whereOther)->sum('sum_money');
        $sumMoney = round($sumMoney,2);
        $payNums = Db::name('goods_order')->where($whereOther)->count('id');
        $this->assign('sum_nums',$sumNums);
        $this->assign('sum_money',$sumMoney);
        $this->assign('pay_nums',$payNums);
        return $this->view->fetch();
    }

}
