<?php
/*
	##################################################################################################
	# BLADE TEMPLATE LANGUAGE PARSER
	#	@author Matthew Schurr
	#
	# This implementation is based loosely on Laravel Blade (http://www.laravel.com/).
	##################################################################################################
	
	#####################################
	# Blade Functions:
	#####################################
	@extends('layout')
		-- (For Views Only): Indicates which layout should be used. Must be the first declaration.
		
	@body('attr','value')
		-- Adds an HTML attribute and value to the <body> tag.
		
	@head ... @endhead
		-- Places HTML inside the <head> tag.
	
	@lang('string')
		-- Substitutes the language string with the provided key.
		
	@lang('string', [...params])
		-- Substitutes the language string with the provided key and parameters.
		
	@script(statement)
		-- Includes a JavaScript file. Possible to use PHP e.g. @script( URL::asset('js/my.js') )
	
	@style(statement)
		-- Includes a Cascading Style Sheet. Possible to use PHP e.g. @script( URL::asset('css/my.css') )
	
	@title('string')
		-- Sets the document title. Usually you would do this in the controller.
	
	@keywords('string')
		-- Sets the document keywords.
	
	@description('string')
		-- Adds the document description.
	
	@meta('key, 'value')
		-- Adds a meta tag to the <head>.
	
	@link('rel', 'type', 'href', 'media')
		-- Adds a link tag to the <head>.
	
	{{{ $var }}}
		-- This prints out an html escaped variable.
		
	{{ $var }}
		-- This prints out a variable.
		
	{{-- ... --}}
		-- This indicates a blade comment (these are not rendered).
	
	@include('view')
		-- Dumps the source of another view file into the current layout or view (similar to PHP includes).
	
	@section('sidebar') ... [@parent] @endsection
		-- (In Layouts): Defines a section with a default value.
		-- (In Views): Defines the content of a section. 
		               Overwrites the content defined in the layout (unless @parent is declared, in which case @parent is replaced with the parent's content).
	
	@yield('section')
		-- (In Layouts): Yields the content of a section.
	
	@if ( ...condition... )
		... do this ...
	@elseif ( ...condition... )
		... do this ...
	@else
		... do this ...
	@endif
		-- Checks whether condition(s) are met. Can use any PHP functions/constructs. e.g. @if($var == true || is_array($array))
	
	@unless ($loggedin)
		You are not logged in.
	@endunless
	
	@for ($i=1; $i <= 10; $i++)
		{{ $i }}
	@endfor
	
	@foreach ($array as $k => $v)
		{{$k}}, {{$v}}
	@endforeach
	
	@while (false)
		Hello World!
	@endwhile
	
	@forelse($array as $k => $v)
		{{$k}}, {{$v}}
	@empty
		This will be printed if $array is empty.
	@endforelse
*/


abstract class BladeParser
{
	public $blade_file;
	protected $blade_file_cache;
	protected $blade_name;
	protected $errors = array();
	protected $nests = array();
	protected $echoTags = array('{{','}}');
	protected $render_data = array();
	protected $escapedEchoTags = array('{{{','}}}');
	protected $compilers = array(
		'extends',
		'includes',
		'comments',
		'echos',
		'forelse',
		'openings',
		'closings',
		'else',
		'unless',
		'endunless',
		'language',
		'documentext',
		'sections',
		'layout'
	);
	
	public function __construct($name)
	{
		$this->controller = Route::__getActiveController();
		$this->blade_name = trim(str_replace(".", "/", $name),'/');	
		$this->blade_file = FILE_ROOT.'/'.($this->is('view') ? 'views' : 'layouts').'/'.$this->blade_name.'.blade.php';
		
		$dir = Config::get('cache.directory', FILE_ROOT.'/cache');
		
		$f = new File($dir.'/');
		if(!$f->exists)
			$f->create();
		
		$this->blade_file_cache = $dir.'/blade-'.md5($this->blade_file).'.blade';
		call_user_func_array(array($this, 'init'), func_get_args());
	}
	
	public function with($data, $data2=null)
	{
		if(!is_array($data))
			$data = array($data => $data2);
		
		$this->render_data = array_merge($this->render_data, $data);
		return $this;
	}
	
	public function nest($sect_name, $view_name, $view_data = array())
	{
		// TODO: nesting is not implemented
		/*$view = new BladeView($view_name, $doc);
		$view->with(array_merge(View::getShared(), $view_data));
		$view->nests[$sect_name] = $view;*/
		return $this;
	}
	
