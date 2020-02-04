<?
header("Content-type:text/html;charset=GBK");
$con = oci_connect('scott','Adu_2019','192.168.31.62/ORCL') or die('数据库连接失败');
//phpinfo();
$sql="select * from EMP";
$stmt = oci_parse($con,$sql);
oci_execute($stmt, OCI_DEFAULT);
var_export(oci_fetch_array($stmt, OCI_ASSOC));