define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'water/audit/index' + location.search,
                    // add_url: 'water/audit/add',
                    edit_url: 'water/audit/edit',
                    del_url: 'water/audit/del',
                    // multi_url: 'water/audit/multi',
                    // import_url: 'water/audit/import',
                    table: 'index_users',
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
                        {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        {field: 'head_img', title: __('Head_img'), operate: false,events: Table.api.events.image, formatter: Table.api.formatter.image},
                        //{field: 'head_img', title: __('Head_img'), operate: false,events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'mobile', title: __('Mobile')},
                        {field: 'b_name', title:'水站名字', operate: 'LIKE'},
                        {field: 'true_name', title:'真实姓名', operate: 'LIKE'},
                        {field: 'positive_image', title: '身份证正面', operate: false,events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'reverse_image', title: '身份证反面', operate: false,events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'apply_address', title:'详细配送地址', operate: 'LIKE'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
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