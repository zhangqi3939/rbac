var map;//定义map
var markerClusterer;
var markerArray=[];//google map marker array
//google 坐标转换
var GPS = {
	PI : 3.14159265358979324,
	x_pi : 3.14159265358979324 * 3000.0 / 180.0,
	delta : function (lat, lon) {
		// Krasovsky 1940
		// a = 6378245.0, 1/f = 298.3
		// b = a * (1 - f)
		// ee = (a^2 - b^2) / a^2;
		var a = 6378245.0; //  a: 卫星椭球坐标投影到平面地图坐标系的投影因子。
		var ee = 0.00669342162296594323; //  ee: 椭球的偏心率。
		var dLat = this.transformLat(lon - 105.0, lat - 35.0);
		var dLon = this.transformLon(lon - 105.0, lat - 35.0);
		var radLat = lat / 180.0 * this.PI;
		var magic = Math.sin(radLat);
		magic = 1 - ee * magic * magic;
		var sqrtMagic = Math.sqrt(magic);
		dLat = (dLat * 180.0) / ((a * (1 - ee)) / (magic * sqrtMagic) * this.PI);
		dLon = (dLon * 180.0) / (a / sqrtMagic * Math.cos(radLat) * this.PI);
		return {'lat': dLat, 'lon': dLon};
	},
	//WGS-84 to GCJ-02
	gcj_encrypt : function (wgsLat, wgsLon) {
		if (this.outOfChina(wgsLat, wgsLon)) return {'lat': wgsLat, 'lon': wgsLon};
		var d = this.delta(wgsLat, wgsLon);
		return {'lat' : parseFloat(wgsLat) + parseFloat(d.lat),'lon' : parseFloat(wgsLon) + parseFloat(d.lon)};
	},
	//GCJ-02 to WGS-84
	gcj_decrypt : function (gcjLat, gcjLon) {
		if (this.outOfChina(gcjLat, gcjLon))
			return {'lat': gcjLat, 'lon': gcjLon};
		 
		var d = this.delta(gcjLat, gcjLon);
		return {'lat': gcjLat - d.lat, 'lon': gcjLon - d.lon};
	},
	//GCJ-02 to WGS-84 exactly
	gcj_decrypt_exact : function (gcjLat, gcjLon) {
		var initDelta = 0.01;
		var threshold = 0.000000001;
		var dLat = initDelta, dLon = initDelta;
		var mLat = gcjLat - dLat, mLon = gcjLon - dLon;
		var pLat = gcjLat + dLat, pLon = gcjLon + dLon;
		var wgsLat, wgsLon, i = 0;
		while (1) {
			wgsLat = (mLat + pLat) / 2;
			wgsLon = (mLon + pLon) / 2;
			var tmp = this.gcj_encrypt(wgsLat, wgsLon)
			dLat = tmp.lat - gcjLat;
			dLon = tmp.lon - gcjLon;
			if ((Math.abs(dLat) < threshold) && (Math.abs(dLon) < threshold))
				break;
 
			if (dLat > 0) pLat = wgsLat; else mLat = wgsLat;
			if (dLon > 0) pLon = wgsLon; else mLon = wgsLon;
 
			if (++i > 10000) break;
		}
		//console.log(i);
		return {'lat': wgsLat, 'lon': wgsLon};
	},
	//GCJ-02 to BD-09
	bd_encrypt : function (gcjLat, gcjLon) {
		var x = gcjLon, y = gcjLat;  
		var z = Math.sqrt(x * x + y * y) + 0.00002 * Math.sin(y * this.x_pi);  
		var theta = Math.atan2(y, x) + 0.000003 * Math.cos(x * this.x_pi);  
		bdLon = z * Math.cos(theta) + 0.0065;  
		bdLat = z * Math.sin(theta) + 0.006; 
		return {'lat' : bdLat,'lon' : bdLon};
	},
	//BD-09 to GCJ-02
	bd_decrypt : function (bdLat, bdLon) {
		var x = bdLon - 0.0065, y = bdLat - 0.006;  
		var z = Math.sqrt(x * x + y * y) - 0.00002 * Math.sin(y * this.x_pi);  
		var theta = Math.atan2(y, x) - 0.000003 * Math.cos(x * this.x_pi);  
		var gcjLon = z * Math.cos(theta);  
		var gcjLat = z * Math.sin(theta);
		return {'lat' : gcjLat, 'lon' : gcjLon};
	},
	//WGS-84 to Web mercator
	//mercatorLat -> y mercatorLon -> x
	mercator_encrypt : function(wgsLat, wgsLon) {
		var x = wgsLon * 20037508.34 / 180.;
		var y = Math.log(Math.tan((90. + wgsLat) * this.PI / 360.)) / (this.PI / 180.);
		y = y * 20037508.34 / 180.;
		return {'lat' : y, 'lon' : x};
		/*
		if ((Math.abs(wgsLon) > 180 || Math.abs(wgsLat) > 90))
			return null;
		var x = 6378137.0 * wgsLon * 0.017453292519943295;
		var a = wgsLat * 0.017453292519943295;
		var y = 3189068.5 * Math.log((1.0 + Math.sin(a)) / (1.0 - Math.sin(a)));
		return {'lat' : y, 'lon' : x};
		//*/
	},
	// Web mercator to WGS-84
	// mercatorLat -> y mercatorLon -> x
	mercator_decrypt : function(mercatorLat, mercatorLon) {
		var x = mercatorLon / 20037508.34 * 180.;
		var y = mercatorLat / 20037508.34 * 180.;
		y = 180 / this.PI * (2 * Math.atan(Math.exp(y * this.PI / 180.)) - this.PI / 2);
		return {'lat' : y, 'lon' : x};
		/*
		if (Math.abs(mercatorLon) < 180 && Math.abs(mercatorLat) < 90)
			return null;
		if ((Math.abs(mercatorLon) > 20037508.3427892) || (Math.abs(mercatorLat) > 20037508.3427892))
			return null;
		var a = mercatorLon / 6378137.0 * 57.295779513082323;
		var x = a - (Math.floor(((a + 180.0) / 360.0)) * 360.0);
		var y = (1.5707963267948966 - (2.0 * Math.atan(Math.exp((-1.0 * mercatorLat) / 6378137.0)))) * 57.295779513082323;
		return {'lat' : y, 'lon' : x};
		//*/
	},
	// two point's distance
	distance : function (latA, lonA, latB, lonB) {
		var earthR = 6371000.;
		var x = Math.cos(latA * this.PI / 180.) * Math.cos(latB * this.PI / 180.) * Math.cos((lonA - lonB) * this.PI / 180);
		var y = Math.sin(latA * this.PI / 180.) * Math.sin(latB * this.PI / 180.);
		var s = x + y;
		if (s > 1) s = 1;
		if (s < -1) s = -1;
		var alpha = Math.acos(s);
		var distance = alpha * earthR;
		return distance;
	},
	outOfChina : function (lat, lon) {
		if (lon < 72.004 || lon > 137.8347)
			return true;
		if (lat < 0.8293 || lat > 55.8271)
			return true;
		return false;
	},
	transformLat : function (x, y) {
		var ret = -100.0 + 2.0 * x + 3.0 * y + 0.2 * y * y + 0.1 * x * y + 0.2 * Math.sqrt(Math.abs(x));
		ret += (20.0 * Math.sin(6.0 * x * this.PI) + 20.0 * Math.sin(2.0 * x * this.PI)) * 2.0 / 3.0;
		ret += (20.0 * Math.sin(y * this.PI) + 40.0 * Math.sin(y / 3.0 * this.PI)) * 2.0 / 3.0;
		ret += (160.0 * Math.sin(y / 12.0 * this.PI) + 320 * Math.sin(y * this.PI / 30.0)) * 2.0 / 3.0;
		return ret;
	},
	transformLon : function (x, y) {
		var ret = 300.0 + x + 2.0 * y + 0.1 * x * x + 0.1 * x * y + 0.1 * Math.sqrt(Math.abs(x));
		ret += (20.0 * Math.sin(6.0 * x * this.PI) + 20.0 * Math.sin(2.0 * x * this.PI)) * 2.0 / 3.0;
		ret += (20.0 * Math.sin(x * this.PI) + 40.0 * Math.sin(x / 3.0 * this.PI)) * 2.0 / 3.0;
		ret += (150.0 * Math.sin(x / 12.0 * this.PI) + 300.0 * Math.sin(x / 30.0 * this.PI)) * 2.0 / 3.0;
		return ret;
	}
};
//切换地图
function changeMap(mapType){
	window.localStorage.setItem('mapType', mapType);
	initMap();
	openTarget(window.localStorage.getItem("box_id"));
}
//获取地图类型分
function getMapType(){
	var mapType = window.localStorage.getItem('mapType');
	if(mapType == undefined || mapType == null){
		mapType = 'baidu';
	}
	return mapType;
}
//初始化地图
function initMap(){
	var mapType = getMapType();
	if(mapType == 'baidu'){
		initMap_baidu();
	}else if(mapType == 'google'){
		initMap_google();
	}
}
//baidu map
	//初始化百度地图
	function initMap_baidu(){
		map = null;
		map = new BMap.Map("map_container"); 
		// point = new BMap.Point(96.404, 35.917);
		point = new BMap.Point(101.404, 35.917);
		map.centerAndZoom(point, 5);// 初始化地图，设置中心点坐标和地图级别 
		map.setMinZoom(0);
		map.enableScrollWheelZoom();
		//添加左上角地图控制
		var top_left_navigation = new BMap.NavigationControl({anchor: BMAP_ANCHOR_TOP_LEFT}); 
		map.addControl(top_left_navigation);
		//清除当前地图内容
		map.clearOverlays();
		window.map = map;
		if(window.full_map == 1){
			addMark_baidu();
		}
	}
	//添加位置点
	function addMark_baidu(){
		var points = JSON.parse(window.localStorage.getItem('new_mapTable'))||JSON.parse(window.localStorage.getItem('new_searchData_addr'));
		var label_offset_size=new BMap.Size(24,0);
		var markers = [];
		for (var i = 0; i < points.length; i++){
			//wgs84坐标 to 百度坐标
			var lnglat = wgs84togcj02(parseFloat(points[i].longitude),parseFloat(points[i].latitude));
			lnglat = gcj02tobd09(lnglat[0],lnglat[1]);
			points[i].lng_bd = lnglat[0];
			points[i].lat_bd = lnglat[1];

			var p = new BMap.Point(parseFloat(points[i].lng_bd),parseFloat(points[i].lat_bd));
			var marker = new BMap.Marker(p);
			//var lb = new BMap.Label(points[i].name,{offset:label_offset_size});
			//marker.setLabel(lb);
			marker.setIcon(new BMap.Icon(markerIcon(points[i]),new BMap.Size(41,51)));
			addClickHandler_baidu(points[i],marker);
			// marker.addEventListener('click',function(e){
			// 	console.log(points[i]);
			// 	//openInfo_baidu(points[i],e);
			// });
			
			markers.push(marker);
			
			//console.log([points[i].lng_bd,points[i].lat_bd]);
			//marker.click();
		}
		markerClusterer = new BMapLib.MarkerClusterer(map, {markers:markers});

	}
	//添加一个位置
	function addMarkOne_baidu(box){
		var label_offset_size=new BMap.Size(24,0);
		//wgs84坐标 to 百度坐标
		var lnglat = wgs84togcj02(parseFloat(box.longitude),parseFloat(box.latitude));
		lnglat = gcj02tobd09(lnglat[0],lnglat[1]);
		box.lng_bd = lnglat[0];
		box.lat_bd = lnglat[1];
		var p = new BMap.Point(parseFloat(box.lng_bd),parseFloat(box.lat_bd));
		var marker = new BMap.Marker(p);
		//var lb = new BMap.Label(box.name,{offset:label_offset_size});
		//marker.setLabel(lb);
		marker.setIcon(new BMap.Icon(markerIcon(box),new BMap.Size(41,51)));
		addClickHandler_baidu(box,marker);
		// marker.addEventListener('click',function(e){
		// 	console.log(box);
		// 	//openInfo_baidu(box,e);
		// });
		map.addOverlay(marker);
		//console.log([box.lng_bd,box.lat_bd]);
		//marker.click();
	}
	function addClickHandler_baidu(box,marker){
		marker.addEventListener("click",function(e){
			openInfo_baidu(box,e)}
		);
	}
	//打开信息窗口
	function openInfo_baidu(box,e){
				var p = e.target;
				var point = new BMap.Point(p.getPosition().lng, p.getPosition().lat);
				var opts = {	
					width : 240,	 // 信息窗口宽度	
					height: 200	 // 信息窗口高度 
				};
				var geo = new BMap.Geocoder();
				geo.getLocation(point,function(result){
					var addr = result.address;
					if(addr == ""){
						addr = result.addressComponents.province;
						if(result.addressComponents.city.length > 1){
							addr += " "+ result.addressComponents.city;
						}
						if(result.addressComponents.district.length > 1){
							addr += " "+ result.addressComponents.district;
						}
						if(result.addressComponents.street.length > 1){
							addr += " "+ result.addressComponents.street;
						}
					}
					map.openInfoWindow(new BMap.InfoWindow(makeInfoWindow(box,addr),opts),point); //开启信息窗口
					
				});
	}
