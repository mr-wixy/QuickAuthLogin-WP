<?php
include 'common.php';
$option = QuickAuthLogin_Plugin::getoptions();;
if ($user->hasLogin()) {
    $response->redirect($options->adminUrl);
}
if($option->off == '1'){
    $response->redirect($option->qauth_api."/qrconnect?appkey=".$option->qauth_app_key.'&state=login');
}

$rememberName = htmlspecialchars(Typecho_Cookie::get('__typecho_remember_name'));
Typecho_Cookie::delete('__typecho_remember_name');
$header = '<link rel="stylesheet" href="' . Typecho_Common::url('normalize.css?v=' . $suffixVersion, $options->adminStaticUrl('css')) . '">
<link rel="stylesheet" href="' . Typecho_Common::url('grid.css?v=' . $suffixVersion, $options->adminStaticUrl('css')) . '">
<link rel="stylesheet" href="' . Typecho_Common::url('style.css?v=' . $suffixVersion, $options->adminStaticUrl('css')) . '">
<!--[if lt IE 9]>
<script src="' . Typecho_Common::url('html5shiv.js?v=' . $suffixVersion, $options->adminStaticUrl('js')) . '"></script>
<script src="' . Typecho_Common::url('respond.js?v=' . $suffixVersion, $options->adminStaticUrl('js')) . '"></script>
<![endif]-->';
?>
<!DOCTYPE HTML>
<html class="no-js">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="renderer" content="webkit">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php _e('%s - %s - Powered by Typecho', $menu->title, $options->title);?></title>
        <meta name="robots" content="noindex, nofollow">
      <?= $header;?>
	</head>
    <body class="body-100">
    <!--[if lt IE 9]>
        <div class="message error browsehappy" role="dialog">当前网页 <strong>不支持</strong> 你正在使用的浏览器. 为了正常的访问, 请 <a href="http://browsehappy.com/">升级你的浏览器</a>.</div>
    <![endif]-->
	<div class="typecho-login-wrap">
    <div class="typecho-login">
        <h1><a href="#" class="i-logo">Typecho</a></h1>
		<div id="login">
			<form action="<?php $options->loginAction(); ?>" method="post" name="login" role="form" id="login">
				<p>
					<label for="name" class="sr-only">用户名</label>
					<input type="text" id="name" name="name" value="" placeholder="用户名" class="text-l w-100" autofocus />
				</p>
				<p>
					<label for="password" class="sr-only">密码</label>
					<input type="password" id="password" name="password" class="text-l w-100" placeholder="密码" />
				</p>
				<p class="submit">
				<input type="hidden" name="referer" value="<?php echo htmlspecialchars($request->get('referer')); ?>" />
    				<button type="submit" class="btn primary">立即登录</button>
    				<button type="button" class="btn primary" style="background: #2a0;" onclick="openLogin()">微信登录</button>
				</p>
			</form>
		</div>
        <p class="more-link"> <a href="<?php $options->siteUrl(); ?>">返回首页</a> 
        <?php if($options->allowRegister): ?>
        &bull;
        <a href="<?php $options->registerUrl(); ?>"><?php _e('用户注册'); ?></a>
        <?php endif; ?>
        </p>
    </div>
</div>
<?php include 'common-js.php';include 'footer.php';?>

<script>
	function openLogin(){
	    <?php if(empty($option->qauth_app_key)): ?>
            <?php 
            Typecho_Cookie::set('__typecho_notice', Json::encode(array('使用微信登陆需要先配置QuickAuth插件的AppKey和UserSecret')));
            Typecho_Cookie::set('__typecho_notice_type', "error");
            ?>
        <?php else : ?>
    	    var iTop = (window.screen.availHeight - 30 - 600) / 2; 
            var iLeft = (window.screen.availWidth - 10 - 500) / 2; 
    	    window.open ('<?php echo $option->qauth_api; ?>/qrconnect?appkey=<?php echo $option->qauth_app_key; ?>&state=login&popup=true','QuickAuth登录','width=500,height=600,top='+iTop+',left='+iLeft); 
        <?php endif; ?>
    }
</script>
