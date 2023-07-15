<?php


namespace app\common\exception;

use Exception;
use think\exception\Handle;

class ApiMsgSendHandler extends Handle
{
    public function render(Exception $e){
        if($e instanceof ApiMsgSend){
            $result=[
                'message' => $e->message,
                'status' => $e->status,
                'data' => $e->data
            ];
        }
        else{
            return parent::render($e);
        }
        header("Access-Control-Allow-Origin: *");
        header('Access-Control-Allow-Headers: X-Requested-With,X_Requested_With');
        return json($result, 200);
    }
}