//google map
	//初始化谷歌地图
	function initMap_google(){
		var center = {lat: 35.363, lng: 101.044};
		map = new google.maps.Map(document.getElementById('map_container'), {
			zoom: 4,
			center: center,
			zoomControl: true,
			mapTypeControl: true,
			scaleControl: true,
			streetViewControl: true,
			rotateControl: false,
			fullscreenControl: false,
			mapTypeControlOptions: {
				style:google.maps.MapTypeControlStyle.DROPDOWN_MENU
			},
			mapTypeId:google.maps.MapTypeId.HYBRID,
		});
		window.map = map;
		//addMark_google();
		if(window.full_map == 1){
			addMark_google();
		}
	}
	//添加点
	function addMark_google(){
		var box_latest = JSON.parse(window.localStorage.getItem('new_searchData'));
		var points=box_latest;
		//var markerArray=[];
		for (var i = 0; i < points.length; i++){
			//wgs84坐标 to 百度坐标
			var gcj02 = GPS.gcj_encrypt(points[i].latitude,points[i].longitude);

			markerArray[i] = new google.maps.Marker({
				position: new google.maps.LatLng(gcj02.lat,gcj02.lon),
				//title:points[i].name,
				//label:points[i].name,
				icon:markerIcon(points[i]),
				clickable:true,
				//extData:boxs[i].box_id,
				map: map
			});
			addClickHandler_google(points[i],markerArray[i]);
		}
	}
	//添加一个点
	function addMarkOne_google(box){
		//var markerArray=[];
		//wgs84坐标 to 百度坐标
		var gcj02 = GPS.gcj_encrypt(box.latitude,box.longitude);

		markerArray[0] = new google.maps.Marker({
			position: new google.maps.LatLng(gcj02.lat,gcj02.lon),
			//title:box.name,
			//label:box.name,
			icon:markerIcon(box),
			clickable:true,
			//extData:boxs[i].box_id,
			map: map
		});
		addClickHandler_google(box,markerArray[0]);
	}
	//google listener
	//监听点击事件
	function addClickHandler_google(point,marker){
			marker.addListener('click',function(){
				var geocoder = new google.maps.Geocoder;
				var latlng = this.getPosition();
				var cont_add = '';
				geocoder.geocode({'location':latlng}, function(results, status) {
					if(status == 'OK'){
						if(results[1]){//获取到地址
							cont_add = results[1].formatted_address;
						}else{//没有地址
							cont_add = 'No address specified'
						}
					}else{//获取地址失败
						cont_add = 'GEO Error！'+status;
					}
					openInfo_google(point,cont_add,latlng);
				});
			});
	}
	//打开 google map窗口
	function openInfo_google(point,add,lnglatXY){
			var infoCont=makeInfoWindow(point,add);
			var infowindow = new google.maps.InfoWindow({
				content: infoCont,
				position:lnglatXY,
				width:240,
				height:200,
				pixelOffset:new google.maps.Size(0, -30)
			});
			infowindow.open(map);
			//map.setCenter(lnglatXY);
	}
