<?php
/**
 * Mysql的封装类：包括增删改查操作
 * @author SwordBearer e-mail:ranxiedao@163.com
 * @date 2013-11-15
 *
 */
class MySqlEngine{
	private $conn;
	private $host;
	private $user;
	private $password;
	private $database;
	private $isConnected=FALSE;
	//查询条件
	private $conditionAnd=array();
	private $conditionOr=array();
	private $options=array();
	//
	const OPTION_OFFSET='offset';
	const OPTION_LENGTH='length';
	const OPTION_GROUPBY='groupBy';
	const OPTION_ORDERBY='orderBy';

	function __construct($config=array()){
		$this->host=$config['host'];
		$this->user=$config['user'];
		$this->password=$config['password'];
		$this->database=$config['database'];
	}

	private function connect(){
		$this->conn=mysql_connect($this->host,$this->user,$this->password);
		if(!$this->conn){ //连接出错
			throw new Exception('MySql could not connect: '.mysql_error());
		}
		$result=mysql_select_db($this->database,$this->conn);
		if(!$result){
			throw new Exception('MySql could not find database: '.$this->database.'  '.mysql_error());
		}
		$this->isConnected=true;
	}

	private function getConnection(){
		if(!$this->isConnected){
			$this->connect();
			mysql_query("SET names UTF8");
		}
		return $this->conn;
	}

	/**
	 * 保存数据模型
	 * @param String $tblName
	 * @param Model $model
	 */
	public function saveModel($model,$tblName=null){
		if(is_null($tblName)){
			$tblName=$model->modelName;
			$tblName='t_'.$tblName;
		}
		$data=$model->asArray();
		$id=$data[Model::PRO_ID];
		if(isset($id)){ //修改
			unset($data[Model::PRO_ID]);
			$sql='update '.$tblName;
			$sql.=$this->buildUpdateValues($data);
			$sql.=' where '.Model::PRO_ID.'='.$id;
		}else{ //保存
			$sql='INSERT INTO '.$tblName;
			$sql.=$this->buildInsertValues($data);
		}
		mysql_query($sql,$this->getConnection());
		return mysql_insert_id($this->getConnection());
	}

	/**
	 * 生成插入字段
	 * @param Array $data eg: ('id'=>'1','name'=>'test')
	 */
	private function buildInsertValues($data){
		$keys=array_keys($data);
		$values=array_values($data);
		
		$str=' ('.implode(",",$keys).') ';
		$valueStr='';
		foreach($values as $tmp){
			if(is_numeric($tmp)){
			}else if(is_array($tmp)){
				$tmp=json_encode($tmp);
				$tmp='\''.$tmp.'\'';
			}else{
				$tmp='\''.$tmp.'\'';
			}
			$valueStr.=$tmp.',';
		}
		$valueStr=trim($valueStr,',');
		$str.=' VALUES ('.$valueStr.')';
		return $str;
	}

	/**
	 * 生成修改字段
	 * @param Array $data eg: ('id'=>'1','name'=>'test')
	 */
	private function buildUpdateValues($data){
		$str=' set ';
		foreach($data as $key=>$value){
			if(is_numeric($value)){
				$str.=$key.'='.$value.',';
			}else if(is_array($value)){
				$str.=$key.'=\''.json_encode($value).'\',';
			}else{
				$str.=$key.'=\''.$value.'\',';
			}
		}
		$str=trim($str,',');
		
		return $str;
	}
	
	/*
	 * 2013-11-11 新增mysql的封装
	 */
	public function whereAnd($conditions=array()){
		foreach($conditions as $item){
			array_push($this->conditionAnd,$item);
		}
		return $this;
	}

	public function whereOr($conditions=array()){
		foreach($conditions as $item){
			array_push($this->conditionAnd,$item);
		}
		return $this;
	}

	public function orderBy($orderBy){
		$this->options['orderBy']=$orderBy;
		return $this;
	}

	public function limit($offset=null,$length=null){
		if(!empty($offset)){
			$this->options['offset']=$offset;
		}
		if(!empty($length)){
			$this->options['length']=$length;
		}
	}

