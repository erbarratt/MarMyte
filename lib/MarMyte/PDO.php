<?php
namespace MarMyte;

if(!defined('PDO')){ exit; }

/**
* PDO Class
*
* Handles Strict Select, Update, Insert and Delete functions
*
* @author     Elliott Barratt
* @copyright  Elliott Barratt, all rights reserved.
*
*/ 
class PDO
{

	/* @var object $DBH The Database Handle */
		public $DBH;
	
	/* @var int SINGLE Integer param for variadic function params to set as single row result retrieval */
		public const SINGLE = 1;
		
	/* @var int MULTI Integer param for variadic function params to set as multiple row result retrieval */
		public const MULTI = 2;
	
	/* @var int STRICT Integer param for variadic function params to set as single result retrieval */
		public const STRICT = 3;
	
	/* @var int TABLED Integer param for variadic function params to prefix array elements with a parent element of the table name, useful in large scripts */
		public const TABLED = 4;
	
	/* @var int SQL_ECHO Integer param for variadic function params to echo the SQL WITHOUT bounds params */
		public const SQL_ECHO = 5;
	
	/* @var array|string $_selectFields Array of fields to select, or string */
		private $_selectFields = '*';
	
	/* @var ?array $_insertFields Nullable array of insert field=>value pairs */
		private ?array $_insertFields = null;
	
	/* @var ?array $_updateFields Nullable array of update field=>value pairs */
		private ?array $_updateFields = null;
	
	/* @var ?array $_whereArray Nullable array of where field=>value pairs */
		private ?array $_whereArray = null;
	
	/* @var ?string $_whereOps Nullable string of where operators to match the _whereArray key=>value pairs */
		private ?string $_whereOps = null;
	
	/* @var ?array $_orderByArray Nullable array of order by statements as field=>type pairs */
		private ?array $_orderByArray = null;
	
	/* @var ?array|int $_limit Limit by as a 2 element array or integer to default as 0,X */
		private $_limit = null;
	
	/* @var ?array|string $_groupBy Nullable array to define group by statement as either single field string or array of fields */
		private $_groupBy = null;

	/**
	* Construct and set Database Handle
	* @param \PDO $DBH The database handle
	* @throws \Exception 
	* @return void
	*/
		function __construct(\PDO &$DBH)
		{
			
			if($DBH === null){
				throw new \Exception('PDO class instantiated with null DBH Database handle');
			}
			
			$this->DBH = $DBH;
			
		}
	
	//Query Functions
	
		/**
		 * Quick Strict Select function
		 * @param array $whereFields array of selection fields
		 * @param string $ops where ops string
		 * @param string $table the table to select data from
		 * @param string ...$args array of args for single/multi, strict, tabled prefix return array and echo.
		 * @throws \Exception
		 * @return array
		 */
			public function selectQuick(array $whereFields, string $ops, string $table = '', string ...$args): ?array
			{
				
				//build where array
					$this->setWhereParams($whereFields, $ops);
				
				//check table
					$this->checkTable($table);
					
				//build sql string
					$sql = 'SELECT '. $this->getFieldInjSelect().' FROM `'.$table.'` '.$this->getWhereInj().' '.$this->getGroupByInj().' '.$this->getOrderByInj().' '.$this->getLimitInj();
				
				//echo if in args
					if (in_array(self::SQL_ECHO, $args)){
						echo $sql;
					} 
				
				//run using any passed arguments
					return $this->runSelect($sql, $table, in_array(self::SINGLE, $args), in_array(self::STRICT, $args), in_array(self::TABLED, $args), $this->_whereArray);
				
			}
	