//判断标注图标
function markerIcon(box){
	var icon='/img/zaixian4.png';
	var voltage = Number(box.gps_voltage);
	if(box.speed < 1){
		if(box.cooler_alarm_cnt != 0 && box.cooler_alarm_cnt != null){
			icon = '../img/baojing4.png';
		}else if(box.online != 1){
			icon = '../img/lixian4.png';
		}else if(voltage<=12){
			icon = '../img/xiumian4.png';
		}else{
			icon = '../img/zaixian4.png';
		}
	}else{
		if(box.direction < 0){
			if(box.cooler_alarm_cnt != 0 && box.cooler_alarm_cnt != null){
				icon = '../img/baojing4.png';
			}else if(box.online != 1){
				icon = '../img/lixian4.png';
			}else if(voltage<=12){
				icon = '../img/xiumian4.png';
			}else{
				icon = '../img/zaixian4.png';
			}
		}else if(292.5 <= box.direction && box.direction <337.5){
			if(box.cooler_alarm_cnt != 0 && box.cooler_alarm_cnt != null){
				icon = '../img/baojing1.png';
			}else if(box.online != 1){
				icon = '../img/lixian1.png';
			}else if(voltage<=12){
				icon = '../img/xiumian1.png';
			}else{
				icon = '../img/zaixian1.png';
			}
		}else if(247.5 <= box.direction  && box.direction <292.5){
			if(box.cooler_alarm_cnt != 0 && box.cooler_alarm_cnt != null){
				icon = '../img/baojing2.png';
			}else if(box.online != 1){
				icon = '../img/lixian2.png';
			}else if(voltage<=12){
				icon = '../img/xiumian2.png';
			}else{
				icon = '../img/zaixian2.png';
			}
		}else if(67.5 <= box.direction && box.direction <112.5){
			if(box.cooler_alarm_cnt != 0 && box.cooler_alarm_cnt != null){
				icon = '../img/baojing3.png';
			}else if(box.online != 1){
				icon = '../img/lixian3.png';
			}else if(voltage<=12){
				icon = '../img/xiumian3.png';
			}else{
				icon = '../img/zaixian3.png';
			}
		}else if((337.5 <= box.direction  && box.direction <360)|| (0 <= box.direction  && box.direction <22.5)){
			if(box.cooler_alarm_cnt != 0 && box.cooler_alarm_cnt != null){
				icon = '../img/baojing4.png';
			}else if(box.online != 1){
				icon = '../img/lixian4.png';
			}else if(voltage<=12){
				icon = '../img/xiumian4.png';
			}else{
				icon = '../img/zaixian4.png';
			}
		}else if(112.5 <= box.direction  && box.direction <157.5){
			if(box.cooler_alarm_cnt != 0 && box.cooler_alarm_cnt != null){
				icon = '../img/baojing5.png';
			}else if(box.online != 1){
				icon = '../img/lixian5.png';
			}else if(voltage<=12){
				icon = '../img/xiumian5.png';
			}else{
				icon = '../img/zaixian5.png';
			}
		}else if(22.5 <= box.direction  && box.direction <67.5){
			if(box.cooler_alarm_cnt != 0 && box.cooler_alarm_cnt != null){
				icon = '../img/baojing6.png';
			}else if(box.online != 1){
				icon = '../img/lixian6.png';
			}else if(voltage<=12){
				icon = '../img/xiumian6.png';
			}else{
				icon = '../img/zaixian6.png';
			}
		}else if(157.5 <= box.direction  && box.direction <202.5){
			if(box.cooler_alarm_cnt != 0 && box.cooler_alarm_cnt != null){
				icon = '../img/baojing7.png';
			}else if(box.online != 1){
				icon = '../img/lixian7.png';
			}else if(voltage<=12){
				icon = '../img/xiumian7.png';
			}else{
				icon = '../img/zaixian7.png';
			}
		}else if(202.5 <= box.direction  && box.direction <247.5){
			if(box.cooler_alarm_cnt != 0 && box.cooler_alarm_cnt != null){
				icon = '../img/baojing8.png';
			}else if(box.online != 1){
				icon = '../img/lixian8.png';
			}else if(voltage<=12){
				icon = '../img/xiumian8.png';
			}else{
				icon = '../img/zaixian8.png';
			}
		}
	}
	return icon;
}	
//组织弹窗窗口内容
function makeInfoWindow(box,address){
	//	console.log(F2C(dealDataValue(box.cooler_set_temp,'temp')) );
	var info = '<span id="mapTitle">'+ box.name+'('+box.box_id+')' +'</span>';
	// info +='<div id="mapWindowInfo">';
	// info += '定位地址：'+ address;
	// info += '<br/>定位时间：'+ formatTime(box.gps_time);
	// info += '<br/>速　　度：'+ Math.round(box.speed * 100)/100 +'Km/h';
	// info += '<br/>箱内温度：'+ ((Number(box.gps_temp2)+Number(box.gps_temp3))/2).toFixed(1)+'°C';
	// info += '<br/>设定温度：'+ box.cooler_set_temp +'°C' + '　环境温度：'+ box.ambient_temp + '°C';
	// info += '<br/>出风温度：'+ box.out_air_temp+'°C' + '　回风温度：'+ box.re_air_temp +'°C';
	var runInfo = box.cooler_off_flag == '正常' ? '运行':'关闭';
	// info += '<br/>机组模式：'+ runInfo;
	// info += '　  电压：'+ box.gps_voltage+'V';
	// info += '<br/>数据时间：'+ box.insert_time;
	// info += '</div>';

	info += '<table id="mapWindowInfo">';
	info += '<tr><td class="b">行驶速度：</td><td>'+ Math.round(box.speed * 100)/100 +'Km/h</td></tr>';
	info += '<tr><td class="b">箱内温度：</td><td>'+(isNaN(((Number(box.gps_temp2)+Number(box.gps_temp3))/2).toFixed(1))?'-':((Number(box.gps_temp2)+Number(box.gps_temp3))/2).toFixed(1))+'°C</td></<tr>';
	info += '<tr><td class="b">箱外温度：</td><td>'+box.gps_temp1+'°C</td></tr>';
	info += '<tr><td class="b">电瓶电压：</td><td>'+ box.gps_voltage+'V</td></tr>';
	info += '<tr><td class="b">定位时间：</td><td colspan="3">'+formatTime(box.gps_time)+'</td></tr>';
	info += '<tr><td class="b">数据时间：</td><td colspan="3">'+box.insert_time+'</td></tr>';
	info += '<tr><td class="b">定位地址：</td><td colspan="3" title='+address+'>'+address+'</td></tr></table>';
	return info;
}
//打开目标
function openTarget(box_id){
	var box_latest = JSON.parse(window.localStorage.getItem('new_searchData'));
	var points=box_latest;
	var mapType = window.localStorage.getItem('mapType');
	if(mapType == null) mapType = 'baidu';
	for (var i = 0; i < points.length; i++) {
		if(points[i].box_id == box_id){
			var box = points[i];
			if(mapType =='baidu'){///百度地图
				if(window.full_map != 1){
					//清除地图
						map.clearOverlays();
					//添加坐标点
						addMarkOne_baidu(points[i]);
				}
				
				//打开窗口
				//wgs84坐标 to 百度坐标
				var lnglat = wgs84togcj02(parseFloat(points[i].longitude),parseFloat(points[i].latitude));
				lnglat = gcj02tobd09(lnglat[0],lnglat[1]);
				points[i].lng_bd = lnglat[0];
				points[i].lat_bd = lnglat[1];

				var point = new BMap.Point(points[i].lng_bd, points[i].lat_bd);
				var opts = {	
					width : 250,// 信息窗口宽度	
					height: 200 // 信息窗口高度 
				};
				var geo = new BMap.Geocoder();
				geo.getLocation(point,function(result){
					//console.log(window.box);
					//map.openInfoWindow(makeInfoWindow(box,result.address,opts),point); //开启信息窗口
					map.openInfoWindow(new BMap.InfoWindow(makeInfoWindow(box,result.address),opts),point); //开启信息窗口
					// $("#index_address").html('地址：'+result.address);
					$("#info_address").html(result.address);
				});
			}else if(mapType =='google'){//google地图
				if(window.full_map != 1){
					//清除地图
						clearOverlay_google();
					//添加坐标点
						addMarkOne_google(points[i]);
				}
				var gcj02 = GPS.gcj_encrypt(points[i].latitude,points[i].longitude);
				var latlng = new google.maps.LatLng(gcj02.lat,gcj02.lon);
				var cont_add = '';
				var geocoder = new google.maps.Geocoder;
				geocoder.geocode({'location':latlng}, function(results, status) {
					if(status == 'OK'){
						if(results[1]){//获取到地址
							cont_add = results[1].formatted_address;
						}else{//没有地址
							cont_add = 'No address specified'
						}
					}else{//获取地址失败
						cont_add = 'GEO Error！'+status;
					}
					openInfo_google(box,cont_add,latlng);
					// $("#index_address").html('地址：'+cont_add);
					$("#info_address").html(results.address);
				});
			}
		}
	}
	layer.closeAll();
}


