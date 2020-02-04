<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use app\index\model\UserModel;
use app\index\model\TravelModel;
class Travel extends Controller
{
    public function travel_list()
    {
        $User =  new UserModel();
        $Travel=  new TravelModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $user_info['STATUS'] = input('post.status');
        $user_info['keyword'] = input('post.keyword');
        is_null($user_info['STATUS']) && $user_info['STATUS'] = 1;
        $user_info['STATUS'] == -1 && $user_info['STATUS'] = NULL;
        app_send(arraykeyToLower($Travel->getTravelList($user_info)));
    }
    //行程添加（修改）
    public function travel_save()
    {
        $User =  new UserModel();
        $Travel=  new TravelModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $data = input('post.');
        $d=array(
            'STATUS'=>input('post.status'),
            'TITLE'=>input('post.title'),
            'START_TIME'=>strtotime(input('post.startTime')),
            'END_TIME'=>strtotime(input('post.endTime')),
            'GOODS'=>input('post.goods'),
            'REMARKS'=>input('post.remarks')
        );
        if(!empty($data['boxIDs'])){
            $d['boxIDs'] = $data['boxIDs'];
        }else{
            $d['boxIDs'] = array();
        }
        if(empty($d['ENDTIME'])) unset($d['ENDTIME']);
        $user_info['d'] = $d;
        $user_info['travelID'] = input('post.id');
        if(empty($d['TITLE'])){
            app_send('','400','行程名称不能为空');
            exit();
        }
        $result = $Travel->travelSave($user_info);
        app_send();
    }
    //行程设备维护
    public function travel_info()
    {
        $User =  new UserModel();
        $Travel=  new TravelModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $travelID = input('post.travelID');
        app_send(arraykeyToLower($Travel->getTravelInfo($travelID)));
    }
    //行程删除
    function travel_delete(){
        $User =  new UserModel();
        $Travel=  new TravelModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $travelID = input('post.travelID');
        $result = $Travel->deleteTravel($travelID);
        app_send();
    }
    function travel_box_save(){
        $User =  new UserModel();
        $Travel=  new TravelModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $data = input('post.');
        $travelID = input('post.travelID');
        if(!empty($data['boxIDs'])){
            $boxIDs = input('post.')['boxIDs'];
        }else{
            $boxIDs = array();
        }
        $boxIDs = input('post.')['boxIDs'];
        if(empty($travelID)){
            app_send('',400,'行程错误！');
            exit();
        }
        ///判断用户和travelID权限
        //end of 判断
        if(empty($boxIDs)) $boxIDs=array(0);
        Db::name('travel_to_box')->where('TRAVEL_ID',$travelID)->where('BOX_ID','not in',$boxIDs)->delete();
        //$this->db->where_not_in('box_id',$boxIDs)->where('travelID',$travelID)->delete('travel_to_box');
        if(!empty($boxIDs)){
            foreach ($boxIDs as $box_id){
                $whereArray = array(
                    'TRAVEL_ID'=>$travelID,
                    'BOX_ID'=>$box_id
                );
               updateOrInsert('travel_to_box',$whereArray,$whereArray);
            }
        }
        $Travel ->updateTravelDeviceNum($travelID);
        app_send();
    }
}