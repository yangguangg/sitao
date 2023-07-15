define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'qian_bucket_report/index' + location.search,
                    // add_url: 'qian_bucket_report/add',
                    // edit_url: 'qian_bucket_report/edit',
                    // del_url: 'qian_bucket_report/del',
                    // multi_url: 'qian_bucket_report/multi',
                    // import_url: 'qian_bucket_report/import',
                    table: 'qian_bucket_report',
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
                        // {field: 'id', title: __('Id')},
                        // {field: 'bucket_cate_id', title: __('Bucket_cate_id')},
                        {field: 'bucketcate.name', title: __('Bucketcate.name'), operate: 'LIKE'},
                        {field: 'num', title: __('Num')},
                        {field: 'hai_num', title: __('Hai_num')},
                        {field: 'left_num', title: __('Left_num')},
                        // {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        // {field: 'index_users_id', title: __('Index_users_id')},
                        // {field: 'water_id', title: __('Water_id')},
                        {field: 'indexusers.nickname', title: __('Index_users_id'), operate: 'LIKE'},
                        {field: 'water.b_name', title: __('水站'), operate: 'LIKE'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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