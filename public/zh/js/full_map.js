// 页面初始化
function pageInit(){
  $('#map_container').height($('#main').height()-124+'px');
}
//添加列表数据
function box_select() { 
  var box_latest = JSON.parse(window.localStorage.getItem('new_boxLatest'));
  if(box_latest == null) {
    return false;
  }
  for(var i = 0; i < box_latest.length; i++) {
    if(box_latest[i].box_id == window.localStorage.getItem('box_id')){
      $("#box_select_list").append('<option selected value="' + box_latest[i].box_id + '">' + box_latest[i].name+'（'+box_latest[i].box_id+'）' + '</option>');
    }else{
      $("#box_select_list").append('<option value="' + box_latest[i].box_id + '">' + box_latest[i].name+'（'+box_latest[i].box_id+'）' + '</option>');
    }
  }
  window.localStorage.setItem('box_id',$("#box_select_list").val());
  var box = JSON.parse(window.localStorage.getItem('new_mapTable'));
  for(var i=0;i<box.length;i++){
    if(box[i].box_id == $("#box_select_list").val()){
      $('#info_box_id').html($("#box_select_list").val());
      $("#info_doorOpen").html(box[i].gps_door1);
      $("#info_gps_temp1").html(box[i].gps_temp1);
      $("#info_gps_temp2").html(box[i].gps_temp2);
      $("#info_gps_temp3").html(box[i].gps_temp3);
      $("#info_gps_door1").html(box[i].cooler_off_flag=='正常'?'运行':'关闭');
      $("#info_gps_voltage").html(box[i].gps_voltage);
      if(box[i].cooler_alarm_cnt == 0){
        $('.alarm_op').html('无');
        $('.alarm_op').css('color','#333');
      }else{
        var arr = getAlarmCode(box[i]).split(',');
        var str = '';
        for(var j=0;j<arr.length;j++){
          str += arr[j]+' → '+cooler_alarm_value(arr[j]);
        }
        $('.alarm_op').html(str);
        $('.alarm_op').css('color','red');
      }
    }
  }
  openBoxDetail();
  $("#box_select_list").multiselect({
    maxHeight: 400,
    includeSelectAllOption: true,
    enableFiltering: true,
    filterBehavior: 'text',
  });
}
$('#box_select_list').change(function(){
  openTarget($("#box_select_list").val());
  $('#maptable tr').removeClass('bg');
  window.localStorage.setItem('box_id',$("#box_select_list").val());
})
//给地图选择select赋值
var mapSelected = window.localStorage.getItem('mapType') == 'baidu' ? 0 : 1;
$("#changeMap").val(mapSelected);
//地图切换动作
$("#changeMap").change(function() {
  var mapType = $(this).val() == 0 ? 'baidu' : 'google';
  changeMap(mapType);
});
// 地图表格
$("#maptable").bootstrapTable({
  height: 235,
  showColumns: true,
	showExport: true,
	sortName: 'box_id',
	search: true,
	exportDataType: 'all',
  searchTimeOut: 700,
	columns: JSON.parse(window.localStorage.getItem('indexTableCol')),
	rowStyle:function(row){
		if(row.cooler_alarm_cnt!=0 && row.cooler_alarm_cnt != null){
			//报警
			return {css:{'background':'#FFF0F0','color':'red'}}		
		}else if(row.online == 0){
			//休眠
			return {css:{'background':'#ba84f6'}}	
		}else if(row.gps_voltage <= 12){
			//低频
			return {css:{'background':'orange'}}			
		}else{
			//在线
			return {css:{'background':'#00b050'}}		
		}
	},
	//保存用户设置显示列配置
	onColumnSwitch:function(field, checked){
		var indexTableCol = JSON.parse(window.localStorage.getItem('indexTableCol'));
		for(var i=0;i<indexTableCol.length;i++){
			if(indexTableCol[i].field == field){
				indexTableCol[i].visible = checked;
			}
		}
		window.localStorage.setItem('indexTableCol',JSON.stringify(indexTableCol));
		return false
	}
});
//获取地图表格数据
function getMapTable(){
  setTimeout(function(){
    getMapTable();
    box_group();
  },180000);
  $.ajax({
    async:false,
    cache:false,
    type:'POST',
		url: reqDomain + "/device/box_latest",
		dataType:'json',
		xhrFields: {
			withCredentials: true
		},
		success:function(data){
			if(data.code == '200'){
        showState(data.result);
        new MapTable(data.result)
				getAddr(data.result);
        window.localStorage.setItem('new_mapTable',JSON.stringify(data.result));        
			} else if(data.code == 401){
				window.location.href = "../login.html";
			}else if(data.code == 400){
				console.log(data.reason);
			}
		}
  });
}
//数据表格点击
$('#maptable').on('click-row.bs.table',function(e,row,element){
	$(element).addClass('bg').siblings().removeClass('bg');
	var box_id = row.box_id;
	if(box_id != window.localStorage.getItem('box_id')){
		window.localStorage.setItem('row',JSON.stringify(row));
    onClickTable(box_id);
  }
})
// 状态数显示
function showState(data){
	$('.map_circle .all').text('全部：' + getStateNum(data).allNum);
	$('.map_circle .onLine').text('在线：'+ getStateNum(data).onLineNum);
	$('.map_circle .outLine').text('离线：' + getStateNum(data).outLineNum);
	$('.map_circle .dormant').text('休眠：' + getStateNum(data).dormantNum);
	$('.map_circle .alarm').text('报警：' + getStateNum(data).alarmNum);
}
function MapTable(opt){
  this.data = opt;
	for(var i=0;i<opt.length;i++){
		this.data[i].name = opt[i].name || opt[i].box_id; //箱号
		this.data[i].box_id = opt[i].box_id;  //设备编号
		this.data[i].gps_humi = dealDataValue(opt[i].gps_humi, 'humi');	 //湿度
		this.data[i].gps_temp1 = dealDataValue(opt[i].gps_temp1, 'temp'); //温度1
		this.data[i].gps_temp2 = dealDataValue(opt[i].gps_temp2, 'temp'); //温度2
		this.data[i].gps_temp3 = dealDataValue(opt[i].gps_temp3, 'temp'); //温度3
		this.data[i].speed = dealDataValue(opt[i].speed, 'speed'); //速度
		this.data[i].gps_door1 = dealDataValue(opt[i].gps_door1, 'door1'); //门开关
		this.data[i].gps_voltage = dealDataValue(opt[i].gps_voltage, 'voltage'); //电压
		this.data[i].cooler_off_flag = dealDataValue(opt[i].cooler_off_flag, 'cooler_off_flag'); //冷机启动状态
		this.data[i].ambient_temp = dealDataValue(opt[i].ambient_temp,'ambient_temp'); //环境温度
		this.data[i].re_air_temp = dealDataValue(opt[i].re_air_temp, 'temp'); //回风温度
		this.data[i].out_air_temp = dealDataValue(opt[i].out_air_temp, 'temp'); //出风温度
		this.data[i].cooler_set_temp = dealDataValue(opt[i].cooler_set_temp, 'temp'); //设置温度
		this.data[i].oil_temp = dealDataValue(opt[i].oil_temp, 'temp'); //蒸发器盘管温度
		this.data[i].zone_alarm_code = opt[i].zone_alarm_code; //冷机故障码
		this.data[i].zone_status = dealDataValue(opt[i].zone_status, 'zone_status'); //冷机状态
    this.data[i].insert_time = formatTime(opt[i].insert_time); //数据时间
	}
	$('#maptable').bootstrapTable('load',this.data);
}
//MapTable.prototype.init = function(){
//	$('#maptable').bootstrapTable('load',this.data);
//}
//点击状态按钮切换表格显示
$('.all').parents('.map_circle').click(function(){
	var tableData = JSON.parse(window.localStorage.getItem('new_searchData_addr'));
	$('#maptable').bootstrapTable('load',tableData);
})
$('.onLine').parents('.map_circle').click(function(){
	var tableData = JSON.parse(window.localStorage.getItem('new_searchData_addr'));
	var data = [];
	for(var i=0;i<tableData.length;i++){
		if(tableData[i].online == 1 && tableData[i].cooler_alarm_cnt == 0 && tableData[i].gps_voltage > 12){
			data.push(tableData[i]);
		}
	}
	$('#maptable').bootstrapTable('load',data);
})
$('.outLine').parents('.map_circle').click(function(){
	var tableData = JSON.parse(window.localStorage.getItem('new_searchData_addr'));
	var data = [];
	for(var i=0;i<tableData.length;i++){
		if(tableData[i].online == 0 && tableData[i].cooler_alarm_cnt == 0){
			data.push(tableData[i]);
		}
	}
	$('#maptable').bootstrapTable('load',data);
})
$('.dormant').parents('.map_circle').click(function(){
  var tableData = JSON.parse(window.localStorage.getItem('new_searchData_addr'));
	var data = [];
	for(var i=0;i<tableData.length;i++){
    if(tableData[i].online == 1 && tableData[i].cooler_alarm_cnt == 0 && tableData[i].gps_voltage <= 12){
      data.push(tableData[i]);
		}
	}
	$('#maptable').bootstrapTable('load',data);
})
$('.alarm').parents('.map_circle').click(function(){
	var tableData = JSON.parse(window.localStorage.getItem('new_searchData_addr'));
	var data = [];
	for(var i=0;i<tableData.length;i++){
		if(tableData[i].cooler_alarm_cnt != 0 && row.cooler_alarm_cnt != null){
			data.push(tableData[i]);
		}
	}
	$('#maptable').bootstrapTable('load',data);
})
/*底部表格大小*/
$(".tablemap_title_right>div").click(function() {
  var index = $(this).index();
  if(index == 2) {
    /*最小化*/
    $(".bootstrap-table").hide();
    $(".not_all").css({
      "height": "31px",
      'top': '',
      "bottom": "0px",
      'position': 'absolute'
    })
    //$("#map_container").css("height", "822px");
  } else if(index == 1) {
    /*最大化*/
    $(".bootstrap-table").show();
    $(".not_all").css({
      "height": "100%",
      'position': 'absolute',
      'top': '61px'
    })
    //$("#map_container").css("height", "600px");
    $("#maptable").bootstrapTable('resetView', {
      height: 600
    });
  } else if(index == 0) {
    /*还原*/
    $(".bootstrap-table").show();
    $(".not_all").css({
      "height": "253px",
      'top': '',
      "bottom": "0px",
      'position': 'absolute'
    })
    $("#maptable").bootstrapTable('resetView', {
      height: 235
    });
    //$("#map_container").css("height", "600px");
  }
});
//设备分组列表
function box_group() {
  $.ajax({
    type: 'POST',
    url: reqDomain + "/device/group_list",
    data: {},
    dataType: "json",
    xhrFields: {
      withCredentials: true
    },
    success: function(data) {
      if(data.code == 200) {
        var html = "<div>设备列表</div>";
        var list = data.result;
        for(var i = 0; i < list.length; i++) {
          html += '<div class="more" onclick="group_box_list('+list[i].id+')">'+list[i].name+'<img src="../img/zhankai.png" width="10px"/></div>';
          html += '<ul class="detail_in" id="group_box_list'+list[i].id+'"></ul>';
        }
        $("#box_group_list").html(html);
      } else if(data.code == 401) {
        window.location.href = "../login.html";
      } else if(data.code == 400) {
        console.log(data.reason);
      }
    }
  });
}
//分组下设备的列表
function group_box_list(groupID) {
  $.ajax({
    async: false,
    cache: false,
    type: 'POST',
    url: reqDomain + "/device/box_latest",
    data: {
      "groupID": groupID
    },
    dataType: "json",
    xhrFields: {
      withCredentials: true
    },
    success: function(data) {
      if(data.code == 200) {
        var html = "";
        var list = data.result;
        for(var i = 0; i < list.length; i++) {
          if(list[i].name == null) {
            name = list[i].box_id;
          } else {
            name = list[i].name;
          }
          html += "<li onclick='onClickTable(" + list[i].box_id + ")'>" + name + "</li>"
        }
        $("#group_box_list" + groupID).append(html);
      } else if(data.code == 401) {
        window.location.href = "../login.html";
      } else if(data.code == 400) {
        alert(data.reason);
      }
    }
  })
}
//点击表格
function onClickTable(box_id){
  var box = JSON.parse(window.localStorage.getItem('new_mapTable'));
  for(var i=0;i<box.length;i++){
    if(box[i].box_id == box_id){
      $('#info_box_id').html(box_id);
      $("#info_doorOpen").html(box[i].gps_door1);
      $("#info_gps_temp1").html(box[i].gps_temp1);
      $("#info_gps_temp2").html(box[i].gps_temp2);
      $("#info_gps_temp3").html(box[i].gps_temp3);
      $("#info_gps_door1").html(box[i].cooler_off_flag=='正常'?'运行':'关闭');
      $("#info_gps_voltage").html(box[i].gps_voltage);
      if(box[i].cooler_alarm_cnt == 0){
        $('.alarm_op').html('无');
        $('.alarm_op').css('color','#333');
      }else{
        var arr = getAlarmCode(box[i]).split(',');
        var str = '';
        for(var j=0;j<arr.length;j++){
          str += arr[j]+' → '+cooler_alarm_value(arr[j]);
        }
        $('.alarm_op').html(str);
        $('.alarm_op').css('color','red');
      }
    }
  }
  window.localStorage.setItem('box_id',box_id);
  $('#box_select_list').val(box_id)
  $('#box_select_list').multiselect('refresh');
  openTarget(box_id);
  openBoxDetail();
}
// 显示设备信息
function openBoxDetail(){
  $(".op_tablemap img:eq(1)").trigger("click");
}
// 显示用户信息
function showOwner(){
  var userInfo = JSON.parse(window.localStorage.getItem('new_userInfo'));
  $('#info_realName').html(userInfo.realName);
  $('#info_tel').html(userInfo.tel);
}
//消息栏消息列表显示隐藏
$('#box_group_list').on('click','div',function() {
	var isclass = $(this).hasClass("yzk");
	if(isclass) {
		$(this).removeClass("yzk");
		$(this).next("ul").fadeOut();
		$(this).find("img").attr({"src": "../img/zhankai.png","width": "10px"});
	} else {
		$(this).addClass("yzk");
		$(this).next("ul").fadeIn();
		$(this).find("img").attr({"src": "../img/shouqi.png","width": "6px"});
	}
})
//侧栏显示状态切换
$(".op_tablemap img").click(function() {
  var index = $(this).index();
  if(index == 0) {
    $(this).attr("src", "../img/ivo02.png");
    $(this).next().attr("src", "../img/ivop01.png");
    $(".infomation_op").hide()
    $(".op" + index).show();
  } else if(index == 1) {
    $(this).attr("src", "../img/ivop02.png");
    $(this).prev().attr("src", "../img/ivo01.png");
    $(".infomation_op").hide()
    $(".op" + index).show();
  } else if(index == 2) {
    var has = $(this).hasClass("ov");
    if(has) {
      $(this).attr("src", "../img/p.png")
      $(".fullmap_right").css({
        "right": "-300px"
      });
      $(this).removeClass("ov");
    } else {
      $(this).attr("src", "../img/o.png")
      $(".fullmap_right").css({
        "right": "0"
      });
      $(this).addClass("ov");
    }
  }
});