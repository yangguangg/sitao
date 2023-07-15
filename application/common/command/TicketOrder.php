<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class TicketOrder extends Command
{
    protected function configure()
    {
        $this->setName('auto_cancel_ticket_order')->setDescription('auto_cancel_ticket_order per 15 minutes');
    }
    /**
     * 水票订单：下单15分钟未支付，自动取消
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
        $data = ['status'=>'2'];
        Db::name('ticket_order')->where($where)->update($data);
    }
}