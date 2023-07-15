define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'water/users/index' + location.search,
                    // add_url: 'water/users/add',
                    // edit_url: 'water/users/edit',
                    // del_url: 'water/users/del',
                    // multi_url: 'water/users/multi',
                    // import_url: 'water/users/import',
                    table: 'index_users',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                search:false,
                commonSearch:true,
                searchFormVisible:true,
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'b_name', title:'水站名称', operate: 'LIKE'},
                        {field: 'true_name', title:'水站站长', operate: 'LIKE'},
                        {field: 'nickname', title: __('Nickname'), operate: false},
                        {field: 'head_img', title: __('Head_img'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'mobile', title: '水站电话'},
                        // {field: 'status', title: __('Status'),operate: false, searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                        {
                            field: 'operate', 
                            title: '操作', 
                            table: table, 
                            events: Table.api.events.operate,
                            buttons:[
                                {
                                    name:'order_list',
                                    text:'订单统计',
                                    title:'订单统计',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon:'fa fa-list',
                                    url:'order/goods/index?b_user_id={ids}',
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name:'add_sale',
                                    text:'库存',
                                    title:'库存管理',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon:'fa fa-list',
                                    url:'water/goods/index?b_user_id={ids}',
                                    visible: function (row) {
                                        //返回true时按钮显示,返回false隐藏
                                        return true;
                                    }
                                },
                                {
                                    name:'set_location',
                                    text:'位置',
                                    title:'定位',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon:'fa fa-map-marker',
                                    url:'water/Users/edit',
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