define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'indexs/banners/index' + location.search,
                    add_url: 'indexs/banners/add',
                    edit_url: 'indexs/banners/edit',
                    del_url: 'indexs/banners/del',
                    multi_url: 'indexs/banners/multi',
                    import_url: 'indexs/banners/import',
                    dragsort_url:'',
                    table: 'index_banners',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                search:false,
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName:'weigh',
                columns: [
                    [
                        {checkbox: true},
                        // {field: 'id', title: __('Id')},
                        {field: 'banner_image', title: __('Banner_image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        // {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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