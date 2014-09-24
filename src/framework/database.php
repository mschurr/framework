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

class DatabaseException extends Exception {
	protected /*array<string>|array<string, string>*/ $params = [];
	protected /*string*/ $query = '';

	public /*void*/ function __construct($query, $error, $params) {
		parent::__construct($error);
		$this->query = $query;
		$this->params = $params;
	}

	public /*string*/ function getError() {
		return $this->getMessage();
	}

	public /*string*/ function getQuery() {
		return $this->query;
	}

	public /*array<string>|array<string, string>*/ function getParams() {
		return $this->params;
	}

	public /*string*/ function getExplanation() {
		return "A database query failed: ".$this->query." : ".json_encode($this->params)." : ".$this->getMessage();
	}

	public /*string*/ function __toString() {
		return $this->getExplanation();
	}
}

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
			throw new DatabaseException($this->text, $this->error, $this->params);
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

/**
 * Seamlessly iterates over records matched by a query in chunks (pages) of a given size, pulling new records from the server and releasing old ones as needed.
 * Chunks (pages) can be accessed directly as $iterator[$pageid] and the total number of chunks (pages) is count($iterator).
 *
 * Example:
 *  $users = new DatabaseChunkIterator("SELECT * FROM `users` WHERE `last_login` > ?;", array(time() - 3600), 25);
 *  foreach($users as $user) { ... do something (only 25 records ever in memory at a time). }
 *
 * Example (Pagination):
 *  $page = $this->request->get['page'];
 *  try { foreach($iterator[$page] as $record) { ...iterator through provided page... } }
 *  catch(BadAccessException $e) { ...$page is not a valid page... }
 *
 */
class DatabaseChunkIterator implements Iterator, ArrayAccess, Countable
{
	protected /*Database*/ $db;
	protected /*int*/ $chunkSize;
	protected /*string*/ $statement;
	protected /*array*/ $parameters;

	protected /*int*/ $_records;
	protected /*int*/ $_chunks;
	protected /*array*/ $_buffer;
	protected /*int*/ $_chunkNumber;
	protected /*int*/ $_chunkItem;

	/**
	 * Instantiates the object.
	 */
	public function __construct(/*string*/ $statement, /*array*/ $parameters = array(), /*int*/ $chunkSize, /*Database*/ $db = null)
	{
		$this->chunkSize = $chunkSize;
		$this->statement = $statement;
		$this->parameters = $parameters;
		$this->db = ($db ? $db : App::getDatabase());
		$this->_records = -1;
	}

	/**
	 * Iterator: Returns the current item in the iterator.
	 */
	public /*mixed*/ function current()
	{
		return $this->_buffer[$this->_chunkItem];
	}

	/**
	 * Iterator: Returns the key for the current item in the iterator.
	 */
	public /*int*/ function key()
	{
		return $this->_chunkItem;
	}

	/**
	 * Iterator: Advances to the next item in the iterator.
	 */
	public /*void*/ function next()
	{
		if($this->_chunkItem === $this->chunkSize - 1) {
			$this->_chunkItem = 0;
			$this->_chunkNumber++;
			$this->update();
		} else {
			$this->_chunkItem++;
		}
	}

	/**
	 * Iterator: Resets the iterator.
	 */
	public /*void*/ function rewind()
	{
		$this->count();
		$this->_buffer = array();
		$this->_chunkNumber = 0;
		$this->_chunkItem = 0;
		$this->update();
	}

	/**
	 * Iterator: Returns whether or not data currently exists in the iterator.
	 */
	public /*boolean*/ function valid()
	{
		return $this->_chunkItem < sizeof($this->_buffer);
	}

	/**
	 * Refreshes the buffer by pulling from the database system.
	 */
	private function update()
	{
		try {
			$this->_buffer = $this->offsetGet($this->_chunkNumber);
		} catch (BadAccessException $e) {
			$this->_buffer = array();
		}
	}

