define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'users/barrel/index' + location.search,
                    // add_url: 'users/barrel/add',
                    edit_url: 'users/barrel/edit',
                    // del_url: 'users/barrel/del',
                    // multi_url: 'users/barrel/multi',
                    // import_url: 'users/barrel/import',
                    table: 'index_users',
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
                        {field: 'head_img', title: __('Head_img'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'used_barrel', title: __('Used_barrel'),operate:false},
                        {field: 'unused_barrel', title: __('Unused_barrel'),operate:false},
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