//full map 页面
	//轨迹
	function loadTrack(){
		var startTime = formatTime($("#startTime").val() + ':00');
		var endTime = formatTime($("#endTime").val() + ':00');
		if(parseInt(endTime - startTime) / 3600 > 72) {
			layer.msg('轨迹查询时间间隔不能超过72小时。', {
				icon: 15
			});
			return;
		}
		//选择一个箱子
		selectedBoxID = $("#box_select_list").val();
		if(selectedBoxID <= 0) {
			layer.msg('请选择一个箱体查询');
			return;
		}
		window.points = [];
		//加载轨迹
				$.ajax({
					url: 'http://tk2.qianbitou.com/D/map_container_track_json',
					type: 'POST',
					data: {
						id: selectedBoxID,
						"startTime": $("#startTime").val(),
						"endTime": $("#endTime").val(),
						'ts': $.now()
					},
					dataType: "json",
					xhrFields: {
						withCredentials: true
					},
					beforeSend: function() {
						layer.msg(' 加载项目 ...', {
							icon: 16,
							shade: [0.3, '#fff'],
							time: 4000
						});
					},
					success: function(json) {
						layer.closeAll();
						window.points = [];
						var points = [];
						if(json.status == 0) {
							layer.msg(json.msg, {
								shade: [0.1, '#fff'],
								time: 2000
							});
							return;
						}
						boxs = json.boxs;
						if(boxs.length <= 0) {
							layer.msg('未查询到轨迹点');
							return;
						}
						var boxSelectLength = $("#boxSelect option").size(); //箱子选择下拉表个数
						for(var i = 0; i < boxs.length; i++) {
							var lnglat = wgs84togcj02(parseFloat(boxs[i].longitude), parseFloat(boxs[i].latitude));
							lnglat = gcj02tobd09(lnglat[0], lnglat[1]);
							boxs[i].lng_bd = lnglat[0];
							boxs[i].lat_bd = lnglat[1];
							points.push(boxs[i]);
						}
						window.points = points;
						boxs = null;
						//添加地图轨迹
						//console.log(window.points);
						addTrack(window.points);
					},
					error: function(json) {
						layer.msg('加载失败', {
							shade: [0.1, '#fff'],
							time: 2000
						});
					}
				});
	}
	//打开位置信息
	function addTrack(points){
		var mapType = getMapType();
		if(mapType == 'baidu'){
			addTrack_baidu(points);
		}else{
			addTrack_google(points);
		}
	}
	//轨迹
	function addTrack_baidu(points){
				//清空地图
				map.clearOverlays();
				//markerArray.length = 0;
				markerClusterer.clearMarkers();
				var label_offset_size = new BMap.Size(24, 0);
				var path = [];
				for(var i = points.length - 1; i >= 0; i--) {
					var p = new BMap.Point(parseFloat(points[i].lng_bd), parseFloat(points[i].lat_bd));
					path.push(p);

					var marker = new BMap.Marker(p);
					if(i == points.length - 1) { //起始点
						marker.setIcon(new BMap.Icon(markerIcon(points[i]), new BMap.Size(41,51)));
						marker.setLabel(new BMap.Label('起始' + formatTime(points[i].insert_time), {
							offset: label_offset_size
						}));
						map.addOverlay(marker);
					} else if(i == 0) {
						marker.setIcon(new BMap.Icon(markerIcon(points[i]), new BMap.Size(41,51)));
						marker.setLabel(new BMap.Label('结束' + formatTime(points[i].insert_time), {
							offset: label_offset_size
						}));
						map.addOverlay(marker);
					} else {
						marker.setIcon(new BMap.Icon(markerIcon(points[i]), new BMap.Size(41,51)));
						//var lb = new BMap.Label(points[i].name,{offset:label_offset_size});
					}
					//addClickHandler(points[i],marker);
					//map.addOverlay(marker);
					//markerArray[i] = marker;
				}
				//path
				var symbol = new BMap.Symbol(
					BMap_Symbol_SHAPE_BACKWARD_OPEN_ARROW, //百度预定义的 箭头方向向下的非闭合箭头
					{
						fillColor: '#FFF', //设置矢量图标的填充颜色。支持颜色常量字符串、十六进制、RGB、RGBA等格式
						fillOpacity: 1, //设置矢量图标填充透明度,范围0~1
						scale: 0.4, //设置矢量图标的缩放比例
						//rotation:90, //设置矢量图标的旋转角度,参数为角度
						strokeColor: '#FFF', //设置矢量图标的线填充颜色,支持颜色常量字符串、十六进制、RGB、RGBA等格式
						strokeOpacity: 1, //设置矢量图标线的透明度,opacity范围0~1
						strokeWeight: 2, //旋设置线宽。如果此属性没有指定，则线宽跟scale数值相
					}
				);
				var iconSequence = new BMap.IconSequence(symbol, '10', '30');
				var polyLine = new BMap.Polyline(path, {
					icons: [iconSequence],
					strokeColor: "#23A9F6",
					strokeWeight: 6,
					strokeOpacity: 1
				});
				map.addOverlay(polyLine);
				map.centerAndZoom(path[path.length - 1], 10);
	}
	//轨迹
	function addTrack_google(points){
		//清空地图
		clearOverlay_google();
		var path=[];
		for (var i = points.length - 1; i>=0 ;i--){
			var gcj02 = GPS.gcj_encrypt(points[i].latitude,points[i].longitude);
			//记录起始点；
				if(i == points.length-1){
					startPoint = {lat:parseFloat(gcj02.lat), lng:parseFloat(gcj02.lon)};
				}
				if(i == 0){
					endPoint = {lat:parseFloat(gcj02.lat), lng:parseFloat(gcj02.lon)};
				}
			//存入path
			path.push({lat:parseFloat(gcj02.lat), lng:parseFloat(gcj02.lon)});
		}
		var lineSymbol = {
			path: 'M 0.4,0.4 0,0 M -0.4,0.4 0,0',
			strokeColor: '#FFF',
			strokeWeight: 4
			//path:google.maps.SymbolPath.FORWARD_CLOSED_ARROW
		};
		var polyline = new google.maps.Polyline({
			map: map,
			path: path,
			geodesic: true,
			icons:[{
				icon:lineSymbol,
				offset:'0',
				repeat:'50px'
			}],
			strokeColor: "#23A9F6",  //线颜色
			strokeOpacity: 1,     //线透明度
			strokeWeight: 10,      //线宽
			//strokeStyle: "solid",  //线样式
			//isOutline:true,
			//showDir:true,
		});
		//定位和放大地图
			map.setCenter(startPoint);
			map.setZoom(14);
	}
	function clearOverlay_google(){//清空谷歌地图
		if(markerArray){
			for(i in markerArray){
				markerArray[i].setMap(null);
			}
			markerArray.length=0;
		}
	}
	//回放
	function playBack(){
		var mapType = getMapType();
		if(mapType != 'baidu') {
			layer.msg('谷歌地图暂时不支持回放');
			return false;
		}
		var points = window.points;
		if(points.length <= 0){
			layer.msg('请先查询轨迹');
			return false;
		}

		var lushuPoints = [];
		for (var i = points.length-1; i>0; i--) {
			lushuPoints.push(new BMap.Point(points[i].lng_bd,points[i].lat_bd)); 
		}
		
		
		var lushu = new BMapLib.LuShu(map,lushuPoints,{
			defaultContent:'冷链中国行，中铁第一名...',
			speed: 1000,
			icon:new BMap.Icon('http://lbsyun.baidu.com/jsdemo/img/car.png', new BMap.Size(52,26),{anchor : new BMap.Size(27, 13)}),
			enableRotation:true,
			autoView:true,
		});
		lushu.start();
	}
	//围栏
	var overlaycomplete = function(e) {
		$("#fence_longitude").val(e.overlay.point.lng);
		$("#fence_latitude").val(e.overlay.point.lat);
		if(e.overlay.radius) {
			$("#fence_radius").val(e.overlay.radius);
		} else {
			$("#fence_radius").val(e.overlay.Ca);
		}
		layer.open({
			type: 1,
			title: '添加地理围栏',
			skin: 'layui-layer-lan',
			area: '516px',
			shadeClose: false,
			content: $('#fenceInfoDiv'),
			cancel: function() {
				map.clearOverlays();
			}
		});
	}
	//画围栏
	function drawFence() {
		var mapType = getMapType();
		if(mapType != 'baidu') {
			layer.msg('谷歌地图暂时不支持围栏');
			return false;
		}
		var styleOptions = {
			strokeColor: "red", //边线颜色。
			fillColor: "white", //填充颜色。当参数为空时，wu效果。
			strokeWeight: 2, //边线的宽度，以像素为单位。
			strokeOpacity: 0.8, //边线透明度，取值范围0 - 1。
			fillOpacity: 0.1, //填充的透明度，取值范围0 - 1。
			strokeStyle: 'solid' //边线的样式，solid或dashed。
		}
		//实例化鼠标绘制工具
		var drawingManager = new BMapLib.DrawingManager(map, {
			isOpen: true, //是否开启绘制模式
			enableDrawingTool: true, //是否显示工具栏
			drawingMode: BMAP_DRAWING_CIRCLE,
			drawingToolOptions: {
				anchor: BMAP_ANCHOR_BOTTOM_LEFT, //位置
				offset: new BMap.Size(5, 5), //偏离值
				drawingModes: [
					BMAP_DRAWING_CIRCLE
				]
			},
			polygonOptions: styleOptions, //圆的样式
		});
		drawingManager.addEventListener('overlaycomplete', overlaycomplete);
	}
	//保存围栏
		function saveFence() {
			var fence_name = $("#fence_name").val();
			var fence_category = $("#fence_category").val();
			var fence_longitude = $("#fence_longitude").val();
			var fence_latitude = $("#fence_latitude").val();
			var fence_radius = $("#fence_radius").val();
			if(fence_name.length < 2 || fence_category.length < 2 || fence_longitude.length < 2 || fence_latitude.length < 2 || fence_radius.length < 2) {
				layer.msg('请认真录入。');
				return false;
			}
			$.ajax({
				async: false,
				cache: false,
				type: 'POST',
				url: reqDomain + "/map/fence_add",
				data: $("#fence_form").serialize(),
				dataType: "json",
				xhrFields: {
					withCredentials: true
				},
				success: function(json) {
					if(json.code == '401') {
						layer.msg('用户未登录');
						return false;
						}
					if(json.code == '200') {
						layer.msg('操作完成');
						setTimeout(function() {
							layer.closeAll();
						}, 1500);
					} else {
						layer.msg(json.reason + json.code);
					}
				}
			});
		}
		//显示围栏
			function showFence() {
				var mapType = getMapType();
				if(mapType != 'baidu') {
					layer.msg('谷歌地图暂时不支持围栏');
					//return false;
				}
				$.ajax({
					async: false,
					cache: false,
					type: 'POST',
					url: reqDomain + "/map/fence_list", // 请求的action路径
					data: $("#fence_form").serialize(),
					dataType: "json",
					xhrFields: {
						withCredentials: true
					},
					success: function(json) {
						if(json.code == '401') {
							layer.msg('用户未登录');
							return false;
						}
						if(json.code == '200') {
							map.clearOverlays();
							var fenceList = json.result;
							if(fenceList == undefined || fenceList == null || fenceList.length <= 0) {
								layer.msg('未设置围栏');
							}
							var circleOptions = {
								strokeColor: "red", //边线颜色。
								fillColor: "white", //填充颜色。当参数为空时，wu效果。
								strokeWeight: 2, //边线的宽度，以像素为单位。
								strokeOpacity: 0.8, //边线透明度，取值范围0 - 1。
								fillOpacity: 0.3, //填充的透明度，取值范围0 - 1。
								strokeStyle: 'solid' //边线的样式，solid或dashed。
							};
							var labelOption = {
								color: "red",
								fontSize: "12px",
								height: "20px",
								lineHeight: "20px",
								fontFamily: "微软雅黑"
							};

							for(var i = fenceList.length - 1; i >= 0; i--) {

								var point = new BMap.Point(fenceList[i].longitude, fenceList[i].latitude);
								var radius = fenceList[i].radius;
								var fenceName = fenceList[i].name;
								var label = new BMap.Label(fenceName, {
									position: point
								});
								label.setStyle(labelOption);
								var circle = new BMap.Circle(point, radius, circleOptions);
								map.addOverlay(circle);
								map.addOverlay(label);
								// console.log(circle.toString());
								// console.log(label.toString());

								if(i == 0) {
									map.centerAndZoom(point, 14);
								}
							}
						} else {
							layer.msg(json.reason + json.code);
						}
					}
				});
			}
		//在地图上清空围栏
			function clearFence() {

			}
	//end of 围栏