	/**
	 * Returns the total number of pages (chunks) matched by the query.
	 */
	public /*int*/ function count()
	{
		if($this->_records === -1) {
			$str1 = strpos(strtolower($this->statement),"select ") + strlen("select ");
			$str2 = strpos(strtolower($this->statement)," from ") - strlen(" from ");
			$sql = substr($this->statement,0,$str1)."COUNT(*) AS `count`".substr($this->statement,$str2 + strlen(" from "));
			$data = $this->db->prepare($sql)->execute($this->parameters);
			$this->_records = (int) $data->row['count'];
			$this->_chunks = ($this->_records === 0) ? 1 : ceil($this->_records / $this->chunkSize);
		}

		return $this->_chunks;
	}

	/**
	 * ArrayAccess: Returns an array of objects in the provided chunk.
	 */
	public /*mixed*/ function offsetGet(/*scalar*/ $offset)
	{
		if(!is_integer($offset))
			throw new BadAccessException;
		if($offset < 0 || $offset >= $this->count())
			throw new BadAccessException;

		$statement = $this->statement;
		if(substr($statement,-1) == ';')
			$statement = substr($statement,0,-1);
		$statement = $statement." LIMIT ".($offset * $this->chunkSize).",".$this->chunkSize.";";

		$query = $this->db->prepare($statement)->execute($this->parameters);

		if(len($query) > 0)
			return $query->rows;
		else
			return array();
	}

	/**
	 * ArrayAccess: Returns whether or not the provided chunk number is valid.
	 */
	public /*boolean*/ function offsetExists(/*scalar*/ $offset)
	{
		if(!is_integer($offset))
			throw new BadAccessException;
		return $offset >= 0 && $offset < $this->count();
	}

	/**
	 * ArrayAccess: set operation is not allowed.
	 */
	public function offsetSet(/*scalar*/ $offset, /*mixed*/ $value)
	{
		throw new BadAccessException;
	}

	/**
	 * ArrayAccess: unset operation is not allowed.
	 */
	public function offsetUnset($offset)
	{
		throw new BadAccessException;
	}

	/**
	 * Returns an array of all items in the iterator.
	 * Use with caution; may consume large amounts of memory.
	 */
	public /*array*/ function toArray()
	{
		$query = $this->db->prepare($this->statement)->execute($this->parameters);

		if(len($query) > 0)
			return $query->rows;
		return array();
	}

	/**
	 * Returns an array of all items stored in a column within the result set.
	 * Use with caution; may consume large amounts of memory.
	 */
	public /*array*/ function toArrayOfColumn(/*string*/ $column)
	{
		$data = array();

		foreach($this as $row) {
			$data[] = $row[$column];
		}

		return $data;
	}

	/**
	 * Magic: Controls access to undefined properties.
	 */
	public /*mixed*/ function __get(/*scalar*/ $key)
	{
		if($key === 'chunks' || $key === 'pages')
			return $this->_chunks;
		if($key === 'records')
			return $this->_records;
		if($key === 'chunkSize' || $key == 'pageSize')
			return $this->chunkSize;
		throw new BadAccessException($key);
	}

	/**
	 * Magic: Controls access to undefined properties.
	 */
	public /*boolean*/ function __isset(/*scalar*/ $key)
	{
		static $keys = array(
			'chunks' => 1,
			'pages' => 1,
			'records' => 1,
			'chunkSize' => 1,
			'pageSize' => 1
		);

		return isset($keys[$key]);
	}

	/**
	 * Magic: Controls access to undefined properties.
	 */
	public /*void*/ function __set(/*scalar*/ $key, /*mixed*/ $value)
	{
		throw new BadAccessException;
	}

	/**
	 * Magic: Controls access to undefined properties.
	 */
	public /*void*/ function __unset(/*scalar*/ $key)
	{
		throw new BadAccessException;
	}
}
