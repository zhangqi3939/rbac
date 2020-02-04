// 页面初始化
function pageInit(){
	$('#tabbar li').attr('myload','unload');
	window.localStorage.removeItem('box_id');
	window.localStorage.removeItem('row');
	window.localStorage.removeItem('new_boxLatest');
	window.localStorage.removeItem('new_searchData');
	window.localStorage.removeItem('new_searchData_addr');
	window.localStorage.setItem('homePage','cold');
	getUserId();
	monitor_latest();
}
//获取用户id
function getUserId(){
	$.ajax({
		async: false,
		type: 'post',
		url: reqDomain + "/welcome/user_info",
		dataType: "json",
		xhrFields: {
			withCredentials: true
		},
		success: function(data){
			if(data.code == 200){
				window.userId = data.result.id;
			} else if(data.code == 401){
				window.location.href = "../login.html";
			}else if(data.code == 400){
				alert(data.reason);
			}
		}
	})
}
//获取最新监控时间
function monitor_latest() {
	$.ajax({
		async: false,
		type: 'post',
		url: reqDomain + "/schedule/monitor_latest",
		dataType: "json",
		xhrFields: {
			withCredentials: true
		},
		success: function(data){
			if(data.code == 200){
				window.latestT = null;
				latestT = data.result;
			} else if(data.code == 401){
				window.location.href = "../login.html";
			}else if(data.code == 400){
				alert(data.reason);
			}
		}
	})
}
// 分组列表
function getGroupList() {
	$.ajax({
		type: 'POST',
		url: reqDomain + "/device/group_listing",
		dataType: "json",
		xhrFields: {
			withCredentials: true
		},
		success: function(data) {
			if(data.code == 200) {
				$('#groupList').empty().prepend("<option value=''>所有分组</option>");
				for(var i = 0; i < data.result.length; i++) {
					new GroupList(data.result[i]);
        }
        $('#groupList').multiselect({
          includeSelectAllOption: true,
          enableFiltering: true,
          allSelectedText:'所有分组',
          maxHeight: 300,
        })
			} else if(data.code == 401){
				window.location.href = "../login.html";
			}else if(data.code == 400){
				console.log(data.reason);
			}
		}
	});
}
function GroupList(opt){
  this.id = opt.id;
  this.name = opt.name;
  this.init();
}
GroupList.prototype.init = function(){
  var op = document.createElement('option');
  op.value = this.id;
  op.innerHTML = this.name;
  $('#groupList').append(op);
}
// 切换分组
$('#groupList').change(function(){
	var tableData = JSON.parse(window.localStorage.getItem('new_searchData'));
	var data = [];
	if($(this).val() == ''){
		$('#indexTable').bootstrapTable('load',tableData);
	}else{
		for(var i=0;i<tableData.length;i++){
			if(tableData[i].groupID == $(this).val()){
				data.push(tableData[i])
			}
		}
		$('#indexTable').bootstrapTable('load',data);
	}
})
// 表格变宽全屏
$('#changeWidth').click(function(){
	if($('#changeWidth').hasClass('active')){
		$('#changeWidth').removeClass('active');
		$('.map').css('width','100%');
		$('.boxLatestTable .fixed-table-container').css('width','100%');
	}	else{
		$('#changeWidth').addClass('active');
		$('.boxLatestTable .fixed-table-container').css('width','167%');
		$('.map').css('width','0');
	}
	$('.fixed-table-body-columns').css('height','311px');
	$('#changeFull').removeClass('active');
	$('#tabMain').css({'height':'','overflow':'visible'});
	$('#tabbar').css({'height':'','overflow':'visible'});
	$('#indexTable').bootstrapTable('resetView',{
		height:350
	});
})
$('#changeFull').click(function(){
	if($('#changeFull').hasClass('active')){
		$('#changeFull').removeClass('active');
		$('.map').css('width','100%');
		$('.boxLatestTable .fixed-table-container').css('width','100%');
		$('#tabMain').css({'height':'','overflow':'visible'});
		$('#tabbar').css({'height':'','overflow':'visible'});
		$('#indexTable').bootstrapTable('resetView',{
			height:350
		});
		$('.fixed-table-body-columns').css('height','311px');
	}	else{
		$('#changeFull').addClass('active');
		$('.boxLatestTable .fixed-table-container').css('width','167%');
		$('.map').css('width','0');
		$('#tabMain').css({'height':'0','overflow':'hidden'});
		$('#tabbar').css({'height':'0','overflow':'hidden'});
		$('#indexTable').bootstrapTable('resetView',{
			height:$(window).height() - 110
		});
	}
	$('#changeWidth').removeClass('active');
})
// 表格搜索
function searchBox(){
	$.ajax({
		type:'POST',
		url:reqDomain + "/device/box_latest",
		data:{
			"box_category": '01',
			"groupID": $("#groupList").find("option:selected").val(),
			"travelID": $("#travelList").find("option:selected").val()
		},
		dataType:'json',
		xhrFields: {
			withCredentials: true
		},
		success:function(data){
			if(data.code == '200'){
				showState(data.result);
				new BoxLatest(data.result);
				zoneMsg(data.result);
				insert_time(data.result);
				needMonList();
				getAddr(data.result);
				if($("#groupList").find("option:selected").val()==''&&$('.boxLatestTable .search .form-control').val()==''){
					window.localStorage.setItem('new_boxLatest',JSON.stringify(data.result));
				}
				window.localStorage.setItem('new_searchData',JSON.stringify(data.result));
				mapBtnInit();
			} else if(data.code == 401){
				window.location.href = "../login.html";
			}else if(data.code == 400){
				console.log(data.reason);
			}
		}
	})
}
function BoxLatest(opt){
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
		this.data[i].ambient_temp = F2C(dealDataValue(opt[i].ambient_temp,'ambient_temp')); //环境温度
		this.data[i].re_air_temp = F2C(dealDataValue(opt[i].re_air_temp, 'temp')); //回风温度
		this.data[i].out_air_temp = F2C(dealDataValue(opt[i].out_air_temp, 'temp')); //出风温度
		this.data[i].cooler_set_temp = F2C(dealDataValue(opt[i].cooler_set_temp, 'temp')); //设置温度
		this.data[i].oil_temp = F2C(dealDataValue(opt[i].oil_temp, 'temp')); //蒸发器盘管温度
		this.data[i].zone_alarm_code = opt[i].zone_alarm_code; //冷机故障码
		this.data[i].zone_status = dealDataValue(opt[i].zone_status, 'zone_status'); //冷机状态
		this.data[i].insert_time = formatTime(opt[i].insert_time); //数据时间
	}
	this.init();
}
BoxLatest.prototype.init = function(){
	$('#indexTable').bootstrapTable('load',this.data);
}
//数据表格配置
var indexTableCol = [
	{
		title: '箱号',
		field: 'name',
		align:'center'
	},
	{
		title: 'ID',
		field: 'box_id',
		sortable: true,
		visible:false,
		align:'center'
	},
	{
		title: '所在分组',
		field: 'group_name',
		formatter: function(value){
			return value==null?'-':value
		},
		align:'center',
	},
	{
		title: '设定温度',
		field: 'cooler_set_temp',
		sortable: true,
		align:'center',
	},
	{
		title: '回风温度',
		field: 're_air_temp',
		sortable: true,
		align:'center',
	},
	{
		title: '送风温度',
		field: 'out_air_temp',
		sortable: true,
		align:'center',
	},		
	{
		title: '箱中温',
		field: 'gps_temp2',
		sortable: true,
		align:'center'
	},
	{
		title: '箱后温',
		field: 'gps_temp3',
		sortable: true,
		align:'center'
	},
	{
		title: '箱外温',
		field: 'gps_temp1',
		sortable: true,
		align:'center'
	},
	{
		title: '湿度',
		field: 'gps_humi',
		sortable: true,
		align:'center',
		formatter:function(value){
			console.log(value);
			if(value == null){
				return '-%';
			}
			return value + '%';
		}
	},
	{
		title: '速度',
		field: 'speed',
		sortable: true,
		align:'center',
		visible:false,
	},
	{
		title: '门开关',
		field: 'gps_door1',
		sortable: true,
		align:'center'
	},
	{
		title: '电压',
		field: 'gps_voltage',
		sortable: true,
		align:'center'
	},
	{
		title: '通讯状态',
		field: 'cooler_off_flag',
		sortable: true,
		align:'center'
	},
	{
		title: '环境温度',
		field: 'ambient_temp',
		sortable: true,
		align:'center',
		visible:false,
	},	
	{
		title: '蒸发器盘管温度',
		field: 'oil_temp',
		sortable: true,
		align:'center',
		visible:false,
	},
	{
		title: '冷机故障码',
		field: 'zone_alarm_code',
		sortable: true,
		align:'center',
		visible:false,
	},
	{
		title: '冷机状态',
		field: 'zone_status',
		sortable: true,
		align:'center',
		visible:false,
	},		
	{
		title:'省',
		field: 'province',
		sortable: true,
		align:'center',
		visible:false,
	},
	{
		title:'市',
		field: 'city',
		sortable: true,
		align:'center',
		visible:false,
	},
	{
		title:'地址',
		field: 'addr',
		sortable: true,
		align:'center'
	},
	{
		title: '数据时间',
		field: 'insert_time',
		sortable: true,
		align:'center'
	}
];
if(!window.localStorage.getItem('indexTableCol')){
	window.localStorage.setItem('indexTableCol',JSON.stringify(indexTableCol));
}
$("#indexTable").bootstrapTable({
	height: 350,
	showColumns: true,
	showExport: true,
	sortName: 'box_id',
	search: true,
	exportDataType: 'all',
	searchTimeOut: 700,
	fixedColumns: true,//固定列
	fixedNumber:1,//固定前两列
	columns: JSON.parse(window.localStorage.getItem('indexTableCol')),
	rowStyle:function(row){
		if(row.cooler_alarm_cnt!=0){
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
})
//数据表格搜索
$('#indexTable').on('search.bs.table',function(){
	var data = $(this).bootstrapTable('getData');
	showState(data);
})
//数据表格点击
$('#indexTable').on('click-row.bs.table',function(e,row,element){
	$(element).addClass('bg').siblings().removeClass('bg');
	var nowDate = new Date().getTime();
	for(var i=0;i<insertT.length;i++){
		if(insertT[i].box_id == row.box_id && nowDate - insertT[i].insert_time > 180000){
			insertT[i].insert_time = nowDate;
			insertT[i].name = row.name;
			monitoring_time(insertT[i].box_id);
			break;
		}
	}
	var box_id = row.box_id;
	if($('.monList li').length != 0){
		for(var j=0;j<$('.monList li').length;j++){
			if($('.monList li:eq('+j+')').prop('class') == box_id){
				$('.monList li:eq('+j+')').remove();
				$('.msgList .more:eq(3)').html('需要监控（' + $('.monList li').length + '条）<img src="../img/zhankai.png" width="10px"/>');
			}else{
				continue;
			}
		}
	}
	if(box_id != window.localStorage.getItem('box_id')){
		openTarget(box_id);
		$('.equiName').html('设备：'+row.name+'（'+row.box_id+'）');
		$('#tabbar li').attr('myload','unload');
		window.localStorage.setItem('box_id',box_id);
		window.localStorage.setItem('row',JSON.stringify(row));
		tab(row);
	}
})
// 状态数显示
function showState(data){
	$('.stateBtn .all span').text(getStateNum(data).allNum);
	$('.stateBtn .onLine span').text(getStateNum(data).onLineNum);
	$('.stateBtn .outLine span').text(getStateNum(data).outLineNum);
	$('.stateBtn .dormant span').text(getStateNum(data).dormantNum);
	$('.stateBtn .alarm span').text(getStateNum(data).alarmNum);
}
//点击状态按钮切换表格显示
$('.stateBtn .all').click(function(){
	var tableData = JSON.parse(window.localStorage.getItem('new_searchData_addr'));
	$('#indexTable').bootstrapTable('load',tableData);
})
$('.stateBtn .onLine').click(function(){
	var tableData = JSON.parse(window.localStorage.getItem('new_searchData_addr'));
	var data = [];
	for(var i=0;i<tableData.length;i++){
		if(tableData[i].online == 1 && tableData[i].cooler_alarm_cnt == 0 && tableData[i].gps_voltage > 12){
			data.push(tableData[i]);
		}
	}
	$('#indexTable').bootstrapTable('load',data);
})
$('.stateBtn .outLine').click(function(){
	var tableData = JSON.parse(window.localStorage.getItem('new_searchData_addr'));
	var data = [];
	for(var i=0;i<tableData.length;i++){
		if(tableData[i].online == 0 && tableData[i].cooler_alarm_cnt == 0){
			data.push(tableData[i]);
		}
	}
	$('#indexTable').bootstrapTable('load',data);
})
$('.stateBtn .dormant').click(function(){
	var tableData = JSON.parse(window.localStorage.getItem('new_searchData_addr'));
	var data = [];
	for(var i=0;i<tableData.length;i++){
		if(tableData[i].online == 1 && tableData[i].cooler_alarm_cnt == 0 && tableData[i].gps_voltage <= 12){
			data.push(tableData[i]);
		}
	}
	$('#indexTable').bootstrapTable('load',data);
})
$('.stateBtn .alarm').click(function(){
	var tableData = JSON.parse(window.localStorage.getItem('new_searchData_addr'));
	var data = [];
	for(var i=0;i<tableData.length;i++){
		if(tableData[i].cooler_alarm_cnt != 0){
			data.push(tableData[i]);
		}
	}
	$('#indexTable').bootstrapTable('load',data);
})
// 消息列表冷机故障
function zoneMsg(data){
	$(".msgList .zone").empty();
	var zoneNum = Number(getStateNum(data).alarmNum);	
	$('.msgList .more:eq(0)').html('冷机故障（' + zoneNum + '条）<img src="../img/zhankai.png" width="10px"/>');
	if(zoneNum != 0){
		// 弹出侧栏
		if($('#msgList>img').attr('src')=='../img/p.png'){
			$('#msgList>img').removeClass('load');
			$('#msgList>img').trigger('click');
		}
		$('.msgList .more:eq(0)').css('color','red');
		if(timer){
			clearInterval(timer);
		}else{
			var timer = null;
			var flag = null;
		}
		timer = setInterval(function(){
			if(flag){
				$('.stateBtn .alarm i').css('color', 'red');
			}else{
				$('.stateBtn .alarm i').css('color', 'white');
			}
			flag = !flag;
		}, 500);
	}else{
		clearInterval(timer);
	}
	for(var i=0;i<data.length;i++){
		if(data[i].cooler_alarm_cnt != 0){
			$(".msgList .zone").append('<li id="' + data[i].id + '" class="' + data[i].box_id + '">' + (data[i].name == null ? data[i].box_id : data[i].name) + ':' + getAlarmCode(data[i]) + '</li>');
		}
	}
}
// 需要监控设备列表
function needMonList(){
	$(".msgList .monList").empty();
	var nowData = new Date().getTime();
	var num = 0;
	for(var i=0;i<insertT.length;i++){
		if(nowData - insertT[i].insert_time > 1200000){
			var time = ((nowData - insertT[i].insert_time)/1000).toFixed(0);
			num++;
			$('.monList').append('<li class="'+insertT[i].box_id+'">'+insertT[i].name+'长时间未监控</li>');
		}
	}
	if(num != 0){
		$('.msgList .more:eq(3)').css('color','red');
		if($('#msgList>img').attr('src')=='../img/p.png'){
			$('#msgList>img').removeClass('load');
			$('#msgList>img').trigger('click');
		}
	}
	$('.msgList .more:eq(3)').html('需要监控（' + num + '条）<img src="../img/zhankai.png" width="10px"/>');
}
//消息列表报警消息、行驶消息
function msgList(){
	$.ajax({
		type: "POST",
		url: reqDomain + "/alarm/alarm_index",
		dataType: "json",
		xhrFields: {
			withCredentials: true
		},
		success: function (data) {
			if(data.code == '200'){
				alarmMsg(data.result.dataList.alarm);
				travelMsg(data.result.dataList.travel);
			}else if(data.code == 401){
				window.location.href = "../login.html";
			}else if(data.code == 400){
				console.log(data.reason);
			}
		}
	});
}
function alarmMsg(data){
	$('.msgList .more:eq(1)').html('报警消息（' + data.length + '条）<img src="../img/zhankai.png" width="10px"/>');
	if(data.length != 0){
		$('.msgList .more:eq(1)').css('color',"red");
	}
	for(var i=0;i<data.length;i++){
		$('.msgList .alarm').append('<li id="' + data[i].id + '" class="' + data[i].box_id + '">' + data[i].title + '</li>')
	}
}
function travelMsg(data){
	$('.msgList .more:eq(2)').html('行驶消息（' + data.length + '条）<img src="../img/zhankai.png" width="10px"/>');
	if(data.length != 0){
		$('.msgList .more:eq(2)').css('color',"red");
	}
	for(var i=0;i<data.length;i++){
		$('.msgList .travel').append('<li id="' + data[i].id + '" class="' + data[i].box_id + '">' + data[i].title + '</li>')
	}
}
//消息栏消息列表显示隐藏
$('.msgList .more').click(function() {
	var isclass = $(this).hasClass("yzk");
	if(isclass) {
		$(this).removeClass("yzk");
		$(this).next("ul").fadeOut();
		$(this).find("img").attr({"src": "../img/zhankai.png","width": "10px"});
		if($(this).index()==0){
			$('.msgListDetail').html('');
		}
	} else {
		$(this).addClass("yzk");
		$(this).next("ul").fadeIn();
		$(this).find("img").attr({"src": "../img/shouqi.png","width": "6px"});
	}
})
//点击消息切换表格显示
$('.msgList ul').on('click','li',function(){
	var searchData = JSON.parse(window.localStorage.getItem('new_searchData'));
	for(var i=0;i<searchData.length;i++){
		if(searchData[i].box_id == $(this).attr('class')){
			$('#indexTable').bootstrapTable('load',[searchData[i]])
		}
	}
})
//点击侧栏消息切换表格数据
$('.zone').on('click','li',function(){
	var str = $(this).text();
	var name = str.split(':')[0];
	var code = str.split(':')[1].split(',');
	var html = '<p>'+name+'：<span>'
	for(var j=0;j<code.length;j++){
		html += code[j] + ' → ' + cooler_alarm_value(code[j]);
	}
	html += '</span></p>';
	$('.msgListDetail').html(html);
})
//消息栏显示隐藏
$("#msgList>img").click(function() {
	// 第一次点击侧栏展开按钮加载
	if(!$(this).attr('class')){
		$(this).addClass('load');
		msgList();
	}
	var flag = $(this).attr("src");
	if(flag == "../img/p.png") {
		$(this).attr("src", "../img/o.png");
		$('#msgList').animate({'right': '0'},500);
		$('#mask').fadeIn(500);
	} else {
		$(this).attr("src", "../img/p.png");
		$('#msgList').animate({'right': '-300px'},500);
		$('#mask').fadeOut(500);
	}
})
//点击遮罩层隐藏消息栏
$('#mask').click(function(){
	$('#msgList>img').attr("src", "../img/p.png");
	$('#msgList').animate({'right': '-300px'},500);
	$(this).fadeOut(500);
})
// 地图按钮显示和切换
function mapBtnInit(){
	var mapType = window.localStorage.getItem('mapType');
	if(mapType==null||mapType=='baidu'){
		$('.mapBtn:eq(0)').addClass('on').siblings().removeClass('on');
		changeMap('baidu');
	}else{
		$('.mapBtn:eq(1)').addClass('on').siblings().removeClass('on');
		changeMap('google');
	}
}
$(".mapBtn").click(function(){
	$(this).addClass('on').siblings().removeClass('on');
	if($(this).index() == 0){
		window.localStorage.setItem('mapType','baidu');
		changeMap('baidu');
	}else{
		window.localStorage.setItem('mapType','google');
		changeMap('google');
	}
})
// 底部功能切换
$('#tabbar>li').click(function(){
	var index = $(this).index();
	$(this).addClass('current').siblings().removeClass('current');
	$('#tabMain>li').eq(index).addClass('current').siblings().removeClass('current');
	tab(JSON.parse(window.localStorage.getItem('row')));
})
//状态信息
function boxInfo(opt){
	$.ajax({
		type: "POST",
		url: reqDomain + "/device/box_info",
		data: {
			box_id:opt.box_id
		},
		xhrFields: {
			withCredentials: true
		},
		success: function (data) {
			if(data.code == '200'){
				new GetBoxInfo(data.result,opt);
			}else if(data.code == 401){
				window.location.href = "../login.html";
			}else if(data.code == 400){
				console.log(data.reason);
			}
		}
	});
}
function GetBoxInfo(opt,row){
	this.data = opt;
	this.row = row;
	this.init();
}
GetBoxInfo.prototype.init = function(){
	var states = coolerStatusIcon(coolerStatus(this.data.cooler_off_flag, this.data.gps_door1, this.data.reserve7, this.data.cooler_off_flag, this.row.box_id));
	var coolerStr = '<dt>冷机</dt>';
	coolerStr += '<dd>回风温度：'+F2C(dealDataValue(this.data.re_air_temp, 'temp'))+'（℃）</dd>';
	coolerStr += '<dd>通讯状态：'+this.row.cooler_off_flag+'</dd>';
	coolerStr += '<dd>蒸发器盘管温度：'+F2C(dealDataValue(this.data.oil_temp, 'temp'))+'（℃）</dd>';
	coolerStr += '<dd>设定温度：'+F2C(dealDataValue(this.data.cooler_set_temp, 'temp'))+'（℃）</dd>';
	coolerStr += '<dd>状态信息：'+(this.row.cooler_off_flag=="正常"?"开启":"关闭")+'</dd>';
	coolerStr += '<dd>故障码：'+getAlarmCode(this.data)+'</dd>';
	coolerStr += '<dd>出风温度：'+F2C(dealDataValue(this.data.out_air_temp, 'temp'))+'（℃）</dd>';
	coolerStr += '<dd>告警信息：'+this.data.zone_alarm_code+'</dd>';
	coolerStr += '<dd></dd>';
	coolerStr += '<dd>环境温度：'+F2C(dealDataValue(this.data.ambient_temp,'ambient_temp'))+'（℃）</dd>';
	coolerStr += '<dd>冷机状态：'+states[0]+'</dd>';
	document.getElementsByClassName('cooler')[0].innerHTML = coolerStr;
	if(dealDataValue(this.data.speed, 'speed') <= 5){
		var direction = '-';
	}else{
		var direction = parseDirection(this.data.direction);
	}
	var refrigeratorStr = '<dt>传感器</dt>';
	refrigeratorStr += '<dd>箱外温度：'+dealDataValue(this.data.gps_temp1, 'temp')+'（℃）</dd>';
	refrigeratorStr += '<dd>湿度：'+dealDataValue(this.data.gps_humi, 'humi')+'（%）</dd>';
	refrigeratorStr += '<dd>速度：'+dealDataValue(this.data.speed, 'speed')+'（Km/h）</dd>';
	refrigeratorStr += '<dd>箱内中部温度：'+dealDataValue(this.data.gps_temp2, 'temp')+'（℃）</dd>';
	refrigeratorStr += '<dd>电压：'+dealDataValue(this.data.gps_voltage, 'voltage')+'（V）</dd>';
	refrigeratorStr += '<dd>方向：'+direction+'</dd>';
	refrigeratorStr += '<dd>箱内后部温度：'+dealDataValue(this.data.gps_temp3, 'temp')+'（℃）</dd>';
	refrigeratorStr += '<dd>平均温度：'+dealDataValue(calAverageTemp([this.data.gps_temp2, this.data.gps_temp3]), "temp")+'（℃）</dd>';
	refrigeratorStr += '<dd>门状态：'+dealDataValue(this.data.gps_door1, 'door1')+'</dd>';
	refrigeratorStr += '<dd style="width:100%;" title="'+this.row.addr+'" style="overflow: hidden;	text-overflow:ellipsis;	white-space: nowrap;">地址：'+this.row.addr+'</dd>';
	document.getElementsByClassName('refrigerator')[0].innerHTML = refrigeratorStr;
}
//状态信息曲线图
function boxData(opt){
	//出风温度
	var data_out_air_temp = [];
	//回风温度
	var data_re_air_temp = [];
	//温度1
	var data_gps_temp1 = [];
	//温度2
	var data_gps_temp2 = [];
	//温度3
	var data_gps_temp3 = [];
	//湿度
	var data_gps_humi = [];
	//环境温度
	var data_ambient_temp = [];
	//蒸发器盘管温度
	var data_oil_temp = [];
	//油位
	var data_gps_oil_level = [];
	//电压
	var data_gps_voltage = [];
	//CO2
	var data_gps_co2 = [];
	//日期时间
	var datatime = [];
	//设定温度
	var data_cooler_set_temp = [];
	$.ajax({
		async: false,
		cache: false,
		type: 'POST',
		url: reqDomain + "/device/box_data", // 请求的action路径
		data: {
			"box_id": opt.box_id
		},
		dataType: "json",
		xhrFields: {
			withCredentials: true
		},
		success: function(data) {
			if(data.code == '200') {
				for(var i = 0; i < data.result.length; i++) {
					data_out_air_temp[i] = F2C(dealDataValue(data.result[i].out_air_temp, 'temp'));
					data_re_air_temp[i] = F2C(dealDataValue(data.result[i].re_air_temp, 'temp'));
					data_gps_temp1[i] = dealDataValue(data.result[i].gps_temp1, "temp");
					data_gps_temp2[i] = dealDataValue(data.result[i].gps_temp2, "temp");
					data_gps_temp3[i] = dealDataValue(data.result[i].gps_temp3, "temp");
					data_gps_humi[i] = dealDataValue(data.result[i].gps_humi, "humi");
					data_ambient_temp[i] = F2C(dealDataValue(data.result[i].ambient_temp, 'ambient_temp'));
					data_oil_temp[i] = F2C(dealDataValue(data.result[i].oil_temp, 'temp'));
					data_gps_oil_level[i] = dealDataValue(data.result[i].gps_oil_level, "");
					data_gps_voltage[i] = dealDataValue(data.result[i].gps_voltage, "voltage");
					data_gps_co2[i] = dealDataValue(data.result[i].gps_co2, "");
					datatime[i] = formatTime(parseInt(data.result[i].insert_time));
					data_cooler_set_temp[i] = F2C(dealDataValue(data.result[i].cooler_set_temp, 'temp'))
				}
				//冷机
				var chart1 = echarts.init(document.getElementById("chart1"));
				option = null;
				option = {
					title: {
						text: '冷机',
						x: '8',
						y: '10',
						textStyle: {
							color: '#1199D3',
							fontSize: '15'
						},
					},
					tooltip: {
						trigger: 'axis'
					},
					legend: {
						data: ['环境温度', '回风温度', '出风温度', '蒸发器盘管温度', '设定温度'],
						right: 15,
						top: 10,
						left:65
					},
					grid: {
						left: '2%',
						right: '3%',
						bottom: '2%',
						containLabel: true
					},
					xAxis: {
						type: 'category',
						axisLine: {
							onZero: false
						},
						axisLabel: {
							textStyle: {
								color: '#000000',
								fontSize: '10'
							},
						},
						axisTick: {
							show: false,
						},
						boundaryGap: false,
						splitLine: {
							show: true,
							lineStyle: {
								color: ['#EEEEEE']
							}
						},
						data: datatime
					},
					yAxis: {
						type: 'value',
						axisLabel: {
							formatter: '{value}',
							textStyle: {
								color: '#3BA1D6',
								fontSize: '10'
							},
						},
						axisTick: {
							show: false,
						},
						splitLine: {
							show: true,
							lineStyle: {
								color: ['#EEEEEE']
							}
						},
					},
					series: [{
							name: '环境温度',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_ambient_temp
						},
						{
							name: '回风温度',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_re_air_temp
						},
						{
							name: '出风温度',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_out_air_temp
						},
						{
							name: '蒸发器盘管温度',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_oil_temp
						},
						{
							name: '设定温度',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_cooler_set_temp
						}
					]
				};
				chart1.setOption(option);
				window.addEventListener('resize',function(){
					chart1.resize();
				})
				//传感器
				var chart2 = echarts.init(document.getElementById("chart2"));
				option = null;
				option = {
					title: {
						text: '传感器',
						x: '8',
						y: '10',
						textStyle: {
							color: '#1199D3',
							fontSize: '15'
						},
					},
					tooltip: {
						trigger: 'axis'
					},
					legend: {
						data: ['湿度', '温度1', '温度2', '温度3', '电压'],
						right: 15,
						top: 10,
						left:65
					},
					grid: {
						left: '2%',
						right: '3%',
						bottom: '2%',
						containLabel: true
					},
					xAxis: {
						type: 'category',
						axisLine: {
							onZero: false
						},
						axisLabel: {
							textStyle: {
								color: '#000000',
								fontSize: '10'
							},
						},
						axisTick: {
							show: false,
						},
						boundaryGap: false,
						splitLine: {
							show: true,
							lineStyle: {
								color: ['#EEEEEE']
							}
						},
						data: datatime
					},
					yAxis: {
						type: 'value',
						axisLabel: {
							formatter: '{value}',
							textStyle: {
								color: '#3BA1D6',
								fontSize: '10'
							},
						},
						axisTick: {
							show: false,
						},
						splitLine: {
							show: true,
							lineStyle: {
								color: ['#EEEEEE']
							}
						},
					},
					series: [{
							name: '湿度',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_gps_humi
						},
						{
							name: '温度1',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_gps_temp1
						},
						{
							name: '温度2',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_gps_temp2
						},
						{
							name: '温度3',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_gps_temp3
						},
						{
							name: '电压',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_gps_voltage
						}
					]
				};
				chart2.setOption(option);
				window.addEventListener('resize',function(){
					chart2.resize();
				})
				return [chart1,chart2];
			} else if(data.code == 401){
				window.location.href = "../login.html";
			}else if(data.code == 400){
				console.log(data.reason);
			}
		}
	});
}
//控制显示
function control(opt) {
	var zone_status = opt.zone_status;
	var gps_door1 = opt.gps_door1;
	var reserve7 = opt.reserve7;
	var cooler_off_flag = opt.cooler_off_flag == '开' ? '1' : '0';
	var states = coolerStatus(zone_status, gps_door1, reserve7, cooler_off_flag, opt.box_id);
	if(states[1] == 0) {
		$(".box_op2063>.switchtab:nth-child(1)").addClass("on");
		$(".box_op2063>.switchtab:nth-child(2)").removeClass("on");
	} else if(states[1] == 1) {
		$(".box_op2063>.switchtab:nth-child(2)").addClass("on");
		$(".box_op2063>.switchtab:nth-child(1)").removeClass("on");
	}
	if(states[3] == '关') {
		$(".box_op2050>.switchtab:nth-child(2)").addClass("on");
		$(".box_op2050>.switchtab:nth-child(1)").removeClass("on");
	} else if(states[3] == '开') {
		$(".box_op2050>.switchtab:nth-child(1)").addClass("on");
		$(".box_op2050>.switchtab:nth-child(2)").removeClass("on");
	}
	var html = "<div>最后一次操作：</div>" +
		"<div>操作时间：</div>" +
		"<div>操作反馈结果：</div>";
	$(".recent").html("");
	$(".recent").html(html);
	$.ajax({
		type: 'POST',
		url: reqDomain + "/device/box_op_state",
		data: {
			"box_id": opt.box_id
		},
		dataType: "json",
		xhrFields: {
			withCredentials: true
		},
		success: function(data) {
			if(data.code == 200) {
				var flag_change_text = "";
				var opValue_text = "";
				var html2050 = "";
				var html2060 = "";
				var html2061 = "";
				var html2062 = "";
				var html2063 = "";
				var html2064 = "";
				for(var i = 0; i < data.result.length; i++) {
					if(data.result[i].flag_change == 1) {
						flag_change_text = "命令下发";
						setTimeout(function(){
							control();
						},500000);
					} else if(data.result[i].flag_change == 2) {
						flag_change_text = "服务器正在处理";
						setTimeout(function(){
							control();
						},500000);
					} else if(data.result[i].flag_change == 3) {
						flag_change_text = "正确响应";
					} else if(data.result[i].flag_change == 4) {
						flag_change_text = "命令被冷机拒绝";
					} else if(data.result[i].flag_change == 5) {
						flag_change_text = "终端与冷机通讯异常";
					} else if(data.result[i].flag_change == 11) {
						flag_change_text = "远程新风系统无法打开：温度低于零度";
					} else if(data.result[i].flag_change == 12) {
						flag_change_text = "远程新风系统无法打开：冷机非制冷或者制热模式";
					};
					if(data.result[i].opCode == 2050) {
						if(data.result[i].opValue == 1) {
							opValue_text = "远程开机";
						} else if(data.result[i].opValue == 0) {
							opValue_text = "远程关机";
						}
						html2050 += "<div>最后一次操作：" + opValue_text + "</div>" +
							"<div>操作时间：" + formatTime(parseInt(data.result[i].insert_time)) + "</div>" +
							"<div>操作反馈结果：" + flag_change_text + "</div>";
						$("#opCode2050").html("");
						$("#opCode2050").append(html2050);
					} else if(data.result[i].opCode == 2060) {
						opValue_text = F2C(dealDataValue(data.result[i].opValue, 'temp'));
						html2060 += "<div>最后一次操作：" + opValue_text + "</div>" +
							"<div>操作时间：" + formatTime(parseInt(data.result[i].insert_time)) + "</div>" +
							"<div>操作反馈结果：" + flag_change_text + "</div>";
						$("#opCode2060").html("");
						$("#opCode2060").append(html2060);
					} else if(data.result[i].opCode == 2061) {
						opValue_text = "初始化除霜";
						html2061 += "<div>最后一次操作：" + opValue_text + "</div>" +
							"<div>操作时间：" + formatTime(parseInt(data.result[i].insert_time)) + "</div>" +
							"<div>操作反馈结果：" + flag_change_text + "</div>";
						$("#opCode2061").html("");
						$("#opCode2061").append(html2061);
					} else if(data.result[i].opCode == 2062) {
						opValue_text = "清除警告";
						html2062 += "<div>最后一次操作：" + opValue_text + "</div>" +
							"<div>操作时间：" + formatTime(parseInt(data.result[i].insert_time)) + "</div>" +
							"<div>操作反馈结果：" + flag_change_text + "</div>";
						$("#opCode2062").html("");
						$("#opCode2062").append(html2062);
					} else if(data.result[i].opCode == 2063) {
						if(data.result[i].opValue == 1) {
							opValue_text = "连续模式";
						} else if(data.result[i].opValue == 0) {
							opValue_text = "循环模式";
						}
						html2063 += "<div>最后一次操作：" + opValue_text + "</div>" +
							"<div>操作时间：" + formatTime(parseInt(data.result[i].insert_time)) + "</div>" +
							"<div>操作反馈结果：" + flag_change_text + "</div>";
						$("#opCode2063").html("");
						$("#opCode2063").append(html2063);
					} else if(data.result[i].opCode == 2064) {
						if(data.result[i].opValue == 0) {
							opValue_text = "关闭新风";
						} else if(data.result[i].opValue == 1) {
							opValue_text = "设置新风20%开启";
						} else if(data.result[i].opValue == 2) {
							opValue_text = "设置新风40%开启";
						} else if(data.result[i].opValue == 3) {
							opValue_text = "设置新风60%开启";
						} else if(data.result[i].opValue == 4) {
							opValue_text = "设置新风80%开启";
						} else if(data.result[i].opValue == 5) {
							opValue_text = "设置新风100%开启";
						}
						html2064 += "<div>最后一次操作：" + opValue_text + "</div>" +
							"<div>操作时间：" + formatTime(parseInt(data.result[i].insert_time)) + "</div>" +
							"<div>操作反馈结果：" + flag_change_text + "</div>";
						$("#opCode2064").html("");
						$("#opCode2064").html(html2064);
					}
				}
			} else if(data.code == 401){
				window.location.href = "../login.html";
			}else if(data.code == 400){
				console.log(data.reason);
			}
		}
	});

}
//控制操作
function box_op(opCode, opValue) {
	var box_id = window.localStorage.getItem('box_id') || '';
	if(opCode == '2060') {
		opValue = $("#set_temp").val();
	} else if(opCode == '2064') {
		opValue = $("#set_op").find("option:selected").val();
	}
	if(window.confirm("确认要执行这次操作吗？")){
		$.ajax({
			type: 'POST',
			url: reqDomain + "/device/box_op",
			data: {
				"box_id": box_id,
				"opCode": opCode,
				"opValue": opValue
			},
			dataType: "json",
			xhrFields: {
				withCredentials: true
			},
			success: function(data) {
				if(data.code == 200) {
					layer.alert('命令已下发。');
					control();
				} else if(data.code == 401){
					window.location.href = "../login.html";
				} else if(data.code == 400){
					console.log(data.reason);
				}
			}
		});
	}
}
//设备参数显示
function preferences_box(opt) {
	$(".perferqk").html("");
	$.ajax({
		async: false,
		cache: false,
		type: 'POST',
		url: reqDomain + "/device/box_param",
		data: {
			"box_id": opt.box_id
		},
		dataType: "json",
		xhrFields: {
			withCredentials: true
		},
		success: function(data) {
			if(data.code == '200') {
				$('#preferences_name').text(data.result.current_config.field81968);
				$('#preferences_IMIS').text(data.result.current_config.field81969);
				$('#hearttime').text(data.result.current_config.field73787);
				$('#dormancy').val(data.result.current_config.field15);
				$('#dormancy_new').val(data.result.new_config.field15 || '0');
				$('#preferences_up_time').val(data.result.current_config.field69634);
				$('#preferences_up_time_new').val(data.result.new_config.field69634);
			} else if(data.code == 401){
				window.location.href = "../login.html";
			}else if(data.code == 400){
				layer.alert(data.reason);
			}
		}
	});
}
//设备参数设置
function box_param_save() {
	var box_id = window.localStorage.getItem("box_id") || '';
	layer.confirm('确定要执行此操作么？',function(){
		$.ajax({
			async: false,
			cache: false,
			type: 'POST',
			url: reqDomain + "/device/box_param_save",
			data: {
				"box_id": box_id,
				"field69634": $('#preferences_up_time_new').val(),
				'field15':	$('#dormancy_new').val()
			},
			dataType: "json",
			xhrFields: {
				withCredentials: true
			},
			success: function(data) {
				if(data.code == '200') {
					layer.alert('保存成功！');
					preferences_box({'box_id':window.localStorage.getItem("box_id")});
				} else if(data.code == 401){
					window.location.href = "../login.html";
				}else if(data.code == 400){
					console.log(data.reason);
				}
			}
		});
	})
}
//报警保存
function alarm_save() {
	var box_id = window.localStorage.getItem('box_id');
	var alarmPhone = $("#alarmPhone").val();
	var stripTime = $("#stripTime").val() * 60;
	var t1On = $("#t1On").find("option:selected").val();
	var t2On = $("#t2On").find("option:selected").val();
	var t3On = $("#t3On").find("option:selected").val();
	var h1On = $("#h1On").find("option:selected").val();
	var o1On = $("#o1On").find("option:selected").val();
	var v1On = $("#v1On").find("option:selected").val();
	var t1High = $("#t1High").val();
	var t2High = $("#t2High").val();
	var t3High = $("#t3High").val();
	var h1High = $("#h1High").val();
	var o1High = $("#o1High").val();
	var v1High = $("#v1High").val();
	var t1Low = $("#t1Low").val();
	var t2Low = $("#t2Low").val();
	var t3Low = $("#t3Low").val();
	var h1Low = $("#h1Low").val();
	var o1Low = $("#o1Low").val();
	var v1Low = $("#v1Low").val();
	$.ajax({
		async: false,
		cache: false,
		type: 'POST',
		url: reqDomain + "/alarm/alarm_save",
		data: {
			"box_id": box_id,
			"alarmPhone": alarmPhone,
			"stripTime": stripTime,
			"t1On": t1On,
			"t2On": t2On,
			"t3On": t3On,
			"h1On": h1On,
			"o1On": o1On,
			"v1On": v1On,
			"t1High": t1High,
			"t2High": t2High,
			"t3High": t3High,
			"h1High": h1High,
			"o1High": o1High,
			"v1High": v1High,
			"t1Low": t1Low,
			"t2Low": t2Low,
			"t3Low": t3Low,
			"h1Low": h1Low,
			"o1Low": o1Low,
			"v1Low": v1Low
		},
		dataType: "json",
		xhrFields: {
			withCredentials: true
		},
		success: function(data) {
			if(data.code == 200) {
				layer.alert('保存成功！')
				alarmsetting();
			} else if(data.code == 401) {
				window.location.href = "../login.html";
			} else if(data.code == 400) {
				alert(data.reason);
			}
		}
	});
}
//报警显示
function alarmsetting(opt) {
	var text1 = "温差";
	var text2 = "上限";
	$("#box_id_name").html("设备名称：" + opt.name + " 设备号：" + opt.box_id);
	$.ajax({
		async: false,
		cache: false,
		type: 'POST',
		url: reqDomain + "/alarm/alarm_setting",
		data: {
			"box_id": opt.box_id
		},
		dataType: "json",
		xhrFields: {
			withCredentials: true
		},
		success: function(data) {
			if(data.code == 200) {
				if(data.result == null) {
					$("#alarmPhone").val("");
					$("#stripTime").val("30");
					$("#t1On").val(0);
					$("#t2On").val(0);
					$("#t3On").val(0);
					$("#h1On").val(0);
					$("#o1On").val(0);
					$("#v1On").val(0);
					$("#t1High").val("");
					$("#t2High").val("");
					$("#t3High").val("");
					$("#h1High").val("");
					$("#o1High").val("");
					$("#v1High").val("");
					$("#t1Low").val("");
					$("#t2Low").val("");
					$("#t3Low").val("");
					$("#h1Low").val("");
					$("#o1Low").val("");
					$("#v1Low").val("");
				} else {
					$("#alarmPhone").val(data.result.alarm_phone);
					$("#stripTime").val(data.result.strip_time / 60);
					$("#t1On").val(data.result.t1_on);
					if(data.result.t1_on == 0) {
						$("#t1High").attr("disabled", "disabled");
						$("#t1Low").attr("disabled", "disabled");
					} else if(data.result.t1_on == 1) {
						$("#t1High").removeAttr("disabled", "disabled");
						$("#t1High").attr("placeholder", text2);
						$("#t1Low").removeAttr("disabled", "disabled");
					} else if(data.result.t1_on == 2) {
						$("#t1High").removeAttr("disabled", "disabled");
						$("#t1High").attr("placeholder", text1);
						$("#t1Low").attr("disabled", "disabled");
					}
					$("#t2On").val(data.result.t2_on);
					if(data.result.t2_on == 0) {
						$("#t2High").attr("disabled", "disabled");
						$("#t2Low").attr("disabled", "disabled");
					} else if(data.result.t2_on == 1) {
						$("#t2High").removeAttr("disabled", "disabled");
						$("#t2High").attr("placeholder", text2);
						$("#t2Low").removeAttr("disabled", "disabled");
					} else if(data.result.t2_on == 2) {
						$("#t2High").removeAttr("disabled", "disabled");
						$("#t2High").attr("placeholder", text1);
						$("#t2Low").attr("disabled", "disabled");
					}
					$("#t3On").val(data.result.t3_on);
					if(data.result.t3_on == 0) {
						$("#t3High").attr("disabled", "disabled");
						$("#t3Low").attr("disabled", "disabled");
					} else if(data.result.t3_on == 1) {
						$("#t3High").removeAttr("disabled", "disabled");
						$("#t3High").attr("placeholder", text2);
						$("#t3Low").removeAttr("disabled", "disabled");
					} else if(data.result.t3_on == 2) {
						$("#t3High").removeAttr("disabled", "disabled");
						$("#t3High").attr("placeholder", text1);
						$("#t3Low").attr("disabled", "disabled");
					}
					$("#h1On").val(data.result.h1_on);
					if(data.result.h1_on == 0) {
						$("#h1High").attr("disabled", "disabled");
						$("#h1Low").attr("disabled", "disabled");
					} else if(data.result.h1_on == 1) {
						$("#h1High").removeAttr("disabled", "disabled");
						$("#h1Low").removeAttr("disabled", "disabled");
					}
					$("#o1On").val(data.result.o1_on);
					if(data.result.o1_on == 0) {
						$("#o1High").attr("disabled", "disabled");
						$("#o1Low").attr("disabled", "disabled");
					} else if(data.result.o1_on == 1) {
						$("#o1High").removeAttr("disabled", "disabled");
						$("#o1Low").removeAttr("disabled", "disabled");
					}
					$("#v1On").val(data.result.v1_on);
					if(data.result.v1_on == 0) {
						$("#v1High").attr("disabled", "disabled");
						$("#v1Low").attr("disabled", "disabled");
					} else if(data.result.v1_on == 1) {
						$("#v1High").removeAttr("disabled", "disabled");
						$("#v1Low").removeAttr("disabled", "disabled");
					}
					$("#t1High").val(data.result.t1_high);
					$("#t2High").val(data.result.t2_high);
					$("#t3High").val(data.result.t3_high);
					$("#h1High").val(data.result.h1_high);
					$("#o1High").val(data.result.o1_high);
					$("#v1High").val(data.result.v1_high);
					$("#t1Low").val(data.result.t1_low);
					$("#t2Low").val(data.result.t2_low);
					$("#t3Low").val(data.result.t3_low);
					$("#h1Low").val(data.result.h1_low);
					$("#o1Low").val(data.result.o1_low);
					$("#v1Low").val(data.result.v1_low);
				}
			} else if(data.code == 401) {
				window.location.href = "../login.html";
			} else if(data.code == 400) {
				alert(data.reason);
			}
		}
	});
}
//报警设置
$(".box_in select").bind("change", function() {
	var datatype = $(this).val();
	if(datatype == 0) {
		$(this).next(".boxdouble").find("input").attr("disabled", "disabled").val('');
		$(this).next(".boxdouble").find(".text01").attr("placeholder", "上限");
		$(this).next(".boxdouble").find(".text02").attr("placeholder", "下限");
	}
	if(datatype == 1) {
		$(this).next(".boxdouble").find("input").removeAttr("disabled", "disabled");
		$(this).next(".boxdouble").find(".text01").attr("placeholder", "上限").val('');
		$(this).next(".boxdouble").find(".text02").attr("placeholder", "下限").val('');
	}
	if(datatype == 2) {
		$(this).next(".boxdouble").find(".text01").removeAttr("disabled", "disabled");
		$(this).next(".boxdouble").find(".text01").attr("placeholder", "温差").val('');
		$(this).next(".boxdouble").find(".text02").attr("disabled", "disabled").val('');
	}
});
//报警参数输入验证
$('#t1High').blur(function(){
	var dataType = $(this).parents('.box_in').find('select').val();
	if(isNaN(+$(this).val())){
		layer.alert('输入内容不合法！');
	}else if(dataType==1&&$(this).val()==''){
		layer.alert('请输入箱外温度上限！');
	}else if(dataType==1&&+$(this).val()>40){
		layer.alert('箱外温度上限不能大于40！');
	}else if(dataType==2&&$(this).val()==''){
		layer.alert('请输入箱外温度温差！');
	}else if(dataType==2&&(+$(this).val()>10||+$(this).val()<0)){
		layer.alert('箱外温度温差必须在0到10之间！');
	}
})
$('#t1Low').blur(function(){
	var dataType = $(this).parents('.box_in').find('select').val();
	if(isNaN(+$(this).val())){
		layer.alert('输入内容不合法！');
	}else if($(this).val()==''){
		layer.alert('请输入箱外温度下限！');
	}else if(+$(this).val()<-40){
		layer.alert('箱外温度下限不能小于-40！');
	}else if(+$(this).val()>=+$('#t1High').val()){
		layer.alert('箱外温度下限必须低于上限！');
	}
})
$('#t2High').blur(function(){
	var dataType = $(this).parents('.box_in').find('select').val();
	if(isNaN(+$(this).val())){
		layer.alert('输入内容不合法！');
	}else if(dataType==1&&$(this).val()==''){
		layer.alert('请输入箱内中部温度上限！');
	}else if(dataType==1&&+$(this).val()>40){
		layer.alert('箱内中部温度上限不能大于40！');
	}else if(dataType==2&&$(this).val()==''){
		layer.alert('请输入箱内中部温度温差！');
	}else if(dataType==2&&(+$(this).val()>10||+$(this).val()<0)){
		layer.alert('箱内中部温度温差必须在0到10之间！');
	}
})
$('#t2Low').blur(function(){
	var dataType = $(this).parents('.box_in').find('select').val();
	if(isNaN(+$(this).val())){
		layer.alert('输入内容不合法！');
	}else if($(this).val()==''){
		layer.alert('请输入箱内中部温度下限！');
	}else if(+$(this).val()<-40){
		layer.alert('箱内中部温度下限不能小于-40！');
	}else if(+$(this).val()>=+$('#t2High').val()){
		layer.alert('箱内中部温度下限必须低于上限！');
	}
})
$('#t3High').blur(function(){
	var dataType = $(this).parents('.box_in').find('select').val();
	if(isNaN(+$(this).val())){
		layer.alert('输入内容不合法！');
	}else if(dataType==1&&$(this).val()==''){
		layer.alert('请输入箱内后部温度上限！');
	}else if(dataType==1&&+$(this).val()>40){
		layer.alert('箱内后部温度上限不能大于40！');
	}else if(dataType==2&&$(this).val()==''){
		layer.alert('请输入箱内后部温度温差！');
	}else if(dataType==2&&(+$(this).val()>10||+$(this).val()<0)){
		layer.alert('箱内后部温度温差必须在0到10之间！');
	}
})
$('#t3Low').blur(function(){
	var dataType = $(this).parents('.box_in').find('select').val();
	if(isNaN(+$(this).val())){
		layer.alert('输入内容不合法！');
	}else if($(this).val()==''){
		layer.alert('请输入箱内后部温度下限！');
	}else if(+$(this).val()<-40){
		layer.alert('箱内后部温度下限不能小于-40！');
	}else if(+$(this).val()>=+$('#t3High').val()){
		layer.alert('箱内后部温度下限必须低于上限！');
	}
})
$('#h1High').blur(function(){
	var dataType = $(this).parents('.box_in').find('select').val();
	if(isNaN(+$(this).val())){
		layer.alert('输入内容不合法！');
	}else if(dataType==1&&$(this).val()==''){
		layer.alert('请输入湿度上限！');
	}else if(dataType==1&&+$(this).val()>100){
		layer.alert('湿度上限不能大于100！');
	}
})
$('#h1Low').blur(function(){
	var dataType = $(this).parents('.box_in').find('select').val();
	if(isNaN(+$(this).val())){
		layer.alert('输入内容不合法！');
	}else if($(this).val()==''){
		layer.alert('请输入湿度下限！');
	}else if(+$(this).val()<0){
		layer.alert('湿度下限不能小于0！');
	}else if(+$(this).val()>=+$('#h1High').val()){
		layer.alert('湿度下限必须低于上限！');
	}
})
$('#v1High').blur(function(){
	var dataType = $(this).parents('.box_in').find('select').val();
	if(isNaN(+$(this).val())){
		layer.alert('输入内容不合法！');
	}else if(dataType==1&&$(this).val()==''){
		layer.alert('请输入电瓶电压上限！');
	}else if(dataType==1&&+$(this).val()>20){
		layer.alert('电瓶电压上限不能大于20！');
	}
})
$('#v1Low').blur(function(){
	var dataType = $(this).parents('.box_in').find('select').val();
	if(isNaN(+$(this).val())){
		layer.alert('输入内容不合法！');
	}else if($(this).val()==''){
		layer.alert('请输入电瓶电压下限！');
	}else if(+$(this).val()<8){
		layer.alert('电瓶电压下限不能小于8！');
	}else if(+$(this).val()>=+$('#v1High').val()){
		layer.alert('电瓶电压下限必须低于上限！');
	}
})
//分组设置显示
function group(opt){
	var grouphtml = '';
	$("#grouping_box_name").val(opt.name);
	$("#grouping_box_id").val(opt.box_id);
	$.ajax({
		type: 'POST',
		url: reqDomain + "/device/group_listing", 
		data: {},
		dataType: "json",
		xhrFields: {
			withCredentials: true
		},
		success: function(data) {
			if(data.code == 200) {
				grouphtml += "<option value='-1'>请选择一个分组</option>";
				for(var i = 0; i < data.result.length; i++) {
					grouphtml += "<option value='" + data.result[i].group_id + "'>" + data.result[i].name + "</option>";
				};
				$("#grouping_list").html(grouphtml);
				console.log(opt)
				$("#grouping_list").val(opt.group_id);
				$('#grouping_list').multiselect("destroy").multiselect({
					buttonWidth: '180px', 
					includeSelectAllOption: true,
					enableFiltering: true,
					filterBehavior: 'text', //根据value或者text过滤
					enableFullValueFiltering: true, //能否全字匹配
					enableCaseInsensitiveFiltering: true, //不区分大小写
					includeSelectAllOption: true, //全选
					nonSelectedText: '最少选一个分组',
					selectAllText: '全选', //全选的checkbox名称
					selectAllNumber: false, //true显示allselect（6）,false显示allselect
					selectAllName: 'select-all-name',
					selectAllValue: 'select-all-value', //可以为strig或者数字
					numberDisplayed: 1,  //当超过2个标签的时候显示n个被选中
					maxHeight: 150,
				});
				getBoxList();
			} else if(data.code == 401){
				window.location.href = "../login.html";
			}else if(data.code == 400){
				console.log(data.reason);
			}
		}
	})
}
//设备分组保存
function box_group_save() {
	var box_id = $("#grouping_box_id").val();
	var groupID = $("#grouping_list").val();
	var boxIDs = [];
	var selecteds = $("#sel_productTag option:selected");
	for(var i = 0; i < selecteds.length; i++) {
		boxIDs[i] = $(selecteds[i]).val();
	}
	$.ajax({
		async: false,
		cache: false,
		type: 'POST',
		url: reqDomain + "/device/box_group_save",
		data: {
			"box_id": box_id,
			"groupID": groupID,
			"boxIDs[]": boxIDs
		},
		dataType: "json",
		xhrFields: {
			withCredentials: true
		},
		success: function(data) {
			if(data.code == 200) {
				layer.alert("分组保存成功");
			} else if(data.code == 401){
				window.location.href = "../login.html";
			}else if(data.code == 400){
				console.log(data.reason);
			}
		}
	});
}
// 获取全部设备列表
function getBoxList(){
	var data = JSON.parse(window.localStorage.getItem('new_boxLatest'));
	$('#sel_productTag').empty();
	for(var i = 0; i < data.length; i++) {
		if(data[i].groupID<=0){
			new BoxList(data[i]);
		}
	}
	$('#sel_productTag').multiselect("destroy").multiselect({
		buttonWidth: '150px', 
		includeSelectAllOption: true,
		enableFiltering: true,
		filterPlaceholder: '搜索',
		filterBehavior: 'text', //根据value或者text过滤
		enableFullValueFiltering: true, //能否全字匹配
		enableCaseInsensitiveFiltering: true, //不区分大小写
		includeSelectAllOption: true, //全选
		nonSelectedText: '最少选一个设备',
		selectAllText: '全选', //全选的checkbox名称
		selectAllNumber: false, //true显示allselect（6）,false显示allselect
		selectAllName: 'select-all-name',
		selectAllValue: 'select-all-value', //可以为strig或者数字
		numberDisplayed: 2,  //当超过2个标签的时候显示n个被选中
		maxHeight: 150,
	});
}
function BoxList(opt){
	this.name = opt.name || opt.box_id;
	this.id = opt.box_id;
	this.init();
}
BoxList.prototype.init = function(){
	var op = document.createElement('option');
  op.value = this.id;
  op.innerHTML = this.name;
  $('#sel_productTag').append(op);
}
//数据查询表格
$('#dataTable').bootstrapTable({
	columns:[
		{
			title: '设备名称',
			field: 'box_id',
			align:'center'
		},{
			title: '设备ID',
			field: 'box_id',
			align:'center'
		},{
			title: '通讯状态',
			field: 'cooler_off_flag',
			align:'center'
		},{
			title: '电压',
			field: 'gps_voltage',
			align:'center'
		},{
			title: '环境温度',
			field: 'ambient_temp',
			align:'center'
		},{
			title: '回风温度',
			field: 're_air_temp',
			align:'center'
		},{
			title: '送风温度',
			field: 'out_air_temp',
			align:'center'
		},{
			title: '设定温度',
			field: 'cooler_set_temp',
			align:'center'
		},{
			title: '蒸发器盘管温度',
			field: 'oil_temp',
			align:'center'
		},{
			title: '告警码',
			field: 'zone_alarm_code',
			align:'center'
		},{
			title: '湿度',
			field: 'gps_humi',
			align:'center'
		},{
			title: '箱外温',
			field: 'gps_temp1',
			align:'center'
		},{
			title: '箱中温',
			field: 'gps_temp2',
			align:'center'
		},{
			title: '箱后温',
			field: 'gps_temp3',
			align:'center'
		},{
			title: '速度Km/h',
			field: 'speed',
			align:'center'
		},{
			title: '门开关',
			field: 'gps_door1',
			align:'center'
		},{
			title: '数据时间',
			field: 'insert_time',
			align:'center'
		},
	]
})
//设备信息表格
function pivot(page) {
	var box_id = window.localStorage.getItem('box_id');
	var starttime = $("#starttime").val();
	var endtime = $("#endtime").val();
	var perPage = $("#perPage").find("option:selected").val();
	var row = JSON.parse(window.localStorage.getItem('row'));
	$.ajax({
		type: 'POST',
		url: reqDomain + "/device/box_data_page",
		data: {
			"box_id": box_id,
			"startTime": starttime,
			"endTime": endtime,
			"perPage": perPage,
			"page": page
		},
		dataType: "json",
		xhrFields: {
			withCredentials: true
		},
		success: function(data) {
			if(data.code == 200) {
				var list = data.result.dataList;
				for(var i = 0; i < list.length; i++) {
					list[i].box_id = box_id;
					list[i].cooler_off_flag = dealDataValue(list[i].cooler_off_flag,'cooler_off_flag');
					list[i].gps_voltage = dealDataValue(list[i].gps_voltage, "voltage");
					list[i].ambient_temp = F2C(dealDataValue(list[i].ambient_temp, "ambient_temp"));
					list[i].re_air_temp = F2C(dealDataValue(list[i].re_air_temp, "temp"));
					list[i].out_air_temp = F2C(dealDataValue(list[i].out_air_temp, "temp"));
					list[i].cooler_set_temp = F2C(dealDataValue(list[i].cooler_set_temp, 'temp'));
					list[i].oil_temp = F2C(dealDataValue(list[i].oil_temp, "temp"));
					list[i].gps_humi = dealDataValue(list[i].gps_humi, "humi");
					list[i].gps_temp1 = dealDataValue(list[i].gps_temp1, "temp");
					list[i].gps_temp2 = dealDataValue(list[i].gps_temp2, "temp");
					list[i].gps_temp3 = dealDataValue(list[i].gps_temp3, "temp");
					list[i].speed = dealDataValue(list[i].speed, 'speed');
					list[i].gps_door1 = dealDataValue(list[i].gps_door1, 'door1');
					list[i].insert_time = formatTime(parseInt(list[i].insert_time));
				}
				$('#dataTable').bootstrapTable('load',list);
				//总数
				var index = (page - 1) * 10 + 1;
				var index1 = index + list.length - 1;
				var totalhtml = "第" + index + "-" + index1 + "条 共" + data.result.total + "条";
				$("#total").html("");
				$("#total").append(totalhtml);
				//分页
				var pagehtml = "";
				var total = data.result.total;
				var page1 = page - 1;
				var page2 = page + 1;
				if(page > 1 && page <= Math.ceil(total / perPage)) {
					pagehtml += "<li onclick='pivot(" + page1 + ")'>";
				} else {
					pagehtml += "<li class='disabled'>";
				}

				pagehtml += "<a href='#' aria-label='Previous' ><span aria-hidden='true'>&laquo;</span></a></li>";
				for(var i = 1; i < Math.ceil(total / perPage) + 1; i++) {
					if(i < page + 5 && i > page - 5) {
						if(page == i) {
							pagehtml += "<li class='active'><a href='#' onclick='pivot(" + i + ")'>" + i + "</a></li>"
						} else {
							pagehtml += "<li><a href='#' onclick='pivot(" + i + ")'>" + i + "</a></li>"
						}

					}
				}
				if(page >= 1 && page < Math.ceil(total / perPage)) {
					pagehtml += "<li onclick='pivot(" + page2 + ")'>";
				} else {
					pagehtml += "<li class='disabled'>";
				};
				pagehtml += "<a href='#' aria-label='Next' ><span aria-hidden='true' >&raquo;</span></a></li>";
				$("#pivot_page").html("");
				$("#pivot_page").html(pagehtml);
			} else if(data.code == 401){
				window.location.href = "../login.html";
			}else if(data.code == 400){
				console.log(data.reason);
			}
		}
	});
}
// 曲线查询
function curve(){
	var box_id = window.localStorage.getItem('box_id');
	var starttime1 = $("#starttime1").val();
	var endtime1 = $("#endtime1").val();
	//出风温度
	var data_out_air_temp = [];
	//回风温度
	var data_re_air_temp = [];
	//温度1
	var data_gps_temp1 = [];
	//温度2
	var data_gps_temp2 = [];
	//温度3
	var data_gps_temp3 = [];
	//湿度
	var data_gps_humi = [];
	//环境温度
	var data_ambient_temp = [];
	//蒸发器盘管温度
	var data_oil_temp = [];
	//油位
	var data_gps_oil_level = [];
	//电压
	var data_gps_voltage = [];
	//CO2
	var data_gps_co2 = [];
	//日期时间
	var datatime = [];
	//设定温度
	var data_cooler_set_temp = [];
	$.ajax({
		async: false,
		cache: false,
		type: 'POST',
		url: reqDomain + "/device/box_data",
		data: {
			"box_id": box_id,
			"startTime": starttime1,
			"endTime": endtime1,
		},
		dataType: "json",
		xhrFields: {
			withCredentials: true
		},
		success: function(data) {
			if(data.code == '200') {
				for(var i = 0; i < data.result.length; i++) {
					data_out_air_temp[i] = F2C(dealDataValue(data.result[i].out_air_temp, 'temp'));
					data_re_air_temp[i] = F2C(dealDataValue(data.result[i].re_air_temp, 'temp'));
					data_gps_temp1[i] = dealDataValue(data.result[i].gps_temp1, "temp");
					data_gps_temp2[i] = dealDataValue(data.result[i].gps_temp2, "temp");
					data_gps_temp3[i] = dealDataValue(data.result[i].gps_temp3, "temp");
					data_gps_humi[i] = dealDataValue(data.result[i].gps_humi, "humi");
					data_ambient_temp[i] = F2C(dealDataValue(data.result[i].ambient_temp, 'ambient_temp'));
					data_oil_temp[i] = F2C(dealDataValue(data.result[i].oil_temp, 'temp'));
					data_gps_oil_level[i] = dealDataValue(data.result[i].gps_oil_level, "");
					data_gps_voltage[i] = dealDataValue(data.result[i].gps_voltage, "voltage");
					data_gps_co2[i] = dealDataValue(data.result[i].gps_co2, "");
					datatime[i] = formatTime(parseInt(data.result[i].insert_time));
					data_cooler_set_temp[i] = F2C(dealDataValue(data.result[i].cooler_set_temp, 'temp'))
				}
				//冷机
				var chart1 = echarts.init(document.getElementById("curve_chart1"));
				option = null;
				option = {
					title: {
						text: '冷机',
						x: '8',
						y: '10',
						textStyle: {
							color: '#1199D3',
							fontSize: '15'
						},
					},
					tooltip: {
						trigger: 'axis'
					},
					legend: {
						data: ['环境温度', '回风温度', '出风温度', '蒸发器盘管温度', '设定温度'],
						right: 15,
						top: 10,
					},
					grid: {
						left: '2%',
						right: '3%',
						bottom: '2%',
						containLabel: true
					},
					xAxis: {
						type: 'category',
						axisLine: {
							onZero: false
						},
						axisLabel: {
							textStyle: {
								color: '#000000',
								fontSize: '10'
							},
						},
						axisTick: {
							show: false,
						},
						boundaryGap: false,
						splitLine: {
							show: true,
							lineStyle: {
								color: ['#EEEEEE']
							}
						},
						data: datatime
					},
					yAxis: {
						type: 'value',
						axisLabel: {
							formatter: '{value}',
							textStyle: {
								color: '#3BA1D6',
								fontSize: '10'
							},
						},
						axisTick: {
							show: false,
						},
						splitLine: {
							show: true,
							lineStyle: {
								color: ['#EEEEEE']
							}
						},
					},
					dataZoom:[
						{
							type:'inside',
							start:80,
							end:100
						},
						{
							show:true,
							type:'slider',
							y:'90%',
							start:50,
							end:100
						}
					],
					series: [{
							name: '环境温度',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_ambient_temp
						},
						{
							name: '回风温度',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_re_air_temp
						},
						{
							name: '出风温度',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_out_air_temp
						},
						{
							name: '蒸发器盘管温度',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_oil_temp
						},
						{
							name: '设定温度',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_cooler_set_temp
						}
					]
				};
				chart1.setOption(option);
				window.addEventListener('resize',function(){
					chart1.resize();
				})
				//传感器
				var chart2 = echarts.init(document.getElementById("curve_chart2"));
				option = null;
				option = {
					title: {
						text: '传感器',
						x: '8',
						y: '10',
						textStyle: {
							color: '#1199D3',
							fontSize: '15'
						},
					},
					tooltip: {
						trigger: 'axis'
					},
					legend: {
						data: ['湿度', '温度1', '温度2', '温度3', '电压'],
						right: 15,
						top: 10,
					},
					grid: {
						left: '2%',
						right: '3%',
						bottom: '2%',
						containLabel: true
					},
					xAxis: {
						type: 'category',
						axisLine: {
							onZero: false
						},
						axisLabel: {
							textStyle: {
								color: '#000000',
								fontSize: '10'
							},
						},
						axisTick: {
							show: false,
						},
						boundaryGap: false,
						splitLine: {
							show: true,
							lineStyle: {
								color: ['#EEEEEE']
							}
						},
						data: datatime
					},
					yAxis: {
						type: 'value',
						axisLabel: {
							formatter: '{value}',
							textStyle: {
								color: '#3BA1D6',
								fontSize: '10'
							},
						},
						axisTick: {
							show: false,
						},
						splitLine: {
							show: true,
							lineStyle: {
								color: ['#EEEEEE']
							}
						},
					},
					dataZoom:[
						{
							type:'inside',
							start:80,
							end:100
						},
						{
							show:true,
							type:'slider',
							y:'90%',
							start:50,
							end:100
						}
					],
					series: [{
							name: '湿度',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_gps_humi
						},
						{
							name: '温度1',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_gps_temp1
						},
						{
							name: '温度2',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_gps_temp2
						},
						{
							name: '温度3',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_gps_temp3
						},
						{
							name: '电压',
							type: 'line',
							smooth: true,
							lineStyle: {
								normal: {
									width: 3,
								}
							},
							data: data_gps_voltage
						}
					]
				};
				chart2.setOption(option);
				window.addEventListener('resize',function(){
					chart2.resize();
				})
				return [chart1,chart2];
			} else if(data.code == 401){
				window.location.href = "../login.html";
			}else if(data.code == 400){
				console.log(data.reason);
			}
		}
	});
}
//首页选项卡切换
function tab(row){
	var row = row;
	var index = $('#tabbar li.current').index();
	if($('#tabbar li').eq(index).attr('myload')=='unload'&&window.localStorage.getItem('box_id')){
		$('#tabbar li').eq(index).attr('myload','load');
		if(index==0){
			boxInfo(row);
			boxData(row);
		}else if(index==1){
			control(row);
		}else if(index==2){
			preferences_box(row);
		}else if(index==3){
			alarmsetting(row);
		}else if(index==4){
			group(row);
		}else if(index==5){
			pivot(1);
		}else{
			curve();
		}
	}
}
//温度输入验证
function testTemp(v){
	if(v.search(/[^\-?\d.]/) != -1){
		layer.alert('禁止输入非法字符！');
	}
	if(v<-30 || v>40){
		layer.alert('设定温度必须在-30℃到40℃之间！');
	}
}
//上传间隔验证
function testMin(v){
	if(v.search(/[^\-?\d.]/) != -1){
		layer.alert('禁止输入非法字符！');
	}
	if(v<5 || v>1440){
		layer.alert('设定温度必须在5分钟到1440分钟之间！');
	}
}