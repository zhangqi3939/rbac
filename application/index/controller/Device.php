<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use app\index\model\UserModel;
use app\index\model\DeviceModel;
class Device extends Controller
{
    public function group_listing()
    {

        $User =  new UserModel();
        $Device =  new DeviceModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $user = $User->userList(array('ID'=>$user_info['ID']));
//        var_dump($user);
        // $user = $this->api->checkUser();
        $rights = checkRights('');
//        $user = input('post.keyword');
//        var_dump($user);
        app_send(arraykeyToLower($Device->getGroupList($user)));
//        $user->keyword =$this->input->post('keyword');
//        app_send($this->api->getGroupList($user));
    }
    //首页->状态信息
    public function box_info()
    {
        $User =  new UserModel();
        $Device =  new DeviceModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $box_id = input('post.box_id');
        if(empty($box_id)){
            app_send('',400,'编号错误');
            exit();
        }
        $user_info['box_id'] = $box_id;
        $user['dataID'] = 0;
        $row = $Device->getBoxLatestInfo($box_id);
        if(empty($row)){
            app_send('',400);
            exit();
        }
        app_send(arraykeyToLower($row));
    }
    //首页->机组控制
    public function box_op_state()
    {
        $User =  new UserModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $box_id = input('post.box_id');
        $result = Db::name('cmd')->where('BOX_ID',$box_id)->select();
        if(!empty($result)){
            foreach ($result as $row) {
                $row['opCode'] = $row['PID'].$row['ADDR'];
                $row['opValue'] = $row['VALUE'];
                unset($row['PID']);
                unset($row['ADDR']);
                unset($row['VALUE']);
                unset($row['PROJECT_ID']);
                unset($row['CMDTIME']);
            }
        }else{
            $result = array();
        }
        app_send(arraykeyToLower($result));
    }
    //首页->机组控制(操作)
    public function box_op()
    {
        $User =  new UserModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        if($user_info['IS_ADMIN'] < 1){
            app_send('','400','您不是管理员');
            exit();
        }
        $box_id = input('post.box_id');
        $value  = input('post.opValue');
        $opCode = input('post.opCode');
        if(strlen($opCode) != 4){
            app_send('','400','操作代码错误');
            //send_json(array('status'=>0,'msg'=>'操作代码错误'.$opCode));
            exit();
        }
        $pid=substr($opCode,0,3);
        $addr=substr($opCode,3,1);
        if($pid!='205' && $pid !='206'){
            app_send('','400','操作代码错误');
            //send_json(array('status'=>0,'msg'=>'操作代码错误'.$opCode));
            exit();
        }
        //如果是开机，未完成的开机命令要大于3分钟
        if($pid=='205' && $addr=='0'&& $value==1){
            $whereArray=array(
                'BOX_ID' => $box_id,
                'PID'    => $pid,
                'ADDR'   => $addr,
                'VALUE'  => $value,
                'FLAG_CHANGE'=>'2'
            );
            $cmdObj = Db::name('cmd')->where($whereArray)->find();
            if(!empty($cmdObj)){
                $t=time()-180;
                if($cmdObj['INSERT_TIME'] > $t){
                    app_send(array(
                        'data'=>'',
                        'code'=>'400',
                        'reason'=>'3分钟内还有未完成的开机操作，请稍候！'
                    ));
                    exit();
                }
            }
        }

        $whereArray=array(
            'BOX_ID' => $box_id,
            'PID'    => $pid,
            'ADDR'   => $addr
        );
        $d = array(
            'BOX_ID'  => $box_id,
            'PID'     => $pid,
            'ADDR'    => $addr,
            'VALUE'   => $value,
            'USER_ID' => $user_info['ID'],
            'FLAG_CHANGE' => 1,
            'INSERT_TIME' => time()
        );
        //如果是设置温度，转换到华氏度 放到十倍
        if($opCode == '2060'){
            $d['VALUE'] = C2F($d['VALUE']) * 10 ;
        }
        updateOrInsert('cmd',$whereArray,$d);
        app_send();
    }
    //首页->参数设置
    public function box_param()
    {
        $User =  new UserModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $box_id = input('post.box_id');
        $mc8k = Db::connect('DB_Config_mc800');
        $select = 'field69634,field81968,field81969,field126980,field73787,field69634,field15';
       // $currentconfig = $mc8k->select($select,false)->from('currentconfig')->where('field73729',$box_id)->get()->row();
        $currentconfig = $mc8k->name('currentconfig')->field($select)->where('field73729',$box_id)->select();
        $newconfig = $mc8k->name('newconfig')->field($select)->where('field73729',$box_id)->select();
        //$newconfig = $mc8k->select($select,false)->from('newconfig')->where('field73729',$box_id)->get()->row();
        $setting = array(
            'current_config'=>$currentconfig,
            'new_config'=>$newconfig
        );
        app_send(arraykeyToLower($setting));
    }
    //首页->参数设置(保存)
    public function box_param_save()
    {
        $User =  new UserModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        if($user_info['IS_ADMIN'] < 1){
            app_send('','400','您不是管理员');
            exit();
        }
        $params = array('field69634','field81968','field81969','field126980','field73787','field69634','field15');
        $post_data = input('post.');
        $post_data['field69634'] = intval($post_data['field69634']);
        $box_id = intval(input('post.box_id'));
        if(empty($box_id) || empty($post_data['field69634'])){
            app_send('',400,'输入有误');
        }
        if($post_data['field69634'] < 300){
            app_send('',400,'最小间隔不得小于5分钟。');
        }

        if(!empty($post_data)){
            foreach ($post_data as $k => $v) {
                if(!in_array($k, $params)){
                    unset($post_data[$k]);
                }
            }
        }
        //更新到newconfig
        $mc8k = Db::connect('DB_Config_mc800');
        $post_data['field131071'] = 0;
//        $mc8k->where('field73729',$box_id)->update('newconfig',$post_data);
        $mc8k->table('newconfig')->where('field73729',$box_id)->update($post_data);
        //重启
        $deviceid = short2mem(1).long2mem($box_id);
        $mc8k->table('commanddata')->insert(array(
                'commandcode'=>3847,
                'deviceid'=>$deviceid,
                'request'=>'reboot',
                'commandstatus'=>0,
                'clientip'=>'127.0.0.1',
                'createtime'=>'2012-07-19 09:23:00'
            )
        );
        app_send();
    }
    //首页->分组设置（保存）
    public function box_group_save()
    {
        $User =  new UserModel();
        $Device =  new DeviceModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $box_id = intval(input('post.box_id'));
        $groupID = intval(input('post.groupID'));
        Db::name('container')->where('BOX_ID',$box_id)->update(array('GROUP_ID'=>$groupID));
        $boxIDs = input('post.boxIDs[]');
        if(!empty($boxIDs)){
            foreach ($boxIDs as $boxID) {
                Db::name('container')->where('BOX_ID',$boxID)->update(array('GROUP_ID'=>$groupID));
            }
        }
        $Device->updateGroupDeviceNum($groupID);
        app_send();
    }
    //首页->数据查询
    public function box_data_page()
    {
        $User =  new UserModel();
        $Device =  new DeviceModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $box_id = input('post.box_id');
        $start_time = input('post.startTime');
        $end_time = input('post.endTime');
        $page = input('post.page');
        $perPage = input('post.perPage');
        //未传递起止时间，返回最近三天
        if(empty($start_time) && empty($end_time)){
            $startStamp = time() - 72*3600;
            $endStamp = time();
        }else{
            $startStamp = strtotime($start_time);
            $endStamp = empty($end_time) ? time() : strtotime($end_time);
        }
        $select = 'BOX_ID,LONGITUDE,LATITUDE,GPS_HUMI,GPS_TEMP1,GPS_TEMP2,GPS_TEMP3,GPS_OIL_LEVEL,RESERVE5 AS GPS_CO2,SPEED,RESERVE6 AS DIRECTION,GPS_DOOR1,GPS_VOLTAGE,COOLER_OFF_FLAG,COOLER_OIL_LEVEL,COOLER_VOLTAGE,COOLER_RPM,AMBIENT_TEMP,RE_AIR_TEMP,OUT_AIR_TEMP,COOLER_SET_TEMP,OIL_TEMP,ZONE_ALARM_CODE,ZONE_STATUS,INSERT_TIME,GPS_TIME,RESERVE3,RESERVE7';
        app_send($Device->getBoxData_page($box_id,$startStamp,$endStamp,$page,$perPage,$select));
    }
    public function box_latest()
    {
        $User =  new UserModel();
        $channel = 'web';
        $User_info = $User->checkToken($channel);
        $User_info['keyword'] = input('post.keyword');
        $groupID = input('post.groupID');
        $User_info['groupID'] = empty($groupID)?0:intval($groupID);
        $travelID = input('post.travelID');
        $User_info['travelID'] = empty($travelID)?0:intval($travelID);
        $User_info['box_category'] = input('post.box_category');
        //字段检查
        if(!is_numeric($User_info['travelID']) || !is_numeric($User_info['groupID'])){
            app_send('',400,'输入的信息有误！');
            exit();
        }
        $regex = "/\/|\～|\，|\。|\！|\？|\"|\"|\【|\】|\『|\』|\:|\;|\<|\>|\'|\·|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\|/";
        if(preg_match($regex,$User_info['keyword'])){
            app_send('',400,'输入的信息有误！');
            exit();
        }
        //end of 字段检查
        $Device = new DeviceModel();
        $boxes = $Device->getBoxLatest($User_info);
        app_send(arraykeyToLower($boxes));

    }
    //设备->分组列表
    public function group_list(){
        $User =  new UserModel();
        $Device =  new DeviceModel();
        $channel = 'web';
        $User_info = $User->checkToken($channel);
        checkRights(0);
        $User_info['keyword'] = input('post.keyword');
        app_send(arraykeyToLower($Device->getGroupList($User_info)));
    }
    //设备->设备列表
    public function box_list()
    {
        $User =  new UserModel();
        $Device =  new DeviceModel();
        $channel = 'web';
        $User_info = $User->checkToken($channel);
        $User_info['box_id'] = input('post.box_id');
        $User_info['keyword'] = input('post.keyword');
        $User_info['boxGroupID'] = input('post.group_id');
        $User_info['boxAgentID'] = input('post.agentID');
        $User_info['boxClientID'] = input('post.clientID');
        $User_info['boxBranchID'] = input('post.branchID');
        $User_info['boxTravelID'] = input('post.travelID');
        $boxCategory = input('post.box_category');
        //字段过滤
        $User_info['boxTravelID']= empty($User_info['boxTravelID']) ? -1 : intval( $User_info['boxTravelID']);
        $User_info['boxGroupID']= empty($User_info['boxGroupID']) ? -1 : intval( $User_info['boxGroupID']);
        $User_info['boxCategory']= empty($boxCategory) ? "" : $boxCategory;
        !empty($User_info['keyword']) &&  $User_info['keyword'] = trim( $User_info['keyword']);
        if(!is_numeric( $User_info['boxTravelID']) || !is_numeric($User_info['boxGroupID'])){
            app_send('',400,'输入的信息有误！');
            exit();
        }
        $regex = "/\/|\～|\，|\。|\！|\？|\"|\"|\【|\】|\『|\』|\:|\;|\<|\>|\'|\·|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\:|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\-|\=|\\\|\|/";
        if(preg_match($regex,$User_info['keyword'])) {
            app_send('', 400, '输入的信息有误！');
            exit();
        }
        app_send(arraykeyToLower($Device->getBoxList($User_info)));
    }
    //设备分组 ->添加
    public function group_save()
    {
        $User =  new UserModel();
        $channel = 'web';
        $User_info = $User->checkToken($channel);
        $power = checkRights(0,1,-1,1);
        $d = array(
            'NAME'=>input('post.name'),
            'REMARKS'=>input('post.remarks'),
            'AGENT_ID'=>$User_info['AGENT_ID'],
            'CLIENT_ID'=>$User_info['CLIENT_ID']
        );
        if(empty($d['NAME'])){
            app_send('','400','分组名不能为空');
            exit();
        }
        $id = input('post.groupID');
        if(empty($id)){
            Db::name('group')->insert($d);
        }else{
            Db::name('group')->where('ID',$id)->update($d);
        }
        app_send();
    }

