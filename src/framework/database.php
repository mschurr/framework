<?php
/*
	Database Abstraction Layer
	
	--------------------------------------
	
	Supported Drivers:
		mysql
	
	Usage:
		$db = new Database($driver, $host, $port, $user, $pass, $name);
		
		NOTE: By default, these values are filled with the following configuration values (Config::get(value)):
			database.driver
			database.host
			database.port
			database.user
			database.pass
			database.name
		
		You can also retrieve a pointer to the web application database at any time using:
		$db =& App::DB();
		
		NOTE: This ensures that only one instance is created, and that it is only created when it will be used.
		
		After getting a reference to a database object, you can execute commands to the driver.
		
		$query = $db->query("SELECT * FROM `my_table`;");
		
		You should escape parameters in non-prepared statements to prevent injection attacks:
		$query = $db->query("INSERT INTO `my_table` (`name`) VALUES ('".$db->escape($string)."');");
		
		You can also use prepared statements as follows:
		$statement = $db->prepare("INSERT INTO `my_table` (`name`) VALUES (?);");
		$query = $statement->execute(array('my name'));
		
		If you wish, you may also name the parameters:
		$statement = $db->prepare("INSERT INTO `my_table` (`name`) VALUES (:name);");
		$query = $statement->execute(array(':name' => 'my name'));

		NOTE: All ->prepare operations return an instance of DB_PreparedStatement.
		NOTE: All ->query or ->execute operations return an instance of DB_Result.
		
		You may also wish to use transactions:
		
		$db->beginTransaction();
		
		... queries or prepared statements ...
		
		if(!$db->commit())
			$db->rollback();

		Alternate syntax:

		$db->transaction(function(){
			// ...queries...
		});

		The database will automatically return to autocommit mode after a transaction ends.
			
		All query results have the following properties (DB_Result):
			$result->affected			The number of rows affected.
			$result->insertId			The insert id (unique key), if applicable.
			$result->text				The query text.
			$result->row				The first row in the returned result set as a column associative array.
			$result->success			Whether or not the query executed successfully (true or false).
			$result->rows				The result set. An array of column associative arrays.
			$result->size				The size of the result set (the number of rows).
			$result->driver				A pointer to the database driver.
			$result->time				The time (in milliseconds) that the query took to execute.
			$result->error				The error that occured executing the query (if applicable).
			
		You can also use the following:
			len($result)				Returns the size of the result set.
			foreach($result as $row){}	Iterate through the result set ($row is a column associative array).
			$result['key'] 				Equivalent to $result->row['key'].
			
		Important:
			All queries that are not part of a transaction will throw a DatabaseException on failure.
			
*/

class DatabaseException extends Exception {}

class Database
{
	protected $driver;
	protected $driver_name;
	
	public function __construct($driver=null, $host=null, $port=null, $user=null, $pass=null, $name=null)
	{
		$this->driver_name = $this->pick('database.driver', 'mysql', $driver);
		$class = 'DB_Driver_'.$this->driver_name;
		
		if(!class_exists($class)) {
			import('db-'.$this->driver_name);
		}
		
		$this->driver = new $class(
			$this->pick('database.host', 'localhost', $host),
			$this->pick('database.port', '3306', $port),
			$this->pick('database.user', 'httpd', $user),
			$this->pick('database.pass', 'httpd', $pass),
			$this->pick('database.name', 'website', $name)
		);
	}
	
	protected function pick($key, $default, $override) {
		if($override !== null)
			return $override;
			
		return Config::get($key, $default);
	}
	
	public function __isset($key) {
		return isset($this->driver->{$key});
	}
	
	public function __get($key) {
		return $this->driver->{$key};
	}
	
	public function __call($name, $args) {
		return call_user_func_array(array($this->driver, $name), $args);
	}
}

abstract class DB_Driver
{
	protected $host;
	protected $port;
	protected $user;
	protected $pass;
	protected $name;
	protected $link;
	protected $queryCount = 0;
	public $error = '';
	
	public function __sleep()
	{
		return array('host', 'port', 'user', 'pass', 'name', 'error');
	}
	
	public function __wakeup()
	{
		$this->onLoad();
	}
	
	public function __construct($host, $port, $user, $pass, $name)
	{
		$this->host = $host;
		$this->port = $port;
		$this->user = $user;
		$this->pass = $pass;
		$this->name = $name;
		$this->onLoad();
	}
	
	public function __destruct()
	{
		$this->onUnload();
	}
	
	public function getLink()
	{
		return $this->link;
	}
	
	/* Securely establishes a connection to the database. */
	public abstract function connect();
	
	/* Safely drops the connection to the database. */
	public abstract function disconnect();
	
	/* Escapes a string so that it can be safely placed between single quotes in a query. */
	public abstract function escape($str);
	
	/* Executes a database query. Returns <DB_Result>($driver, $affected, $insert_id, $text, $row, $success, $rows, $size, $time, $error) */
	public abstract function query($query);
	
