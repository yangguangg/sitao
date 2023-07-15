define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/goods/index' + location.search,
                    // add_url: 'order/goods/add',
                    edit_url: 'order/goods/edit',
                    del_url: 'order/goods/del',
                    // multi_url: 'order/goods/multi',
                    // import_url: 'order/goods/import',
                    table: 'goods_order',
                }
            });
            var table = $("#table");
            //当表格数据加载完成时
            table.on('load-success.bs.table', function (e, data) {
                //这里可以获取从服务端获取的JSON数据
                console.log(data);
                //这里我们手动设置底部的值
                $("#sum_num").text(data.extend.sum_nums);
                $("#sum_money").text(data.extend.sum_money);
            });
            // 初始化表格
            table.bootstrapTable({
                //启用固定列
                fixedColumns: true,
                //固定右侧列数
                fixedRightNumber: 1,
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'type', title: __('Type'), searchList: {0:__('Type 0'),1:__('Type 1'),2:__('Type 2')}, formatter: Table.api.formatter.normal},
                        {field: 'order_sn', title: __('Order_sn'), operate: false},
                        {field: 'indexusers.b_name', title: __('Indexusers.b_name'), operate:false},
                        {field: 'indexusers.true_name', title: __('Indexusers.true_name'), operate: false},
                        {field: 'sum_money', title: __('Sum_money'), operate:false},
                        {field: 'sum_nums', title: __('Sum_nums'),operate:false},
                        {field: 'is_use_ticket', title: __('Is_use_ticket'), searchList: {"0":__('Is_use_ticket 0'),"1":__('Is_use_ticket 1')}, formatter: Table.api.formatter.normal},
                        {field: 'usercoupons.coupons_value', title: __('Usercoupons.coupons_value'), operate:false},
                        {field: 'is_pay', title: __('Is_pay'), searchList: {"0":__('Is_pay 0'),"1":__('Is_pay 1')}, formatter: Table.api.formatter.normal},
                        {field: 'pay_time', title: __('Pay_time'), operate:false, addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3'),"4":__('Status 4'),"5":__('Status 5'),"6":__('Status 6')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'useraddress.final_address', title: __('Useraddress.final_address'), operate: false},
                        {field: 'useraddress.door', title:'门牌号', operate: false},
                        {field: 'useraddress.user_name', title: __('Useraddress.user_name'), operate: false},
                        {field: 'useraddress.mobile', title: __('Useraddress.mobile'),operate: false},
                        {field: 'arrive_time', title: __('Arrive_time'), operate: false},
                        {
                            field: 'order_id', 
                            operate:false,
                            title: '操作', 
                            table: table, 
                            events: Table.api.events.operate,
                            buttons:[
                                {
                                    name:'goods_list',
                                    text:'商品列表',
                                    title:'订单商品列表',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon:'fa fa-list',
                                    url:'order/extend/index?order_id={ids}',
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name:'goods_list',
                                    text:'评价列表',
                                    title:'订单评价列表',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon:'fa fa-list',
                                    url:'order/comment/index?order_id={ids}',
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        },
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});