    //设备->获取分组设备
    public function  group_box()
    {
        $user = $this->api->checkUser();
        $groupID =$this->input->post('groupID');
//        $result = $this->db->select('c.id,c.name,c.box_id,g.name as groupName',false)
//            ->from('container c')
//            ->join('group g','c.groupID = g.id','left')
//            ->where('c.groupID',$groupID)
//            ->order_by('c.name')
//            ->get()->result();
//        app_send($result);
    }
    //设备->设备分组->清空分组列表
    public function group_box_clear()
    {
        $User =  new UserModel();
        $Device =  new DeviceModel();
        $channel = 'web';
        $User_info = $User->checkToken($channel);
        $groupID = input('post.group_id');
        //用户操作对象权限
        $User->checkRange($groupID,'group');
        Db::name('container')->where('GROUP_ID' , $groupID)->update(array('GROUP_ID'=>-1));
        //更新分组设备数量
        $Device->updateGroupDeviceNum($groupID);
        app_send();
    }
    //设备->设备分组->删除
    public function group_delete()
    {
        $User =  new UserModel();
        $Device =  new DeviceModel();
        $channel = 'web';
        $User_info = $User->checkToken($channel);
        $uid = $User_info['ID'];
        checkRights(0,1,-1,1);
        $groupID = input('post.groupID');
        $User->checkRange($groupID,'group');
        //检查分组是否有设备
        $conNum = Db::name('container')->where('GROUP_ID',$groupID)->select();
        $count = count($conNum);
        if($count > 0){
            app_send('',400,'分组内有设备，不能删除');
            exit();
         }
        $result = $Device->groupDelete($groupID,$uid);
        if($result){
            app_send();
        }else{
            app_send('',400,'删除失败,请重试');
        }
    }
    //添加设备到分组
    public function group_box_save(){
        $data = input('post.');
        $User =  new UserModel();
        $Device =  new DeviceModel();
        $channel = 'web';
        $User_info = $User->checkToken($channel);
        $User_info['groupID'] = input('post.group_id');
        if(!empty($data['groupBoxIDs'])){
            $User_info['groupBoxIDs'] = input('post.')['groupBoxIDs'];
            $group_id = implode(",",$User_info['groupBoxIDs']);            
        }else{
            $User_info['groupBoxIDs'] = array();
            $group_id = implode(",",$User_info['groupBoxIDs']);
        }        
        $User_info['exclusive'] = input('post.exclusive');
        //用户操作对象权限
        $h = $User->checkRange($User_info['groupID'],'group');
        $result = saveBoxToGroup($User_info['groupID'],$group_id,$User_info['exclusive']);
        if($result){//成功
            //更新分组设备数量
            updateGroupDeviceNum($User_info['groupID']);
            app_send();
        }else{
            app_send('',400,'保存失败，请重试！');
        }
    }
    //设备->设备列表->修改信息
    public function box_save()
    {
        $User =  new UserModel();
        $Device =  new DeviceModel();
        $channel = 'web';
        $User_info = $User->checkToken($channel);
        $box_id = input('post.box_id');
        if(!is_numeric($box_id)){
            app_send('','400','设备id只能为数字');
            exit();
        }
        $d = array(
            'NAME'=>input('post.name'),
            'MODEL_NUMBER'=>input('post.model_number'),
            'CATEGORY'=>input('post.category'),
            'COOLER_CODE'=>input('post.cooler_code')
        );
        $obj = Db::name('container')->where('BOX_ID',$box_id)->select();
        if(!empty($obj)){
            Db::name('container')->where('BOX_ID',$box_id)->update($d);
        }else{
            $d['BOX_ID'] = $box_id;
            Db::name('container')->insert($d);
        }
        app_send();
    }
    //设备历史数据
    public function box_data(){
        $User =  new UserModel();
        $Device =  new DeviceModel();
        $channel = 'web';
        $User_info = $User->checkToken($channel);
        $box_id = input('post.box_id');
        $start_time = input('post.startTime');
        $end_time = input('post.endTime');

        //未传递起止时间，返回最近100条数据
        if(empty($start_time) && empty($end_time)){
            $startStamp = -1;
            $endStamp = -1;
        }else{
            $startStamp = strtotime($start_time);
            $endStamp = empty($end_time) ? time() : strtotime($end_time);
        }
        $select = 'BOX_ID,LONGITUDE,LATITUDE,GPS_HUMI,GPS_TEMP1,GPS_TEMP2,GPS_TEMP3,GPS_OIL_LEVEL,RESERVE5 AS GPS_CO2,SPEED,RESERVE6 AS DIRECTION,GPS_DOOR1,GPS_VOLTAGE,COOLER_OFF_FLAG,COOLER_OIL_LEVEL,COOLER_VOLTAGE,COOLER_RPM,AMBIENT_TEMP,RE_AIR_TEMP,OUT_AIR_TEMP,COOLER_SET_TEMP,OIL_TEMP,ZONE_ALARM_CODE,ZONE_STATUS,INSERT_TIME,GPS_TIME,RESERVE3,RESERVE7,COOLER_ALARM_CNT,COOLER_ALARM_VALUE1,COOLER_ALARM_VALUE2,COOLER_ALARM_VALUE3,COOLER_ALARM_VALUE4,COOLER_ALARM_VALUE5,COOLER_ALARM_VALUE6,COOLER_ALARM_VALUE7,COOLER_ALARM_VALUE8,COOLER_ALARM_VALUE9,COOLER_ALARM_VALUE10,COOLER_ALARM_VALUE11,COOLER_ALARM_VALUE12,COOLER_ALARM_VALUE13';
        app_send(arraykeyToLower($Device->getBoxData($box_id,$startStamp,$endStamp,$select)));
    }
    //设备类型
    public function device_category()
    {
        app_send(array(
            '01'=>'冷藏箱',
            '02'=>'保温箱',
            '03'=>'保温车'
        ));
    }
    /*设备数据，地图页面用
     *
     * */
    function map_data_track(){
        $boxID = intval($this->input->post('boxID'));
        if(empty($boxID)){
            app_send_400('设备选择错误');
            exit();
        }
        $startTime = $this->input->post('startTime');
        $endTime = $this->input->post('endTime');
        $startStamp = strtotime($startTime);
        $endStamp = strtotime($endTime);

        if(empty($startStamp) || empty($endStamp)){
            $startStamp = $endStamp = 0;
        }
        $select = 'box_id,longitude,latitude,speed,cooler_off_flag,ambient_temp,re_air_temp,out_air_temp,oil_temp,cooler_set_temp,zone_alarm_code,cooler_alarm_cnt
            ,gps_voltage,gps_oil_level,gps_humi,gps_temp1,gps_temp2,gps_temp3,gps_door1,gps_time,insert_time,is_valid';
        $dm =  new DeviceModel();
        $res = $dm->getBoxData($boxID,$startStamp,$endStamp,$select,$forceValid=1);
        if($res === false){
            app_send_400();
            exit();
        }
        app_send($res);
    }
	public function insert_data($url)
    {
        //$url = 'http://tk2.qianbitou.com/s/device_data_by_id/1';
        $data = curl_get($url);
        
        if(empty($data)){
        	return false;
        }
        $data = json_decode($data);
        $data = $data->result;
        $id=0;
        foreach($data as $row){
        	$row = (array)$row;
        	$row['"current"'] = $row['current'];
        	unset($row['current']);
        	$row = arraykeyToUpper($row);
        	Db::name('data')->insert($row);
        	$id = $row['ID'];
        }
        
        if($id>0){
        	$this->SET_ID($id);
        }
        
    }
    public function SET_ID($id)
    {
    	$result = Db::name('test')->where('ID',1)->update(array("SEQUENCE"=>$id));
    	if($result > 0){
    		return "success";
    	}else{
    		return "error";
    	}
    
    }
    public function GET_ID()
    {
    	 $id = Db::name('test')->where('ID',1)->field('SEQUENCE')->find();
    	 return $id;
    }
    public function SET_URL($url)
    {
    	$url = preg_replace('/(.*)\/{1}([^\/]*)/i', '$1', $str);
    	$id = insert_data();
    }
    public function main_insert()
    {
    	$url = 'http://tk2.qianbitou.com/s/device_data_by_id/';
        $id  = $this->GET_ID()['SEQUENCE']; 
        $url = "$url"."$id";
        $this->insert_data($url);
    }
}