		/**
		 * Strict Select function
		 * @param string $table the table to select data from
		 * @param string ...$args array of args for single/multi, strict, tabled prefix return array and echo.
		 * @throws \Exception
		 * @return array
		 */
			public function selStrict(string $table = '', string ...$args): ?array
			{
				
				//check table
					$this->checkTable($table);
				
				//build sql string
					$sql = 'SELECT '. $this->getFieldInjSelect().' FROM `'.$table.'` '.$this->getWhereInj().' '.$this->getGroupByInj().' '.$this->getOrderByInj().' '.$this->getLimitInj();
				
				//echo if in args
					if (in_array(self::SQL_ECHO, $args)){
						echo $sql;
					}
				
				//run using any passed arguments
					return $this->runSelect($sql, $table, in_array(self::SINGLE, $args), in_array(self::STRICT, $args), in_array(self::TABLED, $args), $this->_whereArray);
				
			}
	
		/**
		 * Lax Select function
		 * @param string $query the table to select data from
		 * @param array|null $boundArray
		 * @param string ...$args array of args for single/multi, strict, tabled prefix return array and echo.
		 * @throws \Exception
		 * @return array
		 */
			public function selLax(string $query, array $boundArray = null, string ...$args): ?array
			{
				
				//echo if in args
					if (in_array(self::SQL_ECHO, $args)){
						echo $query;
					}
				
				//needs to contain select in string for query
					if(mb_stripos($query, 'select') === false){
						throw new \Exception('"SELECT" not part of the query in selLax();');
					}
				
				//run using any passed arguments
					return $this->runSelect($query, 'table', in_array(self::SINGLE, $args), in_array(self::STRICT, $args), in_array(self::TABLED, $args), $boundArray);
				
			}
	
		/**
		 * Strict Update function
		 * @param array $whereFields array of selection fields
		 * @param string $ops where ops string
		 * @param array $updateFields array of fields to be updated with values
		 * @param string $table the table to update the data into
		 * @param string $args array of args
		 * @throws \Exception
		 * @return ?int
		 */
			public function updateQuick(array $whereFields, string $ops, array $updateFields, string $table, ...$args): int
			{
				
				//build where array
					$this->setWhereParams($whereFields, $ops);
					
				//set update array
					$this->setUpdateFields($updateFields);
				
				//build sql string
					$sql = 'UPDATE `'.$table.'` SET '.$this->getUpdateFieldInj().' '.$this->getWhereInj('w_').'';
				
				//echo if in args
					if (in_array(self::SQL_ECHO, $args)){
						echo $sql;
					}
				
				//set bound array if present
					if (is_array($this->_whereArray)){
						$boundArray = array_merge ($this->_updateFields, $this->_whereArray);
					} else {
						$boundArray = $this->_updateFields;
					}
				
				//run query
					return $this->queryLax($sql, $boundArray);
					
			}
	
		/**
		 * Strict Update function
		 * @param string $table the table to update the data into
		 * @param string $args array of args
		 * @throws \Exception
		 * @return ?int
		 */
			public function updateStrict(string $table = '', ...$args): ?int
			{
				
				//check table
					$this->checkTable($table);
				
				//build sql string
					$sql = 'UPDATE `'.$table.'` SET '.$this->getUpdateFieldInj().' '.$this->getWhereInj('w_').'';
				
				//echo if in args
					if (in_array(self::SQL_ECHO, $args)){
						echo $sql;
					}
				
				//set bound array if present
					if (is_array($this->_whereArray)){
						$boundArray = array_merge ($this->_updateFields, $this->_whereArray);
					} else {
						$boundArray = $this->_updateFields;
					}
				
				//run query
					return $this->queryLax($sql, $boundArray);
					
			}
	
