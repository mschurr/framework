<?php
	
class Framework
{
	public static function import($library)
	{
		if(file_exists(FILE_ROOT.'/framework/lib/'.$library.'.php')) {
			require_once(FILE_ROOT.'/framework/lib/'.$library.'.php');
		}
		elseif(file_exists(FILE_ROOT.'/helpers/'.$library.'.php')) {
			require_once(FILE_ROOT.'/helpers/'.$library.'.php');
		}
		else {
			$back = debug_backtrace();
			ErrorHandler::fire(1,'The framework module \''.$library.'\' can not be imported because it does not exist in '.$back[0]['file'].' on line '.$back[0]['line'].'',$back[0]['file'],$back[0]['line'],'');	
		}
	}
}
?>