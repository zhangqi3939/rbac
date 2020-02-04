<?php

namespace app\behavior;
use think\Controller;
use think\Exception;
use think\Db;
use app\index\model\RbacModel;
class OperateBehavior extends Controller{

    // 定义需要排除的权限路由
    protected $exclude = [
        'index/welcome/user_login',
        'index/welcome/user_info',
        'index/welcome/user_info_change',
        'index/welcome/user_password_change',
        'index/welcome/user_login_out',
        'index/welcome/run',
        'index/schedule/monitor_latest',
        'index/schedule/monitor_save',
        'index/schedule/station_listing',
        'index/schedule/station_save',
        'index/schedule/station_delete',
        'index/schedule/station_group_add',
        'index/schedule/station_group_delete',
        'index/schedule/user_listing',
        'index/schedule/class_listing',
        'index/schedule/monitor_listing',
        'index/schedule/class_save',
        'index/schedule/schedule_save',
        'index/schedule/class_delete',
        'index/schedule/schedule_listing',
        'index/device/group_listing',
        'index/device/box_latest',
        'index/device/box_data',
        'index/device/box_data_page',
        'index/device/group_list',
        'index/device/group_save',
        'index/device/group_box_save',
        'index/device/box_list',
        'index/device/group_box_clear',
        'index/device/group_delete',
        'index/device/box_save',
        'index/device/box_info',
        'index/device/box_param',
        'index/device/box_param_save',
        'index/device/box_op_state',
        'index/device/box_op',
        'index/device/box_group_save',
        'index/device/device_category',
        'index/device/main_insert',
        'index/alarm/alarm_index',
        'index/alarm/alarm_setting',
        'index/alarm/alarm_index_info',
        'index/alarm/alarm_msg',
        'index/alarm/alarm_save',
        'index/alarm/alarm_current',
        'index/alarm/alarm_history',
        'index/travel/travel_list',
        'index/travel/travel_delete',
        'index/travel/travel_save',
        'index/travel/travel_change',
        'index/travel/travel_info',
        'index/travel/travel_box_save',
        'index/report/run_time',
        'index/systemlog/log_category',
        'index/systemlog/log_list',
        'index/dept/dept_save',
        'index/dept/dept_delete',
        'index/dept/dept_listing',
        'index/dept/user_listing',
        'index/dept/user_info',
        'index/dept/user_save',
        'index/dept/user_delete',
        'index/dept/limit_add',
        'index/dept/limit_info',
        'index/dept/limit_edit',
        'index/dept/limit_delete',
        'index/dept/limit_list',
        'index/dept/role_user',
        'index/dept/role_list',
        'index/dept/role_save',
        'index/dept/role_detail',
        'index/dept/role_delete',
        'index/dept/role_grouplist',
        'index/dept/role_group_add',
        'index/dept/role_group_edit',
        'index/dept/role_group_delete',
        'index/manual/manual_list',
        'index/manual/manual_info',
        //手持设备
       /*'handheld/information/waybill_list',
       'handheld/information/waybill_list_page',
       'handheld/information/waybill_goods_details',
       'handheld/information/save_waybill',
       'handheld/information/save_operation_list',
       'handheld/information/save_travel_info_status',
       'handheld/information/delete_travel_info_status',
       'handheld/information/all_operation_list_page',
       'handheld/information/operation_list_details',
       'handheld/information/delete_box_info',
       'handheld/information/delete_img',
       'handheld/information/save_box_damage',
       'handheld/information/box_damage_list',
       'handheld/information/damage_style',
       'handheld/information/box_damage_list_details',
       'handheld/information/reminder_version',
       'handheld/information/user_login_info',
       'handheld/information/handle_user_login_out',
       'handheld/information/waybill_delete',
       'handheld/information/operation_list_delete',*/
        //手持设备[运单]
        'container/waybill/station_list',
        'container/waybill/waybill_save',
        'container/waybill/waybill_detail',
        'container/waybill/waybill_list',
        'container/waybill/waybill_delete',
        'container/waybill/waybill_status_save',
        'container/waybill/waybill_status_delete',
        'container/station/operation_save',
        'container/station/operation_detail',
        'container/station/operation_list',
        'container/station/operation_delete',
        'container/station/damage_category',
        'container/station/operation_attachment_delete',
        'container/container/app_version',
        'container/container/user_login_info',
        'container/operation/box_damage_details',
        'container/operation/box_damage_list',
        'container/operation/delete_img',
        'container/operation/delete_box_info',
        'container/container/handle_user_login_out',
        //服务类
        'index/s/alarm_current',//分析当前报警
        'index/s/fence_message',//分析行程围栏信息
        //end of 服务类
        //地图服务
        'index/map/fence_list',//围栏列表
        'index/map/fence_info',//围栏列表
        'index/map/fence_save',//围栏保存
        'index/map/fence_delete',//围栏删除
        //end of 地图服务

    ];

    /**
     * 权限验证
     */
    public function run(&$params){
        // 行为逻辑
        try {
            // 获取当前访问路由
            $url  = $this->getActionUrl();
            $Rbac =  new RbacModel();
            $token_exit = $Rbac->getTokenFromHttp();
            $token_exit = trim($token_exit,'"');
            $uid = Db::name('token')->where('TOKEN',$token_exit)->find();
            $user_info = Db::name('user')->where('ID',$uid['USER_ID'])->find();
            if(empty($user_info['ID']) && !in_array($url,$this->exclude)){
                $this->error('请先登录',    '/welcome/user_login');
            }
            $token_express = $Rbac->checkToken($params = web);
            $role_id = Db::name('rbac_user_role_relation')->field('ROLE_ID')->where('USER_ID',$uid['USER_ID'])->find();
            $role_id = explode(",", $role_id['ROLE_ID']);
            $limit_id = Db::name('rbac_role')->field('ROLE_IN_LIMIT')->where('ID','IN',$role_id)->select();
            $data = array_column($limit_id,"ROLE_IN_LIMIT");
            $mod = array();
            foreach($data as $key=>$value){
                if(strpos($value,',') != false){
                    $array = explode(',',$value);
                    $array = array_filter($array);
                    $mod = $array+$mod;
                }
            }
            $data  = array(
                "ROLE_IN_LIMIT"=>$mod,
                "NUMROW"=>""
            );
            $auth = Db::name('rbac_limit')->field('URL')->where('PARENT_ID','IN',$data['ROLE_IN_LIMIT'])->select();
            // 用户所拥有的权限路由
            //$auth = Db::name('rbac_limit')->field('URL')->where('ID','in',$limit_id_total['ID'])->select();
            //$auth = array_column($auth, 'URL','NUMROW');
            if(!in_array($url, $auth) && !in_array($url, $this->exclude)) {
                app_send('','400','您没有操作权限，请联系管理员');
            }

        } catch (Exception $ex) {
            //print_r($ex);
        }
    }
    /**
     * 获取当前访问路由
     * @param $Request
     * @return string
     */
    private function getActionUrl()
    {
        $module     = request()->module();
        $controller = request()->controller();
        $action     = request()->action();
        $url        = $module.'/'.$controller.'/'.$action;
        return strtolower($url);
    }




}