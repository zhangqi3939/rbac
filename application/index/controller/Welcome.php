<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Debug;
use app\index\model\UserModel;

class welcome extends Controller
{
    //用户登录
    public function user_login()
    {
        $userName = input('post.userName');
        $userPassword = input('post.userPassword');
        $channel = input('post.channel');
        empty($channel) && $channel = 'web';
        $where = array( 'USER_NAME'=>$userName, 'STATUS'=>0);
        $User =  new UserModel();
        $userExit  = $User->userList($where);
        if(!empty($userExit) && count($userExit)>0){
            if($userExit['USER_PASSWORD']  == $userPassword){
                if($channel == 'app'){
                    $uuid = input('post.uuid');
                    $token = $User->newToken($userExit["ID"],$channel,$uuid);
                }else{
                    $token = $User->newToken($userExit["ID"],$channel);
                }
                if(!empty($token)){
                    $userdata = array(
                        'UID' => $userExit["ID"],
                        'USER_ID' => $userExit["ID"],
                        'USER_NAME' =>$userExit["USER_NAME"],
                        'REAL_NAME' => $userExit["REAL_NAME"],
                        'AGENT_ID' => $userExit["AGENT_ID"],
                        'CLIENT_ID' => $userExit["CLIENT_ID"],
                        'USER_LEVEL' => $userExit["USER_LEVEL"],
                        'IS_ADMIN' => $userExit["IS_ADMIN"],
                        'TEL'=> $userExit['TEL'],
                        'TOKEN'=> $token
                    );
                    $User->saveLoginLog($userdata['UID']);
                    app_send(arraykeyToLower($userdata));
                }else{
                    app_send('','400','token error.');
                }
            }else{
                app_send('','400','password error1!');
                exit();
            }
        }else{
            app_send('','400','user error!');
            exit();
        }
    }
    //获取当前登录用户的信息
    public function user_info()
    {
        $User =  new UserModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        app_send(arraykeyToLower($user_info));
    }
    //修改用户信息
    function user_info_change(){
        $User =  new UserModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $realName	= input('post.realName');
        $gender	= input('post.gender');
        $tel	= input('post.tel');
        $email	= input('post.email');
        $upArray=array();
        !empty($realName) && $upArray['REAL_NAME']=$realName;
        !empty($gender) && $upArray['GENDER']=$gender;
        !empty($tel) && $upArray['TEL']=$tel;
        !empty($email) && $upArray['EMAIL']=$email;
        $result = Db::name('user')->where('ID',$user_info['ID'])->update($upArray);
        if($result == true){
            app_send('','200','修改成功');
        }else{
            app_send('','400','修改失败');
        }
    }
    //修改密码
    function user_password_change(){
        $User =  new UserModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $oldpwd	= input('post.oldPassword');
        $newpwd	= md5(input('post.newPassword'));
        if(md5($oldpwd) == $user_info['USER_PASSWORD']){
            Db::name('user')->where('id',$user_info['ID'])->update(array('USER_PASSWORD'=>$newpwd));
            app_send('','200','密码修改成功,请重新登录,稍后跳转...');
        }else{
            app_send('','400','源密码错误');
        }
    }
    //退出登录
    public function user_login_out()
    {
        $User =  new UserModel();
        $channel = 'web';
        $result = $User->deleteToken($channel);
        if($result>0){
            app_send();
        }else{
            app_send('','400','退出失败，请联系管理员');
        }
    }
    public function run()
    {
        $url = $this->add();
    }
    public function add()
    {
        $module     = request()->module();
        $controller = request()->controller();
        $action     = request()->action();
        $url        = $module.'/'.$controller.'/'.$action;
        return strtolower($url);
    }
}