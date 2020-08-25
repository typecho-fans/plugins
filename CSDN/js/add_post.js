const nanoBar = new Nanobar();

function add_post() {

    function error(XMLHttpRequest, textStatus, errorThrown) {
        toastr.error('出现未知异常 ' + errorThrown + '可以使用开发者模式查看改请求add-post的reponse确认出错信息')
    }

    function beforeSend() {
        toastr.options = {
            "closeButton": true,
            "newestOnTop": true,
            "positionClass": "toast-top-center",
            "preventDuplicates": false,
            "onclick": null,
            // "showDuration": "0",
            "hideDuration": "0",
            "timeOut": "0",
            "extendedTimeOut": "0",
            "showEasing": "swing",
            "hideEasing": "linear",
            "showMethod": "fadeIn",
            "hideMethod": "fadeOut"
        }
        toastr.info('正在导入数据中,请耐心等待,不要离开当前页面，如果文章太多，将会需要很长一段时间，。。。')
    }

    function get_list() {
        return new Promise((resolve) => {
            $.ajax(
                {
                    type: "GET",//通常会用到两种：GET,POST。默认是：GET
                    url: "../get-articles-id",//(默认: 当前页地址) 发送请求的地址
                    dataType: "json",//预期服务器返回的数据类型。
                    beforeSend: beforeSend, //发送请求
                    success: function (msg) {
                        if (msg.code !== 1) {
                            toastr.remove()
                            toastr.error('发生了错误:' + msg.msg)
                        } else {
                            resolve(msg)
                        }
                    }, //请求成功
                    error: error,//请求出错
                });
        })
    }

    get_list().then(function (data) {
        if (data.code === 1) {
            total = data.data.length
            data.data.some((e, index) => {
                aid = e[0]
                date = e[1]
                $.ajax(
                    {
                        type: "POST",//通常会用到两种：GET,POST。默认是：GET
                        url: "../add-article",//(默认: 当前页地址) 发送请求的地址
                        dataType: "json",//预期服务器返回的数据类型。
                        data: {
                            'aid': aid,
                            'date': date,
                        },
                        // async: false,
                        success: function (msg) {
                            let temp;
                            if (msg.code == 1) {
                                temp = (index + 1) / total * 100
                                nanoBar.go(temp)
                                if (temp == 100) {
                                    toastr.remove()
                                    toastr.info('导入完成')
                                }
                            } else {
                                toastr.error('发生了错误:' + msg.msg)
                            }
                        }, //请求成功
                        error: error,//请求出错
                    });
            })
        } else {
            toastr.remove()
            toastr.error(data.msg)
        }
    })
}


