<?php
namespace app\index\model;
use think\Model;
use think\Db;
class DeviceModel extends Model
{

    public function getBoxLatest($params)
    {
        $where = ' 1=1 ';
        if(!empty($params['groupID'])){
            $where .= " and C.GROUP_ID = ".$params['boxGroupID'];
        }
        if(!empty($params['keyword'])){
            $where .= " and (C.NAME like '%".$params['keyword']."%')";
        }
        empty($params['box_category']) && $params['box_category'] = '01';
        /*//角色
        if($params['ROLE_ID'] == '500'){

        }else{
            if(!empty($params['ROLE_ID'])){
                $group_id = Db::name('rbac_role_group_relation')->field('GROUP_ID')->where('ROLE_ID',$params['ROLE_ID'])->find();
                $group_id = explode(',',$group_id['GROUP_ID']);
                $box_id = Db::name('container')->field('BOX_ID')->where('GROUP_ID','in',$group_id)->select();
                $box_id = array_column($box_id,'BOX_ID');
                $box_id = implode(",", $box_id);
                if(!empty($box_id)){
                    $where .= " and (C.BOX_ID in ($box_id))";
                    $where .= " and C.CATEGORY = ".$params['box_category'];
                }else{
                    //给个不存在的条件让设备不显示
                    $params['box_category'] = 000;
                    $where .= " and C.CATEGORY = ".$params['box_category'];
                }
            }else{
                $box_id = '0000';
                $where .= " and (C.BOX_ID in ($box_id))";
            }
        }*/
 		$User = new UserModel();
        $channel = 'web';
        $User_info = $User->checkToken($channel);
        //查看该用户可以管理那些种类的设备
        $role_id = $User_info['ROLE_ID'];//获取用户的角色id
        if(!empty($role_id)){
            $role_id = explode(',',$role_id);
            $category = Db::name('rbac_role')->field('ROLE_DEVICE_CATEGORY')->where('ID','IN',$role_id)->select();
            $data = array_column($category,"ROLE_DEVICE_CATEGORY");
            $mod = array();
            foreach($data as $key=>$value){
                if(strpos($value,',') != false){
                    $array = explode(',',$value);
                    $array = array_filter($array);

                    $mod = $array+$mod;
                }
            }
            if(!empty($mod)){
                $mod = implode(",",$mod);
            }else{
                $mod = '';
            }
            $where .= " and (C.CATEGORY in ($mod))";
        }else{
            $where .= " and (C.CATEGORY = 0)";
        }
        $where .= " and C.CATEGORY = ".$params['box_category'];
        //$param['C.CATEGORY'] = $params['box_category'];
        $order = 'D.INSERT_TIME desc';
        $result = Db('data_latest')
                ->alias('D')
                ->join('container C','D.BOX_ID = C.BOX_ID','right')
                ->join('group G','G.ID = C.GROUP_ID','left')
                ->field('D.ID,D.IS_VALID,G.NAME AS GROUP_NAME,G.ID AS GROUP_ID,C.CATEGORY,D.BOX_ID,D.LONGITUDE,D.SPEED,D.LATITUDE,D.GPS_HUMI,D.GPS_TEMP1,D.GPS_TEMP2,D.GPS_TEMP3,D.AMBIENT_TEMP,D.COOLER_SET_TEMP,D.RE_AIR_TEMP,D.OUT_AIR_TEMP,D.ZONE_STATUS,D.RESERVE2,D.GPS_DOOR1,D.GPS_DOOR2,D.COOLER_OFF_FLAG,D.ZONE_ALARM_CODE,D.INSERT_TIME,D.GPS_TIME,D.GPS_VOLTAGE,D.GPS_OIL_LEVEL,D.RESERVE7,C.NAME')
                ->where($where)
                ->order($order)
                ->select();
        if(!empty($result)) {
            $now = time() - 3600;
            foreach ($result as $row) {
                # code...
                $row['online'] = $row['INSERT_TIME'] > $now ? 1 : 0;
                //报警数量，暂时用报警码来判断
                $row['alarm'] = $row['ZONE_ALARM_CODE'] == 0 ? 0 : 1;
            }
        }
        return $result;
    }
    //分组列表
    public function getGroupList($params){
        $where = '1=1 ';
        if(!empty($params['keyword'])){
            $where .= " and (G.NAME like '%".$params['keyword']."%')";
        }
//        $role_id = Db::name('rbac_user_role_relation')->field('ROLE_ID')->where('USER_ID',$params['ID'])->find();
//        if(!empty($role_id)){
//            $group_id = Db::name('rbac_role_group_relation')->field('GROUP_ID')->where('ROLE_ID',$role_id['ROLE_ID'])->find();
//            if($role_id["ROLE_ID"] == '500'){
//
//            }else{
//                if(!empty($group_id)){
//                    $group_id = explode(',',$group_id['GROUP_ID']);
//                    $group_id = implode(",", $group_id);
//                }else{
//                    //定义一个不存在的分组id
//                    $group_id = 0000;
//                }
//                $where .= " and (G.ID in ($group_id))";
//            }
//
//        }else{
//            $group_id = 0000;
//            $where .= " and (G.ID in ($group_id))";
//        }
        $result = Db::name('group')->alias('G')
            ->join('agent A','G.AGENT_ID = A.ID','left')
            ->join('client C','G.CLIENT_ID = C.ID','left')
            ->where($where)
            ->field('A.NAME as AGENT_NAME,C.NAME as CLIENT_NAME,G.ID AS GROUP_ID,G.NAME,G.AGENT_ID,G.CLIENT_ID,G.STATION_ID,G.BOX_NUM,G.CONTACT,G.REMARKS')
            ->select();
        return $result;
    }
    public function getBoxList($params)
    {
        $Model = new DeviceModel();
        $where = 'where 1=1 ';
        if (!empty($params['keyword'])) {
            $where .= " and (NAME like '%" . $params['keyword'] . "%' or BOX_ID like '%" . $params['keyword'] . "%')";
        }
        //该设备未在任何分组(定义超级管理员才可以看所有)
//        if($params['ROLE_ID'] == '500'){
//            if(!empty(input('post.groupID'))){
//                $group_id = input('post.groupID');
//                $box_id = Db::name('container')->field('ID')->where('GROUP_ID','=',$group_id)->select();
//                if(!empty($box_id)){
//                    $ID = array_column($box_id,'ID');
//                    $ID = implode(",",$ID);
//                }else{
//                    $ID = '0000';
//                }
//                $where .= " and (ID in ($ID))";
//            }else{
//                $box_id = Db::name('container')->field('ID')->select();
//                $ID = array_column($box_id,'ID');
//                $ID = implode(",", $ID);
//                $where .= " and (ID in ($ID))";
//            }
//        }else{
//            if(!empty(input('post.groupID'))){
//                $group_id = input('post.groupID');
//                $box_id = Db::name('container')->field('ID')->where('GROUP_ID','in',$group_id)->select();
//                $ID = array_column($box_id,'ID');
//                $ID = implode(",",$ID);
//                $where .= " and (ID in ($ID))";
//            }else{
//                $group_id = Db::name('rbac_role_group_relation')->field('GROUP_ID')->where('ROLE_ID', $params['ROLE_ID'])->find();
//                if(is_array($group_id)){
//                    $group_id = implode(",",$group_id);
//                }else{
//                    //定义一个不存在的group_id
//                    $group_id = "0000";
//                }
//                $box_id = Db::name('container')->field('ID')->where('GROUP_ID','in',$group_id)->select();
//                $ID = array_column($box_id,'ID');
//                $ID = implode(",",$ID);
//                $where .= " and (ID in ($ID))";
//            }
//        }
		 //选择好了某个分组
        //根据有没有boxGroupID做判定
        if($params['boxGroupID'] !== -1){
                $box_id = Db::name('container')->field('ID')->where('GROUP_ID','=',$params['boxGroupID'])->select();
                if(!empty($box_id)){
                    $ID = array_column($box_id,'ID');
                    $ID = implode(",",$ID);
                }else{
                    $ID = '0000';
                }
                $where .= " and (ID in ($ID))";
        }
        //筛选某种设备
        if(!empty($params['boxCategory'])){
        	$where .= " and (CATEGORY = ".$params['boxCategory'].")";
        }
        $User = new UserModel();
        $channel = 'web';
        $User_info = $User->checkToken($channel);
       	$role_id = $User_info['ROLE_ID'];//获取用户的角色id
        if(!empty($role_id)){
            $role_id = explode(',',$role_id);
            $category = Db::name('rbac_role')->field('ROLE_DEVICE_CATEGORY')->where('ID','IN',$role_id)->select();
            $data = array_column($category,"ROLE_DEVICE_CATEGORY");
            $mod = array();
            foreach($data as $key=>$value){
                if(strpos($value,',') != false){
                    $array = explode(',',$value);
                    $array = array_filter($array);

                    $mod = $array+$mod;
                }
            }
            if(!empty($mod)){
                $mod = implode(",",$mod);
            }else{
                $mod = '';
            }
            $where .= " and (CATEGORY in ($mod))";
        }else{
            $where .= " and (CATEGORY = 0)";
        }
        //监控管理员，按工位查询分组设备[查询该登录用户是否被分配工位]
        $station = Db::name('schedule')->where('USER_ID',$User_info['ID'])->select();
        if(!empty($station)){
            $data = array();
            foreach($station as $row){
                $start_time = $row['START_TIME'];
                $end_time = $row['END_TIME'];
                $now_time = time();
                //判断当前时间该用户有没有排班情况
                if($now_time >= $start_time && $now_time <= $end_time){
                    $data['where'] =  "$now_time between ".$start_time." and ".$end_time."";
                }else{
                    $data['where'] = "$now_time = 000";
                }
            }
            $station_info = Db::name('schedule')->where($data['where'])->find();
            if(!empty($station_info)){
                $station_id = $station_info['STATION_ID'];
                $group_info = Db::name('group')->where('STATION_ID',$station_id)->select();
                //取出我所可以管理的分组id
                foreach($group_info as $row){
                    $group_id[] = $row['ID'];
                }
                $group_id = array_unique($group_id);
                $group_id = implode(",",$group_id);
                $where .= " and (ID in ($group_id))";
            }else{
                $ID = '000';
                $where .= " and (ID in ($ID))";
            }
        }
        
        $select = 'AGENT_ID,CLIENT_ID,BRANCH_ID,GROUP_ID,NAME,MODEL_NUMBER,CATEGORY,BOX_ID,BOX_CATEGORY,COOLER_CODE,COOLER_VERSION,BOX_UP_TIME,IBOX_VERSION,ADD_TIME,ALARM_PHONE,DEVICE_SETTING,ALARM_SETTING,OUT_DATE,BOX_PARAM';
        $containerTable = 'zj_container';
        $sql = "select $select from $containerTable $where ";
        //行程
        if(!empty($params['boxTravelID']) && $params['boxTravelID'] > 0){
            $sql .= " and  BOX_ID in(select BOX_ID from zj_travel_to_box where TRAVEL_ID = ".$params['boxTravelID'].")";
        }
        
        if(empty($params['perPage'])){//不分页
            $sql .= ' order by NAME';
            //$total =count($Model->query($sql));
            //return $this->db->query($sql)->result();
            $dataList = $Model->query($sql);
            return $dataList;
        }else{//分页
            (empty($params['page']) || $params['page'] < 1) && $params['page'] = 1;
            $total = count($Model->query($sql));
            $offset = ($params['page'] - 1) * $params['perPage'];
            $limit = $params['perPage'];
            $sql .= " limit $offset,$limit";
            $dataList = $Model->query($sql);
            return array('total'=>$total,'dataList'=>$dataList);
        }
    }
    //更新分组设备数量
    public function updateGroupDeviceNum($groupID){
        $groupTable = 'zj_group';
        $boxTable = 'zj_container';
        $Model = new DeviceModel();
        $sql = "update $groupTable set BOX_NUM = (select count(1) from $boxTable where GROUP_ID = $groupID) where ID = $groupID";
        $Model->execute($sql);
    }
    //删除分组
    public function groupDelete($groupID,$uid){
        Db::name('container')->where('GROUP_ID',$groupID)->update(array('GROUP_ID'=>0));
       $result =  Db::name('group')->where('ID',$groupID)->delete();
       if(!$result){
           return 0;
       }else{
           //存入日志
            saveToLog(array('op'=>'group_delete','objName'=>$groupID,'objID'=>-1,'UID'=>$uid));
            return 1;
       }

    }
    //设备历史数据
    public function getBoxData($box_id,$startTime,$endTime,$select='',$forceValid=0,$order_by='INSERT_TIME desc'){
        empty($select) && $select ="*";
        //未指定起止时间，返回最新100条数据
        if($startTime <=0 && $endTime <=0){
            if(!empty($forceValid)){
                $where = "'IS_VALID',1";
            }else{
                $where = '1=1 ';
            }
            $result = Db::name('data')
                ->field($select)
                ->where('BOX_ID',$box_id)
                ->where($where)
                ->limit(100)
                ->order($order_by)
                ->select();
            return $result;
        }else{
            if(!empty($forceValid)){
                $where = "'IS_VALID',1";
            }else{
                $where = '1=1 ';
            }
            $result = Db::name('data')
                ->field($select)
                ->where('BOX_ID',$box_id)
                ->where("INSERT_TIME between $startTime and $endTime")
                ->where($where)
                ->order($order_by)
                ->select();
            return $result;
        }
    }
    public function getBoxLatestInfo($box_id)
    {
        $result = Db::name('data_latest')
            ->alias('D')
            ->join('container C','D.BOX_ID = C.BOX_ID','left')
            ->field('D.LONGITUDE,D.LATITUDE,D.BOX_ID,D.GPS_HUMI,D.GPS_TEMP1,D.GPS_TEMP2,D.GPS_TEMP3,D.GPS_OIL_LEVEL,D.RESERVE5 AS GPS_CO2,D.SPEED,D.RESERVE6 AS DIRECTION,D.GPS_DOOR1,D.GPS_VOLTAGE,D.COOLER_OFF_FLAG,D.COOLER_OIL_LEVEL,D.COOLER_VOLTAGE,D.COOLER_RPM,D.AMBIENT_TEMP,D.RE_AIR_TEMP,D.OUT_AIR_TEMP,D.COOLER_SET_TEMP,D.OIL_TEMP,D.ZONE_ALARM_CODE,D.ZONE_STATUS,D.INSERT_TIME,D.GPS_TIME,D.RESERVE7,D.RESERVE8,D.ENGINE_HOUR,D.WORK_HOUR,D.POWER_ON_HOUR,C.NAME,D.COOLER_ALARM_CNT,D.COOLER_ALARM_VALUE1,D.COOLER_ALARM_VALUE2,D.COOLER_ALARM_VALUE3,D.COOLER_ALARM_VALUE4,D.COOLER_ALARM_VALUE5,D.COOLER_ALARM_VALUE6,D.COOLER_ALARM_VALUE7,D.COOLER_ALARM_VALUE8,D.COOLER_ALARM_VALUE9,D.COOLER_ALARM_VALUE10,D.COOLER_ALARM_VALUE11,D.COOLER_ALARM_VALUE12,D.COOLER_ALARM_VALUE13')
            ->where('D.BOX_ID',$box_id)
            ->find();
        return $result;
    }
    public function getBoxData_page($box_id,$startTime,$endTime,$page=1,$perPage=10,$select,$forceValid=0,$order_by='INSERT_TIME desc'){
        $page = $page > 1 ? $page : 1;
        $offset = empty($perPage) ? 10 : $perPage;
        $perPage = ($page - 1) * $offset + 1;
        if($startTime <=0 && $endTime <=0){
            if(!empty($forceValid)){
                $where = "'IS_VALID',1";
            }else{
                $where = '1=1 ';
            }
            $dataList = Db::name('data')->field($select)->where('BOX_ID',$box_id)->where($where)->order($order_by)->limit($perPage,$offset)->select();
            $total = Db::name('data')->where('BOX_ID',$box_id)->where($where)->select();
            return(array('total'=>$total,'dataList'=>$dataList));
        }else{
            if(!empty($forceValid)){
                $where = "'IS_VALID',1";
            }else{
                $where = '1=1 ';
            }
            $dataList = Db::name('data')->field($select)->where('BOX_ID',$box_id)->where("INSERT_TIME between $startTime and $endTime")->where($where)->order($order_by)->limit($perPage,$offset)->select();
            $total = Db::name('data')->where('BOX_ID',$box_id)->where("INSERT_TIME between $startTime and $endTime")->where($where)->select();
            $dataList = arraykeyToLower($dataList);
            $total = count($total);
            return(array('total'=>$total,'dataList'=>$dataList));
        }

    }
}