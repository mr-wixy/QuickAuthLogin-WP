<?php
/*
Plugin Name: QuickAuthLogin
Plugin URI: https://gitee.com/wixy/QuickAuthLogin-WP
Description: QuickAuth微信扫码登陆插件
Version: 0.9.2
Author: wixy
Author URI: https://blog.wixy.cn/
*/

const PLUGIN_VERSION  = '0.9.2';

//自定义登录按钮
function custom_login_button() {
    echo '<button class="button button-primary button-large" style="color:#fff;background: #2a0; float: right; margin: 18px 0 5px 10px; min-height: 32px;" href="" type="button" onClick="openLogin()">微信登陆</button><br />';
}
add_action('login_form', 'custom_login_button');

//自定义显示错误消息
function custom_login_message(){
    $msg =  $_GET['err_msg'];
    if($msg){
        echo '<div id="login_error">'.$msg.'<br></div>';
    }
}
add_action( 'login_message', 'custom_login_message');

//登录按钮调用函数
function custom_html() {
    if(get_option("qauth_options")["qauth_appkey"]){
        $url = get_option("qauth_options")["qauth_api"].'/qrconnect?appkey='.get_option("qauth_options")["qauth_appkey"].'&state=login&popup=true';
         echo '<script>
         function openLogin(){
	        var iTop = (window.screen.availHeight - 30 - 600) / 2; 
            var iLeft = (window.screen.availWidth - 10 - 500) / 2; 
	        window.open ("'.$url.'","QuickAuth登录","width=500,height=600,top="+iTop+",left="+iLeft);
	        }
         </script>
         ';
    }
    else{
        echo '<script>function openLogin(){alert("请先完成QuickAuth的相关配置！");}</script>';
    }
}
add_action('login_footer', 'custom_html');
add_action('wp_footer', 'custom_html');

//回调接口定义
add_action( 'rest_api_init', function () {
    register_rest_route( 'wp/v2', '/qauth_login', array(
        'methods' => 'GET',
        'callback' => 'qauth_login',
    ) );
    register_rest_route( 'wp/v2', '/qauth_login/ping', array(
        'methods' => 'GET',
        'callback' => 'ping',
    ) );
} );

function ping(){
    $data = [
        "code" => 0,
        "msg" => "pong",
        "data" => [
            "name" => "QuickAuthLogin For WordPress",
    	    "version" => PLUGIN_VERSION
            ]
    ];
    echo json_encode($data);
}

function qauth_login() {
    $code = $_GET['code'];
    $state = $_GET['state'];
    if(!$code || !$state){
        wp_redirect(wp_login_url(get_permalink()));
        exit;
    }
    
    $response = wp_remote_get( get_option("qauth_options")["qauth_api"].'/user?code='.$code.'&appkey='.get_option("qauth_options")["qauth_appkey"].'&secret='.get_option("qauth_options")["qauth_usersecret"] );
    $body = wp_remote_retrieve_body( $response );
    $content_obj = json_decode($body);
    if($content_obj->code === 0){
        if($state == "binding"){
        	wp_redirect( home_url().'/wp-admin/admin.php?page=qauth_binding&openId='.$content_obj->res->openId.'&nickName='.$content_obj->res->nickName.'&avatarUrl='.urlencode($content_obj->res->avatarUrl) ); 
        }
        else{
            $user_query = new WP_User_Query( array( 'meta_key' => 'qa_openid', 'meta_value' => $content_obj->res->openId) );
            
            if($user_query->get_results()){
                $login_user=$user_query->get_results()[0]->data;
                wp_set_current_user( $login_user->ID);
            	wp_set_auth_cookie( $login_user->ID);
            	wp_redirect( home_url().'/wp-admin' ); 
        	    exit;
            }
            else{
                if(get_option('qauth_options')['qauth_auto_register']){ 
                    $newUserName = 'wx_'.$content_obj->res->nickName;
                    $user_id = username_exists($newUserName); 
                    if($user_id){  
                        $newUserName = $newUserName.'_'.substr(md5(uniqid(microtime())), 0, 4);
                    } 
                    $random_password = substr(md5(uniqid(microtime())), 0, 6);  	
                    $user_id = wp_create_user($newUserName, $random_password, $newUserName.'@qauth.cn');  
                    add_user_meta($user_id, 'qa_openid', $content_obj->res->openId);
                    add_user_meta($user_id, 'qa_nickname', $content_obj->res->nickName);
                    add_user_meta($user_id, 'qa_avatarurl', $content_obj->res->avatarUrl);
                    wp_set_current_user( $user_id);
        	        wp_set_auth_cookie( $user_id);
                	wp_redirect( home_url().'/wp-admin' ); 
            	    exit;
                }else{
                    wp_redirect( home_url().'/wp-login.php?err_msg='.urlencode('未绑定微信用户禁止登陆'));
                }
                	    
            }
        }
    }
    else{
        wp_redirect( home_url().'/wp-login.php?err_msg='.urlencode('QuickAuth接口调用出错【'.$content_obj->msg.'】'));
    }
}

