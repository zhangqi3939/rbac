<?php
namespace app\index\controller;

use app\common\controller\MyController;
use app\index\model\ServiceModel;

/**
 * 服务类，处理所有后台处理的内容，系统任务定时调用
 1、报警分析
 2、数据处理
 */
class S extends MyController{
	//分析传感器报警
	function alarm_current(){
		echo 'parsing alarm....';
		$sm = new ServiceModel();

        echo $sm->dealAlarmRefrigeration();
        die();

		echo $sm->dealAlarm('t1','gps_temp1');
		echo '<br/>';
		echo $sm->dealAlarm('t2','gps_temp2');
		echo '<br/>';
		echo $sm->dealAlarm('t3','gps_temp3');
		echo '<br/>';
		echo $sm->dealAlarm('h1','gps_humi');
		echo '<br/>';
		echo $sm->dealAlarm('o1','gps_oil_level');
		echo '<br/>';
		echo $sm->dealAlarm('v1','gps_voltage');
		echo '<br/>';
		//echo $sm->dealAlarm_low_voltage('v2','gps_voltage');
		exit();
	}
    //分析冷机报警码
    function alarm_cooler(){

    }
	function fence_message(){
		echo 'parsing fence massage...';
	}
}