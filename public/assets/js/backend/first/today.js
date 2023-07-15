define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'first/today/index' + location.search,
                    // add_url: 'first/today/add',
                    // edit_url: 'first/today/edit',
                    // del_url: 'first/today/del',
                    // multi_url: 'first/today/multi',
                    // import_url: 'first/today/import',
                    table: 'goods_order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'order_sn', title: __('Order_sn'), operate: 'LIKE'},
                        {field: 'sum_money', title: __('Sum_money'), operate:'BETWEEN'},
                        {field: 'sum_nums', title: __('Sum_nums')},
                        {field: 'createtime', title: __('Createtime'), operate:false, addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3'),"4":__('Status 4'),"5":__('Status 5'),"6":__('Status 6')}, formatter: Table.api.formatter.status},
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