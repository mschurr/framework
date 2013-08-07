<?php
	
class Log
{	
	public static function write($s)
	{
		$log = FILE_ROOT.'/webapp.log';
		$s = '['.date("d-M-Y H:i:s").'] '.$s.EOL;
		
		if(!file_exists($log)) {
			$handle = fopen($log,'w');
			fwrite($handle, $s);
			fclose($handle);
		}
		else {
			$handle = fopen($log,'a');
			fwrite($handle, $s);
			fclose($handle);
		}
	}
}
?>