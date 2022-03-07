<?php
if (!defined('__TYPECHO_ROOT_DIR__')) {

    exit;
}

class QuickAuthLogin_Action extends Typecho_Widget
{

    /* 重置当前用户绑定数据 */
    public function reset()
    {
        require_once __TYPECHO_ROOT_DIR__ . __TYPECHO_ADMIN_DIR__ . 'common.php';
        $res = new Typecho_Response();
        $ret = [];

        if ($user->haslogin()) {
            // 获取当前用户名
            $name = $user->__get('name');
            $options = QuickAuthLogin_Plugin::getoptions();
            $db   = Typecho_Db::get();
            $db->query($db->update('table.users')->rows(['qa_openid' => null, 'qa_nickname' => null, 'qa_avatar' => null])->where('name = ?', $name));
          
            $ret['code'] = 200;
            $ret['msg']  = '';
            $this->widget('Widget_Notice')->set(_t('当前用户绑定信息重置成功', $name, $nickName[1]), 'success');
            //$res->redirect(QuickAuthLogin_Plugin::tourl('QuickAuthLogin/auth-bind'));
        } else {
            $ret['msg'] = 'what are you doing?';
        }
        $res->throwJson($ret);
    }

    /* 微信Callback跳转登录逻辑 */
    public function wechatlogin()
    {
        $options = QuickAuthLogin_Plugin::getoptions();
        $res   = new Typecho_Response();
        $req = new Typecho_Request();
        $ret   = [];
        
        $code = $req->get('code');
        $state = $req->get('state');
        
        $api = $options->qauth_api."/user?code=".$code."&appkey=".$options->qauth_app_key."&secret=".$options->qauth_user_secret;
        $paras['header'] = 1;
        $body=self::get_curl($api, $paras);
        preg_match('/\"code\":(.*?),/', $body, $code);
        $ret['code'] = $code[1];
        preg_match('/\"msg\":\"(.*?)\"/', $body, $msg);
        if($ret['code'] == 0){
            preg_match('/\"openId\":\"(.*?)\"/', $body, $openId);
            preg_match('/\"nickName\":\"(.*?)\"/', $body, $nickName);
            preg_match('/\"avatarUrl\":\"(.*?)\"/', $body, $avatarUrl);
            
            if($state == "binding"){
                require_once __TYPECHO_ROOT_DIR__ . __TYPECHO_ADMIN_DIR__ . 'common.php';
                $name = $user->__get('name');
                $key = $options->key;
                
                $db   = Typecho_Db::get();
                
                $user = $db->fetchRow($db->select()->from('table.users')->where( 'qa_openid' . ' = ?', $openId[1])->limit(1));
                if($user){
                    $this->widget('Widget_Notice')->set('此微信账号已被绑定！', 'error');
                    $res->redirect("/admin/extending.php?panel=QuickAuthLogin/views/authbind.php");
                }
                //更新基础信息
                $db->query($db->update('table.users')->rows(['qa_openid' => $openId[1], 'qa_nickname' => $nickName[1], 'qa_avatar' => $avatarUrl[1]])->where('name = ?', $name));
                $this->widget('Widget_Notice')->set(_t('用户 <strong>%s</strong> 成功绑定微信账号 <strong>%s</strong>', $name, $nickName[1]), 'success');
                $res->redirect("/admin/extending.php?panel=QuickAuthLogin/views/authbind.php");
            }
            else{
                $ret['login']['msg']  = 'Fail';
                $ret['login']['code'] = 0;
                $db = Typecho_Db::get();
                $user = $db->fetchRow($db->select()->from('table.users')->where( 'qa_openid' . ' = ?', $openId[1])->limit(1));
                
                if($user){
                    $authCode = function_exists('openssl_random_pseudo_bytes') ? bin2hex(openssl_random_pseudo_bytes(16)) : sha1(Typecho_Common::randString(20));
                    $user['authCode'] = $authCode;
        
                    Typecho_Cookie::set('__typecho_uid', $user['uid'], $expire);
                    Typecho_Cookie::set('__typecho_authCode', Typecho_Common::hash($authCode), $expire);
        
                    $db->query($db->update('table.users')->expression('logged',
                        'activated')->rows(['authCode' => $authCode])->where('uid = ?', $user['uid']));
        
                    /** 压入数据 */
                    $this->push($user);
                    $this->_user    = $user;
                    $this->_hasLogin = true;

                    echo 'success';
                    $res->redirect(Helper::options()->adminUrl);
                }
                else{//该微信账号未绑定
                    if($options->allow_register){//匿名账号注册登录
                        $hasher = new PasswordHash(8, true);
                        $generatedPassword = Typecho_Common::randString(7);
                        
                        $newUserName = "wx_".$nickName[1];
                        $existUser = $db->fetchRow($db->select()->from('table.users')->where( 'name' . ' = ?', $newUserName)->limit(1));
                        if($existUser)
                            $newUserName = "wx_".$nickName[1].'_'.Typecho_Common::randString(4);
                        
                        $dataStruct = array(
                            'name'      =>  $newUserName,
                            'mail'      =>  $newUserName."@qauth.cn",
                            'screenName'=>  $nickName[1],
                            'password'  =>  $hasher->HashPassword($generatedPassword),
                            'created'   =>  time(),
                            'group'     =>  'subscriber',
                            'qa_openid' =>  $openId[1],
                            'qa_nickname' =>    $nickName[1],
                            'qa_avatar' =>  $avatarUrl[1] 
                        );
                        
                        $dataStruct = $this->pluginHandle()->register($dataStruct);
                        $insertId = $db->query($db->insert('table.users')->rows($dataStruct));
                        $user = $db->fetchRow($db->select()->from('table.users')->where( 'qa_openid' . ' = ?', $openId[1])->limit(1));
                        
                        $authCode = function_exists('openssl_random_pseudo_bytes') ? bin2hex(openssl_random_pseudo_bytes(16)) : sha1(Typecho_Common::randString(20));
                        $user['authCode'] = $authCode;
                        Typecho_Cookie::set('__typecho_uid', $user['uid'], $expire);
                        Typecho_Cookie::set('__typecho_authCode', Typecho_Common::hash($authCode), $expire);
            
                        $db->query($db->update('table.users')->expression('logged',
                            'activated')->rows(['authCode' => $authCode])->where('uid = ?', $user['uid']));
            
                        $this->push($user);
                        $this->_user    = $user;
                        $this->_hasLogin = true;
    
                        $this->widget('Widget_Notice')->set(_t('用户 <strong>%s</strong> 已经成功注册, 密码为 <strong>%s</strong>', $newUserName, $generatedPassword), 'success');
                        $res->redirect(Helper::options()->adminUrl);
                        
                       
                    }
                    else{
                        $this->widget('Widget_Notice')->set('该微信未绑定用户，无法登陆！', 'error');
                        $res->redirect(Helper::options()->loginUrl);
                    }
                }
            }
            $res->throwJson($ret);
        }
        else{
            $ret['msg'] = $msg[1];
            $res->throwJson($ret);
        }
    }

