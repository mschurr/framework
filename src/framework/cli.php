<?php

class CLIExitStatus
{
	const Success = 0;
	const Failure = 1;
}

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
	protected /*void*/ function clierror(/*array<string>*/ $argv)
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
	protected /*void*/ function __construct()
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
	 * Dispatches an incoming command-line request and runs the command. Returns status.
	 */
	protected /*int*/ function dispatch()
	{
		global $argv, $argc;

		// Resolve Command
		if($argc < 2) {
			$this->clierror($argv);
			return CLIExitStatus::Failure;
		}

		$command = $argv[1];

		// Resolve Target
		if(!isset($this->routes[$command])) {
			$this->clierror($argv);
			return CLIExitStatus::Failure;
		}

		$target = $this->routes[$command];

		// Handle Class Targets
		if(is_string($target)) {
			if(!class_exists($target)) {
				fprintf(STDOUT, "Invalid command target for '".$command."' (not found).\r\n");
				return CLIExitStatus::Failure;
			}

			$handler = new $target();

			if(!is_subclass_of($handler,'CLIApplicationController')) {
				fprintf(STDOUT, "Invalid command target for '".$command."' (must inherit from CLIApplicationController).\r\n");
				return CLIExitStatus::Failure;
			}

			$status = $handler->main(array_slice($argv, 1));
			return ($status === null) ? CLIExitStatus::Success : $status;
		}

		// Handle Closure Targets
		if($target instanceof Closure) {
			$status = $target(array_slice($argv, 1));
			return ($status === null) ? CLIExitStatus::Success : $status;
		}

		// Handle Unknown Target
		fprintf(STDOUT, "Unable to resolve target for command \"%s\"\r\n", $command);
		return CLIExitStatus::Failure;
	}

	/**
	 * Runs a CLI application.
	 */
	protected /*void*/ function run()
	{
		$status = $this->dispatch();

		Config::_save();

		exit($status);	
	}
}

/**
 * An abstract controller class for command-line applications.
 * Command-line controllers should inherit from this class and override main().
 */
abstract class CLIApplicationController
{
	protected $_users;
	protected $_groups;
	protected $_database;
	protected $_stderr;
	protected $_stdin;
	protected $_stdout;

	/**
	 * Runs the application.
	 * $args contains the command-line arguments, beginning with the routed command.
	 * Returns the status (0 = successful) or an error code.
	 */
	public abstract /*int*/ function main(/*array<string>*/ $args);

	public /*boolean*/ function __isset(/*scalar*/ $key)
	{
		return method_exists($this, 'get'.ucfirst($key));
	}

	public /*mixed*/ function __get(/*scalar*/ $key)
	{
		return $this->{'get'.ucfirst($key)}();
	}

	public /*int*/ function getStderr()
	{
		return STDERR;
	}

	public /*int*/ function getStdin()
	{
		return STDIN;
	}

	public /*int*/ function getStdout()
	{
		return STDOUT;
	}

	public /*Database*/ function getDatabase()
	{
		if(!$this->_database)
			$this->_database = App::getDatabase();
		return $this->_database;
	}

	public /*User_Service_Provider*/ function getUsers()
	{
		if(!$this->_users)
			$this->_users = App::getUserService();
		return $this->_users;
	}

	public /*Group_Service_Provider*/ function getGroups()
	{
		if(!$this->_groups)
			$this->_groups = App::getGroupService();
		return $this->_groups;
	}
}