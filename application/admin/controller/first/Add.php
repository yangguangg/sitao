<?php

namespace app\admin\controller\first;

use app\common\controller\Backend;
use think\Db;
use Exception;
use think\exception\PDOException;
use think\exception\ValidateException;

/**
 * 商品库存申请管理
 *
 * @icon fa fa-circle-o
 */
class Add extends Backend
{
    
    /**
     * Add模型对象
     * @var \app\admin\model\first\Add
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\admin\model\first\Add;
        $this->view->assign("statusList", $this->model->getStatusList());
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
        //设置过滤方法
        $this->request->filter(['strip_tags', 'trim']);
        if ($this->request->isAjax()) {
            //如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            // $list = $this->model
            //         ->with(['indexusers'])
            //         ->where($where)
            //         ->order($sort, $order)
            //         ->group('id')
            //         ->paginate($limit);
            $list = Db::name('b_goods_add')->alias('add')
                ->join('index_users indexusers','add.b_user_id = indexusers.id','left')
                ->field('add.id,add.status,indexusers.mobile,indexusers.head_img,indexusers.b_name,indexusers.true_name')
                ->where($where)
                ->order($sort, $order)
                ->where('add.status','0')
                ->group('indexusers.id')
                ->paginate($limit);
            $result = array("total" => $list->total(), "rows" => $list->items());

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
                    if($params['status'] == '1'){
                        $addList = Db::name("b_goods_add")
                            ->where('b_user_id',$row['b_user_id'])
                            ->where('status','0')
                            ->select();
                        foreach ($addList as $v){
                            $where = [
                                'b_user_id' => $row['b_user_id'],
                                'goods_id' => $v['goods_id']
                            ];
                            $record = Db::name("b_goods")
                                ->where($where)
                                ->find();
                            if($record){
                                Db::name("b_goods")
                                    ->where($where)
                                    ->setInc('goods_nums',$v['add_num']);
                                Db::name("b_goods")
                                    ->where($where)
                                    ->update(['updatetime'=>time()]);
                            }else{
                                $data = [
                                    'b_user_id' => $row['b_user_id'],
                                    'goods_id' => $v['goods_id'],
                                    'goods_nums' => $v['add_num'],
                                    'updatetime' => time()
                                ];
                                Db::name("b_goods")->strict(false)->insert($data);
                            }
                            //减少总库存
                            Db::name('goods')->where('id',$v['goods_id'])->setDec('goods_nums',$v['add_num']);
                        }
                        $result = Db::name("b_goods_add")->where('b_user_id',$row['b_user_id'])->update(['status'=>'1']);
                    }
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