		/**
		 * Strict Insert function
		 * Function uses defined clauses as set before this function call to insert that data as key=>value pairs into database table
		 * @param array $insertFields Field=>value array of inserted info
		 * @param string $table
		 * @param mixed ...$args array of args
		 * @throws \Exception
		 * @return int
		 */
			public function insertQuick(array $insertFields, string $table, ...$args): ?int
			{
			
				//set update array
					$this->setInsertFields($insertFields);
				
				//build sql string
					$inj = $this->getFieldToValueInsert();
					$sql = 'INSERT INTO `'.$table.'` ('.$inj['fields'].') VALUES ('.$inj['values'].')';
				
				//echo if in args
					if (in_array(self::SQL_ECHO, $args)){
						echo $sql;
					}
				
				//run query
					$this->queryLax($sql, $this->_insertFields);
				
				return $this->DBH->lastInsertId();
				
			}
	
	
		/**
		 * Strict Insert function
		 * Function uses defined clauses as set before this function call to insert that data as key=>value pairs into database table
		 * @param string $table
		 * @param mixed ...$args array of args
		 * @throws \Exception
		 * @return int
		 */
			public function insertStrict(string $table = '', ...$args): ?int
			{
			
				//check table
					$this->checkTable($table);
			
				//build sql string
					$inj = $this->getFieldToValueInsert();
					$sql = 'INSERT INTO `'.$table.'` ('.$inj['fields'].') VALUES ('.$inj['values'].')';
				
				//echo if in args
					if (in_array(self::SQL_ECHO, $args)){
						echo $sql;
					}
				
				//run query
					$this->queryLax($sql, $this->_insertFields);
				
				return $this->DBH->lastInsertId();
				
			}
	
		/**
		 * Strict Delete function
		 * @param string $table the table to delete data from
		 * @param string $args array of args
		 * @throws \Exception
		 * @return ?int
		 */
			public function deleteStrict(string $table = '', ...$args): ?int
			{
				
				//check table
					$this->checkTable($table);
				
				//build sql string
					$sql = 'DELETE FROM `'.$table.'` '.$this->getWhereInj().'';
				
				//echo if in args
					if (in_array(self::SQL_ECHO, $args)){
						echo $sql;
					}
				
				//run query
					return $this->queryLax($sql, $this->_whereArray);
				
			}
	
		/**
		 * Strict Delete function
		 * @param array $whereFields array of selection fields
		 * @param string $ops where ops string
		 * @param string $table the table to delete data from
		 * @param string $args array of args
		 * @throws \Exception
		 * @return ?int
		 */
			public function deleteQuick(array $whereFields, string $ops, string $table = '', ...$args): ?int
			{
				
				//check table
					$this->checkTable($table);
				
				//build where array
					$this->setWhereParams($whereFields, $ops);
				
				//build sql string
					$sql = 'DELETE FROM `'.$table.'` '.$this->getWhereInj().'';
				
				//echo if in args
					if (in_array(self::SQL_ECHO, $args)){
						echo $sql;
					}
				
				//run query
					return $this->queryLax($sql, $this->_whereArray);
				
			}
	
		/**
		 * Straight Query function for passed query strings, none return
		 * @param string $query the query to run
		 * @param array|null $boundArray array of bound parameters
		 * @throws \Exception
		 * @return ?int
		 */
			public function queryLax(string $query, array $boundArray = null): int
			{
				
				try {
					
					$STH = $this->DBH->prepare($query);
					
					if ($boundArray === null){
						$STH->execute();
					} else {
						$STH->execute($boundArray);
					}
					
					$this->clearProperties();
					
					if($STH->errorCode() != 0) {
						$errors = $STH->errorInfo();
						throw new \Exception($errors[2]);
					}
					
				} catch (\PDOException $e){
					throw new \Exception($e->getMessage());
				}
				
				return $STH->rowCount();
				
			}
	
		/**
		 * Check if row(s) exist
		 * @param string $table the table to select data from
		 * @param string ...$args array of args for single/multi, strict, tabled prefix return array and echo.
		 * @throws \Exception
		 * @return bool
		 */
			public function rowsExist(string $table = '', string ...$args): bool
			{
				
				$result = $this->selStrict($table, ...$args);
				
				return ($result === null) ? false : true ;
				
			}
	
