<?php
namespace app\index\model;
use think\Model;
use think\Db;
class UserModel extends Model
{
    //查询登录用户的信息
    public function userList($where)
    {
        $result = Db::name('user')->field('ID,USER_NAME,USER_PASSWORD,REAL_NAME,USER_LEVEL,AGENT_ID,CLIENT_ID,IS_ADMIN,TEL,EMAIL')->where($where)->find();
        return $result;
    }
    //用户登录日志
    public function saveLoginLog($uid,$remarks='用户登录'){
        $d = array(
            'userID'=>$uid,
            'remarks'=>$remarks,
            'userIP'=>getIP()
        );
        $sql = Db::name('log')->insert(array('USER_ID'=>$d['userID'],'REMARKS'=>$d['remarks'],'USER_IP'=>$d['userIP'],'INSERT_TIME'=>time()));
    }
    //从请求中的到token
    function getTokenFromHttp(){
        $headers = getallheaders();
        return isset($headers['Token']) ? $headers['Token'] : '';
    }
    //生成新的token
    public function newToken($userID,$channel,$uuid =''){
        //组织数据
        $d = array(
            'CHANNEL'=>$channel,
            'USER_ID'=>$userID,
            'UUID'   => $uuid,
            'ADD_TIME'=>time(),
            'USER_IP'=>getIP(),
            'TOKEN'=>''
        );
        if($channel == 'web'){
            //生成新的token
            $d['TOKEN'] = md5($d['USER_ID'].$d['USER_IP'].$d['CHANNEL']);
        }else if($channel == 'app' && $uuid != ''){
            //生成新的token
            $d['TOKEN'] = md5($d['USER_ID'].$d['USER_IP'].$d['CHANNEL'].$d['UUID']);
        }

        //查询有没有记录
        $row = Db::name('token')->field('ID')->where(array('USER_ID'=>$userID,'CHANNEL'=>$channel))->find();
        if(!empty($row)){
            //更新
            Db::name('token')->where(array('ID'=>$row['ID']))->update(array('CHANNEL'=>$channel,'USER_ID'=>$userID,'ADD_TIME'=>time(),'USER_IP'=>$d['USER_IP'],'TOKEN'=>$d['TOKEN']));
        }else{
            //新记录
            Db::name("token")->insert(array('CHANNEL'=>$channel,'USER_ID'=>$userID,'ADD_TIME'=>time(),'USER_IP'=>$d['USER_IP'],'TOKEN'=>$d['TOKEN'],'UUID'=>$uuid));
        }
        return $d['TOKEN'];
    }
    //删除token，退出登录
    public function deleteToken($channel){
        $token = $this->getTokenFromHttp();
        $t_token = time().'';
        //var_dump($t_token);die;
        //$this->db->where('token',$token)->where('channel',$channel)->update('token',array('token'=>$t_token));
        $result = DB::name('token')->where('CHANNEL',$channel)->update(array('TOKEN'=>$t_token));
        return $result;
    }
    //查询token和用户信息
    public function getTokenInfo($token,$channel){
        $sql = Db::name('token')->alias('T')
                                       ->join('user U','T.USER_ID = U.ID','left')
                                       ->join('rbac_user_role_relation R','R.USER_ID =  U.ID','left')
                                       ->join('department D','D.ID = U.BRANCH_ID' , 'left')
                                       ->field('D.TITLE AS BRANCH_NAME,U.ID,R.ROLE_ID,U.USER_NAME,U.REAL_NAME,U.EMAIL,U.TEL,U.GENDER,U.USER_LEVEL,U.AGENT_ID,U.CLIENT_ID,U.USER_PASSWORD,U.IS_ADMIN,U.IS_SUPER,U.BRANCH_ID,T.TOKEN,T.ID AS TOKEN_ID,T.ADD_TIME')
                                       ->where('T.CHANNEL',$channel)
                                       ->where('T.TOKEN',$token)
                                       ->find();
        return $sql;
    }
    //token检查
    public function checkToken($channel){
        $token = $this->getTokenFromHttp();
        $token = trim($token,'"');
        if(empty($token)){
            app_send('',401,'您的登录信息为空。');
            die();
        }
        //检查token信息，并更新
        $tokenInfo = $this->getTokenInfo($token,$channel);
        if(empty($tokenInfo)){
            app_send('',401,'您的登录信息失效。');
            exit();
        }
        if($tokenInfo['ADD_TIME'] < time() - 7200 && $channel != 'app'){
            app_send('',401,'您超过两小时未动作，请重新登录。');
            exit();
        }
        //如果超过5分钟，更新token时间
        if($tokenInfo['ADD_TIME'] < time() - 300){
            Db::name('token')->where('ID',$tokenInfo['TOKEN_ID'])->update(array('ADD_TIME'=>time()));
        }
        //返回用户及token信息
        return $tokenInfo;
    }
    /*判断是否需要*/
    function checkRange($id=0,$cat='device',$strict = 0){

        if(0){
            app_send('',400,'您不能执行此操作');
            exit();
        }
    }
}