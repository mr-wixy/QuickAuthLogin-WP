![](https://cdn.wixy.cn/blog-picture/WordPress-logotype-alternative.png)

# QuickAuthLogin-WP

基于[QuickAuth](https://qauth.cn)扫码登录平台开发的WordPress微信扫码登录插件

## 起始

本插件是基于 QuickAuth 开发的 插件，使用前需要进入[QuickAuth平台](https://qauth.cn)注册配置自己的应用

如需修改插件或开发自己的接入项目，请参考 [QuickAuth接入文档](https://qauth.cn/doc/index.html)

插件地址：[https://github.com/mr-wixy/QuickAuthLogin-WP](https://github.com/mr-wixy/QuickAuthLogin-WP)

下载地址：[QuickAuthLogin.zip][1]

(请勿与其它同类插件同时启用，以免互相影响)

## 使用方法

第 1 步：下载本插件，解压，放到 `wp-content/plugins` 目录中；

第 2 步：登录管理后台，激活插件；

第 3 步：登录QuickAuth网站创建接入应用；

![](https://cdn.wixy.cn/blog-picture/blog-picture20220127160420.png)

第 4 步：填写应用的基本信息（注意：此时可以获取到AppKey，回调地址请填写自己博客的域名+/index.php/wp-json/wp/v2/qauth_login 此处必须为https）

![](https://cdn.wixy.cn/blog-picture/20220207150311.png)

第 5 步：发布应用；

![发布应用](https://cdn.wixy.cn/blog-picture/blog-picture20220127161055.png)

第 6 步：[获取](https://qauth.cn/config/secret)UserSecretKey；

![](https://cdn.wixy.cn/blog-picture/blog-picture20220127161157.png)

第 7 步：进入博客插件后台配置AppKey和UserSecret；

![](https://cdn.wixy.cn/blog-picture/20220207145540.png)

第 8 步：进入博客后台绑定管理员微信账号；

![](https://cdn.wixy.cn/blog-picture/20220207151735.png)

最后：我们就可以点击登录界面的微信登录按钮扫码登陆了

![](https://cdn.wixy.cn/blog-picture/20220207151419.png)

## 重要说明

1. QuickAuthApi 默认配置，正常情况下无需修改（除非QuickAuth网站接口地址改了）
2. 未绑定用户自动注册 开启后，未绑定的微信扫码则会自动注册账号

## 与我联系

作者：wixy

如果有任何意见或发现任何BUG请联系我

博客：[https://blog.wixy.cn/](https://blog.wixy.cn/)

邮箱：[wixy@qq.com](mailto:wixy@qq.com)

  [1]: https://cdn.wixy.cn/blog-file/2022/02/07/1644217858.zip
