<?php
	public static function exportFile($fileName,$dataStr){
		ob_clean();
		header("Content-type: application/force-download");
		header("Content-Disposition: attachment; filename=$fileName");
		echo $dataStr;
	}

	/**
	 * 
	 * @param String $fileName
	 * @param Array $data
	 */
	public static function exportCSV($fileName,$data){
		if(strpos($fileName,'.csv')!=(strlen($fileName)-4)){
			$fileName.='.csv'; //保证文件是CSV格式
		}
		$fp=fopen('php://memory','w+'); // open up write to memory
		foreach($data as $row){
			fputcsv($fp,$row);
		}
		rewind($fp);
		$csvFile=stream_get_contents($fp);
		fclose($fp);
		
		ob_clean();
		header('Content-Type: text/csv');
		header('Content-Length: '.strlen($csvFile));
		header('Content-Disposition: attachment; filename="'.$fileName.'"');
		exit($csvFile);
	}

?>