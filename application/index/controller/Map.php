<?php
namespace app\index\controller;

use app\common\controller\MyController;
use app\index\model\MapModel;
/**
 * MapModel  地图服务模块类，地图围栏
 */
class Map extends MyController{
    var $map;
    function __construct(){
        parent::__construct();
        $this->map = new MapModel();
    }
    //围栏列表
    function fence_list(){
        $res = $this->map->getFenceList();
        if(is_array($res)){
            app_send($res);
            exit();
        }
        app_send_400($res);
    }
    //围栏详情
    function fence_info(){
        $fenceID = intval(input('itemID'));
        if(empty($fenceID)){
            app_send_400('围栏选择错误');
            exit();
        }
        $res = $this->map->getFenceInfo($fenceID);
        if($res === false){
            app_send_400('没找到围栏');
            exit();
        }
        app_send(arraykeyToLower($res));
    }
    function fence_save(){
        $params = array(
            'TYPE'=>input('type'),
            'BRANCH_ID'=>0,
            'BD_AK'=>'',
            'BD_SERVICE_ID'=>0,
            'BD_FENCE_ID'=>0,
            'NAME'=>input('fence_name'),
            'CATEGORY'=>input('fence_category'),
            'TYPE'=>input('fence_type'),
            'LONGITUDE'=>floatval(input('fence_longitude')),
            'LATITUDE'=>floatval(input('fence_latitude')),
            'RADIUS'=>intval(input('fence_radius')),
            'LNG_BD'=>floatval(input('fence_longitude')),
            'LAT_BD'=>floatval(input('fence_latitude')),
            'LNG_GCJ'=>floatval(input('fence_longitude')),
            'LAT_GCJ'=>floatval(input('fence_latitude'))
        );
        $params['RADIUS'] = 1000;
        empty($params['TYPE']) && $params['TYPE'] = 'station';
        $params['ID'] = intval(input('itemID'));
        $res = $this->map->fenceSave($params);
        if($res === false){
            app_send_400();
            exit();
        }
        if(intval($res) != 1){
            app_send($res);
            exit();
        }
        app_send();
    }
    function fence_delete(){
        $fenceID = intval(input('itemID'));
        $res = $this->map->fenceDelete($fenceID);
        if(intval($res) != 1){
            app_send_400($res);
            exit();
        }
        app_send();
    }
}