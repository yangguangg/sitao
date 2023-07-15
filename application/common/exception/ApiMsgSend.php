<?php
/**
 * Created by PhpStorm.
 * User: win
 * Date: 2019-03-26
 * Time: 14:47
 */

namespace app\common\exception;

use think\Exception;

class ApiMsgSend extends Exception{

    public $status = 400;
    public $message='error';
    public $data = null;
    public function __construct($params=[]){
        parent::__construct();
        if (!is_array($params)) {
            // throw new Exception('参数必须是数组');
            return;
        }
        $this->status = isset($params['status'])?$params['status']:$this->status;
        $this->message = isset($params['message'])?$params['message']:$this->message;
        $this->data = isset($params['data'])?$params['data']:$this->data;
    }

}