//end of full map

//坐标系转换
	//定义一些常量
	　var x_PI = 3.14159265358979324 * 3000.0 / 180.0;
	　var PI = 3.1415926535897932384626;
	　var a = 6378245.0;
	　var ee = 0.00669342162296594323;
	
	function gcj02tobd09(lng, lat) { 
		var z = Math.sqrt(lng * lng + lat * lat) + 0.00002 * Math.sin(lat * x_PI);
		var theta = Math.atan2(lat, lng) + 0.000003 * Math.cos(lng * x_PI);
		var bd_lng = z * Math.cos(theta) + 0.0065;
		var bd_lat = z * Math.sin(theta) + 0.006;
		return [bd_lng, bd_lat];
	}
//编码格式转换
	function wgs84togcj02(lng, lat) {
		if (out_of_china(lng, lat)) {
		return [lng, lat]
		}else{
			var dlat = transformlat(lng - 105.0, lat - 35.0);
			var dlng = transformlng(lng - 105.0, lat - 35.0);
			var radlat = lat / 180.0 * PI;
			var magic = Math.sin(radlat);
			magic = 1 - ee * magic * magic;
			var sqrtmagic = Math.sqrt(magic);
			dlat = (dlat * 180.0) / ((a * (1 - ee)) / (magic * sqrtmagic) * PI);
			dlng = (dlng * 180.0) / (a / sqrtmagic * Math.cos(radlat) * PI);
			var mglat = lat + dlat;
			var mglng = lng + dlng;
			return [mglng, mglat];
		}
	}
	function transformlat(lng, lat) { 
		var ret = -100.0 + 2.0 * lng + 3.0 * lat + 0.2 * lat * lat + 0.1 * lng * lat + 0.2 * Math.sqrt(Math.abs(lng));
		ret += (20.0 * Math.sin(6.0 * lng * PI) + 20.0 * Math.sin(2.0 * lng * PI)) * 2.0 / 3.0;
		ret += (20.0 * Math.sin(lat * PI) + 40.0 * Math.sin(lat / 3.0 * PI)) * 2.0 / 3.0;
		ret += (160.0 * Math.sin(lat / 12.0 * PI) + 320 * Math.sin(lat * PI / 30.0)) * 2.0 / 3.0;
		return ret;
	}
	function transformlng(lng, lat) { 
		var ret = 300.0 + lng + 2.0 * lat + 0.1 * lng * lng + 0.1 * lng * lat + 0.1 * Math.sqrt(Math.abs(lng));
		ret += (20.0 * Math.sin(6.0 * lng * PI) + 20.0 * Math.sin(2.0 * lng * PI)) * 2.0 / 3.0;
		ret += (20.0 * Math.sin(lng * PI) + 40.0 * Math.sin(lng / 3.0 * PI)) * 2.0 / 3.0;
		ret += (150.0 * Math.sin(lng / 12.0 * PI) + 300.0 * Math.sin(lng / 30.0 * PI)) * 2.0 / 3.0;
		return ret;
	}

	function out_of_china(lng, lat) { 
		return (lng < 72.004 || lng > 137.8347) || ((lat < 0.8293 || lat > 55.8271) || false);
	}
//end of 坐标转换

///
function runStatus(v){
	v=parseInt(v);
	if(v<0 || v>255){
		return 'Error'
	}
	var bv = leftPad(v.toString(2),8);
	var code = bv.substr(0,3);
	if(code=='000') return 'Power Off';
	if(code=='001') return 'Cooling';
	if(code=='010') return 'Heating';
	if(code=='011') return 'Defrost';
	if(code=='100') return 'Null';
	if(code=='101') return 'Pretrip';
	if(code=='110') return 'Sleep';
	if(code=='111') return 'Reserved';
}
function makeAlarmStr(box) {
	var str = '';
	for(var i = 1; i <= 13; i++) {
		var k = 'cooler_alarm_level';
		k = k + i;
		if(box[k] != 0) {
			str += box[k] + ", ";
		}
	}
	return '<font color="red">' + str + '</font>';
}