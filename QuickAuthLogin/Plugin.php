<?php
/**
 * QuickAuth微信扫码登陆插件
 * 
 * @package QuickAuthLogin 
 * @author wixy
 * @version 0.9.0
 * @link https://blog.wixy.cn/archives/quickauthlogin.html
 */

class QuickAuthLogin_Plugin implements Typecho_Plugin_Interface {
	
	const PLUGIN_NAME  = 'QuickAuthLogin';
	const PLUGIN_PATH  = __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.'/QuickAuthLogin/';
	
	
	/**
	 * 启用插件方法,如果启用失败,直接抛出异常
	 *
	 * @static
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function activate(){
		$info = self::updateDb();
	
		Typecho_Plugin::factory('admin/menu.php')-> navBar = array(__class__, 'render');
		Typecho_Plugin::factory('admin/header.php')-> header = array(__class__,'login');
		Typecho_Plugin::factory('Widget_User')-> loginSucceed = array(__class__,'afterlogin');
		
		Helper::addRoute('bind',__TYPECHO_ADMIN_DIR__.'QuickAuthLogin/bind','QuickAuthLogin_Action','bind');
		Helper::addRoute('login',__TYPECHO_ADMIN_DIR__.'QuickAuthLogin/login','QuickAuthLogin_Action','login');
		Helper::addRoute('wechatlogin',__TYPECHO_ADMIN_DIR__.'QuickAuthLogin/wechatlogin','QuickAuthLogin_Action','wechatlogin');
		Helper::addRoute('reset',__TYPECHO_ADMIN_DIR__.'QuickAuthLogin/reset','QuickAuthLogin_Action','reset');
		Helper::addRoute('auth-bind',__TYPECHO_ADMIN_DIR__.'QuickAuthLogin/auth-bind','QuickAuthLogin_Action','authbind');
	
	}
	
	public static function updateDb()
    {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        if ("Pdo_Mysql" === $db->getAdapterName() || "Mysql" === $db->getAdapterName()) {
            $sql = "ALTER TABLE `{$prefix}users` ADD COLUMN `qa_openid` varchar(64);
            ALTER TABLE `{$prefix}users` ADD COLUMN `qa_nickname` varchar(64);
            ALTER TABLE `{$prefix}users` ADD COLUMN `qa_avatar` varchar(255);
            ";
            $db->query($sql);
        } else {
            throw new Typecho_Plugin_Exception(_t('对不起, 本插件仅支持MySQL数据库。'));
        }
        
        return "数据表新增字段成功！";
    }
	
	/**
	 * 禁用插件方法,如果禁用失败,直接抛出异常
	 *
	 * @static
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function deactivate(){
		$info = self::uninstallDb();
	
	}
	
	
	public static function uninstallDb()
    {
        $db = Typecho_Db::get();
        $prefix = $db->getPrefix();
        $sql = "ALTER TABLE `{$prefix}users` DROP COLUMN `qa_openid`;
        ALTER TABLE `{$prefix}users` DROP COLUMN `qa_nickname`;
        ALTER TABLE `{$prefix}users` DROP COLUMN `qa_avatar`;
        ";
        $db->query($sql);
    
        return "数据表删除字段成功！";
    }
	
	/**
	 * 获取插件配置面板
	 *
	 * @static
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form 配置面板
	 * @return void
	 */
	public static function config(Typecho_Widget_Helper_Form $form){
		
		
		$user = Typecho_Widget::widget('Widget_User');
		
		//var_dump($user);
		$api = new Typecho_Widget_Helper_Form_Element_Text('qauth_api',null,'https://api.qauth.cn',_t('QuickAuthApi：'),_t('<b>QuickAuthApi地址,正常情况下无需修改</b>'));
		$form->addInput($api);
		
		$appkey = new Typecho_Widget_Helper_Form_Element_Text('qauth_app_key',null,'',_t('AppKey：'),_t('<b>QuickAuth后台创建应用时的AppKey <a target="_blank" href="https://qauth.cn/app">获取AppKey</a></b>'));
		//var_dump($appkey);
		$form->addInput($appkey);
		$encryptscrypt = new Typecho_Widget_Helper_Form_Element_Text('qauth_user_secret',null,'',_t('UserSecret：'),_t('<b>QuickAuth用户的数据加密密钥 <a target="_blank" href="https://qauth.cn/config/secret">获取UserSecret</a></b>'));
		$form->addInput($encryptscrypt);
		
		$off = new Typecho_Widget_Helper_Form_Element_Radio('off',array('0'=>'开启','1'=>'关闭'),0,_t('账户密码登录：',''),'<b><font color=red>默认开启，如需关闭，请确保账号已经绑定微信，否则将无法正常登录后台；如果出现这种情况，请重装插件解决！</font></b>');
		$form->addInput($off);
		
		$allowRegister = new Typecho_Widget_Helper_Form_Element_Radio('allow_register',array('0'=>'否','1'=>'是'),0,_t('允许未绑定微信账号扫码登录：',''),'<b><font color=red>开启后使用没有绑定账号的微信扫码后自动注册新账号登录！</font></b>');
		$form->addInput($allowRegister);
		
		$users = new Typecho_Widget_Helper_Form_Element_Radio('users',array('0'=>'否','1'=>'是'),0,_t('非管理员启用：',''),'<b>启用后在导航栏增加微信账号绑定入口</b>');
		$form->addInput($users);
		
		$username = $user->__get('name');
		$openid = $user->__get('qa_openid');
		$nickname = $user->__get('qa_nickname');
		
		echo '<ul class="typecho-option"><li><label class="typecho-label">使用说明：</label>
		<ol>
		<li><p class="description">登陆 <a target="_blank" href="https://qauth.cn">QuickAuth</a>网站</p></li>
		<li><p class="description"><a target="_blank" href="https://qauth.cn/app">创建应用</a> 并填写相关信息（回调地址请填写https://博客域名/index.php/admin/QuickAuthLogin/wechatlogin）</p></li>
		<li><p class="description"><a target="_blank" href="https://qauth.cn/app">发布</a> 应用</p></li>
		<li><p class="description">在此页面中配置 AppKey和UserSecret</p></li>
		</ol>
		</li>
		</ul><ul class="typecho-option"><li><label class="typecho-label">绑定情况：</label>当前登录用户：'.$username.'&nbsp;&nbsp; 微信账号：<u>'.(empty($openid)?'暂未绑定':$nickname).'</u></li><li><a href="'.QuickAuthLogin_Plugin::tourl('QuickAuthLogin/auth-bind').'"><button type="submit" class="btn primary">账号绑定</button></a></li></ul>';
		
	}
	
