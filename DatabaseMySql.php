<?php

class DB {
	
	private static $host;
	private static $port;
	private static $database;
	private static $user;
	private static $pass;
	
	private static $connection;
	
	public static function connect($host, $port, $database, $user, $pass) {
		self::$host = $host;
		self::$port = $port;
		self::$database = $database;
		self::$user = $user;
		self::$pass = $pass;
		
		self::$connection = new mysqli(( strlen($port) > 0 ? "$host:$port" : "$host" ), $user, $pass, $database);
		if (self::$connection->connect_error) {
			die("Nie można się połączyć: " . $mysqli->connect_error);
		}
		
		self::$connection->query("SET NAMES 'utf8mb4'");
		self::$connection->query("SET CHARACTER_SET utf8mb4_polish_ci");
	}
	
	public static function close() {
		self::$connection->close();
	}
	
	/*
	public static function begin() {
		
		pg_query(self::$connection, "BEGIN");
		
	}
	
	public static function commit() {
		
		pg_query(self::$connection, "COMMIT");
		
	}
	
	public static function rollback() {
		
		pg_query(self::$connection, "ROLLBACK");
		
	}
	*/
	
	public static function query($query, &$result = 845656658) {
		
		$records = self::$connection->query($query) or die("Zapytanie niepoprawne: <b>".$query."</b>");
		
		if($result != 845656658) {
			
			$result = array();
			
			for($i=0;$record = $records->fetch_assoc();$i++) {
				$result[$i] = $record;
			}
			
			return $i;
			
		} else {
			
			return self::$connection->affected_rows;
			
		}
		
	}
	
	public static function insert($query) {
		
		$records = self::$connection->query($query) or die("Zapytanie niepoprawne: <b>".$query."</b>");
		
		return self::$connection->insert_id;
		
	}
	
	
	
	public function count($query) {
		
		$row = DB::query($query, $result);
		
		if($row > 0)
			return intval($result[0]['count']);
		
		return 0;
		
	}
	
	public function countTable($name) {
		
		$row = DB::query("SELECT count(*) AS count FROM ".$name, $result);
		
		if($row > 0)
			return intval($result[0]['count']);
		
		return 0;
		
	}
	
	public function countTableByField($name, $field, $value) {
		
		$row = DB::query("SELECT count(*) AS count FROM ".$name." WHERE ".$field."='".$value."'", $result);
		
		if($row > 0)
			return intval($result[0]['count']);
		
		return 0;
		
	}
	
	public function countByQuery($query) {
		
		$row = DB::query($query, $result);
		
		if($row > 0)
			return intval($result[0]['count']);
		
		return 0;
		
	}
	
};

?>