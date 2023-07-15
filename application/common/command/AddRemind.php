<?php
namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class AddRemind extends Command
{
    protected function configure()
    {
        $this->setName('add_goods_nums_remind')->setDescription('add_goods_nums_remind per day');
    }
    /**
     * 商品订单：下单15分钟未支付，自动取消
     */
    protected function execute(Input $input, Output $output)
    {
        $list = Db::name('b_goods')->select();
        // foreach()
    }
}