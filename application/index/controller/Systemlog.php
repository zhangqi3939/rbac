<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use app\index\model\UserModel;
use app\index\model\SystemlogModel;
class Systemlog extends Controller
{
    public function log_category()
    {
        $User =  new UserModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $logCategory = array(
            array('catName'=>'登录日志','catCode'=>'login'),
            array('catName'=>'控制记录','catCode'=>'cooler_control'),
            array('catName'=>'操作日志','catCode'=>'system_operation')
        );
        app_send($logCategory);
    }

    public function log_list()
    {
        $User =  new UserModel();
        $Systemlog =  new SystemlogModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $travelID = input('post.travelID');
        $logCategory = input('post.logCategory');
        $start_time = input('post.startTime');
        $end_time = input('post.endTime');
        $page = input('post.page');
        $perPage = input('post.perPage');

        //未传递起止时间，返回最近三天
        if(empty($start_time) || empty($end_time)){
            $startStamp = time() - 72*3600;
            $endStamp = time();
        }else{
            $startStamp = strtotime($start_time);
            $endStamp = empty($end_time) ? time() : strtotime($end_time);
        }
        $user_info['startStamp'] = $startStamp;
        $user_info['endStamp'] = $endStamp;
        $user_info['page'] = $page;
        $user_info['perPage'] = $perPage;
        if($logCategory == 'login'){//
            $result = $Systemlog->getLoginLog($user_info);
            app_send($result);
            exit();
        }elseif($logCategory == 'cooler_control'){
            $result = $Systemlog->getCmdLog($user_info);
            app_send($result);

            exit();
        }elseif($logCategory == 'system_operation'){
            $result = $Systemlog->getSystemLog($user_info);
            app_send($result);

            exit();
        }
        app_send('','400','类别错误');
    }
}