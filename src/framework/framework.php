<?php
	
class Framework
{
	public static function import($library)
	{
		if(file_exists(FRAMEWORK_ROOT.'/framework/'.$library.'.php')) {
			require_once(FRAMEWORK_ROOT.'/framework/'.$library.'.php');
		}
		elseif(file_exists(FRAMEWORK_ROOT.'/libraries/'.$library.'.php')) {
			require_once(FRAMEWORK_ROOT.'/libraries/'.$library.'.php');
		}
		elseif(file_exists(FILE_ROOT.'/helpers/'.$library.'.php')) {
			require_once(FILE_ROOT.'/helpers/'.$library.'.php');
		}
		else {
			throw new Exception('The framework module \''.$library.'\' can not be imported because it does not exist!');
		}
	}
}

//$back = debug_backtrace();
			//ErrorHandler::fire(1,'The framework module \''.$library.'\' can not be imported because it does not exist in '.$back[0]['file'].' on line '.$back[0]['line'].'',$back[0]['file'],$back[0]['line'],'');	
