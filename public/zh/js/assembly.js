document.title = '冷链监控系统';
//头部组件
function Header(id){
  this.id = id;
  this.init();
}
Header.prototype.init = function(){
  var str = '<div class="logo"><a href="'+window.localStorage.getItem('homePage')+'.html"><img src="../img/logo-railway.png" alt="logo" /><span>冷链监控系统</span></a></div>';
  str += '<div class="header_right"><div id="admin"><i class="glyphicon glyphicon-user"></i><span id="user"></span><img src="../img/sel.png" alt="" /><ul class="admin_info"><li id="top_user_info" data-toggle="modal" data-target="#myModal" onclick="user_info()">修改信息</li><li id="top_password_change" data-toggle="modal" data-target="#myModal2">修改密码</li><li id="logout" onclick="login_out()">退出登录</li></ul></div><div id="language"><i class="glyphicon glyphicon-comment"></i><ul><li>中文</li><!--<li>英文</li>--></ul></div><div id="system"><i class="glyphicon glyphicon-cog"></i><ul><li>监控系统</li><li>管理系统</li></ul></div><div id="toMap"><i class="glyphicon glyphicon-globe"></i><ul><li>大地图</li></ul></div></div>'
  $(this.id).html(str);
  $('#user').html(JSON.parse(window.localStorage.getItem('new_userInfo')).real_name)
  this.langInit();
  this.sysInit();
  this.userOperation();
  this.toMap();
  $('.header_right>div').hover(function(){
    $(this).find('ul').show();
  },function(){
    $(this).find('ul').hide();
  })
  $('#system ul li:eq(0)').click(function(){
    window.localStorage.setItem('systemType','monitor');
  })
  $('#system ul li:eq(1)').click(function(){
    window.localStorage.setItem('systemType','manage');
  })
}
// 初始化语言显示
Header.prototype.langInit = function(){
  if(window.localStorage.getItem('langType') == 'zh' || window.localStorage.getItem('langType') == null){
    $('#language ul li:eq(0)').addClass('active').siblings().removeClass('active');
  }else{
    $('#language ul li:eq(1)').addClass('active').siblings().removeClass('active');
  }
  $('#language ul li:eq(0)').click(function(){
    window.location.href = window.location.href.replace('en/', 'zh/');
  })
  $('#language ul li:eq(1)').click(function(){
    window.location.href = window.location.href.replace('zh/', 'en/');
  })
}
// 系统初始化
Header.prototype.sysInit = function(){  
  if(window.localStorage.getItem('systemType') == 'monitor'){
    $('#system ul li:eq(0)').addClass('active');
  }else if(window.localStorage.getItem('systemType') == 'manage'){
    $('#system ul li:eq(1)').addClass('active');
  }
  $('#system ul li:eq(0)').click(function(){
    if(window.localStorage.getItem('homePage') == 'warm'){
      window.location.href = 'warm.html';
    }else if(window.localStorage.getItem('homePage') == 'cold'){
      window.location.href = 'cold.html';
    }else{
      window.location.href = 'warm_car.html'
    }
  })
  // 非首页点击首页跳转地址
  if($('.breadcrumb li:eq(0)').find('a').text() == '首页'){
    if(window.localStorage.getItem('homePage') == 'warm'){
      $('.breadcrumb li:eq(0)').find('a').prop('href','warm.html');
    }else if(window.localStorage.getItem('homePage') == 'cold'){
      $('.breadcrumb li:eq(0)').find('a').prop('href','cold.html');
    }else{
      $('.breadcrumb li:eq(0)').find('a').prop('href','warm_car.html');
    }
  }
  $('#system ul li:eq(1)').click(function(){
    window.location.href = 'branch.html';
  })
}
// 初始化修改信息弹框
Header.prototype.userOperation = function(){
  var str = '<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel2"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button><h4 class="modal-title" id="myModalLabel2" data-field="top_info_change">修改信息</h4></div><div class="modal-body"><input type="hidden" id="userID"><input type="text" class="form-control" placeholder="姓名" id="top_realName"><input type="text" class="form-control" placeholder="电话" onkeyup="this.value=this.value.replace(/\D/g,"")" id="top_tel"><input type="text" class="form-control" placeholder="邮箱" id="top_email"><div class="modal_radio"><input type="radio" name="gender" value="男" checked="checked" id="man" />&nbsp;<span id="top_man">男</span><div></div><input type="radio" name="gender" value="女" id="woman" />&nbsp;<span id="top_woman">女</span></div></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal" id="top_close">关闭</button><button type="button" class="btn btn-primary" onclick="user_info_change()" id="top_change">修改</button></div></div></div></div>';
  str += '<div class="modal fade" id="myModal2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel2"><div class="modal-dialog" role="document"><div class="modal-content"><div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button><h4 class="modal-title" id="myModalLabel2" data-field="top_password_change1">修改密码</h4></div><div class="modal-body"><input type="text" class="form-control" placeholder="原密码" id="oldPassword"><input type="text" class="form-control" placeholder="新密码" id="newPassword"></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal" id="top_close1">关闭</button><button type="button" class="btn btn-primary" onclick="user_password_change()" id="top_change1">修改</button></div></div></div></div>';
  $('body').prepend(str);
}
//进入地图初始化
Header.prototype.toMap = function(){
  $('#toMap ul li').click(function(){
    if(window.localStorage.getItem('homePage') == 'warm'){
      window.location.href = 'full_map1.html';
    }else if(window.localStorage.getItem('homePage') == 'cold'){
      window.location.href = 'full_map.html';
    }else{
      window.location.href = 'full_map2.html';
    }
  })
}
//修改个人信息
function user_info_change() {
  var realName = $("#top_realName").val();
  var tel = $("#top_tel").val();
  var email = $("#top_email").val();
  var gender = $('input:radio[name="gender"]:checked').val();
  var text = "请输入姓名";
  if(realName == "") {
    layer.alert(text);
  } else {
    $.ajax({
      async: false,
      cache: false,
      type: 'POST',
      url:  reqDomain + "/welcome/user_info_change",
      data: {
        "realName": realName,
        "tel": tel,
        "email": email,
        "gender": gender
      },
      dataType: "json",
      xhrFields: {
        withCredentials: true
      },
      success: function(data) {
        if(data.code == 200) {
          layer.msg(data.reason);
          user_info();
        } else if(data.code == 401) {
          layer.alert(data.reason);
          window.location.href = "../login.html";
        } else if(data.code == 400) {
          layer.alert(data.reason);
        }
      }
    });
  }
}
//个人信息
function user_info() {
  $.ajax({
    async: false,
    cache: false,
    type: 'POST',
    url: reqDomain + "/welcome/user_info",
    data: {},
    dataType: "json",
    xhrFields: {
      withCredentials: true
    },

    success: function(data) {
      if(data.code == 200) {
        $("#top_realName").val(data.result.real_name);
        $("#top_tel").val(data.result.tel);
        $("#top_email").val(data.result.email);
        if(data.result.gender == "男") {
          $("#man").prop("checked", true)
        } else if(data.result.gender == "女") {
          $("#woman").prop("checked", true)
        }
      } else if(data.code == 401) {
        layer.alert(data.reason);
        window.location.href = "../login.html";
      } else if(data.code == 400) {
        layer.alert(data.reason);
      }
    }
  });
}
//修改密码
function user_password_change() {
  var oldPassword = $("#oldPassword").val();
  var newPassword = $("#newPassword").val();
  var text = "确认要修改密码吗？";
  var text1 = "请输入旧密码";
  var text2 = "请输入新密码";
  if(oldPassword == "") {
    layer.alert(text1);
    return;
  } else if(newPassword == "") {
    layer.alert(text2);
    return;
  }
  if(window.confirm(text)){
    $.ajax({
      async: false,
      cache: false,
      type: 'POST',
      url: reqDomain + "/welcome/user_password_change",
      data: {
        "oldPassword": oldPassword,
        "newPassword": newPassword
      },
      dataType: "json",
      xhrFields: {
        withCredentials: true
      },
      success: function(data) {
        if(data.code == 200) {
          layer.alert(data.reason);
          window.location.href = "../login.html";
        } else if(data.code == 401) {
          layer.alert(data.reason);
          window.location.href = "../login.html";
        } else if(data.code == 400) {
          layer.alert(data.reason);
        }
      }
    });
  }
}
//退出登录
function login_out() {
  layer.confirm('确定要退出登录么？',function(){
    $.ajax({
      async: false,
      cache: false,
      type: 'POST',
      url: reqDomain + "/welcome/user_login_out",
      data: {},
      dataType: "json",
      xhrFields: {
        withCredentials: true
      },
      success: function(data) {
        if(data.code == 200) {
          window.location.href = "../login.html";
        } else if(data.code == 401) {
          layer.alert(data.reason);
          window.location.href = "../login.html";
        } else if(data.code == 400) {
          layer.alert(data.reason);
        }
      }
    });
  })
}
//监控系统导航组件
function Nav(id){
  this.id = id;
  this.init();
}
Nav.prototype.init = function(){
  str = '<div class="navList"><img src="../img/index.png" alt="" /><span>监控</span><div><a href="cold.html">冷藏箱</a><a href="warm.html">保温箱</a><a href="warm_car.html">保温车</a></div></div>';  
  str += '<div class="navList"><img src="../img/sheb.png" alt="" /><span>设备</span><div><a href="equipment_list.html">设备列表</a><a href="equipment_group.html">设备分组</a></div></div>';
  str += '<div class="navList"><img src="../img/xingc.png" alt="" /><span>箱管</span><div><a href="route_list.html">行程列表</a><a href="yard.html">货场操作</a><a href="damage.html">箱损列表</a></div></div>';
  str += '<div class="navList"><img src="../img/baob.png" alt="" /><span>报表</span><div><a href="report_form.html">运行时长</a></div></div>';
  str += '<div class="navList"><img src="../img/jil.png" alt="" /><span>记录</span><div><a href="equipment_data.html">设备数据</a><a href="data_alarm.html">报警数据</a><a href="operation_log.html">操作日志</a></div></div>';
  str += '<a href="http://47.92.231.64:81/docs/th_v2-0/th_v2-0-1bndml8rcume2"><img src="../img/bangz.png" alt="" />  <span>帮助</span></a>';
  $(this.id).html(str);
  $('.navList').hover(function() {
		$(this).find('div').show();
	}, function() {
		$(this).find('div').hide();
  })
  var url = window.location.href;
  var index = url.indexOf('zh/');
  url = url.substring(index+3,url.length)
  $(this.id).find('a').each(function(){
    if($(this).attr('href').indexOf(url) != -1){
      if($(this).parent('div').attr('id')){
        $(this).addClass('current')
      }else{
        $(this).addClass('current');
        $(this).parents('div.navList').addClass('current')
      }
    }
  })
}
//管理系统导航组件
function ManageNav(id){
  this.id = id;
  this.init();
}
//管理系统导航初始化
ManageNav.prototype.init = function(){
  var str = '<a href="branch.html"><i class="fa fa-building-o fa-fw"></i>部门</a>';
  str += '<a href="role.html"><i class="fa fa-user-circle fa-fw"></i>角色</a>';
  str += '<a href="user.html"><i class="fa fa-user-o fa-fw"></i>用户</a>';
  // str += '<div class="navList"><i class="fa fa-desktop fa-fw"></i>排班<div><a href="work_station.html"></i>工位</a><a href="scheduling.html"></i>班次</a><a href="schedule.html">排班</a><a href="monitor.html">监控</a></div></div>';
  str += '<div class="navList"><i class="fa fa-desktop fa-fw"></i>排班<div><a href="work_station.html"></i>工位</a><a href="scheduling.html"></i>班次</a><a href="schedule.html">排班</a></div></div>';
  $(this.id).html(str);
  var url = window.location.href;
  var index = url.indexOf('zh/');
  url = url.substring(index+3,url.length)
  $(this.id).find('a').each(function(){
    if($(this).attr('href').indexOf(url) != -1){
      if($(this).parent('div').attr('id')){
        $(this).addClass('current')
      }else{
        $(this).addClass('current');
        $(this).parents('div.navList').addClass('current')
      }
    }
  })
}
