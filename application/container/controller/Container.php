<?php
namespace app\container\controller;
use think\Controller;
use think\Db;
use app\index\model\UserModel;
use app\container\model\ContainerModel;
class Container extends Controller
{
    //app版本
    public function app_version()
    {
        $re =array(
            'version'=>'1.01',
            'url'=>'http://tk2.qianbitou.com/upload/crscl_1.01.apk',
            'remarks'=>''
        );
        app_send($re);
    }
    //用户
    function user_login_info(){
        $data = input('post.');
        if(!empty($data)){
            $channel = $data['channel'];
        }else{
            $channel = '';
        }
        $User =  new UserModel();
        $user_info = $User->checkToken($channel);
        unset($user_info['USER_PASSWORD']);
        app_send(arraykeyToLower($user_info));
    }
}