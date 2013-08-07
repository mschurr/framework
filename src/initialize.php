<?php
/*
	Basic PHP Web Framework
	@author Matthew Schurr
*/

function included() {
	return true;
}

if (version_compare(phpversion(), '5.3.0', '<') == true) {
	die('You must install PHP >= 5.3.0 in order to use this framework.');
}


define('FILE_ROOT',str_replace("/index.php","",$_SERVER['SCRIPT_FILENAME']));
define('EOL', PHP_EOL);
require(FILE_ROOT.'/framework/errors.php');

ini_set('expose_php',false);
ini_set('magic_quotes_gpc','Off');
ini_set('register_globals','Off');
ini_set('default_charset','UTF-8');
ini_set('memory_limit','64M');
ini_set('max_execution_time','200');
ini_set('max_input_time','200');
ini_set('upload_max_filesize','999M');
ini_set('post_max_size', '999M');
ini_set('max_file_uploads', 10);
ini_set('safe_mode','Off');
ini_set('mysql.connect_timeout','20');
ini_set('session.use_cookies','On');
ini_set('session.use_trans_sid','Off');
ini_set('session.gc_maxlifetime','12000000');
ini_set('allow_url_fopen','off');

require(FILE_ROOT.'/framework/utility.php');
require(FILE_ROOT.'/framework/registry.php');
require(FILE_ROOT.'/framework/framework.php');
require(FILE_ROOT.'/framework/filesystem.php');
require(FILE_ROOT.'/framework/config.php');
require(FILE_ROOT.'/framework/log.php');
require(FILE_ROOT.'/framework/response.php');
require(FILE_ROOT.'/framework/request.php');
require(FILE_ROOT.'/framework/crypt.php');
require(FILE_ROOT.'/framework/requesthandler.php');
require(FILE_ROOT.'/framework/output.php');
require(FILE_ROOT.'/framework/route.php');
require(FILE_ROOT.'/framework/url.php');
require(FILE_ROOT.'/framework/redirect.php');
require(FILE_ROOT.'/framework/input.php');
require(FILE_ROOT.'/framework/cookies.php');
require(FILE_ROOT.'/framework/models.php');
require(FILE_ROOT.'/framework/localization.php');
require(FILE_ROOT.'/framework/document.php');
require(FILE_ROOT.'/framework/views.php');
require(FILE_ROOT.'/framework/blade.php');
require(FILE_ROOT.'/framework/database.php');
require(FILE_ROOT.'/framework/app.php');

if(client_ip() == '127.0.0.1' || client_ip() == '::1') {
	App::displayErrors(true);
}

require(FILE_ROOT.'/webapp.php');
App::run();
?>