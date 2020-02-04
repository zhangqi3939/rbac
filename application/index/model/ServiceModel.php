<?php
namespace app\index\model;
use think\Model;
use think\Db;

/**
 * ServiceModel  服务模块类，处理软件定时服务功能
 */
class ServiceModel extends Model{
	//判断报警
    //deviceCode: t1,t2,h1,$box_cat 设备类型，空默认为所有
    function dealAlarm($deviceCode,$dataColumn,$boxCat = ''){
        $i=0;
        $ratio = 10;//数据倍率
        $deviceCode = strtoupper($deviceCode);
        $dataColumn = strtoupper($dataColumn);
        if($deviceCode =='H1' || $deviceCode == 'O1'){
            $ratio = 1;
        }
        //查询有设置的所有数据
        $select = 'S.ALARM_PHONE,S.STRIP_TIME,S.BOX_ID,
                                S.T1_ON,S.T1_HIGH,S.T1_LOW,
                                S.T2_ON,S.T2_HIGH,S.T2_LOW,
                                S.T3_ON,S.T3_HIGH,S.T3_LOW,
                                S.H1_ON,S.H1_HIGH,S.H1_LOW,
                                S.O1_ON,S.O1_HIGH,S.O1_LOW,
                                S.V1_ON,S.V1_HIGH,S.V1_LOW,
                                D.GPS_TEMP1,D.GPS_TEMP2,D.GPS_TEMP3,D.GPS_HUMI,D.GPS_OIL_LEVEL,D.GPS_VOLTAGE,D.COOLER_SET_TEMP,D.INSERT_TIME,A.ID,A.DEVICE_CODE,A.ALARM_TYPE,A.START_TIME,A.END_TIME,A.ALARM_DATA,A.STATUS';
        $map = array();
        $map['D.ID'] = ['>','0'];
        $alarmData = Db('alarm_setting')
                                    ->alias('S')
                                    ->join('data_latest D','D.BOX_ID = S.BOX_ID','left')
                                    ->join('alarm A','A.BOX_ID = S.BOX_ID AND (A.DEVICE_CODE = \''.$deviceCode.'\' OR A.DEVICE_CODE IS NULL)','left')
                                    ->field($select)
                                    ->where($map)
                                    ->select();
        if(empty($alarmData)){
            return false;
        }
        $alarmOn = $deviceCode.'_ON';
        $setHigh = $deviceCode.'_HIGH';
        $setLow = $deviceCode.'_LOW';

        foreach ($alarmData as $row) {
            $row=(object)$row;
            //echo '<br/><br/><br/>';
            //如果没有数据，循环下一个
            if($row->INSERT_TIME == null){continue;}
            $row->$alarmOn > 0 && ++$i;

            if($row->$alarmOn == 1){//上下限
                if($row->$dataColumn/$ratio > $row->$setHigh && $row->$dataColumn > -999)//高温报警
                {
                    //如果无报警，添加报警
                    $d=array(
                        'BOX_ID'=>$row->BOX_ID,
                        'DEVICE_CODE'=>$deviceCode,
                        'ALARM_TYPE'=>1,
                        'START_TIME'=>$row->INSERT_TIME,
                        'END_TIME'=>$row->INSERT_TIME,
                        'STRIP_TIME'=>$row->STRIP_TIME,
                        'ALARM_DATA'=>$row->$dataColumn/$ratio,
                        'STATUS'=>0
                    );
                    if($row->ID == null){
                        Db::name('alarm')->insertGetId($d);
                        continue;
                    }elseif($row->STATUS == 1){//无报警，更新为有报警
                        Db::name('alarm')->where('ID',$row->ID)->update($d);
                        continue;
                    }
                    //如果有高报警，更新报警时间
                    if($row->alarmType == 1 && $row->id > 0){
                        if($row->endTime >= $row->INSERT_TIME){
                            continue;
                        }
                        $d=array('END_TIME'=>$row->INSERT_TIME);
                        Db::name('alarm')->where('ID',$row->ID)->update($d);
                        continue;
                    }
                    //如果有低报警，结束低报警，添加高报警
                    if($row->alarmType == 2){
                        //结束低报警
                        $d=array(
                            'STATUS'=>0,'END_TIME'=>$row->INSERT_TIME
                        );
                        Db::name('alarm')->where('ID',$row->ID)->update($d);
                        //添加高报警
                        $d=array(
                            'START_TIME'=>$row->INSERT_TIME,
                            'ALARM_TYPE'=>1,
                            'END_TIME'=>$row->INSERT_TIME,
                            'STATUS'=>0
                        );
                        $this->db->where('id',$row->id)->update('alarm',$d);
                        Db::name('alarm')->where('id',$row->ID)->update($d);
                    }
                }
                elseif($row->$dataColumn/$ratio < $row->$setLow && $row->$dataColumn > -999){//低温报警
                    //如果无报警，添加报警
                    $d=array(
                        'BOX_ID'=>$row->BOX_ID,
                        'DEVICE_CODE'=>$deviceCode,
                        'ALARM_TYPE'=>2,
                        'START_TIME'=>$row->INSERT_TIME,
                        'END_TIME'=>$row->INSERT_TIME,
                        'STRIP_TIME'=>$row->STRIP_TIME,
                        'ALARM_DATA'=>$row->$dataColumn/$ratio,
                        'STATUS'=>0
                    );
                    if($row->ID == null){
                        Db::name('alarm')->insertGetId($d);
                        //echo $this->db->last_query().'<br/>';
                        continue;
                    }elseif($row->STATUS == 1){//无报警，更新为有报警
                        Db::name('alarm')->where('id',$row->ID)->update($d);
                        continue;
                    }
                    //如果有低报警，更新报警时间
                    if($row->ALARM_TYPE == 2 && $row->ID > 0){
                        if($row->END_TIME >= $row->INSERT_TIME){
                            continue;
                        }
                        $d=array('END_TIME'=>$row->INSERT_TIME);
                        Db::name('alarm')->where('id',$row->ID)->update($d);
                        continue;
                    }
                    //如果有高报警，结束高报警，添加低报警
                    if($row->alarmType == 1){
                        //结束低报警
                        Db::name('alarm')->where('id',$row->ID)
                            ->update(array('STATUS'=>1,'END_TIME'=>$row->INSERT_TIME));
                        //添加高报警
                        $d=array(
                            'START_TIME'=>$row->INSERT_TIME,
                            'ALARM_TYPE'=>2,
                            'END_TIME'=>$row->INSERT_TIME,
                            'STATUS'=>0
                        );
                        Db::name('alarm')->where('id',$row->ID)->update($d);
                    }
                }else{//无报警
                    //如果当前有报警，结束报警
                    if(!empty($row->id) && $row->STATUS == 0){//如果当前有报警，结束报警
                        Db::name('alarm')->where('id',$row->ID)
                            ->update(array('STATUS'=>1,'END_TIME'=>$row->INSERT_TIME));
                    }
                }
            }elseif($row->$alarmOn == 2){//随设定温度
                //计算设定温度
                $setTemp = F2C($row->COOLER_SET_TEMP/100);
                if($row->$dataColumn/$ratio > $setTemp + $row->$setHigh && $row->$dataColumn > -999)//高报警
                {
                    //如果无报警，添加报警
                    $d=array(
                        'BOX_ID'=>$row->BOX_ID,
                        'DEVICE_CODE'=>$deviceCode,
                        'ALARM_TYPE'=>1,
                        'START_TIME'=>$row->INSERT_TIME,
                        'END_TIME'=>$row->INSERT_TIME,
                        'STRIP_TIME'=>$row->STRIP_TIME,
                        'ALARM_DATA'=>$row->$dataColumn/$ratio,
                        'STATUS'=>0
                    );
                    if($row->id == null)//没有报警记录，插入报警记录
                    {
                        Db::name('alarm')->insert($d);
                        continue;
                    }
                    elseif($row->STATUS == 1)
                    {//无报警，更新为有报警
                        Db::name('alarm')->where('id',$row->ID)->update($d);
                        continue;
                    }
                    //如果有高报警，更新报警时间
                        if($row->ALARM_TYPE == 1 && $row->ID > 0)
                        {
                            if($row->END_TIME >= $row->INSERT_TIME){
                                continue;
                            }
                            $d=array('END_TIME'=>$row->INSERT_TIME);
                            Db::name('alarm')->where('id',$row->ID)->update($d);
                            continue;
                        }
                    //如果有低报警，结束低报警，添加高报警
                        if($row->ALARM_TYPE == 2){
                            //结束低报警
                            Db::name('alarm')->where('id',$row->ID)
                                ->update(array('STATUS'=>1,'END_TIME'=>$row->INSERT_TIME));
                            //添加高报警
                            $d=array(
                                'START_TIME'=>$row->INSERT_TIME,
                                'ALARM_TYPE'=>1,
                                'END_TIME'=>$row->INSERT_TIME,
                                'STATUS'=>0
                            );
                            Db::name('alarm')->where('id',$row->ID)->update($d);
                        }
                }
                elseif($row->$dataColumn/$ratio < $setTemp - $row->$setHigh  && $row->$dataColumn > -999)//低报警
                {
                    $d=array(
                        'BOX_ID'=>$row->BOX_ID,
                        'DEVICE_CODE'=>$deviceCode,
                        'ALARM_TYPE'=>2,
                        'START_TIME'=>$row->INSERT_TIME,
                        'END_TIME'=>$row->INSERT_TIME,
                        'STRIP_TIME'=>$row->STRIP_TIME,
                        'ALARM_DATA'=>$row->$dataColumn/$ratio,
                        'STATUS'=>0
                    );
                    //如果无报警，添加报警
                        if($row->id == null){
                            Db::name('alarm')->insert($d);
                            continue;
                        }
                        elseif($row->STATUS == 1)//无报警，更新为有报警
                        {
                            Db::name('alarm')->where('id',$row->ID)->update($d);
                            continue;
                        }
                    //如果有低报警，更新报警时间
                        if($row->ALARM_TYPE == 2 && $row->ID > 0){
                            if($row->END_TIME >= $row->INSERT_TIME){
                                continue;
                            }
                            $d=array('END_TIME'=>$row->INSERT_TIME);
                            Db::name('alarm')->where('id',$row->ID)->update($d);
                            continue;
                        }
                    //如果有高报警，结束高报警，添加低报警
                        if($row->ALARM_TYPE == 1){
                            //结束低报警
                            Db::name('alarm')->where('id',$row->ID)
                                ->update(array('STATUS'=>1,'END_TIME'=>$row->INSERT_TIME));
                            //添加高报警
                            $d=array(
                                'START_TIME'=>$row->INSERT_TIME,
                                'ALARM_TYPE'=>2,
                                'END_TIME'=>$row->INSERT_TIME,
                                'STATUS'=>0
                            );
                            Db::name('alarm')->where('id',$row->ID)->update($d);
                        }
                }else{//无报警
                    //如果当前有报警，结束报警
                    if(!empty($row->ID) && $row->STATUS == 0){//如果当前有报警，结束报警
                        Db::name('alarm')->where('id',$row->ID)
                            ->update(array('STATUS'=>1,'END_TIME'=>$row->INSERT_TIME));
                    }
                }
            }elseif($row->$alarmOn == 0){//未启用报警,
                if(!empty($row->ID) && $row->STATUS == 0){//如果当前有报警，结束报警
                    Db::name('alarm')->where('id',$row->ID)
                        ->update(array('STATUS'=>1,'END_TIME'=>$row->INSERT_TIME));
                }
            }
        }
        return $i;
    }
    //判断冷机报警,$boxCat 冷机类型，默认01
    function dealAlarmRefrigeration($boxCat="01"){
        $i=0;
        $deviceCode = 'Refrigeration';
        $dataColumn = 'ZONE_ALARM_CODE';
        //查询有设置的所有数据
        $select = 'S.ALARM_PHONE,S.STRIP_TIME,
            D.INSERT_TIME,A.ID,A.DEVICE_CODE,D.ZONE_ALARM_CODE,A.ALARM_TYPE,A.START_TIME,A.END_TIME,A.ALARM_DATA,A.STATUS';
        $map = array();
        $map['C.CATEGORY'] = ['IN',$boxCat];
        $alarmData = Db('data_latest')
            ->alias('D')
            ->join('alarm_setting S','D.BOX_ID = S.BOX_ID','left')
            ->join('container C','D.BOX_ID = C.BOX_ID','left')
            ->join('alarm A','A.BOX_ID = S.BOX_ID AND (A.DEVICE_CODE = \''.$deviceCode.'\' OR A.DEVICE_CODE IS NULL)','left')
            ->field($select)
            ->where($map)
            ->select();

        if(empty($alarmData)){
            return false;
        }
        foreach ($alarmData as $row){
            $row = (object)$row;
            //如果没有查到最新数据，跳过
            if(empty($row->INSERT_TIME)) continue;

            //如果报警时间大于数据时间跳过
            if(!empty($row->END_TIME) && $row->END_TIME > $row->INSERT_TIME) continue;

            if(!empty($row->STATUS) && $row->STATUS > 0)//如果数据当前有报警
            {
                $d=array(
                    'BOX_ID'=>$row->BOX_ID,
                    'DEVICE_CODE'=>$deviceCode,
                    'ALARM_TYPE'=>1,
                    'START_TIME'=>$row->INSERT_TIME,
                    'END_TIME'=>$row->INSERT_TIME,
                    'STRIP_TIME'=>empty($row->STRIP_TIME) ? '180' : $row->STRIP_TIME,
                    'ALARM_DATA'=>$row->$dataColumn,
                    'STATUS'=>0
                );
                if(empty($row->ID))//如果报警表没有记录则添加
                {
                    Db::name('alarm')->insert($d);
                }
                else//如果报警表有记录
                {
                    if(intval($row->STATUS) == 0)//报警表当前为报警状态，更新end_time
                    {
                        unset($d['START_TIME']);
                        Db::name('alarm')->insert($d);
                    }
                    else//报警表当前为非报警状态，更新为报警状态
                    {
                        $d['START_TIME'] = $row->INSERT_TIME;
                        Db::name('alarm')->where('id',$row->ID)->update($d);
                    }
                }

            }
            else //如果数据当前没有报警
            {
                //如果报警表没有记录，略过

                //如果报警表为报警状态，结束报警
                if($row->STATUS == 0){
                    $d=array(
                        'STATUS'=>0,
                        'END_TIME'=>$row->INSERT_TIME
                    );
                    Db::name('alarm')->where('id',$row->ID)->update($d);
                }
                //如果报警表为非报警状态，略过
            }
            $i++;
        }
        return $i;
    }
    function dealAlarm_low_voltage($deviceCode,$dataColumn){//deviceCode: t1,t2,h1
        $i=0;
        $ratio = 10;//数据倍率
        //查询有设置的所有数据
        $deviceCode = strtoupper($deviceCode);
        $dataColumn = strtoupper($dataColumn);
        //查询有设置的所有数据
        $select = 'S.ALARM_PHONE,S.STRIP_TIME,D.BOX_ID,
                                D.GPS_TEMP1,D.GPS_TEMP2,D.GPS_TEMP3,D.GPS_HUMI,D.GPS_OIL_LEVEL,D.GPS_VOLTAGE,D.COOLER_SET_TEMP,D.INSERT_TIME,
                                A.ID,A.DEVICE_CODE,A.ALARM_TYPE,A.START_TIME,A.END_TIME,A.ALARM_DATA,A.STATUS
                                ';
        $map = array();
        $map['D.ID'] = ['>','0'];
        $alarmData = Db('alarm_setting')
            ->alias('S')
            ->join('data_latest D','D.BOX_ID = S.BOX_ID','left')
            ->join('alarm A','A.BOX_ID = S.BOX_ID AND (A.DEVICE_CODE = \''.$deviceCode.'\' OR A.DEVICE_CODE IS NULL)','left')
            ->field($select)
            ->where($map)
            ->select();
        if(empty($alarmData)){
            return false;
        }

        $alarmOn = $deviceCode.'_ON';
        $setHigh = $deviceCode.'_HIGH';
        $setLow = $deviceCode.'_LOW';

        foreach ($alarmData as $row) {
            $row = (object)$row;
            //echo '<br/><br/><br/>';
            //如果没有数据，循环下一个
            if($row->INSERT_TIME == null){
                continue;
            }
            $row->$alarmOn > 0 && ++$i;

            if($dataColumn=='GPS_VOLTAGE'){//判断电压电压
                if($row->$dataColumn/$ratio < 11){//如果小于11v
                    empty($row->STRIP_TIME) && $row->STRIP_TIME = 1800;
                    $d=array(
                        'BOX_ID'=>$row->BOX_ID,
                        'DEVICE_CODE'=>$deviceCode,
                        'ALARM_TYPE'=>2,
                        'START_TIME'=>$row->INSERT_TIME,
                        'END_TIME'=>$row->INSERT_TIME,
                        'STRIP_TIME'=>$row->STRIP_TIME,
                        'ALARM_DATA'=>$row->$dataColumn/$ratio,
                        'STATUS'=>0
                    );
                    //如果无报警，添加报警
                        if($row->ID == null){
                            Db::name('alarm')->insert($d);
                            continue;
                        }elseif($row->STATUS == 1){//无报警，更新为有报警
                            Db::name('alarm')->where('id',$row->ID)->update($d);
                            continue;
                        }
                    //如果有低报警，更新报警时间
                        if($row->ALARM_TYPE == 2 && $row->ID > 0){
                            if($row->END_TIME >= $row->INSERT_TIME){
                                continue;
                            }
                            $d=array('END_TIME'=>$row->INSERT_TIME);
                            Db::name('alarm')->where('id',$row->ID)->update($d);
                            continue;
                        }
                }
            }
        }
        return $i;
    }
    function geoMessage(){
        //获取所有待分析设备点

        //循环计算
    }
}