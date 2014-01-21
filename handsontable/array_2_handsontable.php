<!-- 编辑配置项 -->
<?php
$configKey=$_REQUEST['curKey'];
$configData=AdminHelper::getConfigKeyByField($configKey);
$configKeyName=$configData['name'];
$configValues=$configData['value'];

/**
 * 拆解一行数据，将其属性提取出来作为表格的表头
 * @param String $key 第一次调用的时候传入值必须为 ''空字符串或者null
 * @param Array $value 一行的数据
 * @param Array $cols 提取出来的列
 * @param Array $rowStr 一行的数据,将所有列拼接
 */
function fuckRowValues($key,$value,&$cols,&$rows){
	foreach($value as $vkey=>$vVal){
		if(empty($key)){
			$tmpKey=$vkey;
		}else{
			$tmpKey=$key.'.'.$vkey;
		}
		//
		if(is_array($vVal)){
			fuckRowValues($tmpKey,$vVal,$cols,$rows);
		}else{
			$cols[$tmpKey]='"'.$tmpKey.'"';
			$rows[$tmpKey]=$vVal;
		}
	}
}

/**
 * 将一行数组拼合成字符串,如果数组中值为字符串，则将字符串包在双引号中
 * @param Array $row
 */
function fuckRow2String($row,$maxColumns=array()){
	$rowString='';
	foreach($maxColumns as $colKey=>$value){
		//如果该行数据中没有这一列属性，则设置为空字符串
		if(!isset($row[$colKey])){
			$rowString.='"",';
		}else{
			//如果是字符串，则将字符串中的英文双引号"进行转义，防止handsontable插件出错
			if(is_string($row[$colKey])){
				$value=str_replace('"','\"',$row[$colKey]);
				$rowString.='"'.$value.'",';
			}else{
				$rowString.=$row[$colKey].',';
			}
		}
	}
	return $rowString;
}

//提取出最长的列来
$maxColumns=array();
//每一行的数据数组
$rowsArray=array();
foreach($configValues as $rowKey=>$rowValue){
	$cols=array(
			'key'=>'"key"'
	);
	$row=array(
			'key'=>$rowKey //将每一行的key放到一行数组的第一个
	);
	fuckRowValues(null,$rowValue,$cols,$row);
	$rowsArray[]=$row;
	if(count($cols)>count($maxColumns)){
		$maxColumns=$cols;
	}
}
//handsontable表格的表头
$tableHeads=implode(',',$maxColumns);
?>
<div class="div-area">
	<div class="div-header">
	<?php echo t('修改配置项')."----$configKey [$configKeyName]"?>
	<span class="right">
			<button type="button" id="btnSave" class="btn btn-success"><?php echo t('保存')?></button>
		</span>
	</div>
	<div id="editExcel" class="handsontable"></div>
</div>
<!-- 加载 handsontable插件-->
<script src="res/handsontable/jquery.handsontable.full.js"></script>
<link rel="stylesheet" media="screen"
	href="res/handsontable/jquery.handsontable.full.css">
<script type="text/javascript">

var container=$("#editExcel");/* 表格控件 */
/* 加载数据 */
$(document).ready(function () {
	var data = [
	            [<?php echo $tableHeads?>],
	            <?php
													foreach($rowsArray as $rowKey=>$rowValue){
														$tableRowStr=fuckRow2String($rowValue,$maxColumns);
														echo "[$tableRowStr],";
													}
													?>
	          ];
	container.handsontable({
		  data: data,
		  minSpareRows:3,
		  minSpareCols:2,
		  colHeaders: true,
		  contextMenu: true,
		  manualColumnResize:true,
		  manualColumnMove:true,
		});
	$("#btnSave").bind("click",function(){
// 		var handsontable=container.data("handsontable");
// 		alert(handsontable.getData());
		if(confirm("<?php echo t('请谨慎提交！提交后的数据会直接运行于上线游戏中')?>")){
			saveConfig();
		}
	});
});
/* 保存配置  */
function saveConfig(){
	var handsontable=container.data("handsontable");
	alert(handsontable.getData());
	$.ajax({
		url: "action/ajax/save_configKey.php",
		data: {"configValue": handsontable.getData(),"configKey":"<?php echo $configKey?>"},
		dataType: 'json',
		type: 'POST',
		success:function(res){
			alert(res.data);
			if(res.result ===1) {
				alert('Data saved');
				}
			else{
				alert("Save error");
				}
			},
		error:function(){}
		});
}
</script>
<style type="text/css">
.handsontable th,.handsontable td {
	max-width: 200px;
	overflow: hidden;
	padding: 2px 10px;
	text-overflow: ellipsis;
	white-space: nowrap;
}
</style>