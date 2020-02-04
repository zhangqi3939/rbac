<?php
namespace app\container\controller;
use think\Controller;
use think\Db;
use app\index\model\UserModel;
use app\container\model\WaybillModel;
use think\Request;

class Waybill extends Controller
{
    function __construct()
    {
        parent::__construct();
        $channel = input('post.channel');
        $User = new UserModel();
        $this->waybill = new WaybillModel();
        $this->user = $User->checkToken($channel);
    }
    //获取车站列表
    public function station_list()
    {
        $keywords = input('post.keywords');
        if(empty($keywords) || strlen(trim($keywords)) < 2){
            app_send_400('至少输入2个字符');
        }
        $result = $this->waybill->getStationList($keywords);
        app_send($result);
    }
    //运单列表
    public function waybill_list()
    {
        $user = $this->user;
        $formData = input('post.');
        $user['startTime'] = @$formData['startTime'];
        $user['endTime'] = @$formData['endTime'];
        isset($formData['status']) && $user['status'] = $formData['status'];
        isset($formData['page']) && $user['page'] = $formData['page'];
        isset($formData['perpage']) && $user['perpage'] = $formData['perpage'];
        isset($formData['keywords']) && $user['keywords'] = $formData['keywords'];//keywords是发站名
        $result = $this->waybill->getTravelList($user);
        app_send($result);
    }
    //运单列表  分页
    function waybill_list_page(){
        $user =$this->user;
        $user['travelStatusStart'] = -1;
        $user['travelStatusEnd'] = 3;
        $result = $this->waybill->getTravelList($user);
        app_send(
            array(
                'total'=>count($result),
                'travelInfo'=>arrayKeyToLower($result)
            )
        );
    }
    //运单保存
    public function waybill_save(){
        $formData = input('post.');
        unset($formData['channel']);
        $result = $this->waybill->waybillSave($formData);
        app_send($result);
    }
    //运单详情,包含货物,过程历史
    public function waybill_detail()
    {
        $travelID = input('post.travel_id');
        //判断是否有该业务权限
        //end of 权限判断
        $travelInfo = $this->waybill->getTravelDetail($travelID);
        app_send($travelInfo);
    }
    //运单删除
    public function waybill_delete(){
        $travel_id = intval(input('post.travel_id'));
        $res = $this->waybill->waybillDelete($travel_id);
        if($res === false){
            app_send_400('删除失败');
        }
        app_send();
    }
    //运单状态标记
    public function waybill_status_save(){
        $formData = input('post.');
        $res = $this->waybill->waybillStatusSave($formData);

        if($res !== false){
            app_send($res);
        }else{
            app_send_400('保存失败');
        }
    }
    //运单状态删除
    function waybill_status_delete(){
        $status_id = intval(input('post.status_id'));
        if(empty($status_id)){
            app_send_400('状态选择错误！');
        }
        $res = $this->waybill->waybillStatusDelete($status_id);
        if($res === false){
            app_send_400('删除失败！');
        }else{
            app_send();
        }
    }
}