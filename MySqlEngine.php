<?php
/**
 * Mysql的封装类：包括增删改查操作
 *
 */
class MySqlEngine{
	private $conn;
	private $host;
	private $port;
	private $user;
	private $password;
	private $database;
	private $isConnected=FALSE;
	//
	private $sql;
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
		$this->port=$config['port'];
		$this->user=$config['user'];
		$this->password=$config['password'];
		$this->database=$config['database'];
	}

	private function connect(){
		$this->conn=mysql_connect($this->host.':'.$this->port,$this->user,$this->password);
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
	 * 设置查询条件中的 AND 条件
	 * @param Array $conditions eg: whereAnd(array('playerId=3',"serverId like 'server'") 
	 * @return MySqlEngine
	 */
	public function whereAnd($conditions=array()){
		foreach($conditions as $item){
			array_push($this->conditionAnd,$item);
		}
		return $this;
	}

	/**
	 * 设置查询条件中的 OR 条件
	 * @param Array $conditions eg: whereOr(array('playerId=3',"serverId like 'server'") 
	 * @return MySqlEngine
	 */
	public function whereOr($conditions=array()){
		foreach($conditions as $item){
			array_push($this->conditionOr,$item);
		}
		return $this;
	}

	/**
	 * 排序，等同于SQL 中的 ORDER BY 关键字
	 * @param String $orderBy eg: order(' time DESC ')
	 * @return MySqlEngine
	 */
	public function order($orderBy){
		$this->options[self::OPTION_ORDERBY]=$orderBy;
		return $this;
	}

	/**
	 * 分组，等同于SQL中的 GROUP BY 关键字
	 * @param String $groupBy eg:group('playerId')
	 * @return MySqlEngine
	 */
	public function group($groupBy){
		$this->options[self::OPTION_GROUPBY]=$groupBy;
		return $this;
	}

	/**
	 * 限制查询范围
	 * @param Number $offset 起始查询位置
	 * @param Number $length 查询长度 eg: limit(0,15)
	 * @return MySqlEngine
	 */
	public function limit($offset=0,$length=null){
		if(!empty($offset)){
			$this->options[self::OPTION_OFFSET]=$offset;
		}
		if(!empty($length)){
			$this->options[self::OPTION_LENGTH]=$length;
		}
		return $this;
	}

	/**
	 * 查询一组数据
	 * @param String $tblName 表名
	 * @param Array $columns 需要得到的字段：如果为null,则获取所有字段
	 * @return Array
	 */
	public function select($tblName,$columns=array()){
		$querySQL=$this->buildQuerySQL($tblName,$columns);
		$result=$this->query($querySQL);
		$data=array();
		while($row=mysql_fetch_array($result,MYSQL_ASSOC)){
			$data[]=$row;
		}
		$this->clear();
		return $data;
	}

	/**
	 * 选择一段数据，主要用于分页显示
	 * @param String $tblName 表名
	 * @param Array $columns 要得到的字段：如果为null，则获取所有字段
	 * @return Array 数据包裹于数组中，其中的 count 代表符合查询条件的数据总条数，data则是查询得到的数据
	 */
	public function selectWithCount($tblName,$columns=array()){
		$opt=$this->options;
		unset($this->options);
		$count=$this->count($tblName);
		$this->options=$opt;
		$data=$this->select($tblName,$columns);
		return array(
				'count'=>$count,
				'data'=>$data
		);
	}

	/**
	 * 查询符合条件的1条数据，等同于 limit 1
	 * @param String $tblName 表名
	 * @param Array $columns 要得到的字段：如果为null，则获取所有字段
	 * @return Array
	 */
	public function find($tblName,$columns=array()){
		$this->conditionOr=array();
		$this->limit(null,1);
		return $this->select($tblName,$columns);
	}

	/**
	 * 获得符合条件的数据总数,用于分页
	 * @param String $tblName 表名
	 * @return Number 符合条件的数据总数
	 */
	private function count($tblName){
		$querySQL="SELECT count(*) FROM ".$tblName;
		$querySQL.=$this->buildConditions();
		$querySQL.=$this->buildOptions();
		//
		$result=$this->query($querySQL);
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
			throw new Exception('MySql query failed '.mysql_error().'   SQL:'.$sqlStr);
		}
		return $result;
	}

	/**
	 * 插入符合条件的数据，等同于 SQL 的 INSERT INTO 关键字
	 * @param String $tblName
	 * @param Array $data
	 */
	public function add($tblName,$data){
		$insertSQL='INSERT INTO '.$tblName; //save into mysql db
		$insertSQL.=$this->buildInsertValues($data);
		$insertSQL.=$this->buildConditions();
		//
		$this->query($insertSQL);
		$result=mysql_insert_id($this->getConnection());
		$this->clear();
		return $result;
	}

	/**
	 * 保存符合条件的数据，等同于SQL 的  UPDATE 关键字
	 * @param unknown $tblName
	 * @param unknown $data
	 * @return resource
	 */
	public function save($tblName,$data){
		$updateSQL=' UPDATE '.$tblName;
		$updateSQL.=$this->buildUpdateValues($data);
		$updateSQL.=$this->buildConditions();
		//
		$result=$this->query($updateSQL);
		$this->clear();
		return $result;
	}

	/**
	 * 删除符合条件的数据，等同于 SQL 的 DELETE 关键字
	 * @param String $tblName 表名
	 * @return $result 如果删除成功或者该数据不存在，返回true，否则返回false
	 */
	public function delete($tblName){
		$delteStr=' DELETE FROM '.$tblName;
		$delteStr.=$this->buildConditions();
		//
		$result=$this->query($delteStr);
		$this->clear();
		return $result;
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
	/**
	 * 创建 WHERE 查询条件
	 * @return string
	 */
	private function buildConditions(){
		if(empty($this->conditionAnd)&&empty($this->conditionOr)){
			return '';
		}
		$whereStr=" WHERE ";
		if(!empty($this->conditionAnd)&&is_array($this->conditionAnd)){
			$whereStr.=implode(' AND ',$this->conditionAnd);
		}
		if(!empty($this->conditionOr)&&is_array($this->conditionOr)){
			$whereStr.=' OR ';
			$whereStr.=implode(' OR ',$this->conditionOr);
		}
		return $whereStr;
	}

	/**
	 * 创建一些限制条件，比如 GROUP BY ,ORDER BY, LIMIT
	 * @return string
	 */
	private function buildOptions(){
		$optionStr='';
		if(isset($this->options[self::OPTION_GROUPBY])){
			$optionStr.=' GROUP BY '.$this->options[self::OPTION_GROUPBY];
		}
		if(isset($this->options[self::OPTION_ORDERBY])){
			$optionStr.=' ORDER BY '.$this->options[self::OPTION_ORDERBY];
		}
		if(isset($this->options[self::OPTION_OFFSET])&&isset($this->options[self::OPTION_LENGTH])){
			$optionStr.=' LIMIT  '.$this->options[self::OPTION_OFFSET].' , '.$this->options[self::OPTION_LENGTH];
		}else{
			if(isset($this->options[self::OPTION_LENGTH])){
				$optionStr.=' LIMIT '.$this->options[self::OPTION_LENGTH];
			}
		}
		return $optionStr;
	}

	/**
	 * 生成要查询的字段
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

	/**
	 *生成 SQL 语句
	 * @param String $tblName
	 * @param Array $columns
	 * @return string
	 */
	private function buildQuerySQL($tblName,$columns=array()){
		$querySQL="SELECT ".$this->buildColumnsSQL($columns);
		$querySQL.=" FROM ".$tblName;
		
		$querySQL.=$this->buildConditions();
		$querySQL.=$this->buildOptions();
		return $querySQL;
	}

	/**
	 * 清除所有查询条件和限制条件，比如 WHERE，AND,OR,GROUP BY,ORDER BY ,LIMIT等
	 */
	private function clear(){
		$this->conditionAnd=array();
		$this->conditionOr=array();
		$this->options=array();
	}

	/**
	 * 生成插入字段
	 * @param Array $data eg: ('id'=>'1','name'=>'test')
	 */
	private function buildInsertValues($data){
		$this->getConnection();//必须要先连接数据库才能使用 mysql_real_escape_string()方法
		$keys=array_keys($data);
		$values=array_values($data);
		
		$str=' ('.implode(",",$keys).') ';
		$valueStr='';
		foreach($values as $tmp){
			if(is_numeric($tmp)){
			}else if(is_array($tmp)){
				$tmp=json_encode($tmp);
				$tmp=mysql_real_escape_string($tmp);
				$tmp='\''.$tmp.'\'';
			}else{
				$tmp=mysql_real_escape_string($tmp);
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
		$this->getConnection();//必须要先连接数据库才能使用 mysql_real_escape_string()方法
		$str=' set ';
		foreach($data as $key=>$value){
			if(is_numeric($value)){
				$tmp.=$key.'='.$value.',';
			}else if(is_array($value)){
				$value=json_encode($value);
				$value=mysql_real_escape_string($value);
				$tmp.=$key.'=\''.$value.'\',';
			}else{
				$value=mysql_real_escape_string($value);
				$tmp.=$key.'=\''.$value.'\',';
			}
		}
		$str=trim($tmp,',');
		return $str;
	}
}

?>