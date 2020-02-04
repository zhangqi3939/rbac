<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use app\index\model\UserModel;
class Manual extends Controller
{
    //手册列表
    public function manual_list()
    {
        $User =  new UserModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $result = Db::name('manual')->field('ID,TITLE')->order('SEQUENCE')->select();
        app_send(arraykeyToLower($result));
    }
    //手册信息
    function manual_info(){
        $User =  new UserModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $id = intval(input('post.id'));
        $result = Db::name('manual')->where('ID',$id)->select();
        app_send(arraykeyToLower($result));
    }
}