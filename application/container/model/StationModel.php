<?php
namespace app\container\model;
use think\Model;
use think\Db;
use app\index\model\UserModel;
class StationModel extends Model
{
    function __construct()
    {
        parent::__construct();
        $channel = input('post.channel');
        $User = new UserModel();
        $this->user = $User->checkToken($channel);
    }
    //货场操作列表
    function getOperationList($params){
        //检查处理起止时间，默认最近三天
        if(empty($params['startTime']) || empty($params['endTime'])){
            $endStamp = time();
            $startStamp = $endStamp - 30 * 24 *3600;
        }else{
            $endStamp = strtotime($params['endTime']);
            $startStamp = strtotime($params['startTime']);
        }
        //任何一个 ==false 则替换成默认时间 3天
        if(!$endStamp || !$startStamp){
            $endStamp = time();
            $startStamp = $endStamp - 30 * 24 *3600;
        }
        $where = '1=1';
        if(isset($params['page']))//分页查询
        {
            //总数查询
            if(is_array($params['listType'])){
                $list_type = implode(',',$params['listType']);
                $where .= " and (LIST_TYPE in ($list_type))";
            }else{
                $where .= " and (LIST_TYPE = ".$params['listType']." )";
            }
            //起止时间
            //$this->db->where("operation_time between {$startStamp} and {$endStamp}");
            $where .= " and (OPERATION_TIME between ".$startStamp." and ".$endStamp.")";
            //条件--关键字
            !empty($params['keywords'])&&  $where .= " and (HANDLE_STATION like '%".$params['keywords']."%')";
            //$total = $this->db->where('flag_delete',0)->select('id')->get('travel_operation')->num_rows();
            $total = Db::name('travel_operation')->field('ID')->where('FLAG_DELETE',0)->where($where)->select();
            $total = count($total);
            //返回内容查询*********************************
            if(is_array($params['listType'])){
                $list_type = implode(',',$params['listType']);
                $where .= " and (LIST_TYPE in ($list_type))";
            }else{
                //$this->db->where('list_type',$params['listType']);
                $where .= " and (LIST_TYPE = ".$params['listType']." )";
            }
            //起止时间
            $where .= " and (OPERATION_TIME between ".$startStamp." and ".$endStamp.")";
            //条件--关键字
            !empty($params['keywords']) && $where .= " and (HANDLE_STATION like '%".$params['keywords']."%')";
            //分页
            $page = intval($params['page']);
            $page < 1 && $page = 1;
            $perpage = empty($params['perpage']) ? 6 :intval($params['perpage']);
            $perpage == 0 && $perpage = 6;
            //返回内容查询
            $select = "ID AS LIST_ID,HANDLE_STATION,OPERATION_TIME,LIST_TYPE,TITLE";
            $result = Db::name('travel_operation')
                ->field($select)
                ->where('FLAG_DELETE',0)
                ->where($where)
                ->limit($perpage,($page-1)*$perpage)
                ->select();
        }
        else//不分页
        {
            if(is_array($params['listType'])){
                $list_type = implode(',',$params['listType']);
                $where .= " and (LIST_TYPE in ($list_type))";
            }else{
                $where .= " and (LIST_TYPE = ".$params['listType']." )";
            }
            //起止时间
            $where .= " and (OPERATION_TIME between ".$startStamp." and ".$endStamp.")";
            //条件--关键字
            !empty($params['keywords'])&&  $where .= " and (HANDLE_STATION like '%".$params['keywords']."%')";
            $select = "ID AS LIST_ID,HANDLE_STATION,OPERATION_TIME,LIST_TYPE,TITLE";
            $result = Db::name('travel_operation')->field($select)->where('FLAG_DELETE',0)->where($where)->select();
            $total = count($result);
        }

        return array(
            'total'=>$total,
            'operationInfo'=>arraykeyToLower($result)
        );
    }