		/**
		 * Return a count of selected rows
		 * @param string $table the table to select data from
		 * @param string $field
		 * @param string ...$args array of args for single/multi, strict, tabled prefix return array and echo.
		 * @throws \Exception
		 * @return int
		 */
			public function countRows(string $table = '', string $field = '`id`', string ...$args): int
			{
				
				//check table
					$this->checkTable($table);
				
				//build sql string
					$sql = 'SELECT COUNT('.$field.') as "count" FROM `'.$table.'` '.$this->getWhereInj().' '.$this->getGroupByInj().' '.$this->getLimitInj();
				
				//echo if in args
					if (in_array(self::SQL_ECHO, $args)){
						echo $sql;
					}
			
				//run and return from firs element
					return ($this->runSelect($sql, $table, true, in_array(self::STRICT, $args), false, $this->_whereArray))['count'];
				
			}
	
		/**
		 * Return a Key Value pair array from loaded query
		 * @param string $table the table to select data from
		 * @param array $fields
		 * @param string ...$args array of args for single/multi, strict, tabled prefix return array and echo.
		 * @throws \Exception
		 * @return array
		 */
			public function loadKeyValueArray(string $table = '', array $fields = ['id','name'], string ...$args): array
			{
				
				//run query
					$result = $this->selStrict($table, ...$args);
				
				$array = [];

				if ($result != null){
					
					foreach ($result as $key => $row){
						$array[$row[$fields[0]]] = $row[$fields[1]];
					}
					
				} else {
					
					return ['No Items'=>''];
					
				}
				
				return $array;
				
			}
			
	//End Query Functions
	
	//Utility Functions
	
		/**
		 * Check if the table string passed is ok
		 * @param string $table The table string to check
		 * @throws \Exception
		 * @return void
		 */
			private function checkTable(string $table) :void
			{
				if (trim($table) === ''){
					throw new \Exception ('Table argument empty string.');
				}
			}
	
		/**
		 * Run the select that's set up by selStrict and selLax functions
		 * @param string $query the query to run
		 * @param string $table
		 * @param bool $singleResult single row or multi row return
		 * @param bool $strict throw exception on empty data or not
		 * @param bool $tabled
		 * @param array|null $boundArray array of bound paramters for sql
		 * @throws \Exception
		 * @return array
		 */
			private function runSelect(string $query, string $table, bool $singleResult = false, bool $strict = false, bool $tabled = false, array $boundArray = null): ?array
			{

				//Try to prepare the SQL statement, throw exception if this fails, for example without any where fields + ops?
					try {
						
						$STH = $this->DBH->prepare($query);
						
						if ($boundArray === null){
							$STH->execute();
						} else {
							$STH->execute($boundArray);
						}
						
					} catch (\PDOException $e) {
						throw new \Exception($e->getMessage());
					} finally {
						$this->clearProperties();
					}

				//fetch as an assoc array
					$STH->setFetchMode(\PDO::FETCH_ASSOC);
					
				//build table'd array to return
					if ($tabled === true){
						
						$returnedResult = [];
						$tempResults = [];
						
						if ($singleResult === true){
							
							$fetched = $STH->fetch();
							
							if ($fetched === false){
							
								//no rows
								if($strict === true){
									throw new \Exception('No Results');
								}
								
								return null;
								
							}
							
							return [$table => $fetched];
							
						} else {
							
							$fetched = $STH->fetchAll();
							
							if (empty($fetched)){
								
								//no rows
								if($strict === true){
									throw new \Exception('No Results');
								}
								
								return null;
								
							} else {
								
								foreach($fetched as $row){
									foreach ($row as $key=>$value){
											$tempResults[$table][$key] = $value;
									}
									$returnedResult[] = $tempResults;
								}
								
								return $returnedResult;
								
							}
							
						}
					
				//return direct from result as a single element array or multi element
					} else {
						
						if ($singleResult === true){
							
							$fetched = $STH->fetch();
							
							if ($fetched === false){
								
								//no rows
								if($strict === true){
									throw new \Exception('No Results');
								}
									
								return null;
								
							} else {
								return $fetched;
							}
							
						} else {
							
							$fetched = $STH->fetchAll();
							
							if (empty($fetched)){
								
								//no rows
								if($strict === true){
									throw new \Exception('No Results');
								}
								
								return null;
								
							} else {
								return $fetched;
							}
							
						}
					}
			
			}
	