    /** Curl单例封装函数 */
    public static function get_curl($url, $paras = [])
    {
        //echo $paras;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $httpheader[] = "Accept:*/*";
        $httpheader[] = "Accept-Encoding:gzip,deflate,sdch";
        $httpheader[] = "Accept-Language:zh-CN,zh;q=0.8";
        $httpheader[] = "Connection:close";
        if($paras['httpheader']){
            curl_setopt($ch, CURLOPT_HTTPHEADER, $paras['httpheader']);
        }
        else{
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
        }
        if ($paras['ctime']) { // 连接超时
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $paras['ctime']);
        }
        if ($paras['rtime']) { // 读取超时
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $paras['rtime']);
        }
        if ($paras['post']) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $paras['post']);
        }
        if ($paras['header']) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        if ($paras['cookie']) {
            curl_setopt($ch, CURLOPT_COOKIE, $paras['cookie']);
        }
        if ($paras['refer']) {
            curl_setopt($ch, CURLOPT_REFERER, $paras['refer']);
        }
        if ($paras['ua']) {
            curl_setopt($ch, CURLOPT_USERAGENT, $paras['ua']);
        } else {
            curl_setopt($ch, CURLOPT_USERAGENT,
                "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36");
        }
        if ($paras['nobody']) {
            curl_setopt($ch, CURLOPT_NOBODY, 1);
        }
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($ch);
        curl_close($ch);
        return $ret;
    }

}
