<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use think\Session;
use app\index\model\UserModel;
use app\index\model\ScheduleModel;
use think\Cache;
class schedule extends Controller
{
    public function monitor_latest()
    {
        $schedule = new ScheduleModel();
        $result = $schedule->schedule_log();
        app_send(arraykeyToLower($result));
    }
    public function monitor_save()
    {
        $User = new UserModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $user_id = $user_info['ID'];
        $box_id = input('post.box_id');
        //$station_id = intval(Session::get('station_id'));
        $station_id = 1;
        if($station_id <= 0){
            app_send('','400','非监控管理员');
        }
        $d=array(
            'USER_ID'=>$user_id,
            'BOX_ID'=>$box_id,
            'STATION_ID'=>$station_id,
            'INSERT_TIME'=>time()
        );
        //$this->db->insert('schedule_log',$d);
        $aa = Db::name('schedule_log')->insert($d);
        app_send();
    }
    //排班->工位(列表)
    public function station_listing()
    {
        $result = Db::name('schedule_station')->order('SEQUENCE,ID')->select();
        if(!empty($result)){
            foreach ($result as &$row) {
                $groups = Db::name('group')
                    ->alias('G')
                    ->field('ID AS GROUP_ID,NAME AS GROUP_NAME')
                    ->where('G.STATION_ID',$row['ID'])
                    ->select();
                $row['groups'] = arraykeyToLower($groups);
            }
        }
        app_send(arraykeyToLower($result));
    }
    //排班->工位（添加&修改）
    public function station_save()
    {
        $d=array(
            'NAME'=>input('post.name'),
            'SEQUENCE'=>intval(input('post.sequence'))
        );
        $id = intval(input('post.id'));
        if($id > 0){
           // $this->db->where('id',$id)->update('schedule_station',$d);
            Db::name('schedule_station')->where('ID',$id)->update($d);

        }else{
           // $this->db->insert('schedule_station',$d);
            Db::name('schedule_station')->insert($d);
        }
        app_send();
    }
    //排班->工位（删除）
    public function station_delete(){
        $id = intval(input('post.id'));
        Db::name('schedule_station')->where('ID',$id)->delete();
        app_send();
    }
    //排班->班次（列表）
    public function class_listing(){
        $result = Db::name('schedule_class')->order('SEQUENCE,ID')->select();
        app_send(arraykeyToLower($result));
    }
    //排班->班次（添加&修改）
    function class_save(){
        $d = array(
            'NAME'=>input('post.name'),
            'START_TIME'=>input('post.start_time'),
            'END_TIME'=>input('post.end_time'),
            'SEQUENCE'=>intval(input('post.sequence')),
            'NEXT_DAY'=>intval(input('post.next_day'))
        );
        $id = intval(input('post.id'));
        if($id > 0){
            Db::name('schedule_class')->where('ID',$id)->update($d);
        }else{
           $a =  Db::name('schedule_class')->insert($d);
        }
        app_send();
    }
    //排班->班次（删除）
    public function class_delete()
    {
        $id = intval(input('post.id'));
        Db::name('schedule_class')->where('id',$id)->delete();
        //$this->db->where('id',$id)->delete('schedule_class');
        app_send();
    }
    //排班->排班（列表）
    public function schedule_listing()
    {
        $start_date = input('post.start_date');
        empty($start_date) && $start_date = formatDate();//默认今天
        //工位列表
        $stations = Db::name('schedule_station')->field('ID,NAME')->order('SEQUENCE')->select();
        //查询
        $reArray=[];
        for ($i=0; $i < 7; $i++) {
            $selectDate = formatTime(strtotime($start_date) + 3600*24*$i);
            $result = Db::name('schedule')
                ->alias('S')
                ->field('T.ID AS STATION_ID,T.NAME AS STATION_NAME,C.ID AS CLASS_ID,C.NAME AS CLASS_NAME,S.ID,S.USER_ID,U.REAL_NAME,S.REMARKS')
                ->join('schedule_station T','S.STATION_ID = T.ID','left')
                ->join('schedule_class C','S.CLASS_ID = C.ID','left')
                ->join('user U','S.USER_ID = U.ID','left')
                ->where('S.SCHEDULE_DATE ='."'$selectDate'")
                ->order('T.SEQUENCE,C.SEQUENCE')
                ->select();
            $reArray[$selectDate] = arraykeyToLower($result);
        }
        app_send($reArray);
    }
    //排班->排班（用户）
    public function user_listing()
    {
        //$users = Db::name('user')->field('ID,USER_NAME,REAL_NAME')->where('IS_ADMIN',1)->where('IS_SUPER',0)->select();
        $users = Db::name('user')
        	->alias('u')
        	->field('u.ID,u.USER_NAME,u.REAL_NAME')
        	->join('rbac_user_role_relation r','r.USER_ID = u.id','left')
        	->where('r.ROLE_ID','41')
        	->select();
        app_send(arraykeyToLower($users));
    }
    //排班->排班（用户[保存]）
    public function schedule_save()
    {
        $d = array(
            'SCHEDULE_DATE'=>input('post.schedule_date'),
            'USER_ID'=>input('post.user_id'),
            'CLASS_ID'=>input('post.class_id'),
            'STATION_ID'=>input('post.station_id'),
            'REMARKS'=>input('post.remarks')
        );
        $classObj = Db::name('schedule_class')->where('ID',$d['CLASS_ID'])->find();
        //echo $this->db->last_query();
        if (empty($classObj)) {
            app_send('',400,'班次选择错误！');
            exit();
        }
        if(empty($d['USER_ID'])){
            app_send('',400,'请选择监控管理员');
            exit();
        }
        if(empty($d['REMARKS'])){
            app_send('',400,'请填写备注信息');
            exit();
        }
        $d['START_TIME'] = strtotime($d['SCHEDULE_DATE'].' '.$classObj['START_TIME']);
        $d['END_TIME'] = strtotime($d['SCHEDULE_DATE'].' '.$classObj['END_TIME']);
        if($classObj['NEXT_DAY']){
            $d['END_TIME'] += 3600*24;
        }
        $id = input('post.id');
        if($id){
           // $this->db->where('ID',$id)->update('schedule',$d);
            Db::name('schedule')->where('ID',$id)->update($d);
        }else{
            //$this->db->insert('schedule',$d);
           Db::name('schedule')->insert($d);
        }
        app_send();
    }
    //排班->工位（添加分组）
    public function station_group_add()
    {
        //var_dump(input('post.'));
        $station_id = intval(input('post.station_id'));
        $group_ids = input('post.')['group_id'];
        if(empty($group_ids)){
            app_send('',400,'分组选择错误！');
            exit();
        }
        $i=0;
        foreach ($group_ids as $group_id){
            //$this->db->where('id',$group_id)->where('station_id <=',0)->update('group',array('station_id'=>$station_id));
            Db::name('group')->where('STATION_ID','elt',0)->where('ID',$group_id)->update(array('STATION_ID'=>$station_id));
            $i++;
        }
        if($i>0){
            app_send();
        }else{
            app_send('',400,'未能添加！');
        }
    }
    //排班->工位（分组删除）
    public function station_group_delete()
    {
        $station_id = intval(input('post.station_id'));
        $group_id = intval(input('post.group_id'));
        Db::name('group')->where('ID',$group_id)->where('STATION_ID',$station_id)->update(array('STATION_ID'=>0));
        app_send();
    }
    //排班->监控（列表）
    public function monitor_listing()
    {
        echo '排班->监控（列表）';
        Cache::store('redis')->set('key1','123456789');
       $a =  Cache::store('redis')->get('key1');
       dump($a);
    }
}