define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'water/add/index' + location.search,
                    add_url: 'water/add/add',
                    edit_url: 'water/add/edit',
                    del_url: 'water/add/del',
                    multi_url: 'water/add/multi',
                    import_url: 'water/add/import',
                    table: 'water_add',
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
                        {field: 'indexusers.nickname', title: __('Indexusers.nickname'), operate: 'LIKE'},
                        {field: 'indexusers.mobile', title: __('Indexusers.mobile')},
                        {field: 'indexusers.head_img', title: __('Indexusers.head_img'), operate: 'LIKE'},
                        {field: 'goods.title', title: __('Goods.title'), operate: 'LIKE'},
                        {field: 'goods.sale_price', title: __('Goods.sale_price'), operate:'BETWEEN'},
                        {field: 'goods.image', title: __('Goods.image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'add_nums', title: __('Add_nums')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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