	/* Automatically called when the driver is unloaded. */
	public abstract function onUnload();
	
	/* Automatically called when the driver is loaded. */
	public abstract function onLoad();
	
	/* Create a prepared procedure. Returns <DB_PreparedStatement>($this, $statement) or false on failure. */
	public abstract function prepare($statement);
	
	/* Execute a prepared procedure with parameters. Returns <DB_Result>($driver, $affected, $insert_id, $text, $row, $success, $rows, $size, $time, $error). */
	public abstract function execute($statement, $params);
	
	/* Begins a transaction. Returns true if successful or throws DatabaseException on failure. */
	public abstract function begin();
	public function beginTransaction() { return $this->begin(); }
	
	/* Commit a transaction. Returns true on success or throws DatabaseException on failure. Sets back to autocommit mode. */
	public abstract function commit();
	
	/* Rolls back the last transaction. Returns true on success or throws DatabaseException on failure. */
	public abstract function rollback();
	
	/* Returns true if currently in a transaction and false otherwise. */
	public abstract function inTransaction();

	/* Performs the queries contained in the closure as a transaction. 
	   Throws an exception and rolls back changes if the transaction fails.
	   Returns the value returned from the closure (if any). */
	public /*mixed*/ function transaction(Closure $transaction) /*throws DatabaseException*/
	{
		try {
			$this->beginTransaction();
			$result = $transaction($this);
			$this->commit();
			return $result;
		}
		catch(DatabaseException $e) {
			$this->rollback();
			throw $e;
		}
	}
}

class DB_PreparedStatement
{
	protected $driver;
	public $statement;
	public $wrapper;
	
	public function __construct(&$driver, $statement, &$wrapper)
	{
		$this->driver =& $driver;
		$this->statement = $statement;
		$this->wrapper =& $wrapper;
	}
	
	public function execute($opts=array())
	{
		if(!is_array($opts))
			$opts = array($opts);
		if(func_num_args() > 1)
			$opts = func_get_args();
		return $this->driver->execute($this, $opts);
	}
}

class DB_Result implements Iterator, ArrayAccess, Countable
{
	public $affected;
	public $insertId;
	public $text;
	public $success;
	public $successful;
	public $rows;
	public $size;
	public $driver;
	public $time;
	public $error;
	
	public function __construct(&$driver, $affected, $insertId, $text, $success, $rows, $size, $time, $error, $params=array()) {
		
		$this->affected = $affected;
		$this->insertId = $insertId;
		$this->text = $text;
		$this->success = $success;
		$this->successful = $success;
		$this->rows = $rows;
		$this->size = $size;
		$this->time = $time;
		$this->error = $error;
		$this->params = $params;
		
		if($success === false && $driver->inTransaction() === false)
			throw new DatabaseException("A database query failed: ".$this->text." : ".$this->error);
	}
	
	public function __len()
	{
		return $this->size;
	}
	
	public function count()
	{
		return $this->size;
	}
	
	public function __get($key)
	{	
		if($key == 'row') {
			if($this->size >= 1)
				return $this->rows[0];
			throw new RuntimeException("Access Violation: Access to 'row' failed; the query does not contain any data.");
		}
			
		if(isset($this->row[$key]))
			return $this->row[$key];
		
		throw new RuntimeException("Access Violation: Access to '".$key."' failed because it does not exist.");
	}
	
	public function __invoke()
	{
		return $this->success;
	}
	
	
	
	/*/ --
	public function __call($name, $args)
	{
	}
	
	public function __toString()
	{
	}
	
	public function __set($name, $value)
	{
	}
		
	public function __isset($name)
	{
	}

	public function __unset($name)
	{
	}
	/*/
		
	// -- Iterator Methods
	protected $__position = 0;
	
	public function rewind() {
		$this->__position = 0;
	}
	
	public function current() {
		return $this->rows[$this->__position];
	}
	
	public function key() {
		return $this->__position;
	}
	
	public function next() {
		++$this->__position;
	}
	
	public function valid() {
		return isset($this->rows[$this->__position]);
	}
	
	// -- ArrayAcess or ArrayObject Methods
	public function offsetSet($offset, $value) {
		throw new RuntimeException("Access Violation: You can not modify the result of a database query.");
	}
	
	public function offsetExists($offset) {
		if($offset === 0 || $offset === 1) // For backwards compatability.
			return true;
		return isset($this->rows[0][$offset]);
	}
	
	public function offsetGet($offset) {
		if($offset === 0) // For backwards compatability.
			return $this->size;
		if($offset === 1) // For backwards compatability.
			return $this->rows;
		if(isset($this->rows[0][$offset]))
			return $this->rows[0][$offset];
		throw new RuntimeException("Access Violation: You attempted to access an undefined property '".$offset."'.");
	}
	
	public function offsetUnset($offset) {
		throw new RuntimeException("Access Violation: You can not modify the result of a database query.");
	}
}

class DB_Timer extends Timer {}