function qauth_preprocess_pages($value){ 
    global $pagenow; 
    $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : false); 
    if($pagenow=='admin.php' && $page=='qauth_binding'){ 
        $user_query = new WP_User_Query( array( 'meta_key' => 'qa_openid', 'meta_value' => $content_obj->res->openId) );
        if($user_query->get_results()){
            if($_GET['openId']){
                wp_redirect( home_url().'/wp-admin/admin.php?page=qauth_binding&err_msg=alreadyuse'); 
        	    exit;
            }
        }
        else{
            $current_user = wp_get_current_user();
            $openid = get_user_meta($current_user->data->ID, 'qa_openid', true);
            $nickName = get_user_meta($current_user->data->ID, 'qa_nickname', true);
            $avatarUrl = get_user_meta($current_user->data->ID, 'qa_avatarurl', true);
            
            if($_GET['openId']){
                $uId = $_GET['openId'];
                $uName = $_GET['nickName'];
                $uAvatar = $_GET['avatarUrl'];
                
                $current_user = wp_get_current_user();
                
                if($openid){
                    update_user_meta($current_user->data->ID, 'qa_openid', $uId);
                }
                else{
                    add_user_meta($current_user->data->ID, 'qa_openid', $uId);
                }
                
                if($nickName){
                    update_user_meta($current_user->data->ID, 'qa_nickname', $uName);
                }
                else{
                    add_user_meta($current_user->data->ID, 'qa_nickname', $uName);
                }
                
                if($avatarUrl){
                    update_user_meta($current_user->data->ID, 'qa_avatarurl', urldecode($uAvatar));
                }
                else{
                    add_user_meta($current_user->data->ID, 'qa_avatarurl', urldecode($uAvatar));
                }
                
                wp_redirect( home_url().'/wp-admin/admin.php?page=qauth_binding');
                exit;
            }
        }
    } 
} 
add_action('admin_init', 'qauth_preprocess_pages'); 

//新增菜单
function qauth_options_page() {
  add_menu_page(
      'QuickAuth',
      '微信绑定',
      'read',
      'qauth_binding',
      'qauth_user_binding_html',
       plugins_url('QuickAuthLogin/wechat.png')
  );
   add_submenu_page(
      'plugins.php',
      'QuickAuth设置',
      'QuickAuth设置',
      'manage_options',
      'qauth',
      'qauth_options_page_html'
   );
}

function qauth_user_binding_html(){
     ?>
     <div class=wrap>
        <div class="wrap">        	
            <h2>微信账户绑定</h2>
            
            <?php update_qauth_binding(); 
            $current_user = wp_get_current_user();
            $openid = get_user_meta($current_user->data->ID, 'qa_openid', true);
            $nickName = get_user_meta($current_user->data->ID, 'qa_nickname', true);
            $avatarUrl = get_user_meta($current_user->data->ID, 'qa_avatarurl', true);
            ?>
            
            
    	<div class="qrlogin">
            <form method="post">
    			<h3>当前账号：<?=$current_user->data->user_login?></h3>
    			<?php if($openid):?>
    			<p>已绑微信：<? echo $nickName;?> </p>
    			<div id="qrimg" style="margin-bottom:30px"> <img src="<? echo $avatarUrl;?>"></div>
    			<input type="submit" class="button button-primary" name="submit"  value="重置绑定数据">
                <?php else : ?>
    			<p id='msg'>尚未绑定微信账号</p><hr/>
    			<button class="button button-primary" type="button" onclick="openLogin()">绑定微信</button>
    			<?php endif; ?>
			</form>
		</div>
            
        </div>
    </div>
   <?php
   
   if(get_option("qauth_options")["qauth_appkey"]){
        $url = get_option("qauth_options")["qauth_api"].'/qrconnect?appkey='.get_option("qauth_options")["qauth_appkey"].'&state=binding&popup=true';
         echo '<script>
         function openLogin(){
	        var iTop = (window.screen.availHeight - 30 - 600) / 2; 
            var iLeft = (window.screen.availWidth - 10 - 500) / 2; 
	        window.open ("'.$url.'","QuickAuth登录","width=500,height=600,top="+iTop+",left="+iLeft);
	        }
         </script>';
   }
   else{
       if(current_user_can('manage_options')){
            echo '<script>function openLogin(){
                alert("请先完成QuickAuth的相关配置！");
                location.href="'.home_url().'/wp-admin/plugins.php?page=qauth";
            }</script>';
       }else{
           echo '<script>function openLogin(){
                alert("请联系管理员完成QuickAuth的相关配置！");}</script>';
       }
       
   }
}

