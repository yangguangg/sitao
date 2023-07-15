define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'users/customer/index' + location.search,
                    // add_url: 'users/customer/add',
                    // edit_url: 'users/customer/edit',
                    // del_url: 'users/customer/del',
                    // multi_url: 'users/customer/multi',
                    // import_url: 'users/customer/import',
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
                        // {checkbox: true},
                        // {field: 'id', title: __('Id')},
                        {field: 'head_img', title: __('Head_img'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'nickname', title: __('Nickname'), operate: 'LIKE'},
                        {field: 'ya_num', title: __('押桶数量'), operate: 'LIKE'},
                        {field: 'qian_num', title: __('欠桶数量'), operate: 'LIKE'},
                        {field: 'mobile', title: __('Mobile')},
                        // {field: 'head_img', title: __('Head_img'), operate: false},
                        {
                            field: 'operate', 
                            title: __('AddAct'), 
                            table: table, 
                            events: Table.api.events.operate,
                            buttons:[
                                {
                                    name:'add_sale',
                                    text:'',
                                    title:'添加用户优惠',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon:'fa fa-list',
                                    url:'users/sales/index?c_user_id={ids}',
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
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