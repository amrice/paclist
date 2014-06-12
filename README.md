#PACProxy
## 用途： ##
    利用VPS本身来获取gfwlist.txt，并且转换为PAC文件。可添加自定义内容。
## 特性：##
    PHP编写，单文件，部署方便。
    gfwlist的获取从vps的网络获取，防止在本地gfwlist本身打不开的问题。
    支持网页测试规则。
    PAC文件规则利用Base64进行编码，防止GFW嗅探到规则直接给干掉。
    
## 使用： ##
> 浏览器安装支持PAC的插件。
> 添加PAC地址：http://ip/path/?f=pac&p=proxy_server&pt=socks
> 参数说明：
> ### 前面的路径啥的是VPS的访问路径，重要的是参数。###
>
> 1. f 为返回的模式
>
> 可选值 
>              pac 插件需要的JS格式文件，标准的PAC文件。
>              decode 下载并解码gfw的规则，和自定义的规则。直接输出，没有对GFW做屏蔽，直接打开会挂掉。
>              test 默认选项，测试规则和编辑自定义规则。
>              
>        p 为pac模式的proxy地址
>        pt 为pac模式的代理类型，一般有 http https socks5 sock，默认为socks5