	public function render($data=array())
	{
		$data = array_merge($this->render_data, $data);
		extract($data);
		ob_start();
		try {
			include($this->blade_file_cache);
		}
		catch (Exception $e) {
			throw new Exception('The"'.$this->blade_file.'" blade file contains errors and could not be parsed.');
			//ErrorHandler::fire(1,'The &quot;'.$this->blade_file.'&quot; blade file contains errors and could not be parsed.',$this->blade_file, 0,'',false);
		}
		$content = ob_get_contents();				
		ob_end_clean();
		return $content;
	}
	
	public function prepare()
	{
		if(file_exists($this->blade_file_cache.'.dep')) {
			$modified = false;
			$deps = from_json(file_get_contents($this->blade_file_cache.'.dep'));
			$deps[] = FRAMEWORK_ROOT.'/framework/blade.php';
			$cache_time = filemtime($this->blade_file_cache);
			
			foreach($deps as $i => $file) {
				if(!file_exists($file)) {
					$modified = true;
					break;
				}
				
				if(filemtime($file) > $cache_time) {
					$modified = true;
					break;
				}
			}
			
			if(!$modified)
				return $this;
		}
		
		$blade = $this->compile(true);
		file_put_contents($this->blade_file_cache, $blade);
		file_put_contents($this->blade_file_cache.'.dep', to_json(array_unique($this->dependencies)));
		return $this;
	}
	
	public function &compile($return=false)
	{
		// Check Existence
		if(!file_exists($this->blade_file)) {
			throw new Exception('The "'.$this->blade_file.'" blade file does not exist.');
			//return ErrorHandler::fire(1,'The &quot;'.$this->blade_file.'&quot; blade file does not exist.',$this->blade_file, 0,'',false);
		}
		
		// Load Blade From File
		$blade = file_get_contents($this->blade_file);
		
		// Compile Blade
		foreach ($this->compilers as $compiler)
		{
			if(is_callable(array($this, 'compile_'.$compiler)))
				$blade = $this->{"compile_".$compiler.""}($blade);
			else
				$blade = '[[[ Blade: Missing Compiler compile_'.$compiler.' ]]]'.EOL.$blade;
		}
		
		// Write Compiled Blade to File Cache
		if($return === true) {
			return $blade;
		}
		else {
			file_put_contents($this->blade_file_cache, $blade);
			return $this;
		}
	}
	
	public function getCacheContents()
	{
		if(file_exists($this->blade_file_cache))
			return file_get_contents($this->blade_file_cache);
		return null;
	}
	
	public function __get($key)
	{
		if(isset($this->{$key}))
			return $this->{$key};
		return null;
	}
	
	public function is($opt)
	{
		if($opt == 'layout')
			return ($this instanceof BladeLayout);
		if($opt == 'view')
			return ($this instanceof BladeView);
		return false;
	}
	
	public function valid()
	{
		return sizeof($this->errors) === 0;
	}
	
	// --- Helper Functions
	
	/**
	 * Get the regular expression for a generic Blade function.
	 *
	 * @param  string  $function
	 * @return string
	 */
	public function createMatcher($function)
	{
		return '/(?<!\w)(\s*)@'.$function.'(\s*\(.*\))/';
	}
	
	/**
	 * Get the regular expression for a generic Blade function.
	 *
	 * @param  string  $function
	 * @return string
	 */
	public function createOpenMatcher($function)
	{
		return '/(?<!\w)(\s*)@'.$function.'(\s*\(.*)\)/';
	}

	/**
	 * Create a plain Blade matcher.
	 *
	 * @param  string  $function
	 * @return string
	 */
	public function createPlainMatcher($function)
	{
		return '/(?<!\w)(\s*)@'.$function.'(\s*)/';
	}
	
	// --- Shared Compilers
	
