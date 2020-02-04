<?php
namespace app\index\model;
use think\Model;
use think\Db;
class ScheduleModel extends Model
{
    //设备监控日志情况
    public function schedule_log()
    {
        $result = Db::name('schedule_log')->field('"BOX_ID",max("INSERT_TIME") as "INSERT_TIME"')->Group('"BOX_ID"')->select();
        return $result;
    }
}