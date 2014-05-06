<?php

class CLIApplication
{
	/**
	 * Implements the Facade; maps static function calls to singleton instance method calls.
	 */
	protected static /*CLIApplication*/ $singleton;
	public static /*mixed*/ function __callStatic(/*string*/ $name, /*array<mixed>*/ $args)
	{
		if(static::$singleton === null)
			static::$singleton = new CLIApplication();
		return call_user_func_array(array(static::$singleton, $name), $args);
	}

	protected /*array<string:mixed>*/ $routes;

	/**
	 * Displays the usage error message.
	 */
	protected /*void*/ function clierror()
	{
		printf("Usage: php ".$argv[0]." <command> <...args>\r\n");
		printf("\r\nRecognized Commands:\r\n");
		foreach($this->routes as $cmd => $targ)
			printf("    ".$cmd."\r\n");
		printf("\r\n");
	}

	/**
	 * Insantiates the object.
	 */
	protected function __construct()
	{
		$this->routes = array();
	}

	/**
	 * Listens for command-line commands and runs the target when matched.
	 * $target is a Closure or the name of class that subclasses CLIApplicationController
	 */
	protected /*void*/ function listen(/*string*/ $command, /*mixed*/ $target)
	{
		$this->routes[$command] = $target;
	}

	/**
	 * Runs a CLI application.
	 */
	protected /*int*/ function run()
	{
		global $argv, $argc;

		if($argc < 1) {
			$this->clierror();
			return -1;
		}

		if($argc < 2) {
			$this->clierror();
			return -1;
		}

		$command = $argv[1];

		if(!isset($this->routes[$command])) {
			$this->clierror();
			return -1;
		}

		$target = $this->routes[$command];

		if(is_string($target)) {
			if(!class_exists($target)) {
				printf("Invalid command target for '".$command."'. \r\n");
				return -1;
			}

			$handler = new $target();

			if(!is_subclass_of($handler,'CLIApplicationController')) {
				printf("Invalid command target for '".$command."'. \r\n");
				return -1;
			}

			return $handler->run(array_slice($argv, 1));
		}

		if($target instanceof Closure) {
			return $target(array_slice($argv, 1));
		}

		$this->clierror();
		return -1;
	}
}

abstract class CLIApplicationController
{
	protected $_users;
	protected $_groups;
	protected $_database;
	protected $_stderr;
	protected $_stdin;
	protected $_stdout;

	public function __construct()
	{

	}

	public abstract function run($args);

	public function __isset(/*scalar*/ $key)
	{
		return method_exists($this, 'get'.ucfirst($key));
	}

	public function __get($key)
	{
		return $this->{'get'.ucfirst($key)}();
	}

	public function getStderr()
	{
		return STDERR;
	}

	public function getStdin()
	{
		return STDIN;
	}

	public function getStdout()
	{
		return STDOUT;
	}

	public function getDatabase()
	{
		if(!$this->_database)
			$this->_database = App::getDatabase();
		return $this->_database;
	}

	public function getUsers()
	{
		if(!$this->_users)
			$this->_users = App::getUserService();
		return $this->_users;
	}

	public function getGroups()
	{
		if(!$this->_groups)
			$this->_groups = App::getGroupService();
		return $this->_groups;
	}
}