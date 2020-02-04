<?php
namespace app\datasys\model;
use think\Model;
use think\Db;
use think\cache\driver\Redis;
/**
 * 
 */
class DatasysModel extends Model
{
	var $redis;
	function __construct()
	{
		$this->redis = new Redis();
	}
	//将数据存储到oracle表
	function saveToOracle($dataList){
		if(empty($dataList) || count($dataList) <=0 ){
			return;
		}
		//
		$latestId = 0;
		foreach ($dataList as $row) {
			if(is_object($row)){
				$d = array_change_key_case( (array)($row), CASE_UPPER);
				empty($d['BUF_LONTI']) && $d['BUF_LONTI'] = '0.0';
				empty($d['BUF_LATI']) && $d['BUF_LATI'] = '0.0';
				//var_dump($d);
				$d = arrayKeysQuotation($d);
				Db::name('data')->insert($d);
			
			//存储最新id	
			$this->saveLatestIdToRedis($row->id);
			}
		}
	}
	function saveLatestIdToRedis($id){
		if(!empty($id)){
			$this->redis->set('latestId',$id);
		}
		return 1;
	}
	//获去最新传输的数据时间戳
	function getLatestId(){
		$latestId = $this->redis->get('latestId');
		if(empty($latestId)){
			$latestId = Db::name('data')->order('id','desc')->limit(1)->value('ID');
			(empty($latestId) || $latestId<30000000) && $latestId = 30000000;
		}
		return $latestId;
	}
	//获去oracle data最新表名并转换为时间戳
	function getLatestTableStamp(){
		$latestDataTableStamp = $this->redis->get('latestDataTableStamp');
		if(empty($latestDataTableStamp)){
			$latestDataTableStamp=0;
		}
		return $latestDataTableStamp;
	}
	//创建data表，根据传入时间戳
}