    //货场操作详情
    function getOperationDetail($objID){
        if(empty($objID)){return;}
        $operation_info = Db::name('travel_operation')
            ->field('ID AS LIST_ID,HANDLE_STATION,OPERATION_TIME,LIST_TYPE,USER_ID,INSERT_TIME,UPDATE_TIME,DELIVERY_COMPANY,ACCEPT_COMPANY,DEPARTURE_TIME,TITLE,FLAG_DELETE')
            ->where('FLAG_DELETE',0)
            ->where('ID',$objID)
            ->limit(1)
            ->find();
        if(empty($operation_info)){
            app_send_400('未找到您要的信息');
            exit();
        }

        $box_info = Db::name('travel_operation_box')
            ->field('ID,LIST_ID,BOX_NUM,ORIGINAL_POSITION,MOVE_POSITION,MOVE_REASON,SET_TEMP,OPEN_TEMP,BOX_TYPE,BOX_STATUS,MOVE_CAR_NUM,ABNORMAL_SITUATION,CHARGING_TIME,OUTGOING_TEMP,GASOLINE,DIESEL_OIL,ENGINE_OIL,REMARK,DAMAGE_STYLE,INSERT_TIME,UPDATE_TIME')
            ->where('LIST_ID',$objID)
            ->select();
        $img_info = Db::name('travel_operation_att')
            ->field('ID AS IMG_ID,IMG_NAME,IMG_PATH,LIST_ID')
            ->where('LIST_ID',$objID)
            ->select();
        $array = array(
            "operation" => array(arraykeyToLower($operation_info)),
            "box"       => arraykeyToLower($box_info),
            "img"       => arraykeyToLower($img_info)
        );
        return $array;
    }

