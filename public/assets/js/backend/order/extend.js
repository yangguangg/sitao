define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/extend/index' + location.search,
                    // add_url: 'order/extend/add',
                    // edit_url: 'order/extend/edit',
                    // del_url: 'order/extend/del',
                    // multi_url: 'order/extend/multi',
                    // import_url: 'order/extend/import',
                    table: 'goods_order_extend',
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
                        {field: 'goods_title', title: __('Goods_title'), operate: 'LIKE'},
                        //{field: 'goods_image', title: __('Goods_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'sale_price', title: __('Sale_price'), operate:'BETWEEN'},
                        {field: 'goods_nums', title: __('Goods_nums')},
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