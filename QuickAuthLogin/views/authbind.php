<?php
include 'header.php';
include 'menu.php';

require_once __TYPECHO_ROOT_DIR__.__TYPECHO_ADMIN_DIR__.'common.php';

// 获取当前用户名
$name = $user->__get('name');
$openid = $user->__get('qa_openid');
$nickName = $user->__get('qa_nickname');
$avatarUrl = $user->__get('qa_avatar');
$option = QuickAuthLogin_Plugin::getoptions();
$group = $user->__get('group');

?>

<div class="main">
    <div class="body container">
        <div class="typecho-page-title">
            <h2>微信账号绑定</h2>
        </div>
        
        <div class="row typecho-page-main" role="main" style="text-align:center">
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
        </div>
    </div>
</div>

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

<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>