    //货场作业保存
    function operationSave($user){
        $objID = $this->operationSaveMain($user);
        //保存主表出错，返回false
        if($objID === false){
            return false;
        }
        //保存箱体信息
        $res = $this->operationSaveBox($objID);
        if($res === false){
            //app_send_400('未保存箱体和附件');
            //exit();
            $res=0;
        }
        //保存附件信息
        $res = $this->operationSaveAttachment($objID);
        if($res === false){
            //app_send_400('未保存附件');
            //exit();
            $res=0;
        }
        return $objID;
    }
    //货场作业主表保存
    function operationSaveMain($user){
        $formData = input('post.');
        $opData = array(
            'list_type'=>empty($formData['list_type']) ? 0 : intval($formData['list_type']),
            'handle_station'=>empty($formData['handle_station']) ? 0 : $formData['handle_station'],
            'delivery_company'=>empty($formData['delivery_company']) ? 0 : $formData['delivery_company'],
            'accept_company'=>empty($formData['accept_company']) ? 0 : $formData['accept_company'],
            'departure_time'=>strtotime(@$formData['departure_time']),
            'operation_time'=>strtotime(@$formData['operation_time'])
        );
        //$opData['title'] = empty($formData['title'])?'':$formData['title'];
        $opData['user_id'] = $user['ID'];
        $objID = empty($formData['list_id']) ? 0 : intval($formData['list_id']);
        if($objID > 0){//修改
            $opData['update_time'] = time();
            $res = Db::name('travel_operation')
                ->where('ID',$objID)
                ->update(arraykeyToUpper($opData));
            if($res === false){
                return false;
            }
        }else{//添加
            $opData['insert_time'] = time();
            $res = Db::name('travel_operation')->insertGetId(arraykeyToUpper($opData));
            $objID = $res;
        }
        if($res===false) return false;
        return $objID;
    }
    //货场作业分表箱体信息保存
    function operationSaveBox($objID){
        $boxCount = 0;//更新数量
        $boxIdArray = array();//记录货物表id，不在此数组中的删除
        $boxDataStr = input('post.box_info');
        $boxData = empty($boxDataStr) ? '' : json_decode($boxDataStr,true);
        $operationTitle = '';
        if(!empty($boxData)){
            foreach ($boxData as $box){
                $d = array(
                    'LIST_ID'            => $objID,
                    'BOX_NUM'            => empty($box['box_num'])?'': trim(str_replace("\"","'",$box['box_num'])),
                    'ORIGINAL_POSITION'  => empty($box['original_position'])?'': trim(str_replace("\"","'",$box['original_position'])),
                    'MOVE_POSITION'      => empty($box['move_position'])?'': trim(str_replace("\"","'",$box['move_position'])),
                    'MOVE_REASON'        => empty($box['move_reason'])?'': trim(str_replace("\"","'",$box['move_reason'])),
                    'SET_TEMP'           => empty($box['set_temp'])? -999 : $box['set_temp'],
                    'OPEN_TEMP'          => empty($box['open_temp'])? -999 : $box['open_temp'],
                    'BOX_TYPE'           => empty($box['box_type'])? -1 : intval($box['box_type']),
                    'BOX_STATUS'         => empty($box['box_status'])? -1 : intval($box['box_status']),
                    'MOVE_CAR_NUM'       => empty($box['move_car_num'])? '' : trim($box['move_car_num']),
                    'CHARGING_TIME'      => empty($box['charging_time'])? 0 : intval($box['charging_time']),
                    'DAMAGE_STYLE'       => empty($box['damage_style'])? '' : trim(str_replace("\"","'",$box['damage_style'])),
                    'ABNORMAL_SITUATION' => empty($box['abnormal_situation'])? '' : trim(str_replace("\"","'",$box['abnormal_situation'])),
                    'OUTGOING_TEMP'      => empty($box['outgoing_temp'])? -999 : floatval($box['outgoing_temp']),
                    'GASOLINE'           => empty($box['gasoline'])? '' : trim(str_replace("\"","'",$box['gasoline'])),
                    'DIESEL_OIL'         => empty($box['diesel_oil'])? '' : trim(str_replace("\"","'",$box['diesel_oil'])),
                    'ENGINE_OIL'         => empty($box['engine_oil'])? '' : trim(str_replace("\"","'",$box['engine_oil'])),
                    'REMARK'             => empty($box['remark'])? '' : trim(str_replace("\"","'",$box['remark']))
                );
                $boxID = empty($box['id']) ? 0 : intval($box['id']);
                if($boxID > 0)//修改
                {
                    Db::name('travel_operation_box')->where('ID',$boxID)->update($d);
                }
                else//添加
                {
                    $res = Db::name('travel_operation_box')->insertGetId($d);
                    $boxID = $res;
                }
                $boxCount ++;
                $operationTitle .= $d['BOX_NUM'].',';
                array_push($boxIdArray,$boxID);
            }
            //删除以外的box
            empty($boxIdArray) && $boxIdArray = array(0);
            Db::name('travel_operation_box')
                ->where('LIST_ID',$objID)
                ->where('ID','NOT IN',$boxIdArray)
                ->delete();
            //更新title
            $operationTitle = rtrim($operationTitle,',');
            Db::name('travel_operation')->where('ID',$objID)->update(array('TITLE'=>$operationTitle));
        }
        return $boxCount;
    }
    //货场作业分表附件保存
    function operationSaveAttachment($objID){
        $user = $this->user;
        $fileCount = 0;
        $files = $_FILES;
        if(empty($files)){
            return -1;
        }
        //上传目录
        $uploadPath = './upload/'.date('Ym',time());
        !file_exists($uploadPath) && mkdir ($uploadPath,0777,true);
        foreach ($files as $file){
            if($file['error']){
                continue;
            }
            $ext = strtolower(substr($file['name'], strripos($file['name'], '.')));
            $fileName = date('YmdHis').rand(100,199).$ext;
            if(move_uploaded_file($file['tmp_name'], $uploadPath.'/'.$fileName)){
                //转移成功，保存内容到数据库
                $d = array(
                    'LIST_ID'       => $objID,
                    'IMG_NAME'      => $fileName,
                    'IMG_PATH'      => ltrim($uploadPath,'.').'/'.$fileName,
                    'IMG_REAL_NAME' => $file['name'],
                    'USER_ID'       => $user['ID'],
                    'INSERT_TIME'   => time()
                );
                Db::name('travel_operation_att')->insertGetId($d);
                $fileCount++;
            }else{
                //转移失败，不做处理
               continue;
            }

        }
        return $fileCount;
    }
    //货场作业附件删除
    function operationAttachmentDelete(){
        $attachment_id = input('post.img_id');
        if(empty($attachment_id)){
            return false;
        }
        $objData = Db::name('travel_operation_att')->field('IMG_PATH')->where('ID',$attachment_id)->find();
        if(!empty($objData))
        {
            $res = Db::name('travel_operation_att')->where('ID',$attachment_id)->delete();
            if($res !== false){
                @unlink($objData->img_path);
            }
        }
        else
        {
            return false;
        }
    }
    //货场作业删除
    function operationDelete(){
        $operationID = input('post.list_id');
        if(empty($operationID)){
            return false;
        }
        return Db::name('travel_operation')->where('ID',$operationID)->update(array('FLAG_DELETE'=>1));
    }
}