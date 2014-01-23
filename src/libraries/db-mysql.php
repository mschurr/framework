<?php
/**
 * MySQL Database Driver
 * -----------------------------------------------------------------------------------------------------------------------
 *
 * This class implements the database driver for MySQL. You should not instantiate this class directly; use the Database class.
 * You can find the public API documentation for the class in the Database class.
 */

class DB_Driver_mysql extends DB_Driver
{
	/* Whether or not we are in a transaction. */
	protected $transaction = false;
	
	/* Whether or not the user can issue the rollback command. */
	protected $allow_rollback = false;
	
	/* Whether or not we have established a connection to a database server. */
	protected $connected = false;
	
	/* Securely establishes a connection to the database. */
	public function connect()
	{
		if($this->connected)
			return true;
	
		try {
			$this->link = new PDO('mysql:host='.$this->host.':'.$this->port.';dbname='.$this->name, $this->user, $this->pass);
		}
		catch (PDOException $e) {
			trigger_error('Database Connection Failed: '.nl2br($e->getMessage()));
			return false;
		}
		
		$this->connected = true;
		return true;
	}
	
	/* Safely drops the connection to the database. */
	public function disconnect()
	{
		if(!$this->connected)
			return true;
			
		$this->link = null;
		$this->connected = false;
		return true;
	}
	
	/* Escapes a string so that it can be safely placed between single quotes in a query. */
	public function escape($str)
	{
		if(!$this->connected)
			$this->connect();
			
		try {
			$str = $this->link->quote($str);
		}
		catch (PDOException $e) {
			trigger_error("Database Escape Failed: ".nl2br($e->getMessage()));
			return false;
		}
		
		$str = substr($str, 1, -1);
		return $str;
	}
	
	/* Executes a database query. Returns a <DB_Result>. */
	public function query($query)
	{
		if(!$this->connected)
			$this->connect();
		
		$this->queryCount++;
		$timer = new DB_Timer();
		
		try {
			$result = $this->link->query($query);
			$insertId = $this->link->lastInsertId();
		}
		catch (PDOException $e) {
			return new DB_Result($this, 0, null, $query, false, array(), 0, 0, $e->getMessage());
		}
		
		if($result === false)
			return new DB_Result($this, 0, null, $query, false, array(), 0, 0, implode(' | ',$this->link->errorInfo()));
		
		
		$rows = $result->fetchAll(PDO::FETCH_ASSOC);
		$row = isset($rows[0]) ? $rows[0] : array();
		
		return new DB_Result(
			$this,
			$result->rowCount(),
			$insertId,
			$query,
			true,
			$rows,
			len($rows),
			$timer->reap(),
			null
		);		
	}
	
	/* Automatically called when the driver is unloaded. */
	public function onUnload()
	{
		$this->disconnect();	
	}
	
	/* Automatically called when the driver is loaded. */
	public function onLoad()
	{
		if(!defined('PDO::ATTR_DRIVER_NAME')) {
			throw new Exception("You must enable the PDO extension to utilize database connections.");
		}
	}
	
	/* Create a prepared procedure. Returns <DB_PreparedStatement> or false on failure. */
	public function prepare($statement)
	{
		if(!$this->connected)
			$this->connect();
			
		try {
			$res = $this->link->prepare($statement);
		}
		catch (PDOException $e) {
			trigger_error('Database Prepare Failed: '.nl2br($e->getMessage()));
			return false;
		}
		
		if($res === false)
			return false;
		
		return new DB_PreparedStatement(
			$this,
			$statement,
			$res
		);
	}
	
	/* Execute a prepared procedure with parameters. Returns <DB_Result>. */
	public function execute($statement, $params)
	{		
		if(!$this->connected)
			$this->connect();
			
		$this->queryCount++;
		$timer = new DB_Timer();
			
		try {
			$result = $statement->wrapper->execute($params);
			$insertId = $this->link->lastInsertId();
		}
		catch (PDOException $e) {
			return new DB_Result($this, 0, null, $statement->statement, false, array(), 0, 0, $e->getMessage(), $params);
		}
		
		if($result === false) {
			return new DB_Result($this, 0, null, $statement->statement, false, array(), 0, 0, implode(' | ',$statement->wrapper->errorInfo()), $params);
		}
			
		$rows = $statement->wrapper->fetchAll(PDO::FETCH_ASSOC);
		$row = isset($rows[0]) ? $rows[0] : array();
				
		return new DB_Result(
			$this,
			$statement->wrapper->rowCount(),
			$insertId,
			$statement->statement,
			true,
			$rows,
			len($rows),
			$timer->reap(),
			null,
			$params
		);
	}
	
	/* Begins a transaction. Returns true if successful or false on failure. */
	public function begin()
	{
		if(!$this->connected)
			$this->connect();
			
		try {
			$res = $this->link->beginTransaction();
		}
		catch (PDOException $e) {
			trigger_error('Database Transaction Start Failed: '.nl2br($e->getMessage()));
		}
		
		if($res === true) {
			$this->transaction = true;
			return true;
		}
		
		return false;
	}
	
	/* Commit a transaction. Returns true on success or false on failure. Sets back to autocommit mode. */
	public function commit()
	{
		if(!$this->transaction)
			return;
			
		try {
			$res = $this->link->commit();
		}
		catch (PDOException $e) {
			trigger_error('Database Commit Failed: '.nl2br($e->getMessage()));
		}
		
		if($res === true) {
			$this->transaction = false;
			$this->allow_rollback = true;
			return true;
		}
		
		return false;
	}
	
	/* Rolls back the last transaction. Returns true on success or false on failure. */
	public function rollback()
	{
		if(!$this->connected)
			return;
		if(!$this->allow_rollback)
			return;
			
		try {
			$this->link->rollBack();
		}
		catch (PDOException $e) {
			trigger_error('Database Rollback Failed: '.nl2br($e->getMessage()));
			return false;
		}
		
		$this->allow_rollback = false;
		return true;	
	}
	
	/* Returns true if currently in a transaction and false otherwise. */
	public function inTransaction()
	{
		return $this->connected && $this->transaction && $this->link->inTransaction();
	}
}