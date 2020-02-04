<?php
namespace app\index\model;
use think\Model;
use think\Db;
use app\index\model\UserModel;
class TravelModel extends Model
{
    //行程->行程列表
    public function getTravelList($params)
    {
        $where = '1=1 ';
        if(!empty($params['keyword'])){
            $where .= " and (TITLE like '%".$params['keyword']."%')";
        }
        if(!empty($params['STATUS'])){
            $where .= " and STATUS = ".$params['STATUS'];
        }
        $where .= " and DELETED = ".'0';
        $result = Db::name('travel')->field('ID,STATUS,TITLE,START_TIME,END_TIME,BOX_NUM,GOODS')->where($where)->select();
        return $result;

    }
    public function travelSave($params)
    {
        $User = new UserModel();
        $User_info = $User->checkToken();
        if($params['IS_ADMIN'] == 1){

            $d=$params['d'];
            $boxIDs = $d['boxIDs'];
            unset($d['boxIDs']);
            switch ($params['USER_LEVEL']) {
                case 2://管理员
                    break;
                case 1://agent
                    $d=array(
                        'AGENT_ID'=>$params['AGENT_ID']
                    );
                    break;
                case 0://client
                    $d=array(
                        'CLIENT_ID'=>$params['CLIENT_ID']
                    );
                    break;
                case -1://branch
                    $d=array(
                        'BRANCH_ID'=>$params['BRANCH_ID']
                    );
                    break;
                default:
                    break;
            }
            if(!empty($d)){
                if($params['travelID']>0){//如果是修改
                   // $this->db->where('id',$params->travelID)->update('travel',$d);
                    Db::name('travel')->where('ID',$params['travelID'])->update($d);
                    $this->saveBoxToTravel($params['travelID'],$boxIDs);
                    $travelID = $params['travelID'];
                    //存入日志
                    saveToLog(array('op'=>'travel_edit','objName'=>$d['TITLE'],'UID'=>$User_info['ID'],'objID'=>$travelID));
                }else{//如果是添加
                    $travelID = Db::name('travel')->insertGetId($d);
                    if($boxIDs){
                        $this->saveBoxToTravel($travelID,$boxIDs);
                    }
                    //存入日志
                   saveToLog(array('op'=>'travel_edit','objName'=>$d['TITLE'],'UID'=>$User_info['ID'],'objID'=>$travelID));
                }
               $this->updateTravelDeviceNum($travelID);
                return array('status'=>1,'msg'=>'');
            }else{
                return array('status'=>0,'您的用户信息有误，请重新登录。');
            }
        }else{
            return array('status'=>0,'您没有权限执行操作');
        }
    }
    //保存设备到行程
    function saveBoxToTravel($travelID,$boxs){
        if(!empty($boxs)){
           // $this->db->trans_start();
//            $this->db->where('travelID',$travelID)->delete('travel_to_box');
            Db::name('travel_to_box')->where('TRAVEL_ID',$travelID)->delete();
            foreach ($boxs as $box_id) {
                $d = array('TRAVEL_ID'=>$travelID,'BOX_ID'=>$box_id);
                $result = updateOrInsert('travel_to_box',$d,$d);
            }
            if($result){
                return 0;
            }else{
                return 1;
            }
        }
        return 0;
    }
    //行程详情
    function getTravelInfo($travelID){
        $travelInfo = Db::name('travel')->where('ID',$travelID)->select();
        if(!empty($travelInfo)){
            $travelInfo['boxs'] = $this->getTravelBoxList($travelID);
        }
        unset($travelInfo['AGENT_ID']);
        unset($travelInfo['CLIENT_ID']);
        unset($travelInfo['BRANCH_ID']);
        return arraykeyToLower($travelInfo);
    }
    //行程设备列表
    function getTravelBoxList($travelID){
        $result = Db::name('travel_to_box')
                ->alias('T')
                ->join('container C','T.BOX_ID = C.BOX_ID','LEFT')
                ->where('T.TRAVEL_ID',$travelID)
                ->select();
        return arraykeyToLower($result);
    }
    //更新行程设备数量
    function updateTravelDeviceNum($travelID){
        $Model = new TravelModel();
        $travelTable = 'zj_travel';
        $travelBoxTable = 'zj_travel_to_box';
        $sql = "update $travelTable set BOX_NUM = (select count(1) from $travelBoxTable where TRAVEL_ID = $travelID) where ID = $travelID";
        $Model->execute($sql);
    }
    function deleteTravel($travelID){
        Db::name('travel')->where('ID',$travelID)->update(array('DELETED'=>1));
        //$this->db->where('id',$travelID)->update('travel',array('deleted'=>1));
        return 1;
    }
    function getTravelIndex($params)
    {
        $where = '1=1';
        if(!empty($box_id)){
            $where .= " and B.BOX_ID = ".$box_id;
        }
        $where .= " and T.FLAG_DELETE = ".'0';
        $result = Db::name('travel_operation')
            ->alias('T')
            ->join('travel_operation_box B','T.ID = B.LIST_ID' , 'left')
            ->where($where)
            ->field('T.ID,B.BOX_NUM,T.TITLE')
            ->select();
        $reArray = array();
        if(!empty($result)){
            foreach ($result as $row) {
                $reArray[]=array(
                    'id'=>$row['ID'],
                    'box_id'=>$row['BOX_NUM'],
                    'title'=>$row['TITLE']
                );
            }
        }
        return $reArray;
    }
}