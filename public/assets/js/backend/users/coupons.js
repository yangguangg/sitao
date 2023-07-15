define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'users/coupons/index' + location.search,
                    add_url: 'users/coupons/add',
                    // edit_url: 'users/coupons/edit',
                    // del_url: 'users/coupons/del',
                    // multi_url: 'users/coupons/multi',
                    // import_url: 'users/coupons/import',
                    table: 'user_coupons',
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
                        {field: 'indexusers.head_img', title: __('Indexusers.head_img'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'indexusers.mobile', title: __('Indexusers.mobile')},
                        {field: 'threshold_price', title: __('Threshold_price'), operate:'BETWEEN'},
                        {field: 'coupons_value', title: __('Coupons_value'), operate:'BETWEEN'},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:false, addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'end_time', title: __('End_time'), operate:false, addclass:'datetimerange', autocomplete:false},
                        {field: 'usedtime', title: __('Usedtime'), operate:false, addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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