		/**
		 * Check the sql function passed is ok
		 * @param string|null $value a sql function
		 * @return bool
		 */
			private function checkFunction(?string $value = ''): bool
			{
				$value = trim($value);
				
				//protect against injection by semi-colon
					if(mb_stripos($value, ';') !== false){
						return false;
					}
				
				//none argument functions
					if($value == 'NOW()'){
						return true;
					}
				
				//argument functions
					$lastChar = (strlen($value)-1);
					if((
						//string
							mb_stripos($value, 'CONCAT(') === 0 ||
							mb_stripos($value, 'CHAR_LENGTH(') === 0 ||
							mb_stripos($value, 'FORMAT(') === 0 ||
							mb_stripos($value, 'LOWER(') === 0 ||
							mb_stripos($value, 'UPPER(') === 0 ||
							mb_stripos($value, 'TRIM(') === 0 ||
						//number
							mb_stripos($value, 'ABS(') === 0 ||
							mb_stripos($value, 'AVG(') === 0 ||
							mb_stripos($value, 'CEIL(') === 0 ||
							mb_stripos($value, 'COUNT(') === 0 ||
							mb_stripos($value, 'FORMAT(') === 0 ||
							mb_stripos($value, 'FLOOR(') === 0 ||
							mb_stripos($value, 'MAX(') === 0 ||
							mb_stripos($value, 'MIN(') === 0 ||
							mb_stripos($value, 'ROUND(') === 0 ||
							mb_stripos($value, 'RAND(') === 0 ||
							mb_stripos($value, 'SIGN(') === 0 ||
							mb_stripos($value, 'SUM(') === 0 ||
						//date
							mb_stripos($value, 'DATE(') === 0 ||
							mb_stripos($value, 'DATE_FORMAT(') === 0 ||
							mb_stripos($value, 'DAY(') === 0 ||
							mb_stripos($value, 'HOUR(') === 0 ||
							mb_stripos($value, 'MINUTE(') === 0 ||
							mb_stripos($value, 'MONTH(') === 0 ||
							mb_stripos($value, 'QUARTER(') === 0 ||
							mb_stripos($value, 'SECOND(') === 0 ||
							mb_stripos($value, 'TIME(') === 0 ||
							mb_stripos($value, 'WEEK(') === 0 ||
							mb_stripos($value, 'WEEKDAY(') === 0 ||
							mb_stripos($value, 'YEAR(') === 0
						) &&
						(
							mb_strripos($value, ')') === $lastChar || 	//close of function 
							mb_strripos($value, '"') === $lastChar		//where use of AS "something"
						)
					){
						return true;
					} else {
						return false;
					}
					
			}
			
		/**
		* Clear out the members for next transaction
		* @return void
		*/
			public function clearProperties(): void
			{
				
				$this->_selectFields = '*';
				$this->_insertFields = null;
				$this->_updateFields = null;
				$this->_whereArray = null;
				$this->_whereOps = null;
				$this->_orderByArray = null;
				$this->_groupBy = null;
				$this->_limit =  null;
				
			}
		
	//End Utility Functions
	
	//Get Functions
		
