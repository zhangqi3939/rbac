<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use app\index\model\UserModel;

class Report extends Controller
{
    public function run_time()
    {
        $boxIDs = input('post.')['boxIDs'];//boxIDs[]
        $startTime = input('post.startTime');
        $endTime = input('post.endTime');
        //检查输入
        $regex = "/\/|\～|\，|\。|\！|\？|\"|\"|\【|\】|\『|\』|\;|\<|\>|\'|\·|\~|\!|\@|\#|\\$|\%|\^|\&|\*|\(|\)|\_|\+|\{|\}|\<|\>|\?|\[|\]|\,|\.|\/|\;|\'|\`|\=|\\\|\|/";
        if(preg_match($regex,$startTime)){
            app_send('',400,'输入的信息有误！1');
            exit();
        }
        if(preg_match($regex,$endTime)){
            app_send('',400,'输入的信息有误！2');
            exit();
        }
        // if(preg_match($regex,$boxIDs)){
        // 		app_send('',400,'输入的信息有误！3');
        // 		exit();
        // }
        //boxID
        if(empty($boxIDs)){
            app_send('','400','设备选择错误');
            exit();
        }

        if(!is_array($boxIDs)) {
            app_send('','400','设备选择错误');
            exit();
        }
        if(!empty($boxIDs)){
            $allID = 1;
            foreach ($boxIDs as $bid) {
                if(!is_numeric($bid)){
                    $allID = 0;
                    break;
                }
            }
            if(!$allID){
                app_send('','400','设备选择错误');
                exit();
            }
        }

        //查询数据
        $startStamp = $startTime == '' ? time() - 240*3600 : strtotime($startTime);
        $endStamp = $endTime == '' ? time() : strtotime($endTime);
        // $data = $this->db->select('box_id,startTime,endTime')
        // 								->where_in('box_id',$boxIDs)
        // 								->where('status',1)
        // 								->where('startTime >= ',$startStamp)
        // 								->where('endTime <= ',$endStamp)
        // 								->order_by('id')
        // 								->get('container_runtime')->result();
        if(empty($startStamp) || empty($endStamp)){
            app_send('','400','设备选择错误');
            exit();
        }
        $sql = "select BOX_ID,sum(RESERVE4) as RUN_TIME from zj_data where INSERT_TIME between $startStamp and $endStamp and BOX_ID in(".implode(',',$boxIDs).") 
			group by BOX_ID ";
        $Model = new UserModel();
        $data = $Model->query($sql);
        if(!empty($data)){
            foreach ($data as &$row) {
                $row['START_TIME'] = $startStamp;
                $row['END_TIME'] = $endStamp;
                $row['RUN_TIME'] = round($row['RUN_TIME'] / 3600,1);
            }
        }
        app_send(arraykeyToLower($data));
    }
}