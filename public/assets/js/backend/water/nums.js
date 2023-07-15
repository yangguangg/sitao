define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'water/nums/index' + location.search,
                    // add_url: 'water/nums/add',
                    edit_url: 'water/nums/edit',
                    del_url: 'water/nums/del',
                    // multi_url: 'water/nums/multi',
                    // import_url: 'water/nums/import',
                    table: 'b_goods',
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
                        {field: 'indexusers.b_name', title: __('Indexusers.b_name'), operate: 'LIKE'},
                        {field: 'indexusers.true_name', title: __('Indexusers.true_name'), operate: 'LIKE'},
                        {field: 'goods.title', title: __('Goods.title'), operate: 'LIKE'},
                        {field: 'goods.image', title: __('Goods.image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'goods_nums', title: __('Goods_nums')},
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