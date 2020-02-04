<?php
namespace app\index\model;
use think\Model;
use think\Db;
class AlarmModel extends Model
{
    public function getAlarmIndex($params){
        $url = getActionUrl();
        if($url =  "index/alarm/alarm_index"){
            $box_category = '01';
        }else{
            $box_category = '02';
        }
        $where = '1=1';
        if(!empty($box_id)){
            $where .= " and A.BOX_ID = ".$box_id;
        }
        if(!empty($box_category)){
            $where .= " and C.CATEGORY = ".$box_category;

        }
        $result = Db('alarm')
                ->alias('A')
                ->join('container C','A.BOX_ID = C.BOX_ID','LEFT')
                ->where($where)
                ->field('A.ID,C.NAME,A.BOX_ID,A.DEVICE_CODE,A.ALARM_TYPE,A.START_TIME,A.END_TIME,A.ALARM_DATA,A.STATUS')
                ->select();
        $reArray = array();
        if(!empty($result)){
            foreach ($result as $row) {
                $reArray[]=array(
                    'id'=>$row['ID'],
                    'box_id'=>$row['BOX_ID'],
                    'title'=>$row['NAME'].alarmType($row['DEVICE_CODE'],$row['ALARM_TYPE'])
                );
            }
        }
       return $reArray;
    }

    //获取当前报警
    public function getCurrentAlarm($params){
        $where = '1=1 ';
        if(!empty($box_id)){
           // $this->db->where('a.box_id',$box_id);
            $where .= " and A.BOX_ID = ".$box_id;
        }
        $result = Db::name('alarm')
            ->alias('A')
            ->join('container C','A.BOX_ID = C.BOX_ID','LEFT')
            ->field('C.NAME,A.BOX_ID,A.DEVICE_CODE,A.ALARM_TYPE,A.START_TIME,A.END_TIME,A.ALARM_DATA,A.STATUS')
            ->where($where)
            ->select();
        return $result;
    }
    //报警历史
    public function getAlarmHistory($params){
        $where = '1=1 ';
        if(!empty($box_id)){
            // $this->db->where('a.box_id',$box_id);
            $where .= " and A.BOX_ID = ".$box_id;
        }
        $alarmHistory = Db::name('alarm_log')
            ->alias('A')
            ->join('container C','A.BOX_ID = C.BOX_ID','LEFT')
            ->field('C.NAME,A.BOX_ID,A.DEVICE_CODE,A.ALARM_TYPE,A.START_TIME,A.END_TIME,A.ALARM_DATA,A.STATUS')
            ->where('a.START_TIME between '.$params['startStamp'].' and '.$params['endStamp'])
            ->where($where)
            ->select();
        //查询时间段内的强制低压报警
        $vAlarm = Db::name('data_latest')
            ->alias('D')
            ->join('container C','C.BOX_ID = D.BOX_ID','left')
            ->field("C.NAME,C.BOX_ID,'V2' AS DEVICE_CODE, 2 AS ALARMTYPE,D.INSERT_TIME AS START_TIME,D.INSERT_TIME AS END_TIME,D.GPS_VOLTAGE AS ALARMDATA")
            ->where('d.insert_time between '.$params['startStamp'].' and '.$params['endStamp'])
            ->where('d.gps_voltage between  '.'-1'.' and 110')
            ->select();
        $alarmHistory = array_merge($alarmHistory,$vAlarm);
        return $alarmHistory;
    }
}