		/**
		* Build the whole where injection.
		* Takes all of the values (and arrays) in the Where Array and turns them into a string with bound parametres
		* Note all the trimming, as this can catch whether or not the supplied is with '`' or not.
		* @param string $prefix any field prefix
		* @throws \Exception
		* @return string
		*/
			private function getWhereInj(string $prefix = ''): string
			{
				
				$whereInj = '';
			
				if (is_array($this->_whereArray)){
					
					//check the necessary members
						foreach ($this->_whereArray as $key => $value){
							if (trim($key) === ''){
								throw new \Exception ('Where array key empty.');
							}
						}
						
						if ($this->_whereOps === ''){
							throw new \Exception ('Where Ops empty');
						}
						
						if (strlen($this->_whereOps) !== count($this->_whereArray)){
							throw new \Exception ('Ops does not match Where array count');
						}
				
					//Set up the counting variable and initial string
					$i = 1;
					$whereInj = 'WHERE ';
					$indexCount = count($this->_whereArray);
					
					foreach ($this->_whereArray as $key=>$value){
					
						// If not the first loop, and AND to string
							if ($i <= $indexCount && $i > 1){
								$whereInj .= ' AND ';
							}
						
						//get the operator						
							$op = $this->getOp($i);
						
						//don't need this now, as will cause mis-matched number of bound array elements error
							unset ($this->_whereArray[$key]);
						
						//If the OP is in, then $value is an array, but the key is also set (not assigned int index)
							if ($op === 'IN' || $op === 'NOT IN'){
								
								//has string been passed? I.e. comma'd list
								if(!is_array($value)){
									
									//check for presence of commas, if not there then array is 1 element in length
									if(mb_stripos($value, ',') !== false){
										$valBits = explode(',',$value);
										$valBits = array_filter( $valBits, function($valBit) { return $valBit !== ''; });
									} else {
										$valBits = [$value];
									}
										
								} else {
									$valBits = $value;
								}
								
								if(empty($valBits)){
									throw new \exception('IN operation in Where Fields array need to be a valid array or explodable string.');
								}
								
								//write the colon for preparation to each in the array
									$valueIdentifiers = [];
									$x = 1;
									foreach($valBits as $valBit){
										$valueIdentifiers[] = ':'.$prefix.$key.'_'.$x;
										$this->_whereArray[$prefix.$key.'_'.$x] = $valBit;
										$x++;
									}
								
								//write out to where string
									$whereInj .= ' `'.$key.'` '.$op.' ('.implode(', ',$valueIdentifiers).') ';
						
						//otherwise, then if $value is an array, that means $key has been AUTO assigned as an int, and it's part of multiple field where (eg range `date` > X AND `date < Y)
							} elseif (is_array($value)){
							
								foreach ($value as $subKey => $subValue){
									
									//set up bound parameter as appending the current loop number to it to make it unique in the bound array.
										$whereInj .= ' `'.$subKey.'` '.$op.' :'.$prefix.$subKey.$i.' ';
										$this->_whereArray[$prefix.$subKey.$i] = $subValue;
									
								}
								
						//if op is not IN and $value not an array, then just a flat where clause (possibly like)
							} else {
								
								//catch LIKE operator
									$value = ($op === 'LIKE') ? '%'.$value.'%' : $value;
								//set up bound param and write to where string
									$this->_whereArray[$prefix.$key] = $value;
									$whereInj .= ' `'.$key.'` '.$op.' :'.$prefix.$key.' ';

							}
						
						$i++;
						
					}
				}
			
				return $whereInj;
			}
			
		/**
		* Get SQL operator as opposed to framework operator
		* @param string $i The operator string to find
		* @throws \Exception
		* @return string
		*/
			private function getOp(string $i): string
			{
				$op = substr($this->_whereOps, ($i-1), 1);
				switch ($op){
					case '=':
						return '=';
					case 'N':
						return '!=';
					case '<':
						return '<';
					case '>':
						return '>';
					case 'L':
						return '<=';
					case 'G':
						return '>=';
					case 'X':
						return 'LIKE';
					case 'I':
						return 'IN';
					case 'O':
						return 'NOT IN';
					default:
						throw new \Exception ('Invalid Ops Parameter at index '.$i);
				}
				
			}
			
