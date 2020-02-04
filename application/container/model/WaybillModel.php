<?php
namespace app\container\model;
use think\Model;
use think\Db;
use app\index\model\UserModel;
class WaybillModel extends Model
{
    //获取车站列表
    public function getStationList($params){
        $params = strtoupper($params);
        if(!empty($params)){
            $where1 = "(STATION_ABBREVIATION like '%".$params."')";
            $where2 = "(STATION_ABBREVIATION like '".$params."%')";
            $result1 = Db::name('travel_station')
                ->field('STATION,STATION_ABBREVIATION')
                ->where($where1)
                ->select();
            $result2 = Db::name('travel_station')
                ->where($where2)
                ->field('STATION,STATION_ABBREVIATION')
                ->select();
            return arraykeyToLower(array_merge($result1,$result2));
        }else{
            return array();
        }
    }
    //由车站站点获取分公司名称
    public function getBranchNameForStation($stationName){
        $branch = Db::name('travel_station')
            ->alias('S')
            ->join('department D','D.ID = S.BRANCH_OFFICE_ID')
            ->where('S.STATION',$stationName)
            ->limit(1)
            ->find();
        if(!empty($branch)){
            return $branch['TITLE'];
        }
        return false;
    }
    public function getTravelList($params)
    {
        //检查处理起止时间，默认最近三天
        if(empty($params['startTime']) || empty($params['endTime'])){
            $endStamp = time();
            $startStamp = $endStamp - 30 * 24 *3600;
        }else{
            if(strlen($params['endTime']) < 11 ) $params['endTime'] .= ' 23:59:59';
            $endStamp = strtotime($params['endTime']);
            $startStamp = strtotime($params['startTime']);
        }

        //任何一个 ==false 则替换成默认时间 3天
        if(!$endStamp || !$startStamp){
            $endStamp = time();
            $startStamp = $endStamp - 30 * 24 *3600;
        }
        //检查处理运单状态
        $statusStart = 1;
        $statusEnd = 4;
        if(isset($params['status'])){
            if($params['status'] == ''){//status传空字符串，查询所有状态，-1——5
                $statusStart = -1;
                $statusEnd = 5;
            }elseif($params['status'] == 0){//状态传0（未开始），查询所有未开始的 0——0
                $statusStart = -1;
                $statusEnd = 0;
            }elseif($params['status'] == 5){
                $statusStart = 5;
                $statusEnd = 5;
            }else{
                $statusStart = $params['status'];
                $statusEnd = 4;
            }
        }else{//不传状态，默认正在进行的，1——4
            $statusStart = 1;
            $statusEnd = 4;
        }
        $where = '1=1';
        //查询字段
        $select="T.ID AS TRAVEL_ID,T.TITLE,T.SHIPPER_NAME,T.LEAVE_STATION,T.LOADING_MODE,T.SHIPPER_PHONE,T.DEPARTURE_TIME,T.RECEIVER_NAME,T.ARRIVAL_STATION,T.UNLOAD_MODE,T.RECEIVER_PHONE,T.DOOR_LOADING,T.LOADING_BRANCH,T.LOADING_STATION,T.ARRIVAL_BRANCH,T.DOOR_UNLOAD,T.POWER_BOX_NUM,T.USER_ID,T.INSERT_TIME,T.UPDATE_TIME,T.STATUS,T.GOODS AS GOODS_NAME,T.SET_TEMP,T.BOX_NUM";
        //查询
        if(isset($params['page']))//分页
        {
            //总数查询
            $where .= " and (T.STATUS between ".$statusStart." and ".$statusEnd.")";;
            $where .= " and (T.DEPARTURE_TIME between ".$startStamp." and ".$endStamp.")";
            !empty($params['keywords']) && $where .= " and T.LEAVE_STATION = ".$params['keywords'];
            $total = Db::name('travel_waybill')->alias('T')->field('ID')->where('FLAG_DELETE',0)->where($where)->select();
            $total = count($total);
            //返回内容查询
            $page = intval($params['page']);
            $page < 1 && $page = 1;
            $perpage = empty($params['perpage']) ? 6 :intval($params['perpage']);
            $perpage == 0 && $perpage = 6;
            $where .= " and (T.STATUS between ".$statusStart." and ".$statusEnd.")";;
            $where .= " and (T.DEPARTURE_TIME between ".$startStamp." and ".$endStamp.")";
            !empty($params['keywords']) && $where .= " and T.LEAVE_STATION = ".$params['keywords'];
            $travelBills = Db::name('travel_waybill')
                ->alias('T')
                ->field($select)
                ->where('FLAG_DELETE',0)
                ->where($where)
                ->order('ID desc')
                ->limit($perpage,($page-1)*$perpage)
                ->select();
            $travelBills = arraykeyToLower($travelBills);
        }
        else//不分页
        {
            $where .= " and (T.STATUS between ".$statusStart." and ".$statusEnd.")";;
            $where .= " and (T.DEPARTURE_TIME between ".$startStamp." and ".$endStamp.")";
            !empty($params['keywords']) && $where .= " and T.LEAVE_STATION = ".$params['keywords'];;
            $travelBills = Db::name('travel_waybill')
                ->alias('T')
                ->field($select)
                ->where('FLAG_DELETE',0)
                ->where($where)
                ->order('ID desc')
                ->select();
            $travelBills = arraykeyToLower($travelBills);
            $total = count($travelBills);
        }

        //给结果增加tags
        if(!empty($travelBills)){
            foreach ($travelBills as $row){
                $row['tags']= array();
            }
        }
        //
        //返回
        return array('total'=>$total,'waybill'=>$travelBills);
    }
    //获取运单详情
    public function getTravelDetail($travel_id)
    {
        $select = "ID AS TRAVEL_ID,TITLE,SHIPPER_NAME,LEAVE_STATION,LOADING_MODE,SHIPPER_PHONE,DEPARTURE_TIME,RECEIVER_NAME,ARRIVAL_STATION,UNLOAD_MODE,RECEIVER_PHONE,DOOR_LOADING,LOADING_BRANCH,LOADING_STATION,ARRIVAL_BRANCH AS ARRIVAL_BRANCH,DOOR_UNLOAD,POWER_BOX_NUM,USER_ID,STATUS,GOODS,SET_TEMP,CREW_NAME,CREW_PHONE";
        $travel_info = Db::name('travel_waybill')
            ->field($select)
            ->where('ID',$travel_id)
            ->find();
        if(empty($travel_info)){
            return array();
        }
        $select = "ID AS GOODS_ID,TRAVEL_ID,TRAIN_NUM,BOX_NUM,GOODS_NAME,GOODS_CATEGORY,SET_TEMP,RECEIVE_TIME,LOADING_TIME";
        $goods_detail = Db::name('travel_waybill_goods')
            ->field($select)
            ->where('TRAVEL_ID',$travel_id)
            ->where('FLAG_DELETE',0)
            ->select();
        $travel_info_status = Db::name('travel_waybill_status')
            ->field('ID AS STATUS_ID,TRAVEL_ID,USER_ID,STATUS,STATION_NAME,CROSS_STATION_TIME,INSERT_TIME')
            ->where('TRAVEL_ID',$travel_id)
            ->where('FLAG_DELETE',0)
            ->order('id isc')
            ->select();
        //返回
        $re = array(
              "travel"      => arraykeyToLower(array($travel_info)  ),
              "last_status" => array(array('status'=>$travel_info['STATUS'])),
              "alarm"       => array(),
              "status"      => arraykeyToLower($travel_info_status),
              "goods"       => arraykeyToLower($goods_detail)
        );
        return $re;
    }
    //保存整个运单
    function waybillSave($formData){
        unset($formData['channel']);
        $goodsData = empty($formData['goods_info']) ? '' : json_decode($formData['goods_info'],true);
        unset($formData['goods_info']);
        //如果标题为空，自动生成一个标题
        if(empty($formData['title'])){
            $formData['title'] = $formData['leave_station'].'-'.$formData['arrival_station'];
            if(!empty($goodsData)){
                $goods = $goodsData[0];
                $formData['title'] .= ' '.$goods['goods_name'];
            }
        }
        $travelID = $this->waybillSaveMain($formData);
        //保存主表出错，返回false
        if($travelID === false){
            return false;
        }
        $this->waybillSaveGoods($travelID,$goodsData);
        //根据运单更新集装箱起止站
        $this->containerStationRenew($travelID);
        return $travelID;
    }
    //保存运单主表信息
    function waybillSaveMain($billData){
        $travelID = empty($billData['travel_id']) ? 0 : $billData['travel_id'];
        unset($billData['travel_id']);
        //获取处理起止分局
        $billData['loading_branch'] = $this->getBranchNameForStation($billData['leave_station']);
        $billData['arrival_branch'] = $this->getBranchNameForStation($billData['arrival_station']);
        $billData['departure_time'] = intval(strtotime($billData['departure_time']));
        //end of 获取起止分局
        if($travelID > 0){//修改
            //已经发车，不允许修改
            //end of发车判断
            $res = Db::name('travel_waybill')->where('ID',$travelID)->update(arraykeyToUpper($billData));
            if($res === false){
                return false;
            }
        }else{//添加
            $res = Db::name('travel_waybill')->insertGetId(arraykeyToUpper($billData));
            $travelID = $res;
        }
        if($res===false) return false;
        return $travelID;
    }
    //保存运单货物信息
    function waybillSaveGoods($travel_id,$goodsData){
        $goodsCount = 0;//更新数量
        $goodsInfo = array('goods'=>'','set_temp'=>-999);//保存货物信息更新主表
        $goodsIdArray = array(0);//记录货物表id，不在此数组中的删除
        if(!empty($goodsData)){
            foreach ($goodsData as $g){
                unset($g['goodsCategory']);
                unset($g['show_detail']);
                $g['travel_id'] = $travel_id;
                $g['receive_time'] = strtotime($g['receive_time']) ? strtotime($g['receive_time']) : 0;
                $g['loading_time'] = strtotime($g['loading_time']) ? strtotime($g['loading_time']) : 0;
                $goodsID = empty($g['goods_id']) ? 0 : $g['goods_id'];
                unset($g['goods_id']);
                unset($g['goods_category_des']);
                //var_export($g);
                if($goodsID > 0){
                    $g['update_time'] = time();
                    unset($g['numrow']);
                    Db::name('travel_waybill_goods')->where('ID',$goodsID)->update(arraykeyToUpper($g));
                }else{
                    $g['insert_time'] = time();
                    //$this->db->insert('travel_waybill_goods',$g);
                    $res = Db::name('travel_waybill_goods')->insertGetId(arraykeyToUpper($g));
                    //$goodsID = $this->db->insert_id();
                    $goodsID = $res;
                }
                //更新集装箱货物状态
                $res = Db::name('container')->where('NAME',$g['box_num'])->update(array(
                    'WAYBILL_GOODS' => $g['goods_name'],
                    'IN_WAYBILL'    => 1
                ));
                array_push($goodsIdArray,$goodsID);
                $goodsCount++;
                $goodsInfo['goods'] = $g['goods_name'];
                $goodsInfo['set_temp'] = $g['set_temp'];
            }
        }
        //不在记录之内的货物记录, 删除，并更新集运输状态

        //更新集装箱运输状态为 ‘-’，货物为‘-’
        $contTable = 'zj_container';
        $goodsTable = 'zj_travel_waybill_goods';
        $goodsIDList = implode(',',$goodsIdArray);
        $sql = "update {$contTable} 
            set WAYBILL_STATUS = '-',LEAVE_STATION='-',ARRIVAL_STATION='-',WAYBILL_GOODS = '-',IN_WAYBILL = 0
            where name in(select BOX_NUM from {$goodsTable} where TRAVEL_ID = {$travel_id} and ID not in({$goodsIDList}))
        ";
        $model = new WaybillModel();
        $res = $model->execute($sql);
        //删除
        if(!empty($goodsIdArray)){
            $res = Db::name('travel_waybill_goods')->where('TRAVEL_ID',$travel_id)->where('ID','not in',$goodsIdArray)->delete();
        }

        //更新主表货物信息
        $this->waybillGoodsRenew($travel_id,$goodsInfo);
        //更新主表货物箱子数量
        $this->waybillBoxNumRenew($travel_id);
        //返回保存的数量
        return $goodsCount;
    }
    //运单状态保存
    function waybillStatusSave($formData){
        $status_id = empty($formData['status_id']) ? 0 : intval($formData['status_id']);
        $d=array(
            'travel_id'=>intval($formData['travel_id']),
            'cross_station_time'=>intval(strtotime($formData['cross_station_time'])),
            'station_name'=>empty($formData['station_name']) ? '' : trim($formData['station_name']),
            'status'=>intval($formData['status'])
        );
        if($status_id > 0){
            //$res = $this->db->where('id',$status_id)->update('travel_waybill_status',$d);
            $res = Db::name('travel_waybill_status')->where('ID',$status_id)->update(arraykeyToUpper($d));
        }else{
            $res = Db::name('travel_waybill_status')->insertGetId(arraykeyToUpper($d));
            $status_id = $res;
        }
        if($res == false){
            return false;
        }
        //更新运单状态
        $this->waybillStatusRenew($d['travel_id'],$d['status']);
        //更新集装箱运输状态
        $this->containerStatusRenew($d['travel_id'],$d['status']);
        return $status_id;
    }
    //运单状态删除
    function waybillStatusDelete($status_id){
//        $row = $this->db->select('travel_id,cross_station_time,status')->where('id',$status_id)->get('travel_waybill_status')->row();
        $row = Db::name('travel_waybill_status')->field('TRAVEL_ID,CROSS_STATION_TIME,STATUS')->where('ID',$status_id)->find();
        if(empty($row)){
            return false;
        }
        $where = "STATUS >= ".$row['STATUS']."";
        $where .= "and (CROSS_STATION_TIME >= ".$row['CROSS_STATION_TIME'].")";
        $where .= "and (TRAVEL_ID = ".$row['TRAVEL_ID'].")";
        $res = Db::name('travel_waybill_status')
            ->where($where)
            ->delete();
        if($res === false){
            return false;
        }
        //更新运单状态
        $this->waybillStatusRenew($row['TRAVEL_ID']);
        //更新集装箱运输状态
        $this->containerStatusRenew($row['TRAVEL_ID']);
        return $res;
    }
    //更新运单最新状态
    function waybillStatusRenew($travel_id,$status = -999){
        //如果未传入当前状态，去数据库获取最新一条
        if($status < -1){
            $latestStatus = Db::name('travel_waybill_status')
                ->field('STATUS')
                ->where('TRAVEL_ID',$travel_id)
                ->where('FLAG_DELETE',0)
                ->order('STATUS DESC')
                ->find();
            if(!empty($latestStatus)){
                $status = $latestStatus['STATUS'];
            }else{
                $status = 0;
            }
        }
        if($status >= -1 && $status <= 5){
            $res = Db::name('travel_waybill')->where('ID',$travel_id)->update(array('STATUS'=>$status));
            $res == false && $travel_id = false;
        }
        return $travel_id;
    }
    //更新运单箱子数量
    function waybillBoxNumRenew($travel_id){
        $travelTable = 'zj_travel_waybill';
        $goodsTable = 'zj_travel_waybill_goods';
        $sql = "update {$travelTable} set BOX_NUM = (select count(1) from {$goodsTable} where TRAVEL_ID = {$travel_id} and FLAG_DELETE = 0) where ID={$travel_id}";
        $model = new WaybillModel();
        $res = $model->execute($sql);
        $res == false && $travel_id = $res;
        return $travel_id;
    }
    /*更新运单货物信息，货物设置温度
     * $params :array('goods'=>'goods name','set_temp'=>12)
     * */
    function waybillGoodsRenew($travel_id,$params=array()){
        //如果未传入货物信息
        if(empty($params)){
            $params = Db::name('travel_waybill_goods')->field('GOODS_NAME AS GOODS,SET_TEMP')->where('TRAVEL_ID',$travel_id)->limit(1)->select();
        }
        if(!empty($params)){
            $res = Db::name('travel_waybill')->where('ID',$travel_id)->update(arraykeyToUpper($params));
            if($res == false){
                return false;
            }
        }else{
            return false;
        }
        return $travel_id;
    }
    //运单删除
    public function waybillDelete($travel_id){
        if(empty($travel_id)){
            return false;
        }
        //$res = $this->db->where('id',$travel_id)->update('travel_waybill',array('flag_delete'=>1));
        $res = Db::name('travel_waybill')->where('ID',$travel_id)->update(array('FLAG_DELETE'=>1));
        if($res === false){
            return false;
        }
        $this->waybillStatusRenew($travel_id);
        return $travel_id;
    }
    /*
     * 根据运单修改集装箱运输起止站点
     * */
    public function containerStationRenew($travel_id,$params=false){
        if(!$params){
            $row = Db::name('travel_waybill')
                ->field('LEAVE_STATION,ARRIVAL_STATION,STATUS')
                ->where('ID',$travel_id)
                ->find();
            if(empty($row)){
                return false;
            }
            $params = $row;
        }
        $contTable = 'zj_container';
        $goodsTable = 'zj_travel_waybill_goods';
        $sql = "update {$contTable} 
                set LEAVE_STATION = '{$params['LEAVE_STATION']}',ARRIVAL_STATION = '{$params['ARRIVAL_STATION']} '
                where name in(select BOX_NUM from {$goodsTable} where TRAVEL_ID = {$travel_id})
                ";
        $model = new WaybillModel();
        return$model->execute($sql);
    }
    /*
    * 根据运单状态变化 更改集装箱运输状态，
    * 修改行程状态时更改集装箱的运输状态。装货1：重箱；发车2：如果有装货不变，没有装货改成空箱配送；过站3：不变；到站4：不变；卸货5：空箱；
   */
    public function containerStatusRenew($travel_id,$status=false){
        if($status === false){
            $row = Db::name('travel_waybill')
                ->field('LEAVE_STATION,ARRIVAL_STATION,STATUS')
                ->where('ID',$travel_id)
                ->find();
            if(empty($row)){
                return false;
            }
            $status = $row['STATUS'];
        }
        $waybillStatus = '-';//默认闲置
        if($status == 3 || $status == 4){ //如果未开始、过站、到达，直接返回
            return false;
        }
        if($status == 1) $waybillStatus = '重';//装货
        if($status == 2){//状态为2，确认是否有装货
            $loadNum = Db::name('travel_waybill_status')
                ->where('TRAVEL_ID',$travel_id)
                ->where('STATUS',1)
                ->where('FLAG_DELETE',0)
                ->select();
            $waybillStatus = $loadNum > 0 ?'重':'空';
        }
        $contTable = 'zj_container';
        $goodsTable = 'zj_travel_waybill_goods';
        $sql = "update {$contTable} 
                set WAYBILL_STATUS = '{$waybillStatus}'
                where NAME in(select BOX_NUM from {$goodsTable} where TRAVEL_ID = {$travel_id})
                ";
        $model = new WaybillModel();
        return $model->execute($sql);
    }
}