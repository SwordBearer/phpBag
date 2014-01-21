<?php

function saveAsCsv($file,$handsontableData){
	$csvStr='';
	foreach($handsontableData as $row){
		foreach($row as $cell){
			if(empty($cell)||$cell=='null'){
				$csvStr.='""';
			}else{
				$csvStr.=$cell;
			}
			$csvStr.=",";
		}
		$csvStr.="\r\n";
	}
	$csvStr=iconv('utf-8','gbk',$csvStr);
	return file_put_contents($file,$csvStr);
}

function startWith($string,$prefix){
	return (strpos($string,$prefix)===0);
}

function setProp(&$array,$key,$value){
	// var_dump( $key);
	$params=explode('.',$key);
	// $value = array_pop($params);
	$stack=& $array;
	foreach($params as $param){
		if(isset($stack[$param])){
			$stack=& $stack[$param];
		}else{
			$stack[$param]=array();
			$stack=& $stack[$param];
		}
	}
	$stack=$value;
}

function csv_decode($file_path){
	// 	global $language;
	$fp=fopen($file_path,'r');
	$result=array();
	
	$head_flag=true;
	$current_key='';
	while(($buffer=fgetcsv($fp))!==false){
		// echo json_encode($buffer);
		if(startWith($buffer[0],'//')||$buffer[0]==null||$buffer[0]=="\n"){
			continue; //ignore comment and break
		}
		
		if(startWith($buffer[0],'[')){
			$current_key=substr($buffer[0],1,-1);
			// echo $current_key;
			$head_flag=true;
			continue;
		}
		
		if($head_flag){
			$head=$buffer; //explode(',', $buffer);
			$head_flag=false;
		}else{
			$record=$buffer; //explode(',', $buffer);
			// var_dump($record);
			for($i=0;$i<count($head);$i++){
				if($head[$i]===null||$head[$i]==='')
					continue;
				if(!isset($record[$i]))
					continue;
				if($record[$i]=='')
					continue;
					// var_dump($record[$i]);
				if($head[$i]=='key'){
					$key=$record[$i];
				}else{
					if(!isset($record[$i]))
						$record[$i]=null;
					if(is_numeric($record[$i])){
						$intval=intval($record[$i]);
						if($record[$i]==$intval){
							$record[$i]=intval($record[$i]);
						}else{
							$record[$i]=floatval($record[$i]);
						}
					}else{
						$record[$i]=iconv("gbk","utf-8",$record[$i]);
						
						if(preg_match('/[\x7f-\xff]+/',$record[$i])){
							// 							$language[$record[$i]]=$record[$i];
							// echo $record[$i];
						}
					}
					if(startWith($record[$i],'[')||startWith($record[$i],'{')){
						$record[$i]=json_decode($record[$i],true);
						// var_dump($record[$i]);
					}
					
					if($head[$i]=='value'){
						setProp($result,$current_key.'.'.$key,$record[$i]);
					}else{
						setProp($result,$current_key.'.'.$key.'.'.$head[$i],$record[$i]);
					}
				}
			}
		}
	}
	if(!feof($fp)){
		echo "Error: unexpected fgets() fail\n";
	}
	
	// echo json_encode($result);
	// var_dump($result['skill_001']['level'][1]);
	fclose($fp);
	
	return $result;
}
// function saveAsArray($handsontableData){
// 	$headRow=array_shift($handsontableData);
// 	//如果表格每一行开始一格不是key，则输入错误
// 	if($headRow[0]!='key'){
// 		return false;
// 	}
// 	//如果表头中含有 . 号，则将表头拆分
// 	// 	$headArrays=explode('.',$headRow);
// 	// 	$heads=array();
// 	// 	foreach($headRow as $headCell){
// 	// 		$splitedHeads=explode('.',$headCell);
// 	// 		if(count($splitedHeads)>1){
// 	// 			foreach($splitedHeads as $head){
// 	// 				$heads[]=$head;
// 	// 			}
// 	// 		}else{
// 	// 			$heads[]=$splitedHeads[0];
// 	// 		}
// 	// 	}
// 	$phpArray=array();
// 	foreach($handsontableData as $row){
// 		$rowKey=$row[0]; //每一行的KEY,第一行的第一个
// 		unset($row[0]); //默认每一行的第一格为key，所以不放在值中，当然放里面也无大碍
// 		$cellIndex=1; //遍历每一行数组时的索引
// 		$rowArray=array(); //每一行的数组
// 		//如果该行的第一列为空，则忽略该行
// 		if(empty($rowKey)||$rowKey=='null'){
// 			continue;
// 		}
// 		//遍历每一行的数组
// 		foreach($row as $cell){
// 			$head=$headRow[$cellIndex];
// 			$cellIndex++;
// 			//如果该列没有表头或者值，则忽略
// 			if(empty($cell)||$cell=='null'||empty($head)||$head=='null'){
// 				continue;
// 			}
// 			$rowArray[$head]=$cell;
// 		}
// 		//如果该行的数组为空，则忽略，此时表格中只含有第一格，其余格为空
// 		if(!empty($rowArray)){
// 			$phpArray[$rowKey]=$rowArray;
// 		}
// 	}
// 	return $phpArray;
// }
function addTransFunc(&$val,&$key){
	if(preg_match('/[\x7f-\xff]+/',$val)){
		$val='t('.$val.')';
	}
}

function makeMultiLang($arr){
	array_walk_recursive($arr,'addTransFunc');
	return $arr;
}

function saveFailed(){
	$out=array(
			'result'=>-1
	);
	echo json_encode($out);
}

$configKey=$_POST['configKey'];
$newConfigValue=$_POST['configValue'];

if(empty($configKey)||empty($newConfigValue)){
	saveFailed();
	return;
}

//现将数据保存到CSV文件中，然后进行转化
$tmpFileName='tmp_'.$configKey.'.csv';
saveAsCsv($tmpFileName,$newConfigValue);

$phpArray=csv_decode($tmpFileName);
$phpArray=$phpArray[''];

if(!is_array($phpArray)){
	saveFailed();
	return;
}

$data=makeMultiLang($phpArray);
file_put_contents($configKey.'2.php',json_encode($data));

$out=array(
		'result'=>1,
		'data'=>implode(',',array_shift($newConfigValue))
);
echo json_encode($out);