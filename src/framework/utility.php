<?php
/**
 * Explicitly loads a library contained in the framework, drivers, or helper directories.
 */
/*void*/ function import(/*string*/ $lib) {
	Framework::import($lib);
}

/**
 * A generic bad access exception.
 */
class AccessViolationException extends Exception {}


/**
 * Runs the provided closure immediately.
 */
/*void*/ function scope(Closure $closure) {
	$closure();
}


/**
 * Escapes HTML code so that it will be rendered as text, not HTML.
 */
/*String*/ function escape_html(/*String*/ $s) {
	return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Returns an array created by retrieving json from a remote url.
 * Returns null on failure.
 */
function json_from_url($url)
{
	$response = with(new xHTTPClient())->get($url);

	if($response === null || $response->status != 200)
		return null;

	$data = from_json($response->body);

	return $data;
}

/**
 * Converts escaped HTML code back into valid markup.
 */
/*String*/ function unescape_html(/*String*/ $s) {
	return htmlspecialchars_decode($s, ENT_QUOTES);
}

/**
 * Converts a PHP array into JSON.
 * Returns null on failure.
 */
/*String*/ function to_json(/*array*/ $array, $addXssiPrefix=false) {
	$json = json_encode($array);
	if($json === false) return null;

	if ($addXssiPrefix)
		return "')]}\n".$json;
	return $json;
}

/**
 * Converts a JSON string into a native PHP array.
 * Returns null on failure.
 */
/*array*/ function from_json(/*String*/ $string) {
	$native = json_decode($string, true);

	if(json_last_error() === JSON_ERROR_NONE)
		return $native;
	return null;
}

/*
 * Represents a file attachment to be returned from controllers.
 */
class Attachment {
	public /*File*/ $file = null;
	public function __construct(File $file) {
		$this->file = $file;
	}
}

/**
 * Convenience method to create a file attachment.
 */
/*Attachment*/ function Attach(File $file) {
	return new Attachment($file);
}

/**
 * Creates a list of SQL fields as a string for use in a query template based off of the keys in an array.
 *
 * e.g. sql_keys(array('name' => 'Matt', 'age' => 18)) --> "`name`, `age`"
 */
/*string*/ function sql_keys(/*array<string:mixed>*/ $array) {
	$keys = '';

	foreach($array as $field => $value)
		$keys .= '`'.$field.'`,';

	$keys = substr($keys, 0, -1);

	return $keys;
}


/**
 * Creates a list of SQL placeholders as a string for use in an update query template.
 * e.g. sql_keys(array('name' => 'Matt', 'age' => 18)) --> "`name` = :name, `age` = :age"
 */
/*string*/ function sql_update(/*array<string:mixed>*/ $array) {
	$s = '';

	foreach ($array as $field => $value) {
		$s .= '`'.$field.'` = :'.$field.', ';
	}

	if ($s !== '') {
		$s = substr($s, 0, -2);
	}

	return $s;
}

/**
 * Creates a list of SQL placeholders as a string for use in a query template based off of the keys in an array.
 *
 * e.g. sql_keys(array('name' => 'Matt', 'age' => 18)) --> ":name, :age"
 */
/*string*/ function sql_values(/*array<string:mixed>*/ $array) {
	$values = '';

	foreach($array as $field => $value)
		$values .= ':'.$field.',';

	$values = substr($values, 0, -1);

	return $values;
}

/**
 * Returns a new array map created by prepending ":" to each of the keys in the old map (associated values are preserved).
 *
 * e.g. sql_parameters(array('name' => 'Matt', 'age' => 18)) --> array(':name' => 'Matt', ':age' => 18)
 */
/*array<string:mixed>*/ function sql_parameters(/*array<string:mixed>*/ $array) {
	$new = array();

	foreach($array as $k => $v)
		$new[':'.$k] = $v;

	return $new;
}

/**
 * Generates a random string of the given length.
 * You may optionally specify a set of characters that can be included in the randomly generated string.
 */
/*string*/ function str_random(/*int*/ $length, /*string*/ $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_') {
	// Seed the random number generator.
	mt_srand((int)(microtime(true) * 1000000));

	// Create a unique session identifier.
	$id = "";

	while(strlen($id) < $length) {
		$id .= substr($chars, mt_rand(0, strlen($chars)-1), 1);
	}

	return $id;
}

/**
 * Invokes the "new" operator with a vector of arguments. There is no way to
 * call_user_func_array() on a class constructor, so you can instead use this
 * function:
 *
 *   $obj = newv($class_name, $argv);
 *
 * That is, these two statements are equivalent:
 *
 *   $pancake = new Pancake('Blueberry', 'Maple Syrup', true);
 *   $pancake = newv('Pancake', array('Blueberry', 'Maple Syrup', true));
 *
 * @param  string  The name of a class.
 * @param  list    Array of arguments to pass to its constructor.
 * @return obj     A new object of the specified class, constructed by passing
 *                 the argument vector to its constructor.
 */
function newv($class_name, array $argv) {
  $reflector = new ReflectionClass($class_name);
  if ($argv) {
    return $reflector->newInstanceArgs($argv);
  } else {
    return $reflector->newInstance();
  }
}

/**
 * Instantiates a new Model of the provided type using any additional provided arguments.
 */
/*Model*/ function model(/*string*/ $model /*, ...array $options*/) {
	return call_user_func_array('Model::make', func_get_args());
}

/**
 * Return the length of the given string, array, or object.
 *
 * @param  string  $value
 * @return int
 */
function len($value)
{
	if($value === false)
		return 0;
	if(gettype($value) == "string")
		return mb_strlen($value);
	if(gettype($value) == "array")
		return sizeof($value);
	if(is_object($value) && is_callable(array($value, '__len')))
		return $value->__len();
	if(is_object($value) && is_callable(array($value, 'count')))
		return $value->count();
	return null;
}

if ( ! function_exists('array_extend'))
{
	/**
	 * Extends a first array with the key-value pairs from a second array, overwriting if neccesary.
	 *
	 * @param  array   $src1
	 * @param  array   $src2
	 * @return array
	 */
	function array_extend($src1, $src2)
	{
		foreach($src2 as $k => $v)
			$src1[$k] = $v;
		return $src1;
	}
}

if ( ! function_exists('array_add'))
{
	/**
	 * Add an element to an array if it doesn't exist.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return array
	 */
	function array_add($array, $key, $value)
	{
		if ( ! isset($array[$key])) $array[$key] = $value;

		return $array;
	}
}

if ( ! function_exists('array_build'))
{
	/**
	 * Build a new array using a callback.
	 *
	 * @param  array  $array
	 * @param  \Closure  $callback
	 * @return array
	 */
	function array_build($array, Closure $callback)
	{
		$results = array();

		foreach ($array as $key => $value)
		{
			list($innerKey, $innerValue) = call_user_func($callback, $key, $value);

			$results[$innerKey] = $innerValue;
		}

		return $results;
	}
}

if ( ! function_exists('array_divide'))
{
	/**
	 * Divide an array into two arrays. One with keys and the other with values.
	 *
	 * @param  array  $array
	 * @return array
	 */
	function array_divide($array)
	{
		return array(array_keys($array), array_values($array));
	}
}

if ( ! function_exists('array_dot'))
{
	/**
	 * Flatten a multi-dimensional associative array with dots.
	 *
	 * @param  array   $array
	 * @param  string  $prepend
	 * @return array
	 */
	function array_dot($array, $prepend = '')
	{
		$results = array();

		foreach ($array as $key => $value)
		{
			if (is_array($value))
			{
				$results = array_merge($results, array_dot($value, $prepend.$key.'.'));
			}
			else
			{
				$results[$prepend.$key] = $value;
			}
		}

		return $results;
	}
}

if ( ! function_exists('array_except'))
{
	/**
	 * Get all of the given array except for a specified array of items.
	 *
	 * @param  array  $array
	 * @param  array  $keys
	 * @return array
	 */
	function array_except($array, $keys)
	{
		return array_diff_key($array, array_flip((array) $keys));
	}
}

if ( ! function_exists('array_fetch'))
{
	/**
	 * Fetch a flattened array of a nested array element.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @return array
	 */
	function array_fetch($array, $key)
	{
		foreach (explode('.', $key) as $segment)
		{
			$results = array();

			foreach ($array as $value)
			{
				$value = (array) $value;

				$results[] = $value[$segment];
			}

			$array = array_values($results);
		}

		return array_values($results);
	}
}

if ( ! function_exists('array_first'))
{
	/**
	 * Return the first element in an array passing a given truth test.
	 *
	 * @param  array    $array
	 * @param  Closure  $callback
	 * @param  mixed    $default
	 * @return mixed
	 */
	function array_first($array, $callback, $default = null)
	{
		foreach ($array as $key => $value)
		{
			if (call_user_func($callback, $key, $value)) return $value;
		}

		return value($default);
	}
}

if ( ! function_exists('array_flatten'))
{
	/**
	 * Flatten a multi-dimensional array into a single level.
	 *
	 * @param  array  $array
	 * @return array
	 */
	function array_flatten($array)
	{
		$return = array();

		array_walk_recursive($array, function($x) use (&$return) { $return[] = $x; });

		return $return;
	}
}

if ( ! function_exists('array_forget'))
{
	/**
	 * Remove an array item from a given array using "dot" notation.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @return void
	 */
	function array_forget(&$array, $key)
	{
		$keys = explode('.', $key);

		while (count($keys) > 1)
		{
			$key = array_shift($keys);

			if ( ! isset($array[$key]) or ! is_array($array[$key]))
			{
				return;
			}

			$array =& $array[$key];
		}

		unset($array[array_shift($keys)]);
	}
}

if ( ! function_exists('array_get'))
{
	/**
	 * Get an item from an array using "dot" notation.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	function array_get($array, $key, $default = null)
	{
		if (is_null($key)) return $array;

		if (isset($array[$key])) return $array[$key];

		foreach (explode('.', $key) as $segment)
		{
			if ( ! is_array($array) or ! array_key_exists($segment, $array))
			{
				return value($default);
			}

			$array = $array[$segment];
		}

		return $array;
	}
}

if ( ! function_exists('array_only'))
{
	/**
	 * Get a subset of the items from the given array.
	 *
	 * @param  array  $array
	 * @param  array  $keys
	 * @return array
	 */
	function array_only($array, $keys)
	{
		return array_intersect_key($array, array_flip((array) $keys));
	}
}

if ( ! function_exists('array_pluck'))
{
	/**
	 * Pluck an array of values from an array.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @return array
	 */
	function array_pluck($array, $key)
	{
		return array_map(function($value) use ($key)
		{
			return is_object($value) ? $value->$key : $value[$key];

		}, $array);
	}
}

if ( ! function_exists('array_pull'))
{
	/**
	 * Get a value from the array, and remove it.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @return mixed
	 */
	function array_pull(&$array, $key)
	{
		$value = array_get($array, $key);

		array_forget($array, $key);

		return $value;
	}
}

if ( ! function_exists('array_set'))
{
	/**
	 * Set an array item to a given value using "dot" notation.
	 *
	 * If no key is given to the method, the entire array will be replaced.
	 *
	 * @param  array   $array
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	function array_set(&$array, $key, $value)
	{
		if (is_null($key)) return $array = $value;

		$keys = explode('.', $key);

		while (count($keys) > 1)
		{
			$key = array_shift($keys);

			// If the key doesn't exist at this depth, we will just create an empty array
			// to hold the next value, allowing us to create the arrays to hold final
			// values at the correct depth. Then we'll keep digging into the array.
			if ( ! isset($array[$key]) or ! is_array($array[$key]))
			{
				$array[$key] = array();
			}

			$array =& $array[$key];
		}

		$array[array_shift($keys)] = $value;
	}
}

/**
 * Limit the number of characters in a string.
 *
 * @param  string  $value
 * @param  int     $limit
 * @param  string  $end
 * @return string
 */
function str_limit($value, $limit = 100, $end = '...')
{
	if (mb_strlen($value) <= $limit) return $value;

	return mb_substr($value, 0, $limit, 'UTF-8').$end;
}

/**
 * Limit the number of words in a string.
 *
 * @param  string  $value
 * @param  int     $words
 * @param  string  $end
 * @return string
 */
function str_words($value, $words = 100, $end = '...')
{
	preg_match('/^\s*+(?:\S++\s*+){1,'.$words.'}/u', $value, $matches);

	if ( ! isset($matches[0])) return $value;

	if (strlen($value) == strlen($matches[0])) return $value;

	return rtrim($matches[0]).$end;
}



/**
 * Convert the given string to lower-case.
 *
 * @param  string  $value
 * @return string
 */
function str_lower($value)
{
	return mb_strtolower($value);
}

/**
 * Convert the given string to upper-case.
 *
 * @param  string  $value
 * @return string
 */
function str_upper($value)
{
	return mb_strtoupper($value);
}

/**
 * Generate a URL friendly "slug" from a given string.
 *
 * @param  string  $title
 * @param  string  $separator
 * @return string
 */
function str_slug($title, $separator = '-')
{
	// Remove all characters that are not the separator, letters, numbers, or whitespace.
	$title = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', mb_strtolower($title));

	// Convert all dashes/undescores into separator
	$flip = $separator == '-' ? '_' : '-';

	$title = preg_replace('!['.preg_quote($flip).']+!u', $separator, $title);

	// Replace all separator characters and whitespace by a single separator
	$title = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $title);

	return trim($title, $separator);
}

/**
 * Determine if a given string matches a given pattern.
 *
 * @param  string  $pattern
 * @param  string  $value
 * @return bool
 */
function str_is($pattern, $value)
{
	if ($pattern == $value) return true;

	$pattern = preg_quote($pattern, '#');

	// Asterisks are translated into zero-or-more regular expression wildcards
	// to make it convenient to check if the strings starts with the given
	// pattern such as "library/*", making any string check convenient.
	if ($pattern !== '/')
	{
		$pattern = str_replace('\*', '.*', $pattern).'\z';
	}
	else
	{
		$pattern = '/$';
	}

	return (bool) preg_match('#^'.$pattern.'#', $value);
}

/**
 * Cap a string with a single instance of a given value.
 *
 * @param  string  $value
 * @param  string  $cap
 * @return string
 */
function str_finish($value, $cap)
{
	return rtrim($value, $cap).$cap;
}

/**
 * Determine if a given string ends with a given needle.
 *
 * @param string $haystack
 * @param string|array $needles
 * @return bool
 */
function str_endswith($haystack, $needles)
{
	foreach ((array) $needles as $needle)
	{
		if ($needle == substr($haystack, strlen($haystack) - strlen($needle))) return true;
	}

	return false;
}

/**
 * Determine if a string starts with a given needle.
 *
 * @param  string  $haystack
 * @param  string|array  $needles
 * @return bool
 */
function str_startswith($haystack, $needles)
{
	foreach ((array) $needles as $needle)
	{
		if (strpos($haystack, $needle) === 0) return true;
	}

	return false;
}

/**
 * Determine if a given string contains a given sub-string.
 *
 * @param  string        $haystack
 * @param  string|array  $needle
 * @return bool
 */
function str_contains($haystack, $needle)
{
	foreach ((array) $needle as $n)
	{
		if (strpos($haystack, $n) !== false) return true;
	}

	return false;
}

if ( ! function_exists('class_basename'))
{
	/**
	 * Get the class "basename" of the given object / class.
	 *
	 * @param  string|object  $class
	 * @return string
	 */
	function class_basename($class)
	{
		$class = is_object($class) ? get_class($class) : $class;

		return basename(str_replace('\\', '/', $class));
	}
}

if ( ! function_exists('dd'))
{
	/**
	 * Dump the passed variables and end the script.
	 *
	 * @param  dynamic  mixed
	 * @return void
	 */
	function dd()
	{
		array_map(function($x) { var_dump($x); }, func_get_args()); die;
	}
}

if ( ! function_exists('e'))
{
	/**
	 * Escape HTML entities in a string.
	 *
	 * @param  string  $value
	 * @return string
	 */
	function e($value)
	{
		return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
	}
}

if ( ! function_exists('head'))
{
	/**
	 * Get the first element of an array. Useful for method chaining.
	 *
	 * @param  array  $array
	 * @return mixed
	 */
	function head($array)
	{
		return reset($array);
	}
}

if ( ! function_exists('last'))
{
	/**
	 * Get the last element from an array.
	 *
	 * @param  array  $array
	 * @return mixed
	 */
	function last($array)
	{
		return end($array);
	}
}

if ( ! function_exists('object_get'))
{
	/**
	 * Get an item from an object using "dot" notation.
	 *
	 * @param  object  $object
	 * @param  string  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	function object_get($object, $key, $default = null)
	{
		if (is_null($key)) return $object;

		foreach (explode('.', $key) as $segment)
		{
			if ( ! is_object($object) or ! isset($object->{$segment}))
			{
				return value($default);
			}

			$object = $object->{$segment};
		}

		return $object;
	}
}

if ( ! function_exists('value'))
{
	/**
	 * Return the default value of the given value.
	 *
	 * @param  mixed  $value
	 * @return mixed
	 */
	function value($value)
	{
		if($value instanceof Closure)
			return $value();
		if(is_object($value) && is_callable(array($value, '__value')))
			return $value->__value();
		return $value;
	}

	function valueOf($value) {
		return value($value);
	}
}

if ( ! function_exists('with'))
{
	/**
	 * Return the given object. Useful for chaining.
	 *
	 * @param  mixed  $object
	 * @return mixed
	 */
	function with($object)
	{
		return $object;
	}
}
