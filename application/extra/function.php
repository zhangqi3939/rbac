<?php
use think\Db;
use app\index\model\UserModel;
/*
 * 公共函数
 * phoebus
 * 2019.10.17
 * */

function curl_post($url,$pdata,$json=FALSE){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    if($json){

    }
    // post数据
    curl_setopt($ch, CURLOPT_POST, 1);
    // post的变量
    curl_setopt($ch, CURLOPT_POSTFIELDS, $pdata);
    $output = curl_exec($ch);
    //打印获得的数据
    curl_close($ch);
    return $output;
}

function curl_get($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);// 要求结果为字符串且输出到屏幕上
    curl_setopt($ch, CURLOPT_HEADER,0);//不要http header 加快效率
    curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    curl_setopt($ch, CURLOPT_TIMEOUT,20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

function send_json($data){
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function app_send($data='',$code='200',$reason=''){
    send_json(array(
        'code'=>$code,
        'reason'=>$reason,
        'result'=>$data
    ));
}

function app_send_400($reason=''){
    app_send('',400,$reason);
    exit();
}
//获取IP
function getIP(){
    if(!empty($_SERVER["HTTP_CLIENT_IP"])){
        $cip = $_SERVER["HTTP_CLIENT_IP"];
    }elseif(!empty($_SERVER["HTTP_X_FORWARDED_FOR"])){
        $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    }elseif(!empty($_SERVER["REMOTE_ADDR"])){
        $cip = $_SERVER["REMOTE_ADDR"];
    }else{
        $cip = "无法获取！";
    }
    return $cip;
}
/*
权限判断，判断三个权限得级别是否大于等于目标权限
如果$strict=1,则判断三个权限是否严格等于目标权限
*/
function checkRights($userLevel=-1,$isAdmin=-1,$conOption=-1,$strict = 0){
   /* $sessionLevel = $this->session->userLevel;
    $sessionAdmin = $this->session->isAdmin;
    $sessionOption = $this->session->conOption;
    $disAllow = 0;
    if($strict <= 0){//高权限允许
        $userLevel > -1 && $sessionLevel < $userLevel && $disAllow = 1;
        $isAdmin > -1 && $sessionAdmin < $isAdmin && $disAllow = 1;
    }else{//必须等于权限，例如客户的操作不让更高权限的代理商操作，
        $sessionLevel > -1 && $sessionLevel < $userLevel && $disAllow = 1;
        $sessionAdmin > -1 && $sessionAdmin < $isAdmin && $disAllow = 1;
    }
    if($disAllow){
        app_send('',400,'您不能执行此操作');
        exit();
    }*/
}
//报警部分
function alarmType($code,$cat){
    switch($code){
        case 't1':
            if($cat==1){
                return '温度1过高';
            }else if($cat == 2){
                return '温度1过低';
            }
            break;
        case 't2':
            if($cat==1){
                return '温度2过高';
            }else if($cat == 2){
                return '温度2过低';
            }
            break;
        case 't3':
            if($cat==1){
                return '温度3过高';
            }else if($cat == 2){
                return '温度3过低';
            }
            break;
        case 't4':
            if($cat==1){
                return '温度4过高';
            }else if($cat == 2){
                return '温度4过低';
            }
            break;
        case 'h1':
            if($cat==1){
                return '湿度过高';
            }else if($cat == 2){
                return '湿度过低';
            }
            break;
        case 'o1':
            if($cat==1){
                return '油位过高';
            }else if($cat == 2){
                return '油位过低';
            }
            break;
        case 'v1':
            if($cat==1){
                return '电压过高';
            }else if($cat == 2){
                return '电压过低';
            }
            break;
        case 'v2':
            if($cat==1){
                return '电压过高';
            }else if($cat == 2){
                return '电压过低';
            }
            break;
    }
}
/*
  * 将数组中，大写的键名转换为小写
  */
function arraykeyToLower( $data = array() )
{
    if( !empty($data) )
    {
        //一维数组
        if(count($data) == count($data,1))
        {
            $data = array_change_key_case($data , CASE_LOWER);
        }
        else    //二维数组
        {
            foreach( $data as $key => $value )
            {
                $data[$key] = array_change_key_case($value , CASE_LOWER);
            }
        }
    }
    return $data;
}
/*
  * 将数组中，大写的键名转换为小写
  */
function arraykeyToUpper( $data = array() )
{
    if( !empty($data) )
    {
        //一维数组
        if(count($data) == count($data,1))
        {
            $data = array_change_key_case($data , CASE_UPPER);
        }
        else    //二维数组
        {
            foreach( $data as $key => $value )
            {
                $data[$key] = array_change_key_case($value , CASE_UPPER);
            }
        }
    }
    return $data;
}
//存入日志
function saveToLog($params=array()){
    $remarks = '';
    switch ($params['op']) {
        case 'group_add':
            $remarks = '分添加组';
            break;
        case 'group_edit':
            $remarks = '分组修改';
            break;
        case 'group_delete':
            $remarks = '分删除组';
            break;
        case 'alarm_setting':
            $remarks = '报警设置';
            break;
        case 'travel_add':
            $remarks = '行程添加';
            break;
        case 'travel_edit':
            $remarks = '行程修改';
            break;
        case 'travel_delete':
            $remarks = '行程删除';
            break;
        case 'device_add':
            $remarks = '设备添加';
            break;
        case 'device_edit':
            $remarks = '设备修改';
            break;
        default:
            break;
    }
    $d=array(
        'CATEGORY'=>1,
        'USER_ID'=>$params['UID'],
        'USER_IP'=>getIP(),
        'BOX_ID'=>$params['objID'],
        'OBJ_NAME'=>$params['objName'],
        'REMARKS'=>$remarks,
        'INSERT_TIME'=>time()
    );
    //$this->db->insert('log',$d);
   Db::name('log')->insert($d);
}
//保存设备到分组
function saveBoxToGroup($groupID,$boxs,$exclusive=0){
    if(!empty($boxs)){
        if($exclusive == 1) {
            //$this->db->set('groupID',-1)->where('groupID',$groupID)->update('container');
            //$result = Db::name('container')->where('GROUP_ID',$groupID)->fetchSql(true)->update(array('GROUP_ID'=>-1));
            Db::name('container')->where('GROUP_ID',$groupID)->update(array('GROUP_ID'=>-1));
        }
        $result = Db::name('container')->where('BOX_ID', 'in', $boxs)->update(array('GROUP_ID' => $groupID));        
        if ($result == 0){
            return 0;
        }else{
            return 1;
        }
    }
    return 0;
}
/*
    更新或者插入内容
    如果数据库中存在数据就更新，没有就插入
*/
function updateOrInsert($table,$whereArr,$dataArray){
    //$rownum=$this->db->from($table)->where($whereArr)->get()->num_rows();
    $rownum = Db::name($table)->where($whereArr)->select();
    //$dataArray['insert_time']=time();
    if($rownum){
        //$this->db->where($whereArr)->update($table,$dataArray);
        Db::name($table)->where($whereArr)->update($dataArray);
    }else{
        //$this->db->insert($table,$dataArray);
        Db::name($table)->insert($dataArray);
    }
}function formatTime($time=0,$stamp=1,$leavespace=0){
    if(empty($time)&&$leavespace>0) return '';
    $timeStamp=$time;
    $theTime='';
    switch($stamp){
        case 1://yyyy-mm-dd
            $theTime=date('Y-m-d',$timeStamp);
            break;
        case 2://yyyy-mm-dd hh:mm:ss
            $theTime=date('Y-m-d H:i:s',$timeStamp);
            break;
        case 3://mm-dd
            $theTime=date('m-d',$timeStamp);
            break;
        case 4://mm-dd H:i
            $theTime=date('m-d H:i',$timeStamp);
            break;
        case 9://m月d日
            $theTime=date('n月j日',$timeStamp);
            break;
        case 10://mm-dd-yyyy hh:mm:ss,倒计时timer格式
            $theTime=date('m-d-Y H:i:s',$timeStamp);
            break;
        default:
            $theTime=date('Y-m-d',$timeStamp);
    }
    return $theTime;
}
function formatDate($dateTime='',$stamp=1,$leavespace=0){
    if(empty($dateTime)&&$leavespace>0) return '';
    $timeStamp=$dateTime==''?time():strtotime($dateTime);
    $theTime='';
    switch($stamp){
        case 1://yyyy-mm-dd
            $theTime=date('Y-m-d',$timeStamp);
            break;
        case 2://yyyy-mm-dd hh:mm:ss
            $theTime=date('Y-m-d H:i:s',$timeStamp);
            break;
        case 3://mm-dd
            $theTime=date('m-d',$timeStamp);
            break;
        case 9://m月d日
            $theTime=date('n月j日',$timeStamp);
            break;
        case 10://mm-dd-yyyy hh:mm:ss,倒计时timer格式
            $theTime=date('m-d-Y H:i:s',$timeStamp);
            break;
        default:
            $theTime=date('Y-m-d',$timeStamp);
    }
    return $theTime;
}
function C2F($t){
    if($t== -999 || $t == '-') return '-';
    return sprintf("%01.1f",$t*1.8 + 32);
}
//将php整数封装成字符串(二进制,4 Byte)
function long2mem($v){
    return  chr($v & 255). chr(($v >> 8 ) & 255). chr(($v >> 16 ) & 255). chr(($v >> 24) & 255);
}
function short2mem($v){
    return  chr($v & 255). chr(($v >> 8 ) & 255);
}
//更新分组设备数量
function updateGroupDeviceNum($groupID){
    $model = new UserModel();
    $groupTable = 'zj_group';
    $boxTable = 'zj_container';
    $sql = "update $groupTable set BOX_NUM = (select count(1) from $boxTable where GROUP_ID = $groupID) where ID = $groupID";
    $result = $model->execute($sql);
}
function set_userdata($data, $value = NULL)
{
    if (is_array($data))
    {
        foreach ($data as $key => &$value)
        {
            $_SESSION[$key] = $value;
        }

        return;
    }

    $_SESSION[$data] = $value;
}
function getActionUrl()
{
    $module     = request()->module();
    $controller = request()->controller();
    $action     = request()->action();
    $url        = $module.'/'.$controller.'/'.$action;
    return strtolower($url);
}
//给关联数组key增加引号
function arrayKeysQuotation($arr){
    return array_combine(
        array_map(function($key){ return '"'.$key.'"'; }, array_keys($arr)),
        $arr
    );
}
//手持设备
//运单主表保存
function wayBill_main_save($params){
    if($params['door_loading'] == true){
        $loading = 1;
    }else{
        $loading = 0;
    }
    if($params['door_unload'] == true){
        $unload = 1;
    }else{
        $unload = 0;
    }
    if(empty($params['title'])){
        $info = json_decode($params['goods_info'],true);
        $title_info = $params['leave_station'].'-'.$params['arrival_station'].'-'.$info[0]['goods_name'];
    }else{
        $title_info = $params['title'];
    }
    $User =  new UserModel();
    $user_info = $User->checkToken($params['channel']);
    $user_id = $user_info['ID'];
    $shipper_name = $params['shipper_name'];//托运人
    $shipper_phone = $params['shipper_phone'];//手机号【发货人】
    $loading_station = $params['loading_station'];//装车站
    $leave_station = $params['leave_station'];//发站
    $loading_mode = $params['loading_mode'];//装货方式
    $door_loading = $loading;//上门取货
    $departure_time = strtotime($params['departure_time']);//发运时间
    $receiver_name = $params['receiver_name'];//收货人
    $receiver_phone = $params['receiver_phone'];//手机号【收货人】
    $arrival_station = $params['arrival_station'];//到站
    $unload_mode = $params['unload_mode'];//卸货方式
    $door_unload =  $unload;//上门卸货
    $power_box_num = $params['power_box_num'];//发电箱号
    $loading_branch = Db::name('travel_station')
        ->alias('S')
        ->join('department D','D.ID = S.BRANCH_OFFICE_ID')
        ->where('S.STATION',$loading_station)
        ->find();
    $arrive_branch = Db::name('travel_station')
        ->alias('S')
        ->join('department D','D.ID = S.BRANCH_OFFICE_ID')
        ->where('S.STATION',$arrival_station)
        ->find();
    $arr = array(
        'USER_ID' => $user_id,
        'SHIPPER_NAME'    => $shipper_name,
        'SHIPPER_PHONE'   => $shipper_phone,
        'LOADING_BRANCH'  => $loading_branch['TITLE'],
        'LOADING_STATION' => $loading_station,
        'TITLE'           => $title_info,
        'LEAVE_STATION'   => $leave_station,
        'LOADING_MODE'    => $loading_mode,
        'DOOR_LOADING'    => $door_loading,
        'DEPARTURE_TIME'  => $departure_time,
        'RECEIVER_NAME'   => $receiver_name,
        'RECEIVER_PHONE'  => $receiver_phone,
        'ARRIVE_BRANCH'   => $arrive_branch['TITLE'],
        'ARRIVAL_STATION' => $arrival_station,
        'UNLOAD_MODE'     => $unload_mode,
        'DOOR_UNLOAD'     => $door_unload,
        'POWER_BOX_NUM'   => $power_box_num
    );
    if(!empty($params['travel_id'])){
        $arr['UPDATE_TIME'] = time();
        Db::name('travel_travel_info')->where('ID',$params['travel_id'])->update($arr);
    }else{
        $arr['INSERT_TIME'] = time();
        $result = Db::name('travel_travel_info')->insertGetId($arr);
    }
    if(!empty($params['travel_id'])){
        return $params['travel_id'];
    }else if(!empty($result)){
        return $result;
    }else{
        return false;
    }
}
//运单副表保存(goods_info)
function wayBill_second_save($id,$data)
{
    $data = json_decode($data,true);
    if(!empty($data) &&  !empty($id)) {
        foreach ($data as $row) {
            $arr = array(
                'TRAVEL_ID' => $id,
                'TRAIN_NUM' => $row['train_num'],
                'BOX_NUM' => $row['box_num'],
                'GOODS_NAME' => $row['goods_name'],
                'GOODS_CATEGORY' => $row['goods_category'],
                'SET_TEMP' => $row['set_temp'],
                'RECEIVE_TIME' => strtotime($row['receive_time']),
                'LOADING_TIME' => strtotime($row['loading_time'])
            );
            if (empty($row['goods_id'])) {
                $arr['INSERT_TIME'] = time();
                $result = Db::name('travel_goods')->insertGetId($arr);
            } else {
                $arr['UPDATE_TIME'] = time();
                $result = Db::name('travel_goods')->where('ID', $row['goods_id'])->update($arr);
            }
        }
        $array = array(
            'SET_TEMP' => $row['set_temp'],
            'GOODS_NAME' => $row['goods_name']
        );
        //更新运单主表信息
        Db::name('travel_travel_info')->where('ID', $id)->update($array);
        return $result;
    }
}
//运单状态保存
function wayBill_status_save($user_id,$data)
{
    if(!empty($data['status_id'])){
        if($data['status'] == 2){
            $arr = array(
                'STATION_NAME'       => $data['station_name'],
                'USER_ID'             => $user_id,
                'CROSS_STATION_TIME' => strtotime($data['cross_station_time']),
                'UPDATE_TIME'        => time()
            );
        }else{
            $arr = array(
                'UPDATE_TIME'        => time(),
                'USER_ID'             => $user_id,
                'CROSS_STATION_TIME' => strtotime($data['cross_station_time']),
            );
        }
        $result = Db::name('travel_travel_info_status')->where('STATUS',$data['status'])->where('ID',$data['status_id'])->where('FLAG_DELETE',0)->update($arr);
    }else{
        if($data['status'] == 2){
            $arr = array(
                'TRAVEL_ID'           => $data['travel_id'],
                'STATUS'              => $data['status'],
                'STATION_NAME'        => $data['station_name'],
                'CROSS_STATION_TIME'  => strtotime($data['cross_station_time']),
                'USER_ID'             => $user_id,
                'INSERT_TIME'         => time()
            );
        }else{
            $arr = array(
                'TRAVEL_ID'           => $data['travel_id'],
                'STATUS'              => $data['status'],
                'CROSS_STATION_TIME'  => strtotime($data['cross_station_time']),
                'USER_ID'             => $user_id,
                'INSERT_TIME'         => time()
            );
        }
        $result = Db::name('travel_travel_info_status')->insert($arr);
        //更新运单主表最新的物流状态
        Db::name('travel_travel_info')->where('ID',$data['travel_id'])->update(array('STATUS'=>$data['status']));
    }
    return $result;
}
//操作清单主表保存
function operationList_main_save($user_id,$params)
{
 if ($params['list_type'] == 1 || $params['list_type'] == 3 || $params['list_type'] == 4 || $params['list_type'] == 5) {
        $arr = array(
            'HANDLE_STATION' => $params['handle_station'],//办理站
            'OPERATION_TIME' => strtotime($params['operation_time']),//搬移时间
            'LIST_TYPE' => $params['list_type'],//操作清单类型
            'USER_ID' => $user_id
        );
    } else if ($params['list_type'] == 2) {
        $arr = array(
            'HANDLE_STATION' => $params['handle_station'],//办理站
            'DELIVERY_COMPANY' => $params['delivery_company'],//交付单位
            'ACCEPT_COMPANY' => $params['accept_company'],//接收单位
            'DEPARTURE_TIME' => strtotime($params['departure_time']),//出站时间
            'OPERATION_TIME' => strtotime($params['operation_time']),//交接时间
            'LIST_TYPE' => $params['list_type'],//操作清单类型
            'USER_ID' => $user_id
        );
    }
    if (!empty($params['list_id'])) {
        $arr['UPDATE_TIME'] = time();
        Db::name('travel_box_operation_list')->where('ID', $params['list_id'])->update($arr);
    } else {
        $arr['INSERT_TIME'] = time();
        $result = Db::name('travel_box_operation_list')->insertGetId($arr);
    }
    if(!empty($params['list_id'])){
        return $params['list_id'];
    }else if(!empty($result)){
        return $result;
    }else{
        return false;
    }
}
//操作清单副表保存
function operationList_second_save($id,$data,$list_type,$file)
{
    if($list_type != 5){
        $data = json_decode($data,true);
    }
    if(!empty($id) && !empty($data)){
        if($list_type == 1){
            foreach($data as $row){
                $arr = array(
                    'LIST_ID'           => $id,
                    'BOX_NUM'           => $row['box_num'],
                    'ORIGINAL_POSITION' => $row['original_position'],
                    'MOVE_POSITION'     => $row['move_position'],
                    'MOVE_REASON'       => $row['move_reason'],
                    'SET_TEMP'          => $row['set_temp'],
                    'OPEN_TEMP'         => $row['open_temp'],
                    'BOX_TYPE'          => $row['box_type'],
                    'BOX_STATUS'        => $row['box_status']
                );
                if(empty($row['box_id'])){
                    $arr['INSERT_TIME'] = time();
                    $result = Db::name('travel_box_list')->insert($arr);
                }else{
                    $arr['UPDATE_TIME'] = time();
                    $result = Db::name('travel_box_list')->where('ID',$row['box_id'])->update($arr);
                }
            }
        }else if($list_type == 2){
            foreach($data as $row){
                $arr = array(
                    'LIST_ID'        => $id,
                    'BOX_NUM'        => $row['box_num'],
                    'OUTGOING_TEMP'  => $row['outgoing_temp'],
                    'MOVE_CAR_NUM'   => $row['move_car_num']
                );
                if(empty($row['box_id'])){
                    $arr['INSERT_TIME'] = time();
                    $result = Db::name('travel_box_list')->insert($arr);
                }else{
                    $arr['UPDATE_TIME'] = time();
                    $result = Db::name('travel_box_list')->where('ID',$row['box_id'])->update($arr);
                }
            }
        }else if($list_type == 3){
            foreach($data as $row){
                $arr = array(
                    'LIST_ID'            => $id,
                    'CASE_NUM'           => $row['case_num'],
                    'SET_TEMP'           => $row['set_temp'],
                    'CHARGING_TIME'      => $row['charging_time'],
                    'ABNORMAL_SITUATION' => $row['abnormal_situation']
                );
                if(empty($row['box_id'])){
                    $arr['INSERT_TIME'] = time();
                    $result = Db::name('travel_box_list')->insert($arr);
                }else{
                    $arr['UPDATE_TIME'] = time();
                    $result = Db::name('travel_box_list')->where('ID',$row['box_id'])->update($arr);
                }
            }
        }else if($list_type == 4){
            foreach($data as $row){
                $arr = array(
                    'LIST_ID'            => $id,
                    'CASE_NUM'           => $row['case_num'],
                    'SET_TEMP'           => $row['set_temp'],
                    'GASOLINE'           => $row['gasoline'],//汽油
                    'DIESEL_OIL'         => $row['diesel_oil'],//柴油
                    'ENGINE_OIL'         => $row['engine_oil'],//机油
                    'ABNORMAL_SITUATION' => $row['abnormal_situation']
                );
                if(empty($row['box_id'])){
                    $arr['INSERT_TIME'] = time();
                    $result = Db::name('travel_box_list')->insert($arr);
                }else{
                    $arr['UPDATE_TIME'] = time();
                    $result = Db::name('travel_box_list')->where('ID',$row['box_id'])->update($arr);
                }
            }
        }else if($list_type == 5){
            $washing_info = json_decode($data['box_info'],true);
            foreach($washing_info as $row){
                $arr = array(
                    'LIST_ID'            => $id,
                    'BOX_NUM'            => $row['box_num'],
                    'ABNORMAL_SITUATION' => $row['abnormal_situation']
                );
                if(empty($row['box_id'])){
                    $arr['INSERT_TIME'] = time();
                    $result = Db::name('travel_box_list')->insert($arr);
                }else{
                    $arr['UPDATE_TIME'] = time();
                    $result = Db::name('travel_box_list')->where('ID',$row['box_id'])->update($arr);
                }
            }
            if($result > 0 &&!empty($file)){
                foreach($file as $image){
                    $name = $image['name'];
                    $type = strtolower(substr($name, strrpos($name, '.') + 1));
                    $allow_type = array('jpg', 'jpeg', 'gif', 'png');
                    if (!in_array($type, $allow_type)) {
                        continue;
                    }
                    $upload_path = ROOT_PATH.'/public/upload'; //上传文件的存放路径
                    $time = date("Ymd");
                    $dir = iconv("UTF-8", "GBK", "$upload_path/".$time);
                    $dir_name = substr($dir,strripos($dir,"\\")+8);
                    //检测文件夹是否存在
                    if (!file_exists($dir)){
                        mkdir ($dir,0777,true);
                    }
                    $file_name = date('Ymd').time().rand(100,999).'.'.$type;
                    //开始移动文件到相应的文件夹
                    if (move_uploaded_file($image['tmp_name'], $dir."/$file_name")) {
                        $data['path'] = "$dir_name/" . "$file_name";
                        $data['img_real_name'] = $name;
                        $data['img_name'] = $file_name;
                    }
                    $img_exit = Db::name('travel_box_attachment')->where('IMG_NAME',$data['img_name'])->where('LIST_ID',$id)->select();
                    if(empty($img_exit)){
                        $array= array(
                            'LIST_ID'       => $id,
                            'IMG_NAME'      => $data['img_name'],
                            'IMG_REAL_NAME' => $data['img_real_name'],
                            'IMG_PATH'      =>$data['path'],
                            'INSERT_TIME'   =>time()
                        );
                        $img_info = Db::name('travel_box_attachment')->insert($array);
                    }else{
                        $img_info = "-1";
                    }
                }
            }else{
                $img_info = "0";
            }
        }
    }
    if($list_type == 5){
        $result = array(
            'img_info' => $img_info,
            'result' => $result
        );
        return $result;
    }else{
        return $result;
    }
}
//华氏度转摄氏度
function F2C($t){
    if($t== -999 || $t == '-') return '-';
    return sprintf("%01.1f",($t-32)/1.8);
}
//数组转对象
function arrayToObject($arr){
    if(is_array($arr)) {
        return (object)array_map(__FUNCTION__, $arr);
    }else {
        return $arr;
    }
}