<?php
namespace app\index\controller;
use app\index\model\DeviceModel;
use think\Controller;
use think\Db;
use app\index\model\UserModel;
use app\index\model\AlarmModel;
use app\index\model\TravelModel;
class Alarm extends Controller
{
    public function alarm_index()
    {
        $User =  new UserModel();
        $Alarm = new AlarmModel();
        $Travel = new TravelModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $array = array(
            "category"=>array(
                array('catCode'=>'alarm','catName'=>'报警消息'),
                array('catCode'=>'travel','catName'=>'行驶消息')
            ),
            'dataList'=>array(
                'alarm'=>$Alarm->getAlarmIndex($user_info),
                'travel'=>$Travel->getTravelIndex($user_info)
            )
        );
        app_send($array);
    }
    //首页侧边栏消息详情
    public function alarm_index_info()
    {
        $User =  new UserModel();
        $Alarm = new AlarmModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $id = input('post.id');
        $cagetory = input('post.cagetory');
        $box_id = input('post.box_id');
        $array = array(
            array('title'=>'报警类型','value'=>'高温报警'),
            array('title'=>'报警温度','value'=>'25°C'),
            array('title'=>'报警时间','value'=>formatDate('',2))
        );
        app_send($array);
    }
    public function alarm_setting()
    {
        $User =  new UserModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $box_id = input('post.box_id');
        $row = Db::name('alarm_setting')->where('BOX_ID',$box_id)->find();
        $row['STRIP_TIME'] = intval( $row['STRIP_TIME']);
        app_send(arraykeyToLower($row));
    }
    public function alarm_msg()
    {
        $box_id = input('post.box_id');
        $startTime = input('post.startTime');
        $endTime = input('post.endTime');
        $startStamp = $startTime== '' ? time() - 7*24*3600 : strtotime($startTime);
        $endStamp = $endTime== '' ? time() : strtotime($endTime);
        $data=array('status'=>1,'msg'=>1,'data'=>array());
        $msgTable = 'zj_alarm_msg';
        $containerTable = 'zj_container';
        $sql="select M.*,C.NAME as containerName 
			from $msgTable M 
			left join $containerTable C on M.BOX_ID = C.BOX_ID 
			where M.MSG_TIME > $startStamp and  M.MSG_TIME < $endStamp 
				";
        $User =  new UserModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        !empty($box_id) && $sql .= " and C.BOX_ID like '%".$box_id."'%";
        $sql .= " order by M.ID desc";
        $data['data'] = query($sql);
        send_json($data);
    }
    public function alarm_save()
    {
        $User =  new UserModel();
        $Alarm = new AlarmModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        if($user_info['IS_ADMIN'] < 1){
            app_send('','400','您不是管理员');
            exit();
        }
        $stripTime = input('post.stripTime');
        if($stripTime < 300){
            app_send('',400,'最小间隔不能小于5分钟。');
        }
        $box_id = input('post.')['box_id'];
        $d=array(
            'BOX_ID'=>$box_id,
            'ALARM_PHONE'=>input('post.alarmPhone'),
            'STRIP_TIME'=>intval(input('post.stripTime')),
            'T1_ON'=>intval(input('post.t1On')),
            'T1_HIGH'=>input('post.t1High'),
            'T1_LOW'=>input('post.t1Low'),
            'T2_ON'=>intval(input('post.t2On')),
            'T2_HIGH'=>input('post.t2High'),
            'T2_LOW'=>input('post.t2Low'),
            'T3_ON'=>intval(input('post.t3On')),
            'T3_HIGH'=>input('post.t3High'),
            'T3_LOW'=>input('post.t3Low'),
            'T4_ON'=>intval(input('post.t4On')),
            'T4_HIGH'=>input('post.t4High'),
            'T4_LOW'=>input('post.t4Low'),
            'H1_ON'=>intval(input('post.h1On')),
            'H1_HIGH'=>input('post.h1High'),
            'H1_LOW'=>input('post.h1Low'),
            'O1_ON'=>intval(input('post.o1On')),
            'O1_HIGH'=>input('post.o1High'),
            'O1_LOW'=>input('post.o1Low'),
            'V1_ON'=>intval(input('post.v1On')),
            'V1_HIGH'=>input('post.v1High'),
            'V1_LOW'=>input('post.v1Low')
        );
        //null 值处理
        if($d['T1_HIGH'] == null) unset($d['T1_HIGH']);
        if($d['T1_LOW'] == null) unset($d['T1_LOW']);
        if($d['T2_HIGH'] == null) unset($d['T2_HIGH']);
        if($d['T2_LOW'] == null) unset($d['T2_LOW']);
        if($d['T3_HIGH'] == null) unset($d['T3_HIGH']);
        if($d['T3_LOW'] == null) unset($d['T3_LOW']);
        if($d['T4_HIGH'] == null) unset($d['T4_HIGH']);
        if($d['T4_LOW'] == null) unset($d['T4_LOW']);
        if($d['H1_HIGH'] == null) unset($d['H1_HIGH']);
        if($d['H1_LOW'] == null) unset($d['H1_LOW']);
        if($d['O1_HIGH'] == null) unset($d['O1_HIGH']);
        if($d['O1_LOW'] == null) unset($d['O1_LOW']);
        if($d['V1_HIGH'] == null) unset($d['V1_HIGH']);
        if($d['V1_LOW'] == null) unset($d['V1_LOW']);
        if(is_array($box_id)){
            $boxIDs = $box_id;
            if(!empty($boxIDs)){
                foreach ($boxIDs as $otherID) {
                    $d['BOX_ID'] = $otherID;
                    updateOrInsert('alarm_setting',array('box_id'=>$otherID),$d);

                    //存日志
                    saveToLog(array('op'=>'alarm_setting','objName'=>$otherID,'objID'=>$otherID,'UID'=>$user_info['ID']));
                }
            }
        }else{
            updateOrInsert('alarm_setting',array('BOX_ID'=>$box_id),$d);
            //存日志
            saveToLog(array('op'=>'alarm_setting','objName'=>$box_id,'objID'=>$box_id,'UID'=>$user_info['ID']));
        }

        app_send();
    }
    public function alarm_current()
    {
        $User =  new UserModel();
        $Alarm = new AlarmModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $user_info['box_id'] = input('post.box_id');
        $startTime = input('post.startTime');
        $endTime = input('post.endTime');
        $user_info['startStamp'] = empty($startTime) ? time() - 3600 * 72 : strtotime($startTime);
        $user_info['endStamp'] = empty($endTime) ? time() : strtotime($endTime);
        app_send(arraykeyToLower($Alarm->getCurrentAlarm($user_info)));
    }
    //报警历史
    public function alarm_history(){
        $User =  new UserModel();
        $Alarm = new AlarmModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $user_info['box_id'] = input('post.box_id');
        $startTime = input('post.startTime');
        $user_info['startStamp'] = empty($startTime) ? time() - 3600 * 72 : strtotime($startTime);
        $user_info['endStamp'] = empty($endTime) ? time() : strtotime($endTime);
        app_send(arraykeyToLower($Alarm->getAlarmHistory($user_info)));
    }
}