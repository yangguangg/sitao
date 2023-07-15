define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'ya_bucket_back/index' + location.search,
                    // add_url: 'ya_bucket_back/add',
                    // edit_url: 'ya_bucket_back/edit',
                    // del_url: 'ya_bucket_back/del',
                    // multi_url: 'ya_bucket_back/multi',
                    // import_url: 'ya_bucket_back/import',
                    table: 'ya_bucket_back',
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
                        {field: 'aliname', title: __('Aliname'), operate: 'LIKE'},
                        {field: 'aliaccount', title: __('Aliaccount'), operate: 'LIKE'},
                        {field: 'remark', title: __('退桶备注'), operate: 'LIKE'},
                        // {field: 'index_users_id', title: __('Index_users_id')},
                        {field: 'indexusers.nickname', title: __('Index_users_id'), operate: 'LIKE'},
                        {field: 'water.b_name', title: __('水站'), operate: 'LIKE'},
                        // {field: 'water_id', title: __('Water_id')},
                        {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'confirmtime', title: __('Confirmtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'completetime', title: __('Completetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},

                        // {field: 'yabucket.order_sn', title: __('Yabucket.order_sn'), operate: 'LIKE'},
                        // {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                        {field: 'operate', title: __('Operate'), table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'toexamine',
                                    text: __('打款'),
                                    title: __('打款'),
                                    classname: 'btn btn-xs btn-success btn-magic btn-ajax',
                                    icon: 'fa fa-apply',
                                    url: 'ya_bucket_back/dakuan',
                                    confirm: '确认已打款吗?',
                                    refresh:true,
                                    hidden:function ($row) {
                                        if($row.status==2){
                                            return false;
                                        }else{
                                            return true;
                                        }
                                    },
                                    success: function (data, ret) {
                                        Layer.alert(ret.msg);
                                        //如果需要阻止成功提示，则必须使用return false;
                                        //return false;
                                        $('.btn-refresh').trigger('click');
                                    },
                                    error: function (data, ret) {
                                        console.log(data, ret);
                                        Layer.alert(ret.msg);
                                        return false;
                                    }
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
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