<?php
namespace app\index\controller;
use think\Controller;
use think\Db;
use app\index\model\UserModel;
class Dept extends Controller
{
    function dept_listing(){
        $result = Db::name('department')->select();
        app_send(arraykeyToLower($result));
    }

    public function dept_save()
    {
        $d=array(
            'PARENT_ID'=>intval(input('post.parent_id')),
            'TITLE'=>input('post.title'),
            'REMARKS'=>input('post.remarks')
        );
        $id=intval(input('post.id'));
        if($id > 0){
            Db::name('department')->where('id',$id)->update($d);
        }else{
            Db::name('department')->insert($d);
        }
        app_send();
    }
    //部门->删除
    public function dept_delete(){
        $id = input('post.id');
        Db::name('department')->where('id',$id)->delete();
        app_send();
    }
    public function user_listing(){
            $user = Db::name('user')->field("ID,USER_NAME,REAL_NAME,TEL,EMAIL,GENDER,BRANCH_ID")->select();
            foreach($user as $key => $rows){
            	$role_ids = Db::name('rbac_user_role_relation')->field('ROLE_ID')->where('USER_ID',$rows['ID'])->find();
            	$title = Db::name('department')->field('TITLE')->where('ID',$rows['BRANCH_ID'])->find();
            	if(empty($role_ids)){
            		$role_ids["ROLE_ID"] = '62,';
            	} 
            	$user["$key"]['DEPARTMENT_NAME'] = $title['TITLE'];
            	$user["$key"]['ROLE_ID'] = $role_ids["ROLE_ID"];
            }
         /*$users = Db::name('rbac_user_role_relation')
            ->alias('r')
            ->join('user u','u.ID = r.USER_ID','left')
            ->join('department d','d.ID = u.BRANCH_ID','left')
            ->field('u.ID,u.USER_NAME,u.REAL_NAME,u.TEL,u.EMAIL,u.GENDER,r.ROLE_ID,d.TITLE AS DEPARTMENT_NAME')
            ->select();*/
        app_send(arraykeyToLower($user));
    }
    public function user_save()
    {
        $id = input('post.id');
        $formData['USER_NAME'] = input('post.user_name');
        $password = input('post.user_password');
        $formData['REAL_NAME'] = input('post.real_name');
        $formData['TEL'] = input('post.tel');
        $formData['EMAIL'] = input('post.email');
        $formData['GENDER'] = input('post.gender');
        $formData['BRANCH_ID'] = input('post.branch_id');
        $role_ids = input('post.role_id/a');
        if(is_array($role_ids)){
            $role_ids = implode(",", $role_ids);
        }else{
            $role_ids = "0";
        }
        if(!empty($password)){
            $formData['USER_PASSWORD'] = md5($password);
        }else{
            $formData['USER_PASSWORD'] = md5(123456);
        }
        if(empty($id)){
            $res = Db::name('user')->insertGetId($formData);
            if($res > 0){
                $result = Db::name('rbac_user_role_relation')->insert(array('USER_ID'=>$res,'ROLE_ID'=>$role_ids));
            }
        }else{
            if(empty($password)) unset($formData['USER_PASSWORD']);
            $res = Db::name('user')->where('ID',$id)->update($formData);
            if($res > 0) {
                $result = Db::name('rbac_user_role_relation')->where('USER_ID', $id)->update(array('USER_ID' => $id, 'ROLE_ID' => $role_ids));
            }
        }
        if($res > 0 && $result > 0){
            app_send();
        }else{
            app_send('','400','用户保存失败');
        }
    }
    //用户删除
     public function user_delete(){
        $User =  new UserModel();
        $channel = 'web';
        $user_info = $User->checkToken($channel);
        $role_id = $user_info['ROLE_ID'];
        if((strpos($role_id ,'1') == false ) || $role_id == "0"){
            app_send('','400','您不是超级管理员，不能管理用户');
            exit();
        }
        $id = input('post.id');
        if($id>0){
            $res = Db::name('user')->where('ID',$id)->delete();
            $result = Db::name('rbac_user_role_relation')->where('USER_ID',$id)->delete();
        }else{
            app_send('','400','用户选择错误。');
        }
        if($result>0){
            app_send();
        }
    }
    //用户详情
    public function user_info()
    {
        $user_id = input('post.itemID');
        $info = Db::name('user')
            ->alias('U')
            ->join('rbac_user_role_relation R','U.ID=R.USER_ID','left')
            ->field('U.ID,U.USER_NAME,U.REAL_NAME,U.TEL,U.EMAIL,U.GENDER,U.BRANCH_ID,R.ROLE_ID')
            ->where('U.ID',$user_id)
            ->find();
        $info = arraykeyToLower($info);
        $info['role_id'] = explode(",",$info['role_id']);
        app_send($info);
    }
    //权限添加
    public function limit_add()
    {
        $limit_name = input('post.limit_name');
        $model = input('post.model');
        $controller = input('post.controller');
        $action = input('post.action');
        $parent_id = input('post.parent_id');
        $d = array(
            'LIMIT_NAME' => $limit_name,
            'MODEL' => $model,
            'CONTROLLER' => $controller,
            'ACTION' => $action,
            'PARENT_ID' => $parent_id
        );
        Db::name('rbac_limit')->insert($d);
        app_send();
    }
    //权限编辑
    public function limit_edit()
    {
        $limit_id = input('post.limit_id');
        $limit_name = input('post.limit_name');
        $model = input('post.model');
        $controller = input('post.controller');
        $action = input('post.action');
        $parent_id = input('post.parent_id');
        $d = array(
            'LIMIT_NAME' => $limit_name,
            'MODEL' => $model,
            'CONTROLLER' => $controller,
            'ACTION' => $action,
            'PARENT_ID' => $parent_id
        );
        $result = Db::name('rbac_limit')->where('ID',$limit_id)->update($d);
        if($result < 0){
            app_send('','400','您所选择的权限信息为空，请联系管理员');
        }else{
            app_send();
        }
    }
    //权限详情
    public function limit_info()
    {
        $limit_id = input('post.limit_id');
        $result = Db::name('rbac_limit')->where('ID',$limit_id)->select();
        if(!empty($result)){
            app_send($result);
        }else{
            app_send('','400','您所选择的权限信息为空，请联系管理员');
        }
    }
    //权限删除
    public function limit_delete()
    {
        $limit_id = input('post.limit_id');
        $result = Db::name('rbac_limit')->where('ID',$limit_id)->delete();
        if($result > 0){
            app_send('',200,'删除成功');
        }else{
            app_send('',400,'删除失败，请联系管理员，请仔细核对您的步骤');
        }
    }
    //角色用户列表
    public function role_user()
    {
        $role_id = input('post.roleID');
        $where = "(R.ROLE_ID like '%".$role_id."%')";
        $user_info = Db::name('rbac_user_role_relation')
            ->alias('R')
            ->join('user U','U.ID = R.USER_ID','left')
            ->where($where)
            ->field('U.ID,U.USER_NAME')
            ->order('U.ID desc')
            ->select();
        app_send(arraykeyToLower($user_info));
    }
    //角色列表
    public function role_list()
    {
        $result = Db::name('rbac_role')->select();
        app_send(arraykeyToLower($result));
    }
    //角色添加
    public function role_save()
    {
        $role_id = input('post.role_id');
        $category = input('post.role_device_category/a');
        if(is_array($category)){
            $category = implode(',',$category);
        }else{
            $category = "";
        }
        $d = array(
            'ROLE_NAME'             => input('post.role_name'),
            'ROLE_IN_LIMIT'         => input('post.role_in_limit'),
            'ROLE_IN_SCHEDULE'      => input('post.role_in_schedule'),
            'ROLE_IN_OPTION'        => input('post.role_in_option'),
            'ROLE_DEVICE_CATEGORY' => $category,
            'ROLE_DESCRIPTION'      => input('post.role_description')
        );
        if(empty($role_id)){
            $role_exit = Db::name('rbac_role')->where('ROLE_NAME',$d['ROLE_NAME'])->select();
            if(!$role_exit){
                $result = Db::name('rbac_role')->insertGetId($d);
                if($result){
                    $res = Db::name('rbac_role_limit_relation')->insert(array(
                            'ROLE_ID'  => $result,
                            'LIMIT_ID' => $d['ROLE_IN_LIMIT']
                        )
                    );
                }
            }else{
                app_send('','400','该角色名称已存在');
            }
        }else{
            $result = Db::name('rbac_role')->where('ID',$role_id)->update($d);
            if($result){
                $res = Db::name('rbac_role_limit_relation')->where('ROLE_ID',$role_id)->update(array(
                        'ROLE_ID'  => $role_id,
                        'LIMIT_ID' => $d['ROLE_IN_LIMIT']
                    )
                );
            }
        }
        if($result >0){
            app_send();
        }else{
            app_send('','400','角色保存失败，请联系管理员');
        }

    }
    //角色详情
    public function role_detail()
    {
        $role_id = input('post.role_id');
        $role_info = Db::name('rbac_role')->where('ID',$role_id)->select();
        app_send(arraykeyToLower($role_info));
    }
    //角色编辑
    public function role_edit()
    {
        $role_id = input('post.role_id');
        $role_name = input('post.role_name');
        $role_in_limit = input('post.role_in_limit');
        $role_in_schedule = input('post.role_in_schedule');
        $role_in_option = input('post.role_in_option');
        $role_device_category = input('post.role_device_category');
        $role_description = input('post.role_description');
        $d = array(
            'ROLE_NAME'        => $role_name,
            'ROLE_IN_LIMIT'    => $role_in_limit,
            'ROLE_IN_SCHEDULE' => $role_in_schedule,
            'ROLE_IN_OPTION'   => $role_in_option,
            'ROLE_DEVICE_CATEGORY'    => $role_device_category,
            'ROLE_DESCRIPTION' => $role_description
        );
        $b = array(
            'LIMIT_ID' => $role_in_limit
        );
        $result = Db::name('rbac_role')->where('ID',$role_id)->update($d);
        $res = Db::name('rbac_role_limit_relation')->where('ROLE_ID',$role_id)->update($b);
        if($result >0 && $res>0){
            app_send();
        }else{
            app_send('','400','角色编辑失败，请联系管理员');
        }
    }
    //角色删除
    public function role_delete()
    {
        $role_id = input('post.role_id');
        if(!empty($role_id)){
            $result = Db::name('rbac_role_limit_relation')->where('ROLE_ID',$role_id)->delete();
            if($result>0){
                $res = Db::name('rbac_role')->where('ID',$role_id)->delete();
            }else{
                app_send('','400','角色用户表删除失败');
            }
        }else{
            app_send('','400','请选择您要删除的角色');
        }
        if($res > 0){
            app_send();
        }
    }
    //权限列表
    public function limit_list()
    {
        $result = Db::name('rbac_limit')->field('ID,LIMIT_NAME')->where('PARENT_ID',0)->order('ID ASC')->select();
        app_send(arraykeyToLower($result));
    }
    //角色分组【列表】
    public function role_grouplist()
    {
        $result = Db::name('rbac_role_group_relation')
            ->alias('L')
            ->join('rbac_role R','L.ROLE_ID = R.ID')
            ->field('L.ID AS ID,L.ROLE_ID,L.GROUP_ID,R.ROLE_NAME')
            ->select();
        app_send(arraykeyToLower($result));
    }
    //角色分组【添加】
    public function role_group_add()
    {
        $role_id = input('post.role_id');
        $group_id = input('post.group_id');
        //判断是否存在角色分组
        $result = Db::name('rbac_role_group_relation')->where('ROLE_ID',$role_id)->select();
        if(empty($result)){
            if(!empty($group_id)){
                Db::name('rbac_role_group_relation')->insert(array('ROLE_ID'=>$role_id,'GROUP_ID'=>$group_id));
            }else{
                app_send('','400','请选择您要添加的分组');
            }

        }else{
            app_send('','400','该角色下已经存在分组，请勿重复添加');
        }
        app_send();
    }
    //角色分组【修改】
    public function role_group_edit()
    {

        $id = input('post.id');
        $group_id = input('post.group_id');
        $result = Db::name('rbac_role_group_relation')->where('ID',$id)->update(array('GROUP_ID'=>$group_id));
        if($result>0){
            app_send();
        }else{
            app_send('','400','角色分组，修改失败');
        }
    }
    //角色分组【删除】
    public function role_group_delete()
    {
        $id = input('post.id');
        $result = Db::name('rbac_role_group_relation')->where('ID',$id)->delete();
        if($result > 0){
            app_send();
        }else{
            app_send('','400','删除失败');
        }
    }
    public function role_ce()
    {
        $role_id = getActionUrl();
        var_dump($role_id);
    }
}