	public function select($tblName,$columns=array()){
		$querySQL=$this->buildQuerySQL($tblName,$columns);
		$result=$this->query($querySQL);
		$data=array();
		while($row=mysql_fetch_array($result,MYSQL_ASSOC)){
			$data[]=$row;
		}
		return $data;
	}

	public function selectWithCount($tblName,$columns=array()){
		$count=$this->count($tblName);
		$data=$this->select($tblName,$columns);
		return array(
				'count'=>$count,
				'data'=>$data
		);
	}

	public function find($tblName,$primaryKeyName=' ID ',$id,$columns=array()){
		$this->conditionAnd=array(
				$primaryKeyName.'='.$id
		);
		$this->conditionOr=array();
		$this->limit(null,1);
		return $this->select($tblName,$columns);
	}

	/**
	 * 计算总条数,用于分页
	 * @param String $tblName
	 * @param Array $conditions
	 * @return Ambigous <>
	 */
	public function count($tblName){
		$querySQL="SELECT count(*) FROM ".$tblName;
		$querySQL.=$this->buildConditions();
		$querySQL.=$this->buildOptions();
		//
		$result=mysql_query($querySQL,$this->getConnection());
		$result=mysql_fetch_array($result);
		return $result[0];
	}

	/**
	 * 执行SQL 语句
	 * @param unknown $sqlStr
	 * @throws Exception
	 */
	public function query($sqlStr){
		$result=mysql_query($sqlStr,$this->getConnection());
		if(!$result){
			throw new Exception('MySql query failed '.mysql_error());
		}
		return $result;
	}

	/**
	 * 保存数据
	 * @param String $tblName
	 * @param Array $data
	 */
	public function add($tblName,$data){
		$insertSQL='INSERT INTO '.$tblName; //save into mysql db
		$insertSQL.=$this->buildInsertValues($data);
		mysql_query($insertSQL,$this->getConnection());
	}

	public function save($tblName,$data){
		$updateSQL=' UPDATE '.$tblName;
		$updateSQL.=$this->buildInsertValues($data);
		$updateSQL.=$this->buildConditions();
		$this->query($updateSQL);
		return mysql_insert_id($this->getConnection());
	}

	/**
	 * 关闭数据库连接
	 */
	public function close(){
		if($this->isConnected){
			mysql_close($this->getConnection());
		}
	}

	/**************** private *****************/
	private function buildConditions(){
		$adds=$this->conditionAnd;
		$ors=$this->conditionOr;
		$whereStr=" WHERE ";
		if(!empty($this->conditionAnd)&&is_array($this->conditionAnd)){
			$whereStr.=implode(' AND ',$this->conditionAnd);
		}
		if(!empty($this->conditionOr)&&is_array($this->conditionOr)){
			$whereStr.=implode(' OR ',$this->conditionOr);
		}
		return $whereStr;
	}

	private function buildOptions(){
		$optionStr='';
		if(isset($this->options[self::OPTION_GROUPBY])){
			$result.=' GROUP BY '.$this->options[self::OPTION_GROUPBY];
		}
		if(isset($this->options[self::OPTION_ORDERBY])){
			$result.=' GROUP BY '.$this->options[self::OPTION_GROUPBY];
		}
		if(isset($this->options[self::OPTION_OFFSET])&&isset($this->options[self::OPTION_LENGTH])){
			$result.=' LIMIT  '.$this->options[self::OPTION_OFFSET].' , '.$this->options[self::OPTION_LENGTH];
		}else{
			if(isset($this->options[self::OPTION_LENGTH])){
				$result.=' LIMIT '.$this->options[self::OPTION_LENGTH];
			}
		}
		return $optionStr;
	}

	/**
	 * 生成查询字段
	 * @param string $columns
	 * @return string
	 */
	private function buildColumnsSQL($columns=null){
		$str='*';
		if(!empty($columns)){
			$str=implode(" , ",$columns);
		}
		return $str;
	}

	private function buildQuerySQL($tblName,$columns=array()){
		$querySQL="SELECT ".$this->buildColumnsSQL($columns);
		$querySQL.=" FROM ".$tblName;
		
		$querySQL.=$this->buildConditions();
		$querySQL.=$this->buildOptions();
	}
}

?>