define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'ya_bucket/index' + location.search,
                    // add_url: 'ya_bucket/add',
                    // edit_url: 'ya_bucket/edit',
                    // del_url: 'ya_bucket/del',
                    // multi_url: 'ya_bucket/multi',
                    // import_url: 'ya_bucket/import',
                    table: 'ya_bucket',
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
                        {field: 'order_sn', title: __('Order_sn'), operate: 'LIKE'},
                        // {field: 'bucket_cate_id', title: __('Bucket_cate_id')},
                        {field: 'bucketcate.name', title: __('Bucket_cate_id'), operate: 'LIKE'},
                        {field: 'num', title: __('Num')},
                        {field: 'price', title: __('Price'), operate:'BETWEEN'},
                        {field: 'mobile', title: __('Mobile'), operate: 'LIKE'},
                        // {field: 'index_users_id', title: __('Index_users_id')},
                        {field: 'indexusers.nickname', title: __('Index_users_id'), operate: 'LIKE'},
                        {field: 'water.b_name', title: __('水站'), operate: 'LIKE'},
                        // {field: 'water_id', title: __('Water_id')},
                        {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3'),"4":__('Status 4'),"5":__('Status 5'),"6":__('Status 6')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'paytime', title: __('Paytime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'tuitime', title: __('Tuitime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'confirmtime', title: __('Confirmtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'completetime', title: __('Completetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'canceltime', title: __('Canceltime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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