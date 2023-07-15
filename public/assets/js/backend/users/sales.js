define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'users/sales/index' + location.search,
                    add_url: 'users/sales/add?c_user_id='+Config.c_user_id,
                    edit_url: 'users/sales/edit?c_user_id='+Config.c_user_id,
                    del_url: 'users/sales/del',
                    multi_url: 'users/sales/multi',
                    import_url: 'users/sales/import',
                    table: 'user_sales',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                search:false,
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'goods.title', title: __('Goods.title'), operate: 'LIKE'},
                        {field: 'indexusers.mobile', title: __('Indexusers.mobile')},
                        {field: 'indexusers.nickname', title: __('Indexusers.nickname'), operate: 'LIKE'},
                        {field: 'sale_ratio', title: __('Sale_ratio'), operate:'BETWEEN'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
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