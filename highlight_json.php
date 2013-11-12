<?php

/**
 * 以树状结构输出数组
 * @param Array/String $log log数据
 * @param number $lv 层级
 */
function highlight_json($log,$lv=0){
	$SPACE='&nbsp;&nbsp;';
	if(is_array($log)){ //直接是数组
		$lv++; //进入数组缩进
		$space='';
		for($i=1;$i<$lv;$i++){
			$space.=$SPACE;
		}
		foreach($log as $key=>$value){
			$lv++; //进入数组，向下加一层级
			if(is_array($value)){
				if(empty($value)){
					echo $space.'<span class="js_key">"'.$key.'"</span> =><span class="js_group" >{},</span><BR/>';
				}else{
					echo $space.'<span class="js_key">"'.$key.'"</span> =><span class="js_group" >{</span><BR/>';
					highlight_json($value,$lv);
					echo $space.'<span class="js_group">}</span>,<BR/>';
				}
			}else{
				$str=$space.'<span class="js_key">"'.$key.'"</span>=>';
				if(is_string($value)){
					$str.='<span class="js_str">"'.$value.'"</span>';
				}else if(is_null($value)){
					$str.='null';
				}else{
					$str.=$value;
				}
				echo $str.'<BR/>';
			}
			$lv--; //出数组跳出层级
		}
	}else{
		if(is_array($tmp=json_decode($log,true))){ //字符串可以转为数组
			highlight_json($tmp,$lv);
		}else{ //如果只是字符串
			echo $log.'<BR/>';
		}
	}
}

?>