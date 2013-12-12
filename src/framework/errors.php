<?php
use Whoops\Run;
use Whoops\Handler\PrettyPageHandler;
use Exception as BaseException;

error_reporting(E_ALL & ~E_NOTICE);
ini_set('error_log', FILE_ROOT.'/webapp.log');
ini_set('log_errors', true);

if(true) {
	$run = new Run;
	$handler = new PrettyPageHandler;
	
	$handler->addDataTable('Framework Data', array(
		/*'version' => 'dev-master'*/
	));
	
	$run->pushHandler($handler);
	$run->register();
}
else {
	set_error_handler("ErrorHandler::fire");
}

class ErrorHandler
{
	public static function fire($level,$message,$file,$line,$context,$backtrace=true)
	{
		// Log Error
		Log::write("PHP Runtime error: ".$message." in ".$file." on line ".$line."");
		
		// Build Report
		$report = 'A critical server-side script execution error has occured.<br />
		<br />
		<style type="text/css">td { vertical-align: top; padding: 5px;}</style>
		<fieldset>
			<legend>Report</legend>
			
			<table width="100%" border="0">
				<tr>
					<td width="150">TIME</td>
					<td>'.date("m/d/y h:i:s A").'<br /><br /></td>
				</tr>
				<tr>
					<td valign="top">OFFENDING FILE</td>
					<td>~'.str_replace(str_replace('/','\\',FILE_ROOT),"",str_replace(FILE_ROOT,"",$file)).':'.$line.'<br /><br /></td>
				</tr>
				<tr>
					<td>ERROR</td>
					<td>'.$message.'<br /><br /></td>
				</tr>
				
				<tr>
					<td valign="top">BACK TRACE</td>
					<td>';
					
						ob_start();
						debug_print_backtrace();
						$report .= nl2br(ob_get_contents());				
						ob_end_clean();
					
					$report .= '<br /><br /></td>
				</tr>
				<tr>
					<td valign="top">FILE TRACE</td>
					<td>';
					
					foreach(get_included_files() as $key => $file)
					{
						$s = str_replace(FILE_ROOT,"",$file);
						$s = str_replace(str_replace('/','\\',FILE_ROOT),"",$s);
						$report .= '~'.$s.'<br />';
					}
					
					$report .= '<br /></td>
				</tr>
				
			</table>
			
		</fieldset>
		<br />
		';
		
		// Display Report
		$response = new Response();
		$response->error(500, (App::getErrorMode() === true ? $report : null), true);
	}
	
	public static function backtrace($traces_to_ignore = 2)
	{
		$traces = debug_backtrace();
		$ret = array();
		foreach($traces as $i => $call){
			if ($i < $traces_to_ignore ) {
				continue;
			}
	
			$object = '';
			if (isset($call['class'])) {
				$object = $call['class'].$call['type'];
				if (is_array($call['args'])) {
					foreach ($call['args'] as &$arg) {
						self::backtrace_get_arg($arg);
					}
				}
			}       
		
			$ret[] = '#'.str_pad($i - $traces_to_ignore, 3, ' ')
			.$object.$call['function'].'('.(is_array($call['args']) ? implode(', ', $call['args']) : '')
			.') called at ['.$call['file'].':'.$call['line'].']';
		}
	
		return implode("<br />\n",$ret);
	}
	
	public static function backtrace_get_arg(&$arg) {
		if (is_object($arg)) {
			$arr = (array)$arg;
			$args = array();
			foreach($arr as $key => $value) {
				if (strpos($key, chr(0)) !== false) {
					$key = '';    // Private variable found
				}
				$args[] =  '['.$key.'] => '.self::backtrace_get_arg($value);
			}
	
			$arg = get_class($arg) . ' Object ('.implode(',', $args).')';
		}
	}
}