		/**
		* Build field injection string for select
		* @throws \Exception
		* @return string
		*/
			private function getFieldInjSelect(): string
			{
				
				if(is_array($this->_selectFields)){
					
					//catch any blank elements
						foreach ($this->_selectFields as $value){
							if ($value === ''){
								throw new \Exception ('Blank Select Fields array element found.');
							}
						}
			
					$fieldInj = '';
				
					$i = 1;
					$indexCount = count($this->_selectFields);
					
					//go through each element and add to the field string
						foreach ($this->_selectFields as $value){
							
							if ($i <= $indexCount && $i > 1){
								$fieldInj .= ',';
							}
							
							if($this->checkFunction($value) === true){
								$fieldInj .= ' '.$value.' ';
							} else {
								$fieldInj .= ' `'.$value.'` ';
							}
							
							$i++;
						}
					
					return $fieldInj;
				
				} else {
					return '*';
				}
			
			}
			
		/**
		* Build order by injection
		* @throws \Exception
		* @return string
		*/
			private function getOrderByInj(): string
			{
				
				if(is_array($this->_orderByArray)){
					
					//validate the order by array
						foreach ($this->_orderByArray as $key => $value){
							if (trim($key) === ''){
								throw new \Exception ('Order By array key empty.');
							}
							
							if(strtolower($value) !== 'asc' && strtolower($value) !== 'desc'){
								throw new \Exception ('Order By array value must be "ASC" or "DESC".');
							}
						}
					
					//build the string
						$i = 1;
						$orderbyInj =  ' ORDER BY ';
						$indexCount = count($this->_orderByArray);
						foreach ($this->_orderByArray as $key => $value){
							if ($i <= $indexCount && $i > 1){
								$orderbyInj .= ' , ';
							}
											
							$orderbyInj .= ' `'.$key.'` '.$value.' ';						
							$i++;
						}
						
					return $orderbyInj;
					
				} else {
					return '';
				}
			}
		
		/**
		* Build group by injection
		* @throws \Exception
		* @return string
		*/
			private function getGroupByInj(): string
			{
				
				//can be array
					if(is_array($this->_groupBy)){
						
						//check the group by array
							foreach ($this->_groupBy as $value){
								if ($value === ''){
									throw new \Exception ('Blank Group By array element found.');
								}
							}
						
						//build the string
							$groupByInj = ' GROUP BY ';
							$i = 1;
							$groupCount = count($this->_groupBy);
							foreach ($this->_groupBy as $value){
							
								if ($i <= $groupCount && $i > 1){
									$groupByInj .= ' , ';
								}
								
								$groupByInj .= ' `'.$value.'` ';
								
								$i++;
							
							}
						
						return $groupByInj;
				
				//or just a passed string
					} elseif (trim($this->_groupBy) !== '' ){
						
						return ' GROUP BY `'.trim($this->_groupBy).'` ';
				
				//or nothing
					} else {
						return '';
					}
				
			}
			
		/**
		* Build limit injection
		* @throws \Exception
		* @return string
		*/
			private function getLimitInj(): string
			{
				
				//can be array
					if(is_array($this->_limit)){
						
						//check limit array
							foreach ($this->_limit as $value){
								if (!is_int((int)$value)){
									throw new \Exception ('All Limit array elements must be of type integer.');
								}
							}
						
						return ' LIMIT '.$this->_limit[0].','.$this->_limit[1];
				
				//or just an int
					} elseif(trim($this->_limit) !== ''){
						return ' LIMIT 0,'.(int)$this->_limit;
				
				//or nothing
					} else {
						return '';
					}
			}
			
		/**
		* Build field injection string 
		* @throws \Exception
		* @return string
		*/
			private function getUpdateFieldInj(): string
			{
			
				//needs to be an array
					if (!is_array($this->_updateFields)){
						throw new \Exception ('Update Fields not set.');
					}
			
				//build string
					$fieldInj = '';
				
					$i = 1;
					$indexCount = count($this->_updateFields);
					
					foreach ($this->_updateFields as $key=>$value){
						
						if ($i <= $indexCount && $i > 1){
							$fieldInj .= ',';
						}
						
						//check passed function
							if ($this->checkFunction($value)){
								$fieldInj .= ' `'.$key.'` = '.$value.'';
								unset($this->_updateFields[$key]);
							} else {
								$fieldInj .= ' `'.$key.'` = :'.$key.'';
							}
							
						$i++;
					}
				
				return $fieldInj;
			
			}
			