	public function compile_extends($value)
	{
		// By convention, Blade views using template inheritance must begin with the
		// @extends expression, otherwise they will not be compiled with template
		// inheritance. So, if they do not start with that we will just return.
		
		$check = strpos($value, '@extends');
		
		if($check === false)
			return $value; // We don't have anything to compile.
			
		if($check !== 0) {
			$this->errors[] = 'Syntax Error: @extends must be the declaration';
			return;
		}
		
		if(!$this->is('view')) {
			$this->errors[] = 'Syntax Error: @extends can only be used by views';
			return;
		}	
		
		// Next, we just want to split the values by lines, and create an expression
		// to include the parent layout at the end of the templates. Which allows
		// the sections to get registered before the parent view gets rendered.
		
		$lines = preg_split("/(\r?\n)/", $value);
		$pattern = $this->createMatcher('extends');
		$self =& $this;
		$lines[] = preg_replace_callback($pattern, function($matches) use ($self) {
			$self->layout = new BladeLayout(substr($matches[2], 2, -2));
			$self->registerDependency($self->layout->blade_file);
			return ''; /*'$1@use-layout$2'*/
		}, $lines[0]);
		
		return implode("\r\n", array_slice($lines, 1));
		return $value;
	}
	
	protected function compile_comments($value)
	{
		$pattern = sprintf('/%s--((.|\s)*?)--%s/', $this->echoTags[0], $this->echoTags[1]);
		return preg_replace($pattern, '', $value);
		return preg_replace($pattern, '<?php /* $1 */ ?>', $value);
	}
	
	protected function compile_echos($value)
	{
		return $this->compile_regular_echos($this->compile_escaped_echos($value));
	}
	
	protected function compile_regular_echos($value)
	{
		$pattern = sprintf('/%s\s*(.+?)\s*%s/s', $this->echoTags[0], $this->echoTags[1]);
		return preg_replace($pattern, '<?php echo $1; ?>', $value);
	}
	
	protected function compile_escaped_echos($value)
	{
		$pattern = sprintf('/%s\s*(.+?)\s*%s/s', $this->escapedEchoTags[0], $this->escapedEchoTags[1]);
		return preg_replace($pattern, '<?php echo escape_html($1); ?>', $value);
	}
	
	protected function compile_openings($value)
	{
		$pattern = '/(?(R)\((?:[^\(\)]|(?R))*\)|(?<!\w)(\s*)@(if|elseif|foreach|for|while)(\s*(?R)+))/';

		return preg_replace($pattern, '$1<?php $2$3: ?>', $value);
	}
	
	protected function compile_closings($value)
	{
		$pattern = '/(\s*)@(endif|endforeach|endfor|endwhile)(\s*)/';

		return preg_replace($pattern, '$1<?php $2; ?>$3', $value);
	}
	
	protected function compile_else($value)
	{
		$pattern = $this->createPlainMatcher('else');

		return preg_replace($pattern, '$1<?php else: ?>$2', $value);
	}
	
	protected function compile_unless($value)
	{
		$pattern = $this->createMatcher('unless');

		return preg_replace($pattern, '$1<?php if ( !$2): ?>', $value);
	}

	protected function compile_endunless($value)
	{
		$pattern = $this->createPlainMatcher('endunless');

		return preg_replace($pattern, '$1<?php endif; ?>$2', $value);
	}
	
	protected function compile_language($value)
	{
		$pattern = $this->createMatcher('lang');

		$value = preg_replace($pattern, '$1<?php echo Localization::choose$2; ?>', $value);

		$pattern = $this->createMatcher('choice');

		return preg_replace($pattern, '$1<?php echo Localization::choose$2; ?>', $value);
	}
	
	protected function compile_includes($value)
	{
		$pattern = $this->createOpenMatcher('include');
		$self =& $this;
		return preg_replace_callback($pattern, function($matches) use (&$self){
			$view = substr(trim($matches[0]),10,-2);
			$view = new BladeView($view, $self->layout, ($self->master === true ? $self : $self->master));
			$content = $view->compile(true);
			return $content;
		}, $value);
	}
	
	public function compile_layout($value)
	{
		if($this->master === true) {
			if($this->layout instanceof BladeLayout)
				$value = $this->layout->layoutCompile($value);
		}
		
		return $value;
	}
	
	public function compile_forelse($value)
	{
		$value = preg_replace('/@forelse(.?[ \t]*)\((.*?) as (.*?)\)(.?[ \t]*)\n(.*?)@empty(.?[ \t]*)\n(.*?)@endforelse/s', '<?php if(len(\\2) > 0) { ?>
	<?php foreach(\\2 as \\3) { ?>
		\\5
	<?php } ?>
<?php } else { ?>
	\\7
<?php } ?>'.EOL, $value);
		return $value;
	}
	
