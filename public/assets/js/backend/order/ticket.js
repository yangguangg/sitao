define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/ticket/index' + location.search,
                    // add_url: 'order/ticket/add',
                    // edit_url: 'order/ticket/edit',
                    // del_url: 'order/ticket/del',
                    // multi_url: 'order/ticket/multi',
                    // import_url: 'order/ticket/import',
                    table: 'ticket_order',
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
                        // {checkbox: true},
                        {field: 'order_sn', title: __('Order_sn'), operate: 'LIKE'},
                        {field: 'indexusers.nickname', title: __('Indexusers.nickname'), operate: 'LIKE'},
                        {field: 'indexusers.mobile', title: __('Indexusers.mobile')},
                        {field: 'ticket_title', title: __('Ticket_title'), operate: false},
                        {field: 'getting_nums', title: __('Getting_nums'),operate:false},
                        {field: 'amount', title: __('Amount'), operate:false},
                        {field: 'usercoupons.coupons_value', title: __('Usercoupons.coupons_value'), operate:false},
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