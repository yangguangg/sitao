define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/barrel/index' + location.search,
                    // add_url: 'order/barrel/add',
                    // edit_url: 'order/barrel/edit',
                    // del_url: 'order/barrel/del',
                    // multi_url: 'order/barrel/multi',
                    // import_url: 'order/barrel/import',
                    table: 'barrel_order',
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
                        {field: 'indexusers.nickname', title: __('Indexusers.nickname'), operate: 'LIKE'},
                        {field: 'indexusers.mobile', title: __('Indexusers.mobile')},
                        {field: 'barrel_nums', title: __('Barrel_nums')},
                        {field: 'amount', title: __('Amount'), operate:'BETWEEN'},
                        {field: 'is_pay', title: __('Is_pay'), searchList: {"0":__('Is_pay 0'),"1":__('Is_pay 1')}, formatter: Table.api.formatter.normal},
                        {field: 'pay_time', title: __('Pay_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
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