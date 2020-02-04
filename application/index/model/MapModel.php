<?php
namespace app\index\model;
use think\Model;
use think\Db;

class MapModel extends Model{
    //围栏列表
    function getFenceList(){
        $type = input('type');
        $keyword = input('keyword');
        $where=array();
        if(!empty($type)){
            $where['TYPE']=['=',$type];
        }
        if(!empty($keyword)){
            $where['NAME']=['like',$keyword];
        }
        $select = 'TYPE,BRANCH_ID,BD_AK,BD_SERVICE_ID,BD_FENCE_ID,NAME,CATEGORY,LONGITUDE,LATITUDE,RADIUS,LNG_BD,LAT_BD,LNG_GCJ,LAT_GCJ';

        if(!empty($where)){
            $res = Db::$this->name('geo_fence')->where();
        }else{
            $res = Db::name('geo_fence')->field($select)->select();
        }

        return arraykeyToLower($res);
    }
    //围栏详情
    function getFenceInfo($fenceID){
        if(empty($fenceID)){
            return false;
        }
        return Db::name('geo_fence')->where('id',$fenceID)->find();
    }
    //围栏保存
    function fenceSave($params){
        if(!isset($params['ID'])){
            return false;
        }
        $itemID = $params['ID'];
        unset($params['ID']);
        if($itemID > 0){
            $res = Db::name('geo_fence')->where('id',$itemID)->update($params);
        }else{
            $res = Db::name('geo_fence')->insert($params);
        }
        $res = intval($res);
        return $res;
    }
    //围栏删除
    function fenceDelete($fenceID){
        if(empty($fenceID)){
            return false;
        }
        return Db::name('geo_fence')->where('id',$fenceID)->delete();
    }
}