	/**
	 * 个人用户的配置面板
	 *
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form
	 * @return void
	 */
	public static function personalConfig(Typecho_Widget_Helper_Form $form){
	}
	
	public static function afterlogin($this_, $name, $password, $temporarily, $expire){
		
		$options = self::getoptions();
		if($options->off === '1'){
			echo 'what are you doing?';
			// 登录之前没有合适的插入点，这里强制退出
			$this_ -> logout();
		}
	}
	
	
	public static function login($header){
		
		/** 获取链接信息 */
		$baseurl = Typecho_Request::getInstance()->getBaseUrl();
		
		/** 判断是否登录 */
		if($baseurl == __TYPECHO_ADMIN_DIR__.'login.php'){
			
			/** 清空输出缓存区 */
			ob_clean();
			
			require_once self::PLUGIN_PATH.'views/login.php';
			
			ob_end_flush();
			exit();
		}else{
			return $header;
		}
	}
	
	public static function render(){
		$options = self::getoptions();
        if($options->users){
            echo '<a href="'.QuickAuthLogin_Plugin::tourl('QuickAuthLogin/auth-bind').'" target="_blank">' . _t('微信账号绑定') . '</a>';
        }
	}
	
	/** 生成URL，解决部分博客未开启伪静态，仅对本插件有效 */
	public static function tourl($action){
		return Typecho_Common::url(__TYPECHO_ADMIN_DIR__.$action, Helper::options()->index);
	}
	
	/** 获取插件配置 */
	public static function getoptions(){
		return Helper::options()->plugin(QuickAuthLogin_Plugin::PLUGIN_NAME);
	}
}