		/**
		* Build field => value array for insert and update
		* @throws \Exception
		* @return array
		*/
			private function getFieldToValueInsert(): array
			{
				
				//check insert array
					if(!is_array($this->_insertFields)){
						throw new \Exception ('Insert Fields array not set.');
					}
				
				$inj = [
					'fields' => '', 'values' => ''
				];
				
				$i = 1;
				$indexCount = count($this->_insertFields);
				
				foreach ($this->_insertFields as $key=>$value){
					
					$value = ($value == null) ? '' : $value; // so checkFunction works if trying to insert null data type
					
					if ($i <= $indexCount && $i > 1){
						$inj['fields'] .= ',';
						$inj['values'] .= ',';
					}
					
					$inj['fields'] .= ' `'.$key.'` ';
					
					//check if passed function
					if ($this->checkFunction($value) === true){
						$inj['values'] .= ' '.$value.' ';
						unset($this->_insertFields[$key]);
					} else {
						$inj['values'] .= ' :'.$key.' ';
					}
					
					$i++;
					
				}
				
				return $inj;
			}
			
	//End Get Functions
	
	//Set Functions
	
		/**
		* Set both fields and ops at once.
		* @param array $array The array of fields to values for where inj
		* @param string $ops The string of ops for where inj
		* @return void
		*/
			public function setWhereParams(array $array, string $ops): void
			{
				
				$this->_whereArray = $array;
				$this->_whereOps = $ops;
				
			}
			
		/**
		* Set the select where fields member
		*
		*	If a field is needed more than once in the where array, then do the following:
		*		
		*	instead of the usual:
		*			
		*	$this-_whereArray = array(
		*		'someKey' => $someValue,
		*		'keySame' => $value1,
		*		'keySame' => $value2
		*	);
		*			
		*	YOU MUST use:
		*		
		*	$this-_whereArray = array(
		*		'someKey' => $someValue,
		*		['keySame' => $value1] ,
		*		['keySame' => $value2]
		*	);
		*		
		* this will get picked up in the get where injection function and dealt with accordingly.
		*
		* @param array $array The array of fields to values for where inj
		* @return void
		*/
			public function setwhereFields(array $array): void
			{		
				$this->_whereArray = $array;
			}
			
		/**
		* Set the where ops member
		* @param string $ops The string of ops for where inj
		* @return void
		*/
			public function setWhereOps(string $ops): void
			{
				$this->_whereOps = $ops;
			}
			
		/**
		* Set the select fields member
		* @param array $array The Select Fields Array
		* @return void
		*/
			public function setSelectFields(array $array): void
			{
				$this->_selectFields = $array;
			}
		
		/**
		* Set the order by member
		* @param array $array The Order By Array
		* @return void
		*/
			public function setOrderBy(array $array): void
			{
				$this->_orderByArray = $array;
			}
			
		/**
		* Set the group by member
		* @param mixed $groupBy The Group By Array
		* @return void
		*/
			public function setGroupBy($groupBy): void
			{
				$this->_groupBy = $groupBy;
			}
			
		/**
		* Set the limit by member
		* @param mixed $limit The Limit Array
		* @return void
		*/
			public function setLimit($limit): void
			{
				$this->_limit = $limit;
			}
	
		/**
		 * Set the select insert fields member
		 * @param array $array
		 * @return void
		 */
			public function setUpdateFields(array $array): void
			{
				$this->_updateFields = $array;
			}
	
		/**
		 * Set the select insert fields member
		 * @param array $array
		 * @return void
		 */
			public function setInsertFields(array $array): void
			{
				$this->_insertFields = $array;
			}
			
}
