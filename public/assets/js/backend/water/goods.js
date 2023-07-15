define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'water/goods/index' + location.search,
                    // add_url: 'water/goods/add',
                    edit_url: 'water/goods/edit',
                    // del_url: 'water/goods/del',
                    // multi_url: 'water/goods/multi',
                    // import_url: 'water/goods/import',
                    table: 'b_goods',
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
                        {field: 'title', title: __('Goods.title'),operate: false},
                        //{field: 'image', title: __('Goods.image'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'sale_price', title: __('Goods.sale_price'), operate:false},
                        {field: 'goods_nums', title: __('Goods_nums'),operate:'BETWEEN'},
                        {
                            field: 'operate', 
                            operate:false,
                            title: '操作', 
                            table: table, 
                            events: Table.api.events.operate,
                            buttons:[
                                {
                                    name:'add_sale',
                                    text:'提醒补货',
                                    title:'提醒补货',
                                    classname: 'btn btn-xs btn-primary btn-dialog',
                                    icon:'',
                                    url:'water/goods/add_remind?id={ids}',
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