	public function compile_documentext($value)
	{
		$self =& $this;
		$append = '';
				
		$functions = array(
			'script' => 'addScript',
			'style' => 'addStyle',
			'title' => 'setTitle',
			'keywords' => 'setKeywords',
			'meta' => 'setMeta',
			'description' => 'setDescription',
			'body' => 'setBody',
			'link' => 'addLink'
		);
		
		foreach($functions as $bl_fn => $php_fn) {
			$value = preg_replace_callback($this->createMatcher($bl_fn), function($matches) use (&$self, &$functions, &$php_fn, &$append){
				
				$append .= '<?php App::getResponse()->document->'.$php_fn.''.$matches[2].';?>';
				return '';
				
				/*$args = substr($matches[2],1,-1);
				$args = explode(",", $args);
								
				foreach($args as $k => $v) {
					$args[$k] = eval('return ('.$v.');');
				}
				
				call_user_func_array(array($self->document, $php_fn), $args);//*/
			}, $value);
		}
		
		$value = preg_replace_callback('/@head(.?[ \t]*)\n(.*?)@endhead/s', function($matches) use (&$self) {
			//$self->document->appendToHead($matches[2]);
			return '<?php App::getResponse()->document->appendToHead("'.str_replace('"','\"',$matches[2]).'");?>';
		}, $value);
		
		
		return $append.$value;
	}
	
	public function compile_sections($value)
	{
		$self =& $this;
		
		$value = preg_replace_callback('/@section(.?[ \t]*)\(\'(.*?)\'\)(.?[ \t]*)\n(.*?)@endsection/s', function($matches) use (&$self) {
			if($self->layout instanceof BladeLayout)
				$self->layout->registerSection($matches[2], $matches[4]);
			return '';
		}, $value);
		
		return $value;
	}
	
	protected /*<Controller>*/ $controller;
	public function getAssociatedController() {
		return $this->controller;
	}
}

class BladeLayout extends BladeParser
{
	public /*<array>*/ $views = array(/* <String> HASH => <BladeView> VIEW, ..+ */);
	public /*<array>*/ $sections = array(/* <String> NAME => <String> Compiled Blade Code*/);
	public /*<array>*/ $section_parents = array(/* <String> NAME => <String> Compiled Blade Code*/); 
	
	public function init()
	{
	}
	
	public function compile_sections($value)
	{
		$self =& $this;
		$layout = preg_replace_callback('/@section(.?[ \t]*)\(\'(.*?)\'\)(.?[ \t]*)\n(.*?)@endsection/s', function($matches) use (&$self) {
			$self->section_parents[$matches[2]] = $matches[4];
		}, $value);
		
		return $value;
	}
	
	public function layoutCompile($value)
	{
		// Load and Compile Layout Markup
		$ignore = array('includes');
		
		foreach($this->compilers as $k => $v)
			if(in_array($v, $ignore))
				unset($this->compilers[$k]);
		
		$layout = $this->compile(true);
		
		$self =& $this;
		
		// Replace Yields
		$layout = preg_replace_callback($this->createMatcher('yield'), function($matches) use (&$self){
			$section = substr($matches[2],2,-2);
			
			if(isset($self->sections[$section]))
				return str_replace("@parent","",$self->sections[$section]);
			return '';
			
		}, $layout);
		
		// Replace Sections
		$layout = preg_replace_callback('/@section(.?[ \t]*)\(\'(.*?)\'\)(.?[ \t]*)\n(.*?)@endsection/s', function($matches) use (&$self) {
			$section = $matches[2];
			if(isset($self->sections[$section]))
				return str_replace("@parent".EOL,(isset($self->section_parents[$section]) ? $self->section_parents[$section] : ''),$self->sections[$section]);
			return '';
		}, $layout);
		
		// Append Leftover View Markup
		$layout .= $value;		
		
		// Return Finalized Layout
		return $layout;
	}
	
	public function registerSection($name, $blade)
	{
		$this->sections[$name] = $blade;
		return $this;
	}
}

class BladeView extends BladeParser
{
	public /*<BladeLayout>*/ $layout = null;
	public /*<Mixed>*/ $master;
	protected /*<Array>*/ $dependencies = array(/*File,..+*/);
	
	public function init($view, &$layout=null, &$master=true)
	{
		$this->layout =& $layout;
		$this->master =& $master;
		
		if($this->master instanceof BladeView)  {
			$this->master->registerDependency($this->blade_file);
		}
		else {
			$this->registerDependency($this->blade_file);
		}
	}
	
	public function registerDependency($file) {
		$this->dependencies[] = $file;
	}
}

?>