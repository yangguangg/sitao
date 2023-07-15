define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'users/withdrawal/index' + location.search,
                    // add_url: 'users/withdrawal/add',
                    // edit_url: 'users/withdrawal/edit',
                    del_url: 'users/withdrawal/del',
                    // multi_url: 'users/withdrawal/multi',
                    // import_url: 'users/withdrawal/import',
                    table: 'withdrawal_record',
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
                        {field: 'act_money', title: __('Act_money'), operate:'BETWEEN'},
                        {field: 'final_money', title: __('Final_money'), operate:'BETWEEN'},
                        {field: 'get_way', title: __('Get_way'), searchList: {"0":__('Get_way 0'),"1":__('Get_way 1')}, formatter: Table.api.formatter.normal},
                        {field: 'account', title: __('Account'), operate: 'LIKE'},
                        {field: 'true_name', title: __('True_name'), operate: 'LIKE'},
                        {field: 'bank_name', title: __('Bank_name'), operate: 'LIKE'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                        {
                            field: 'operate', 
                            title: '操作', 
                            table: table, 
                            events: Table.api.events.operate,
                            buttons:[
                                {
                                    name:'add_sale',
                                    text:'提现审核',
                                    title:'提现审核',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon:'',
                                    url:'users/withdrawal/edit',
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
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