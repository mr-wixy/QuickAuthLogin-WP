<?php

require_once __TYPECHO_ROOT_DIR__.__TYPECHO_ADMIN_DIR__.'common.php';



// 获取当前用户名
$name = $user->__get('name');
$openid = $user->__get('qa_openid');
$nickName = $user->__get('qa_nickname');
$avatarUrl = $user->__get('qa_avatar');
$option = QuickAuthLogin_Plugin::getoptions();
$group = $user->__get('group');


if($group != 'administrator' && !$option->users){ //非管理员且[非管理员启用]处于否
	throw new Typecho_Widget_Exception(_t('禁止访问'), 403);
}
?>
<!DOCTYPE HTML>
<html class="no-js">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="renderer" content="webkit">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>QuickAuthLogin - 扫码登录授权绑定</title>
        <meta name="robots" content="noindex, nofollow">
        <link rel="stylesheet" href="<?=__TYPECHO_ADMIN_DIR__?>css/normalize.css?v=17.10.30">
		<link rel="stylesheet" href="<?=__TYPECHO_ADMIN_DIR__?>css/grid.css?v=17.10.30">
		<link rel="stylesheet" href="<?=__TYPECHO_ADMIN_DIR__?>css/style.css?v=17.10.30">
		<!--[if lt IE 9]>
		<script src="/admin/js/html5shiv.js?v=17.10.30"></script>
		<script src="/admin/js/respond.js?v=17.10.30"></script>
		<![endif]-->    
</head>
    <body class="body-100">
    <!--[if lt IE 9]>
        <div class="message error browsehappy" role="dialog">当前网页 <strong>不支持</strong> 你正在使用的浏览器. 为了正常的访问, 请 <a href="http://browsehappy.com/">升级你的浏览器</a>.</div>
    <![endif]-->
	<div class="typecho-login-wrap">
    <div class="typecho-login">
        <h1><a href="#" class="i-logo">Typecho</a></h1>
		<div class="qrlogin">
			<h3>当前账号：<?=$name?></h3>
			<?php if($openid):?>
			<p>已绑微信：<? echo $nickName;?> </p>
			<div id="qrimg" style="margin-bottom:30px"> <img src="<? echo $avatarUrl;?>"></div>
			<button type="submit" class="btn primary" onclick="reset()">重置绑定数据</button>
            <?php else : ?>
			<p id='msg'>尚未绑定微信账号</p><hr/>
			<button type="submit" class="btn primary" onclick="binding()">绑定微信</button>
			<?php endif; ?>
		</div>
        <p class="more-link"> <a href="<? echo Helper::options()->adminUrl; ?>">返回后台</a> <a href="/">返回首页</a></p>
    </div>
</div>
<script src="<?=__TYPECHO_ADMIN_DIR__?>js/jquery.js?v=17.10.30"></script>
<script src="<?=__TYPECHO_ADMIN_DIR__?>js/jquery-ui.js?v=17.10.30"></script>
<script src="<?=__TYPECHO_ADMIN_DIR__?>js/typecho.js?v=17.10.30"></script>

<?php include __TYPECHO_ROOT_DIR__.__TYPECHO_ADMIN_DIR__.'common-js.php'; ?>

<script>
	var data = {};
	function binding(){
	    var iTop = (window.screen.availHeight - 30 - 600) / 2; 
        var iLeft = (window.screen.availWidth - 10 - 500) / 2; 
	    window.open ('<?php echo $option->qauth_api; ?>/qrconnect?appkey=<?php echo $option->qauth_app_key; ?>&state=binding&popup=true','QuickAuth登录','width=500,height=600,top='+iTop+',left='+iLeft); 
	}
	function reset(){
		var api = "<?= QuickAuthLogin_Plugin::tourl('QuickAuthLogin/reset');?>";
		$.ajax({
			url: api,
			aycnc: false,
			type: 'POST',
			dataType: 'json',
			success: function (data) {
				window.location.reload();
			},
			error: function () {
				console.log('falil!~~');
			}
		});
	}

</script>
    </body>
</html>
