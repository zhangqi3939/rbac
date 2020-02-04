//格式化数字
function toDecimal(x){ 
	var f = parseFloat(x); 
	if (isNaN(f)) { 
		return '-'; 
	} 
	f = Math.round(x * 10) / 10; 
	var s = f.toString(); 
	var rs = s.indexOf('.'); 
	if (rs == -1) {
		s += '.0'; 
	}
	return s; 
}
//时间戳处理
function formatNumber(n) {
	n = n.toString()
	return n[1] ? n : '0' + n
}
//时间格式转换
function formatTime(date) {
	if(date == 0 || date == null){
		return '-'
	}
	var date = new Date(date * 1000);
	var year = date.getFullYear()
	var month = date.getMonth() + 1
	var day = date.getDate()
	var hour = date.getHours()
	var minute = date.getMinutes()
	var second = date.getSeconds()
	return [year, month, day].map(formatNumber).join('-') +'\n '+ ' ' + [hour, minute, second].map(formatNumber).join(':')
}
//设备和冷机数据处理
function dealDataValue(v,c){
  if(v == -99 || v == -999 ){
    return '-';
  }
  var toDecimal_flag =  1; //转成十进制
  switch (c) {
    case 'temp': //温度
      v = Math.round(10 * v) / 100;
      if(v > 100 || v < -100){
        v = '-';
      }
      break;
    case 'ambient_temp': //环境温度
      v = Math.round(10 * v) / 1000;
      if(v > 100 || v < -100){
        v = '-';
      } 
      break;
    case 'time'://冷机工作时间，引擎工作时间，上电时间
			v = Math.round(10 * v) / 1000;
      break;
    case 'voltage'://电压
			v = Math.round(10 * v) / 100;
      break;
    case 'humi'://湿度
			toDecimal_flag = 0;
			v = v > 100 ? 100 : v;
      break;
    case 'zone_status'://冷机状态
			toDecimal_flag=0;
			v = v==1?'运行':'关闭';
      break;
    case 'cooler_off_flag'://通讯状态
			toDecimal_flag=0;
			v = parseInt(v);
			v = v==0?'断开':'正常';
      break;
    case 'door1'://门状态
			toDecimal_flag=0;
			v = v==1?'开':'关';
			break;
		case 'speed'://speed
			toDecimal_flag=0;
			v = v<1?0:parseFloat(v).toFixed(2);
			break;
    default:
      break;
  }
  if(toDecimal_flag){
		return toDecimal(v);
	}else{
		return v;
	}
}
function addZore(num){
  num = num + '';
  var zero = '';
  for(var i=0;i<4-num.length;i++){
    zero += '0';
  }
  return zero + num;
}
//状态分类统计
function getStateNum(data){
  var allNum = data.length;
  var onLineNum = 0;
  var outLineNum = 0;
  var dormantNum = 0;
  var alarmNum = 0;
  for(var i=0;i<data.length;i++){
    if(data[i].cooler_alarm_cnt!=0 && data[i].cooler_alarm_cnt != null){
			//报警
			alarmNum += 1;
		}else if(data[i].online == 0){
			//离线
			outLineNum += 1;
		}else if(dealDataValue(data[i].gps_voltage, 'voltage') <= 12){
			//休眠
			dormantNum += 1;
		}else{
			//在线
			onLineNum += 1;
		}
  }
  return {
    allNum:addZore(allNum),
    onLineNum:addZore(onLineNum),
    outLineNum:addZore(outLineNum),
    dormantNum:addZore(dormantNum),
    alarmNum:addZore(alarmNum)
  }
}
function getStateNum1(data){
  var allNum = data.length;
  var onLineNum = 0;
  var outLineNum = 0;
//var dormantNum = 0;
  var alarmNum = 0;
  for(var i=0;i<data.length;i++){
  	if(data[i].cooler_alarm_cnt!=0 && data[i].cooler_alarm_cnt != null){
		//报警
		ralarmNum += 1;
	}else if(data[i].insert_time == null || new Date().getTime() - (data[i].insert_time + '000') > 7200000){
		//休眠
		outLineNum += 1;
	}else{
		//在线
		onLineNum += 1;
	}
  }
  return {
    allNum:addZore(allNum),
    onLineNum:addZore(onLineNum),
    outLineNum:addZore(outLineNum),
    alarmNum:addZore(alarmNum)
  }
}
function getStateNum2(data){
  var allNum = data.length;
  var onLineNum = 0;
  var outLineNum = 0;
  var alarmNum = 0;
  for(var i=0;i<data.length;i++){
  	if(data[i].cooler_oil_level != 0 && data[i].cooler_oil_level != null){
		//报警
		alarmNum += 1;
	}else if(data[i].insert_time == '-' || new Date().getTime() - new Date(data[i].insert_time).getTime() > 24*60*60*1000){
		//休眠
		outLineNum += 1;
	}else{
		//在线
		onLineNum += 1;
	}
  }
  return {
    allNum:addZore(allNum),
    onLineNum:addZore(onLineNum),
    outLineNum:addZore(outLineNum),
    alarmNum:addZore(alarmNum)
  }
}
//获取地址
function getAddr(data){
  var geo = new BMap.Geocoder();
  var SAMPLE_POST_REVERSE = 'http://api.map.baidu.com/geocoder/v2/?ak=VsTbD0CSmlginFy4WoGOab78W5RMwZ7H';
	var safe = '';
  var pois;
  window.addrCount = 0;
  for(var i=0;i<data.length;i++){
    safe = SAMPLE_POST_REVERSE;
		safe += "&location=" + data[i].latitude + "," + data[i].longitude;
		safe += "&output=json";
		safe += '&pois=' + pois;
		safe += '&coordtype=wgs84ll';
    var newURL = safe.replace('密钥', 'VsTbD0CSmlginFy4WoGOab78W5RMwZ7H');
    toAddr(newURL,data,i);
  }
}
function toAddr(url,res,index){
  $.ajax({
    async: false,
		cache: false,
    type: "get",
    url: url,
    dataType: "jsonp",
    success: function (data) {
      if(data.status == 0){
        addrCount++;
        res[index].addr = data.result.formatted_address;
        res[index].province = data.result.addressComponent.province;
        res[index].city = data.result.addressComponent.city;
        res[index].district = data.result.addressComponent.district;
        res[index].street = data.result.addressComponent.street+data.result.addressComponent.street_number;
        if(addrCount == res.length){
			$('#indexTable').bootstrapTable('load',res);
			window.localStorage.setItem('new_searchData_addr',JSON.stringify(res));
			if(window.full_map == 1){
				$('#maptable').bootstrapTable('load',res);
			}
        }
      }
    }
  });
}
// 提取故障码
function getAlarmCode(data){
  var value1 = data.cooler_alarm_value1 == '0' ? '' : data.cooler_alarm_value1;
  var value2 = data.cooler_alarm_value2 == '0' ? '' : data.cooler_alarm_value2;
  var value3 = data.cooler_alarm_value3 == '0' ? '' : data.cooler_alarm_value3;
  var value4 = data.cooler_alarm_value4 == '0' ? '' : data.cooler_alarm_value4;
  var value5 = data.cooler_alarm_value5 == '0' ? '' : data.cooler_alarm_value5;
  var value6 = data.cooler_alarm_value6 == '0' ? '' : data.cooler_alarm_value6;
  var value7 = data.cooler_alarm_value7 == '0' ? '' : data.cooler_alarm_value7;
  var value8 = data.cooler_alarm_value8 == '0' ? '' : data.cooler_alarm_value8;
  var value9 = data.cooler_alarm_value9 == '0' ? '' : data.cooler_alarm_value9;
  var value10 = data.cooler_alarm_value10 == '0' ? '' : data.cooler_alarm_value10;
  var value11 = data.cooler_alarm_value11 == '0' ? '' : data.cooler_alarm_value11;
  var value12 = data.cooler_alarm_value12 == '0' ? '' : data.cooler_alarm_value12;
  var value13 = data.cooler_alarm_value13 == '0' ? '' : data.cooler_alarm_value13;
  var alarmArr = [value1,value2,value3,value4,value5,value6,value7,value8,value9,value10,value11,value12,value13];
  for(var i=0;i<alarmArr.length;i++){
    if(alarmArr[i]<0){
      alarmArr[i]*=-1
    }
    if(alarmArr[i]==255){
      alarmArr[i]=127
    }
    if(alarmArr[i]==''){
      alarmArr.splice(i,1);
      alarmArr.length-1;
      i--;
    }
  }
  return alarmArr.join(',');
}
//故障码说明
function cooler_alarm_value(code){
	code = Number(code);
	switch (code){
		case 0:
			return '送风传感器断路';
			break;
		case 1:
			return '送风传感器短路';
			break;
		case 2:
			return '回风传感器断路';
			break;
		case 3:
			return '回风传感器短路';
			break;
		case 4:
			return '蒸发器盘管传感器断路';
			break;
		case 5:
			return '蒸发器盘管传感器短路';
			break;
		case 6:
			return '压缩机电流太高';
			break;
		case 7:
			return '压缩机电流太低';
			break;
		case 10:
			return '电热器电流太高';
			break;
		case 11:
			return '电热器电流太低';
			break;
		case 12:
			return '蒸发器风扇高转速电流太高';
			break;
		case 13:
			return '蒸发器风扇高转速电流太低';
			break;
		case 14:
			return '蒸发器风扇低转速电流太高';
			break;
		case 15:
			return '蒸发器风扇低转速电流太低';
			break;
		case 16:
			return '冷凝器风扇电流太高';
			break;
		case 17:
			return '冷凝器风扇电流太低';
			break;
		case 18:
			return '电源相位错误';
			break;
		case 19:
			return '温度与设置点偏离过大';
			break;
		case 20:
			return '除霜时间过长';
			break;
		case 22:
			return '制冷量测试  1 错误';
			break;
		case 23:
			return '制冷量测试  2 错误';
			break;
		case 26:
			return '蒸汽喷射阀故障';
			break;
		case 31:
			return '低压切断故障';
			break;
		case 32:
			return '冷凝器温度传感器断路';
			break;
		case 33:
			return '冷凝器温度传感器短路';
			break;
		case 34:
			return '环境空气传感器断路';
			break;
		case 35:
			return '环境空气传感器短路';
			break;
		case 43:
			return '回风温度太高';
			break;
		case 44:
			return '回风温度太低';
			break;
		case 51:
			return '电源电压低';
			break;
		case 52:
			return '探头故障';
			break;
		case 53:
			return '高压切断开关闭合故障';
			break;
		case 54:
			return '高压切断开关断开故障';
			break;
		case 56:
			return '压缩机温度过高';
			break;
		case 57:
			return 'FAE设备错误'; 
			break;
		case 58:
			return '相位传感器错误';
			break;
		case 59:
			return '三角形电流误差';
			break;
		case 60:
			return '湿度传感器故障';
			break;
		case 65:
			return '二氧化碳过高';
			break;
		case 66:
			return '二氧化碳过低';
			break;
		case 68:
			return '气体分析仪错误';
			break;
		case 69:
			return '气体分析仪校准错误';
			break;
		case 70:
			return '氧传感器错误';
			break;
		case 71:
			return '二氧化碳传感器错误r';
			break;
		case 97:
			return '压缩机传感器开路';
			break;
		case 98:
			return '压缩机传感器短路';
			break;
		case 119:
			return '数控阀故障';
			break;
		case 120:
			return '吸气压力传感器';
			break;
		case 121:
			return '排气压力传感器';
			break;
		case 122:
			return 'CO2 传感器校准故障';
			break;
		case 123:
			return '控制器电池故障';
			break;
		case 124:
			return '检查电源模块传感器';
			break;
		case 127:
			return '报警常规单元错误';
			break;
		case 128:
			return '检查送风温度探头';
			break;
		case 129:
			return '检查回风温度探头';
			break;
		case 130:
			return '检查蒸发器盘管温度探头';
			break;
		case 131:
			return '检查 AMB - 冷凝器温度探头故障';
			break;
		case 132:
			return '电源模块传感器错误';
			break;	
		case 133:
			return '电源模块网络错误';
			break;
		case 134:
			return '控制器误差';
			break;
		case 135:
			return '电源模块错误';
			break;
		case 136:
			return '传感器电路错误';
			break;
		case 137:
			return '传感器系统过载';
			break;
		case 138:
			return 'AVL传感器错误';
			break;
		case 139:
			return '内部文件处理错误';
			break;
		case 140:
			return '蒸发器部分过热';
			break;
		case 141:
			return '电源模块热交换器过热';
			break;
		case 157:
			return '数据记录器电池故障';
			break;
		case 255:
			return '报警常规单元错误';
			break;	
		default:
			return code;
			break;
	}
}
// 冷机状态图标
function coolerStatusIcon(statusArray,url){
  if(url == undefined || url == null){
    url='../img/icon/';
  }
  var result = [
    '<img src="../img/icon/null24.png" title="Unknown">',
    '<img src="../img/icon/null24.png" title="Unknown">',
    '<img src="../img/icon/null24.png" title="Unknown">',
    '<img src="../img/icon/null24.png" title="Unknown">',
    '<img src="../img/icon/null24.png" title="Unknown">',
    '<img src="../img/icon/null24.png" title="Unknown">'
    ];
  ////0 冷机运行状态
    switch(statusArray[0]){
      case '000':
        result[0]='<img src="../img/icon/poweroff.png" title="Power off">';
        break;
      case '001':
        result[0]='<img src="../img/icon/poweron.png" title="Power on">';
        break;
      case '010':
        result[0]='<img src="../img/icon/heating.png" title="Heating">';
        break;
      case '011':
        result[0]='<img src="../img/icon/defrost.png" title="Defrost">';
        break;
      case '100':
        result[0]='<img src="../img/icon/null.png" title="Null">';
        break;
      case '101':
        result[0]='<img src="../img/icon/pretrip.png" title="Pretrip">';
        break;
      case '110':
        result[0]='<img src="../img/icon/sleep.png" title="Sleep">';
        break;
      case '111':
        result[0]='<img src="../img/icon/null.png" title="Null">';
        break;
    }
    result[0] = statusArray[0] == 1 ? '<img src="../img/icon/poweron.png" title="poweron">':'<img src="../img/icon/poweroff.png" title="Power off">';
  //1：冷机运行cs模式  0:cycle 1:continuous
    if(statusArray[1] == 1){
      result[1] = '<img src="../img/icon/continuous.png" title="Continuous OP mode">';
    }else{
      result[1] = '<img src="../img/icon/cycle.png" title="Cycle sentry OP mode">';
    }
  //2：冷机是否高速状态，0:not high，1 higt speed
    if(statusArray[2] == 1){
      result[2] = '<img src="../img/icon/highspeed.png" title="High speed">';
    }else{
      result[2] = '<img src="../img/icon/highspeed_none.png" title="Not in high speed">';
    }
  //3：门开关 0,开门，1关门，2，未设置
    switch(parseInt(statusArray[3])){
      case 0:
        result[3]='<img src="../img/icon/dooropen.png" title="Door open">';
        break;
      case 1:
        result[3]='<img src="../img/icon/doorclose.png" title="Door closed">';
        break;
      case 2:
        result[3]='<img src="../img/icon/null24.png" title="No door setting">';
        break;
      default:

        break;
    }
  //4：油电模式 0:diesel 1:electric
    if(statusArray[4] == 1){
      result[4] = '<img src="../img/icon/electric.png" title="Electric mode">';
    }else{
      result[4] = '<img src="../img/icon/diesel.png" title="Diesel mode">';
    }
  //5：新风开启状态，0开启，1关闭
    switch (parseInt(statusArray[5])) {
      case 1:
        result[5] = '<img src="../img/icon/freshair_close.png" title="Fresh air stoped">';
        break;
      case 0:
        result[5] = '<img src="../img/icon/freshair_open.png" title="Fresh air running">';
        break;
      default:
        result[5] = '<img src="../img/icon/null24.png" title="No fresh air setting">';
        break;
    }
  return result;
}
/* 
  说明 分析冷机运行模式，返回六个状态数组
  0:冷机运行状态 000，001，010，011，100，101，110，111
  1：冷机运行cs模式 0 1
  2：冷机是否高速状态，0，1
  3：门开关0,开门，1关门，
  4：油电模式 0 油汽模式，1电器模式
  5：新风开启状态，0关闭，1开始
  参数说明：参数使用后台返回的数据即可，每个参数名对应返回的数据字段名
  zone_status,
  gps_door1,
  reserve7,
  cooler_off_flag,
  box_id
*/
//冷机状态
function coolerStatus(zone_status,gps_door1,reserve7,cooler_off_flag,box_id){
  var dtuSet = 0;
  var result=['-','-','-','-','-','-'];
  if(dtuSet == 0){//
    var zoneStatusBinStr = leftPad(parseInt(zone_status).toString(2),8);
    //console.log(hasCooler+'-'+zoneStatusBinStr);
    //console.log(zoneStatusBinStr);
    //运行状态
      var runCode = zoneStatusBinStr.substr(0,3);
      result[0] = runCode;
      result[0] = cooler_off_flag == 1 ? 1 : 0;
    //cs模式
      var csCode = zoneStatusBinStr.substr(3,1);//substr(zoneStatusBinStr,3,1);
      result[1] = csCode;
    //hs，是否高速模式
      var hsCode = zoneStatusBinStr.substr(4,1);//substr($zoneStatusBinStr,4,1);
      result[2] = hsCode;
    //门开关
      result[3] = gps_door1;
    //油电模式
      var dsCode = zoneStatusBinStr.substr(6,1);//substr($zoneStatusBinStr, 6,1);
      result[4] = dsCode;
    //新风开关
      result[5] = reserve7;
      //三联花木20台新风临时取反
      if(box_id >= 21711001 && box_id <= 21711020){result[5] = result[5] ==0 ? 1 : 0;}
  }
  return result;
}
//左边补齐字符串函数
function leftPad(str,num){
	if(str.length >= num) return str;
	for(i=0;i < num; i++){
		if(str.length >= num) break;
		str = '0'+str;
	}
	return str;
}
//方向显示角度转描述
function parseDirection(angle){
	if(angle < 0){return '-';}
	var modulo = parseInt(angle/45);
	var remainder = angle - modulo*45;
	remainder = parseInt(remainder);
	var direction = '-';
	switch(modulo){
		case 0://北偏东
			direction = remainder > 0 ?'北偏东'+ remainder +'度':'正北';
			break;
		case 1://东偏北
			direction = '东偏北'+ (45 - remainder) +'度';
			break;
		case 2://东偏南
			direction = remainder > 0 ?'东偏南'+ remainder +'度':'正东';
			break;
		case 3://南偏东
			direction = '南偏东'+ (45 - remainder) +'度';
			break;
		case 4://南偏西
			direction = remainder > 0 ?'南偏西'+ remainder +'度':'正南';
			break;
		case 5://西偏南
			direction = '西偏南'+ (45 - remainder) +'度';
			break;
		case 6://西偏北
			direction = remainder > 0 ?'西偏北'+ remainder +'度':'正西';
			break;
		case 7://西偏北
			direction = '北偏西'+ (45 - remainder) +'度';
			break;
	}
	return direction;
}
//平均温度
function calAverageTemp(tempArray,errorValue,valCategory){
  errorValue = errorValue == undefined? -999 : errorValue;
  valCategory = valCategory == undefined?'int':valCategory;
  if(tempArray.length <= 0){
    return '--';
  }
  var num=0;
  var sum=0;
  for (var i = tempArray.length - 1; i >= 0; i--) {
    if(tempArray[i] != errorValue && tempArray[i] != errorValue/10 ){
      if(valCategory == 'int'){
        sum += parseInt(tempArray[i]);
      }else{
        sum += parseFloat(tempArray[i]);
      }
      num ++;
    }
  }
  if(num > 0){
    //return
    return toDecimal(sum/num);
  }else{
    return '-';
  }
}
//华氏度转摄氏度
function F2C(t){
	if (t=='-') return t;
	return parseInt(10*(t - 32)/1.8)/10;
}
//手机号输入校验
function pho(v){
	var reg = /^1[3|5|7|9]\d{9}([,]1[3|5|7|9]\d{9}){0,3}$/;
	if(!reg.test(v)){
		layer.alert('输入的手机号不符合规范');
	}
}
//报警部分
function alarmType(code,cat){
	switch(code){
		case 't1':
			if(cat==1){
				return '温度1过高';
			}else if(cat == 2){
				return '温度1过低';
			}
			break;
		case 't2':
			if(cat==1){
				return '温度2过高';
			}else if(cat == 2){
				return '温度2过低';
			}
			break;
		case 't3':
			if(cat==1){
				return '温度3过高';
			}else if(cat == 2){
				return '温度3过低';
			}
			break;
		case 't4':
			if(cat==1){
				return '温度4过高';
			}else if(cat == 2){
				return '温度4过低';
			}
			break;
		case 'h1':
			if(cat==1){
				return '湿度过高';
			}else if(cat == 2){
				return '湿度过低';
			}
			break;
		case 'o1':
			if(cat==1){
				return '油位过高';
			}else if(cat == 2){
				return '油位过低';
			}
			break;
		case 'v1':
			if(cat==1){
				return '电压过高';
			}else if(cat == 2){
				return '电压过低';
			}
			break;
		case 'v2':
			if(cat==1){
				return '电压过高';
			}else if(cat == 2){
				return '电压过低';
			}
			break;
	}
}	
// 上传监控时间
function monitoring_time(id){
  $.ajax({
    type: "post",
    url:  reqDomain + "/schedule/monitor_save",
    data: "data",
    dataType: "dataType",
    data: {
      box_id: id,
      user_id: userId
    },
    xhrFields: {
			withCredentials: true
		},
		success: function(data){
			if(data.code == 200){
				console.log('上传成功')
			} else if(data.code == 401){
				window.location.href = "../login.html";
			}else if(data.code == 400){
				alert(data.reason);
			}
		}
  });
}
// 初始化监控时间
function insert_time(data){
  window.insertT = [];
  for(var i=0;i<data.length;i++){
    insertT.push({
      'box_id': data[i].box_id,
      'name': data[i].name,
      'insert_time': 0
    })
    for(var j=0;j<latestT.length;j++){
      if(data[i].box_id == latestT[j].box_id){
        insertT[i].insert_time = parseInt(latestT[j].insert_time)*1000
      }
    }
  }
}
//时间格式转换
function formatTime1(date) {
    if(date == 0 || date == null){
        return ''
    }
		var date = new Date(date * 1000);
		var year = date.getFullYear()
		var month = date.getMonth() + 1
    var day = date.getDate()
    var hour = date.getHours()
    var minute = date.getMinutes()
    var second = date.getSeconds()
		return [year, month, day].map(formatNumber).join('-') + ' ' + [hour, minute, second].map(formatNumber).join(':')
}