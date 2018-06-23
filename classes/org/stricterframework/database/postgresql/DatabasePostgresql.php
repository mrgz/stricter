<?php

class DatabasePostgresql implements DatabaseInterface
{
	private $conn;
	private $debug = false;
	private $connected;
	private $dbhost;
	private $dbuser;
	private $dbpass;
	private $dbname;
	private $dbcase;
	private $dbport;
	private $dbtype;
	private $sqlStatement;

	public function __construct($dbhost, $dbuser, $dbpass, $dbname)
	{
		$this->dbhost = $dbhost;
		$this->dbuser = $dbuser;
		$this->dbpass = $dbpass;
		$this->dbname = $dbname;
		$this->dbport = 5432;
		$this->dbtype = 'postgresql';
	}
	
	public function connect()
	{
		$conn = pg_connect("host=".$this->dbhost." port=".$this->dbport." dbname=".$this->dbname." user=".$this->dbuser." password=".$this->dbpass);

		$this->conn =& $conn;

		if(!$this->conn)
			$this->connected = false;
		else
			$this->connected = true;

		return $conn;
	}

	public function query($sql)
	{
		$this->sqlStatement = $sql;
		if($sql == "")
			return false;

		$q = pg_query($this->conn, $sql);

		if($this->debug===true)
			Stricter::getInstance()->log("Database::query ".$this->error()." on query:\n $sql", E_INFO, null, true);

		if($this->error()) {
			Stricter::getInstance()->log("Database::error ".$this->error()." on query:\n $sql", E_ERROR);
		}

		if(!$q)
			return false;
		else
			return $q;
	}

	function numrows(&$resource)
	{
		$n = pg_num_rows($resource);
		
		return $n;
	}

	function fetch(&$query, $sql_assoc=DatabaseInterface::STRICTER_DB_SQL_ASSOC)
	{
		$r = pg_fetch_array($query, null, $sql_assoc);
				
		return $r;
	}

	function free(&$query)
	{
		pg_free_result($query);
	}

	function lastInsertId($entity)
	{
		$entity_name = $entity->getName();

		$pk = $entity->getPrimaryKey();

		$primary_key = $pk[0];

		$sql = "SELECT CURRVAL('".$primary_key->getSequence()."') AS lid";

		$q = $this->query($sql);

		$r = $this->fetch($q);

		return $r["lid"];
	}

	function disconnect()
	{
		if($this->isConnected)
			pg_close($this->conn);
	}

	function escapeString($string_val)
	{
		$magic_quotes = ini_get('magic_quotes_gpc');

		if($magic_quotes==0 || !$magic_quotes)
			$string_val = pg_escape_string($this->conn, $string_val);

		return $string_val;
	}

	function error()
	{
		if( pg_last_error() )
			return pg_last_error();
		else
			return false;
	}

	function transaction()
	{
		$this->query("BEGIN");
	}

	function commit()
	{
		$this->query("COMMIT");
	}

	function rollback()
	{
		$this->query("ROLLBACK");
	}

	function formatField($field)
	{
		$type=$field->getType();

		if($field->getValue()===null)
		{
			if($field->getRequired()===false) {
				return 'NULL';
			} else {
				switch ( $type ) {
					case 'IntegerType':return "0";   break;
					case 'NumericType':return "0";   break;
					case 'BooleanType':return "'f'"; break;
					case 'StringType':return "''"; break;
				}
			}
		} else {
			switch ( $type ) {
				case 'DateType':
					$dt = date('Y-m-d H:i:s', $field->getValue());
					$dt = $field->getValue();
					return "'".$dt."'";
				break;

				case 'BinaryType':
					$bytea = pg_escape_bytea($this->conn, $field->getValue() );
					return "'".$bytea."'";
				break;

				case 'BooleanType':
					$val = $field->getValue();
					$val = str_replace('0', 'f',$val);
					$val = str_replace('1', 't',$val);
					if($field->getRequired()===true){
						if($field->getValue()===false)
							$val = 'f';
						else if ($field->getValue()===true)
							$val='t';
						return "'$val'";
					} else {
						if($field->getValue()===false)
							$val = 'f';
						else if ($field->getValue()===true)
							$val='t';
						return "'$val'";
					}
				break;

				case 'IntegerType':
						return $field->getValue();
				break;

				case 'NumericType':
						return $field->getValue();
				break;

				default:
					return "'".addslashes($field->getValue())."'";
				break;
			}
		}
	}

	function paginate(&$query, $limit, $offset) {
		$query .= " LIMIT $limit OFFSET $offset";	
		return $query;
	}

	function setDebug($dbg) {
		$this->debug=$dbg;
	}

	function setDbPort($port) {
		$this->port=$port;
	}

	function getDbCase() {
		return $this->dbcase;
	}

	function getDbType() {
		return $this->dbtype;
	}
	function getSqlStatement() {
		return $this->sqlStatement;
	}

	function isConnected() {
		return $connected;
	}
}

class DataTypes{
	
}

?>
