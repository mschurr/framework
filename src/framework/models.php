<?php
/*
	All models have a path. For example, for the path:
		MyFolder.MySubFolder.MyModel
		
	The class for the model must be named:
		myfolder_mysubfolder_mymodel
		
	The file containing the model must be named:
		FILE_ROOT/models/myfolder/mysubfolder/mymodel.php
		
	All models must extend the Model class. The init() function will be called automatically.
	
	class myfolder_mysubfolder_mymodel extends Model
	{
		public function init()
		{
		}
	}
	
	If you construct the model using Model::make(path, ...args... ) then the arguments will be passed to init.
	
	class anothermodel extends Model
	{
		public function init($arg1, $arg2) {
			$this->db->query(...); // $this->db points to database object
		}
		protected function cleanup() // automatically called when no more references to the model remain
	}
	
	$model =& Model::make('AnotherModel', 'arg1', 'arg2');
*/

class Model
{
	public $database;
	public $db;
	
	public final function __construct()
	{
	}
	
	public final function __destruct()
	{
		if(method_exists($this, 'cleanup'))
			$this->cleanup();
	}
	
	public final function &__get($k)
	{
		if($k == 'db' || $k == 'database')
			return App::database();
		throw new Exception("Access to undefined model property.");
	}
	
	public final function __isset($k)
	{
		if($k == 'db' || $k == 'database')
			return true;
		return false;
	}
	
	//protected function cleanup();
	//public function init();
	
	// --- Static Functions
	protected static $references = array();
	
	public static function &load($path, $args=array())
	{
		// Define a key to hold a reference to the model.
		$refkey = md5(strtolower($path).serialize($args));
		
		// Check if the model is in our references array.
		if(isset(self::$references[$refkey])) {
			return self::$references[$refkey];
		}
		
		// If not, attempt to load a new instance of the model.
		$name = trim(str_replace(".", "/", strtolower($path)),'/');
		$file = new File(FILE_ROOT.'/models/'.$name.'.php');
		$class = str_replace("/", "_", strtolower($name));
		
		if(class_exists($class)) {
			$model = new $class();
		}
		else {
			if(!$file->exists)
				throw new Exception("The model ".$path." does not exist.");
		
			require_once($file->path);
			
			if(!class_exists($class))
				throw new Exception("The model ".$path." does not exist.");
				
			$model = new $class();
		}
		
		// Apply loading parameters.
		if(method_exists($model, 'init'))
			call_user_func_array(array($model, 'init'), $args);
		
		// Push the model to our references array.
		self::$references[$refkey] =& $model;
		
		// Return the model.
		return $model;
	}
	
	public static function make(/* String name, [Mixed parameter1,[...]]*/)
	{
		$n = func_get_args();
		
		if(sizeof($n) < 1)
			throw new Exception("Can not instantiate unknown model.");
		
		return self::load($n[0], array_slice($n, 1));
	}
}