function update_qauth_binding(){
    if($_GET['err_msg']){
        if($_GET['err_msg'] == 'alreadyuse'){
            echo '<p style="color:red;">该微信账号已经被其他用户绑定</p>';
        }
    }
    $current_user = wp_get_current_user();
    $openid = get_user_meta($current_user->data->ID, 'qa_openid', true);
    $nickName = get_user_meta($current_user->data->ID, 'qa_nickname', true);
    $avatarUrl = get_user_meta($current_user->data->ID, 'qa_avatarurl', true);

    if($_POST['submit']){
        delete_user_meta($current_user->data->ID, 'qa_openid', $openid);
        delete_user_meta($current_user->data->ID, 'qa_nickname', $nickName);
        delete_user_meta($current_user->data->ID, 'qa_avatarurl', $avatarUrl);
        echo '<p style="color:green;">重置成功</p>';
    }
}

function qauth_options_page_html() {
    if (!current_user_can('manage_options')){
        return;
    }
    ?>

    <div class=wrap>
        <div class="wrap">        	
            <h2>QuickAuth设置</h2>
            <?php update_qauth_options(); ?>
            <ul class="typecho-option">
                <li><label class="typecho-label">使用说明：</label>
            		<ol>
            		<li><p class="description">登陆 <a target="_blank" href="https://qauth.cn">QuickAuth</a>网站</p></li>
            		<li><p class="description"><a target="_blank" href="https://qauth.cn/app">创建应用</a> 填写相关信息 保存并发布</p></li>
            		<li><p class="description">在此页面中配置 AppKey和UserSecret</p></li>
            		</ol>
    		    </li>
    		</ul>
            <form method="post">
                <div style="margin:10px;">
                    <label style="display:block;margin:10px 0;">QuickAuthApi<small>（默认配置，正常情况无需修改）</small></label>
                    <input class="regular-text code" type="text" name="QauthApi" value="<?php echo get_option('qauth_options')['qauth_api'];?>"/>
                </div>
            	<div style="margin:10px;">
            	    <label style="display:block;margin:10px 0;">AppKey <a href="https://qauth.cn/app" target="_blank">获取</a></label>
            	    <input class="regular-text code" type="text" name="QauthAppKey" value="<?php echo get_option('qauth_options')['qauth_appkey'];?>"/>
        	    </div>
            	<div style="margin:10px;"><label style="display:block;margin:10px 0;">UserSecret <a href="https://qauth.cn/config/secret" target="_blank">获取</a></label><input class="regular-text code" type="text" name="QauthUserSecret" value="<?php echo get_option('qauth_options')['qauth_usersecret'];?>"/></div>
            	<div style="margin:10px;">
            	    <label style="display:block;margin:10px 0;">未绑定用户自动注册</label>
            	    
            	    <?php if(get_option('qauth_options')['qauth_auto_register']):?>
            	    <input class="regular-text code" type="checkbox" name="QauthAutoRegister" value="<?php echo get_option('qauth_options')['qauth_auto_register'];?>" checked="checked"/>
            	    <?php else : ?>
            	    <input class="regular-text code" type="checkbox" name="QauthAutoRegister" value="<?php echo get_option('qauth_options')['qauth_auto_register'];?>"/>
        			<?php endif; ?>
            	</div>
                <div style="margin:10px;"><input class="button button-primary" type="submit" name="submit" value="保存"/></div>
            </form>
        </div>
    </div>
   <?php
}

function update_qauth_options(){
	if($_POST['submit']){
        if($_POST['QauthAutoRegister'] === null){
            $auto_register = false;
        }
        else{
            $auto_register = true;
        }
		$flag = false;
		$data_r = [
            'qauth_api' => $_POST['QauthApi'],
            'qauth_appkey' => $_POST['QauthAppKey'],
            'qauth_usersecret' => $_POST['QauthUserSecret'],
            'qauth_auto_register' => $auto_register,
            ];
		if($_POST['QauthApi'] && $_POST['QauthAppKey'] && $_POST['QauthUserSecret']){
			update_option('qauth_options',$data_r);
			$flag = true;
		}
		if($flag){
			echo '<p style="color:green;">保存成功</p>';
		}else{
			echo '<p style="color:red;">保存失败</p>';	
		}
	}
}
add_action( 'admin_menu', 'qauth_options_page' );

function qauth_login_rewrites_init(){
    add_rewrite_rule(
        'qauthlogin/(.+)\$',
        'index.php?&code=$matches[1]',
        'top' 
    );
    flush_rewrite_rules();
}
add_action( 'init', 'qauth_login_rewrites_init' );

function qauth_setup() {
    $data_r = [
        'qauth_api' => 'https://api.qauth.cn' ,
        'qauth_appkey' => '', 
        'qauth_usersecret' => '',
        'qauth_auto_register' => false
        ];
    add_option('qauth_options', $data_r);
}
 
function qauth_install() {
    qauth_setup();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'qauth_install' );

function qauth_deactivation() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'qauth_deactivation' );