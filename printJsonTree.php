<?php


/**
 * 将数组转化成符合jstree的数据格式
 * @param unknown $str
 * @param unknown $data
 */
private static function convertArrayToTree(&$str,$data){
	foreach($data as $key=>$value){
		if(is_array($value)){
			$str.='{"data":"'.$key.'",';
			$str.='"children":[';
			self::convertArrayToTree(&$str,$value);
			$str.=']},';
		}else{
			$str.='{"data":"'.$key.'=>'.$value.'"},';
		}
	}
}

/**
 * 输出树状的JSON数据:使用此方法前，一定要确保已经导入了jquery和 jstree.js库，否则无法显示
 * @param Number $divId
 * @param String $jsonData
 */
public static function printJsonTree($divId,$jsonData){
	$data=json_decode($jsonData,true);
	if(is_null($data)){
		$jsonStr="invalid JSON data:".$jsonStr;
	}else{
		$jsonStr='"data":[';
		self::convertArrayToTree(&$jsonStr,$data);
		$jsonStr.=']';
	}
	$javaScriptStr=<<<FUCK
		<script type="text/javascript">
									$("#{$divId}").jstree({
										"json_data" : {
                    {$jsonStr}
                    },
										"themes" :{
											"theme":"apple",
											},
										"plugins" : [ "themes", "json_data","ui"]
										})
								</script>
FUCK;
                    echo $javaScriptStr;
	}
?>