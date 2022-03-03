<?php


// 获取菜单
$menus = json_decode(file_get_contents(__DIR__ . '/interface_menu.json'), true);


// 遍历files构建html
function buildHtml($menus) {
    $html = '';
    foreach ($menus as $module => $classes) {
        $html .= '<li><a href="javascript:;">' . $module . '</a>' . PHP_EOL . '<ul>';
        foreach ($classes as $c => $m) {
            $html .= '<li><a href="javascript:;">' . $c . '</a>' . PHP_EOL . '<ul>';
            foreach ($m as $n => $u) {
                $html .= '<li><a href="javascript:;" onclick="page(this)" data-url="'. $u .'">'
                    . $n
                    . '</a></li>'
                    . PHP_EOL;
            }
            $html .= '</ul></li>' . PHP_EOL;
        }
        $html .= '</ul></li>' . PHP_EOL;
    }
    return $html;
}

$tree_html = buildHtml($menus);

?>

<!doctype html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>接口文档</title>
    <script src="https://cdn.bootcss.com/jquery/3.4.1/jquery.min.js"></script>
    <script>
        (function($) {
            $.fn.menu = function(b) {
                var c,
                    item,
                    httpAdress;
                b = jQuery.extend({
                        Speed: 220,
                        autostart: 1,
                        autohide: 1
                    },
                    b);
                c = $(this);
                item = c.children("ul").parent("li").children("a");
                httpAdress = window.location;
                item.addClass("inactive");
                item.append('<span></span>')
                function _item() {
                    var a = $(this);
                    if (b.autohide) {
                        a.parent().parent().find(".active").parent("li").children("ul").slideUp(b.Speed / 1.2,
                            function() {
                                $(this).parent("li").children("a").removeAttr("class");
                                $(this).parent("li").children("a").attr("class", "inactive")

                            })
                    }
                    if (a.attr("class") == "inactive") {
                        a.parent("li").children("ul").slideDown(b.Speed,
                            function() {
                                a.removeAttr("class");
                                a.addClass("active")
                            })
                    }
                    if (a.attr("class") == "active") {
                        a.removeAttr("class");
                        a.addClass("inactive");
                        a.parent("li").children("ul").slideUp(b.Speed)
                    }
                }
                item.unbind('click').click(_item);
                if (b.autostart) {
                    c.children("a").each(function() {
                        if (this.href == httpAdress) {
                            $(this).parent("li").parent("ul").slideDown(b.Speed,
                                function() {
                                    $(this).parent("li").children(".inactive").removeAttr("class");
                                    $(this).parent("li").children("a").addClass("active")
                                    $(this).parent("li").children("a").remove('span')
                                })
                        }
                    })
                }
            }
        })(jQuery);
    </script>
    <script type="text/javascript">
        $(document).ready(function (){

            $(".menu ul li").menu();

        });
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            text-decoration: none;
        }

        html {
            height: 100%;
        }

        body {
            height:100%;
            width:100%;
            overflow:hidden;
            margin:0;
            padding:0;
        }

        .box {
            width: 100%;
            height: 100%;
        }

        .menu-bar {
            float: left;
            width: 18%;
            height: 100%;
            font-size: 18px;
            line-height: 25px;
            color: #333;
            overflow:hidden;
            border-right: 1px solid #e6e6e6;
            background-color: #fafafa;
        }

        #interface-content {
            float: right;
            height: 100%;
            width: 82%;
        }

        .go-index {
            padding: 5px 0 0 10px;
            color: #409eff;
            font-weight: 700;
            font-size: 18px;
        }

        .menu ul li {
            display:block;
            padding-top:2px;
            /*margin-bottom:5px;*/
            list-style:none;
            overflow:visible;
            font-weight: 700;

        }
        .menu ul li a {
            display:block;
            height:30px;
            margin-top:0;
            padding-left:14px;
            font-size:16px;
            color:#333;
            outline:none;
        }

        .menu ul li ul {
            display:none;
            margin-top:-4px;
            margin-bottom:20px;
        }
        .menu ul li ul li ul {
            margin-bottom:5px;
        }
        .menu ul li ul li {
            display:block;
            background:none;
            font-size:14px;
            list-style:circle;
            color:#8f9d4c;
            margin-bottom:0px;
            margin-top:0px;
            padding-top:0px;
            padding-bottom:0px;
            padding-left:1px;
            margin-left:35px;
        }
        .menu ul li ul li a {
            background:none;
            font-size:16px;
            height:30px;
            color:#333;
            padding-left:14px;
        }


        #content {
            padding:10px;
            font-size:11px;
            margin:0 auto;
            overflow:hidden;
        }

        span {
            float:left;
            margin-left:-12px;
            margin-top: 6px;
            display:inline-block;
            height:0;
            width:0;
            border-width: 6px;
            border-style: solid;
            border-color:transparent transparent transparent #409eff;
        }
    </style>
</head>
<body>

<div class="box">
    <div class="menu-bar">
        <div><a href="javascript:;" onclick="index()" class="go-index">首页</a></div>
        <div id="content">
            <div class="menu">
                <ul>
                    <?php echo $tree_html; ?>
                </ul>
            </div>
        </div>
    </div>

    <iframe src="./interface_doc_index.html" frameborder="0" id="interface-content"></iframe>
</div>

</body>

<script>
    function index() {
        $('#interface-content').attr('src','./interface_doc_index.html');
    }
    function page(_this) {
        var uri = $(_this).attr('data-url');
        console.log(uri);
        $('#interface-content').attr('src',uri);
    }
</script>
</html>
