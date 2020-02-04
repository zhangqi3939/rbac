<?php
namespace app\container\controller;
use think\Controller;
use think\Db;
use app\index\model\UserModel;
use app\container\model\StationModel;
class Station extends Controller
{
    function __construct()
    {
        parent::__construct();
        $channel = input('post.channel');
        $User = new UserModel();
        $this->station = new StationModel();
        $this->user = $User->checkToken($channel);
    }
    public function damage_category(){
        $catStr ='箱前损坏,箱侧损坏,箱内损坏,其他';
        app_send($catStr);
    }
    //货场作业列表
    public function operation_list(){
        $user = $this->user;
        $formData = input('post.');
        //查询条件
        //操作类型条件
        if(empty($formData['list_type'])){
            $user['listType'] = array(1,2,3,4,5);
        }else {
            $user['listType'] = explode(',',trim($formData['list_type'],','));
        }

        //起止时间
        $user['startTime'] = empty($formData['startTime']) ? '':$formData['startTime'];
        $user['endTime'] =  empty($formData['endTime']) ? '':$formData['endTime'];
        //关键词
        $user['keywords'] = trim(@$formData['keywords']);
        //分页
        if(isset($formData['page'])){
            $user['page'] = intval($formData['page']);
            $user['perpage'] = empty($formData['perpage']) ? 5 : intval($formData['perpage']);
        }
        $result = $this->station->getOperationList($user);
        //var_dump($this->db->last_query());
        app_send($result);
    }
    //货场操作详情
   public function operation_detail(){
        $objID = intval(input('post.list_id'));
        if(empty($objID)){
            app_send_400('操作选择错误！');
            exit();
        }
        app_send($this->station->getOperationDetail($objID));
    }
    //货场操作保存
    public function operation_save(){
        $user = $this->user;
        $result = $this->station->operationSave($this->user);
        if($result === false){
            app_send_400('');
            exit();
        }
        app_send($result);
    }
    //货场操作删除
    public function operation_delete(){
        $user = $this->user;
        $res = $this->station->operationDelete($user);
        if($res === false){
            app_send_400('删除失败！');
        }
        app_send();
    }
    //货场操作附件删除
    public function operation_attachment_delete(){
        $res = $this->station->operationAttachmentDelete();
        if($res === false){
            app_send_400('删除失败！');
        }
        app_send();
    }
}
