<?php

namespace app\admin\controller\order;

use app\common\controller\Backend;
use Exception;
use think\Db;
use think\exception\PDOException;
use think\exception\ValidateException;
/**
 * 用户买水订单管理
 *
 * @icon fa fa-circle-o
 */
class Goods extends Backend
{
    
    /**
     * Goods模型对象
     * @var \app\admin\model\order\Goods
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\order\Goods;
        $this->view->assign("isPayList", $this->model->getIsPayList());
        $this->view->assign("isUseTicketList", $this->model->getIsUseTicketList());
        $this->view->assign("statusList", $this->model->getStatusList());
        $this->view->assign("typeList", $this->model->getTypeList());
        $this->view->assign("payTypeList", $this->model->getPayTypeList());
        $this->view->assign("stationList", $this->model->getStationList());
        $this->view->assign("stationsList", $this->model->getStationsList());
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
//        //当前是否为关联查询
        $this->relationSearch = true;
        $this->searchFields = 'id,type,is_use_ticket,indexusers.b_name,is_pay,status,createtime';
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        $stationId = input('b_user_id');
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $whereOr = [];
            if($stationId){
                $whereOr['goods.b_user_id'] = $stationId;
            }
            $list = $this->model
                    ->with(['useraddress','indexusers','usercoupons'])
                    ->where($where)
                    ->where($whereOr)
                    ->order($sort, $order)
                    ->paginate($limit);

//            foreach ($list as $row) {
//                $row->visible(['id','order_sn','is_pay','is_use_ticket','pay_time','status','createtime','arrive_time','sum_money','sum_nums','type']);
//                $row->visible(['useraddress']);
//				$row->getRelation('useraddress')->visible(['final_address','user_name','mobile','door']);
//				$row->visible(['indexusers']);
//				$row->getRelation('indexusers')->visible(['b_name','true_name']);
//				$row->visible(['usercoupons']);
//				$row->getRelation('usercoupons')->visible(['coupons_value']);
//            }
            $sumNums = $this->model->where($where)->where($whereOr)->count('id');
            $sumMoney = $this->model->where($where)->where($whereOr)->sum('sum_money');
            $sumMoney = round($sumMoney,2);
            $result = array("total" => $list->total(), "rows" => $list->items(),"extend" => ['sum_nums' => $sumNums, 'sum_money' => $sumMoney]);
            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->get($ids);
        $this->view->assign("workerList", $this->model->getWorkerList($row['b_user_id']));
        if (!$row) {
            $this->error(__('No Results were found'));
        }
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $params = $this->preExcludeFields($params);
                $result = false;
                Db::startTrans();
                try {
                    //是否采用模型验证
                    if ($this->modelValidate) {
                        $name = str_replace("\\model\\", "\\validate\\", get_class($this->model));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : $name) : $this->modelValidate;
                        $row->validateFailException(true)->validate($validate);
                    }
                    $result = $row->allowField(true)->save($params);
                    Db::commit();
                } catch (ValidateException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (PDOException $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                } catch (Exception $e) {
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                if ($result !== false) {
                    send_msg_to_user($ids);
                    $this->success();
                } else {
                    $this->error(__('No rows were updated'));
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    

}
