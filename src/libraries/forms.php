<?php
/*
    ###################################################################################################
    #                                                                                                 #
    #    Extended Form Library                                                                        #
	#		By: Ectrian <ectrianlol@gmail.com>                                                        #
	#                                                                                                 #
	#    You are free to use and redistribute this library as long as you give credit to the original #
    #      author.                                                                                    #
    ###################################################################################################
	
	$form = new Form($params,$fields,$dbtranslation);
	
	if($form->submitted() && $form->validate())
		$form->db_save();
		
	---- Software Requirements
		PHP >= 5.3.0 (*The new closure function is needed for some of the functions in this script.)
		MySQL (Recommended most recent version)
		
	---- Dependencies
		lib_captcha { /_data/library/lib_captcha.php, /_data/main/captcha.php }
		jquery-1.7.2.min.js
		jquerytools.min.js
		lib_ckeditor { /_data/library/lib_ckeditor.php, /_data/cdn/ext/ckeditor/*, CKEditor.js }
		core_session
		core_handler
		core_template
		core_database

	---- Parameters
	array (
		'name' => '',			(String) The name of the form. (Used to uniquely indentify it)
		'action' => '',			(String) The target of the form or "__SELF__" (default)
		'method' => '',			("POST"||"GET") The method the form should be submitted using.
		'button' => '',			(String) The text of the submit button.
		'preventdupe' => ,		(Boolean) Prevent the form from being submitted more than once.
	)
	
	---- Fields
	array (
		'FORM_FIELD_NAME' => array (
			'type' => '',			(String) The type of input. (Accepted: checkbox,placeholder,select,radio,textarea,file,text,password,bbcode,richeditor,email,url,range,captcha,number,date,birthdate,hidden,multiselect,salt,multifile,multicheck,phone,country,state,fullname,gender,color,time,code,imagecrop,webcam,multiwebcam,webcamvideo,soundclip)
			'minlength' => '',		(int) Maximum string length.
			'maxlength' => '',		(int) Minimum string length. (ONLY ENFORCED IF REQUIRED = TRUE)
			'required' => ,			(boolean) Whether this field is required. (for fields such as checkboxes, this means checked)
			'ctype' => '',			(String) Character validation to enforce. (Accepted: alnum, num, int, alpha, all, email, url, alnum_punct_spaces)
			'label' => '',			(String) The label of the field.
			'tooltip' => '',		(String) The tooltip for the field.
			'min' => '',			(int) Maximum numeric input (Enforces numeric input)
			'max' => '',			(int) Minimum numeric input (Enforces numeric input)
			'equals' => '',			(String) The FORM_FIELD_NAME of a field that this one must equal.
			'disabled' => ,			(boolean) Whether this field should accept input.
			'value' => '',			(String) The default value of the field. (intelligent: will automatically handle selects/radio/etc.)
			'show-if' => array		(array) Determines when the form should be shown.
			(
				array('FIELD_NAME','<>!=','VALUE'),
				..+
			),
			
			[type=checkbox|placeholder]
			'text' => '',			(String) The text of a checkbox or placeholder object.
			
			[type=select]
			'options' => array(		(array) The options for a select box.
				'KEY' => 'TEXT',
				'OPTGROUP' => array(
					'KEY' => 'TEXT',
					...+
				)
				...+
			),
			
			[type=radio|multiselect|multicheck]
			'options' => array(
				'KEY' => 'TEXT',
			),
			'value' => array(		(array) Keys of already selected values
				KEY, ..+
			) 
			
			[type=multifile|multicheck|multiselect|multiwebcam]
			'max_select' => '',		(int) The maximum number of items allowed to be selected/uploaded.
			'min_select' => '',		(int) The minimum number of items allowed to be selected/uploaded.
			
			[type=checkbox]
			'switch' => boolean,	(boolean) Whether to display the checkbox as an on-off switch
			'value' => boolean,		(boolean) Start checked
			
			[type=textarea]
			'rows' => '',			(int) The number of lines in the text field.
			'minwords' => '',		(int) The minimum word count of the text field.
			'maxwords' => '',		(int) The maximum word count of the text field.
			
			[type=file|multifile|webcam|multiwebcam|webcamvideo]
			'maxsize' => '',		(int) The maximum file size in bytes.
			'minsize' => '',		(int) The minimum file size in bytes.
			'types' => array(		(array) The accepted file mime types or extensions or group (images|documents).
				'MIME/EXTENSION',
				...+
			),
			
			[type=url]
			'insite' => ,			(boolean) Whether the URL should be part of the website.
			
			[type=number]
			'precision' => ,		(int) Number of decimal places to enforce precision.
			'round' => '',			(String) Algorithm to use for rounding (ceil|floor|normal)
			
			[type=password]
			'algo' => ,				(function) The encryption algorithm or alias to it.
			
			[type=imagecrop]
			'SizeX' => ,			(int) Force X Size of Crop
			'SizeY' => ,			(int) Force Y Size of Crop
			'aspect' => ,			Force aspect ratio - (false|"4:3"|"16:9"|"16:10"|true)
			
			[type=code]
			'proglang' => '',		(String) The programming language (Ex: "php")
			
			[type=time]
			'before' => '',			(time) Must be before... (Ex: "7:10 PM")
			'after' => '',			(time) Must be after.... (Ex: "7:10 AM")
			
			[type=salt]
			'length' => '',			(int) Length of salt hash
			
			[type=date]
			'before' => '',			(date) Must be before... (Ex: "12/31/1990")
			'after' => '',			(date) Must be after... (Ex: "12/31/1990")
			
			[type=birthdate]
			'requireage' => '',		(int) Age requirement
						
			[type=webcamvideo|soundclip]
			'maxlength' => ,		(int) Maxlength in seconds
			
			[type=color]
			'colorformat' => '',	(String) Format to record the color in - (rgb|rgba|hex)
		)
		...+
	)
	
	---- DB Translation
	array (
		'TABLE' => '',				(String) The name of the table.
		'KEY' => '',				(String) The name of the table's primary key (if applicable)
		'I_METHOD' => '',			(String) How records should be created. (REPLACE|INSERT)
		'U_METHOD' => '',			(String) How records should be updated. (REPLACE|UPDATE)
		'DATABASE' => $db,			(Pointer) The pointer to the instance of "Database" to use.
		'CORRELATE' => array (
			'FORM_FIELD_NAME' => 'DATABASE_FIELD_NAME',
			...+
		)		
	)
	
	---- Methods
	->f_get(FIELD)				Returns the value of FIELD or a wrapper object for files, photos, videos, soundclips, etc.
	->f_isset(FIELD)			Returns whether or not FIELD is set (boolean)
	->f_empty(FIELD)			Returns whether or not FIELD is empty (boolean)
	->f_serialize()				Returns a serial of the form's data.
	->f_import(SERIAL)			Imports a serial SERIAL of the form's data.
	->f_validate(FIELD)			Forces validation to be re-run on FIELD.
	->set_action(ACTION)		Changes the form's action to ACTION.
	->set_field(F,K,V)			Sets the properter K of field F to V.
	->set_field(F,DATA)			Sets the properties of field F to DATA.
	->submitted()				Returns whether the form has been submitted (boolean)
	->hasErrors(!FIELD)			Returns whether a FIELD or the entire form has errors (boolean)
	->addError(F,E)				Adds an error E to field F.
	->getErrors(!FIELD)			Returns the errors of a FIELD or the entire form (boolean)
	->validate()				Validates the entire form and returns whether or not it passes (boolean)
	->db_load()					Loads the database using primary key.
	->db_execute(RETURN)		Executes or (if RETURN=TRUE, returns) the compiled database query.
	->get_html()				Returns the HTML code for the entire form.
	->get_field(FIELD)			Returns the HTML code for a field.
	->get_head()				Returns the HTML code for the form header.
	->get_foot()				Returns the HTML code for the form footer.
	->get_submit()				Returns the HTML code for the form submit button.
	->get_label(FIELD)			Returns the HTML code for the label of FIELD.
	->get_errors(FIELD)			Returns the HTML code for the errors of FIELD.
	->add_field(FIWLS,DATA)		Adds a field Field with data DATA
	->add_validator(FIELD,FN)	Adds a function validator to field. FN = function(value,formpointer) { return (boolean) true or (string) "Error"; }
	->add_gvalidator(FIELD,FN)	Adds a validator that acts on the entire form. FN = function(formpointer) { return (boolean) true or (string) "Error"; }
	
	-------------------------------------------------------------

	To Do:
		add support for prevent_dupe
		Add Support for a DIVIDER (i.e. different subareas)
		Finish Error Display Support
		Finish [Range, File, Radio] Inputs + File Validation
		Multiple Captchas (seems redundant but w/e)
		Custom Styling
		jQuery Form / ajax Support
		Saveable Forms
		Add: Input[webcam]
		On Error: Highlight Field Border in Red
		webcam input: http://tutorialzine.com/2011/04/jquery-webcam-photobooth/ http://www.xarg.org/project/jquery-webcam-plugin/
		image resize library for image resizing
		getting really advanced: webcamvideo [http://www.red5-recorder.com/index.php] kaltura
		soundclips: use red5
		be sure to clear captcha to ensure no spam
		support for uploaded video files
		csrf protection tokens
		accept db connection as a parameter to use non default connection
		client-side javascript encrpytion/ server-side php decryption
		lib mcrypt support
		
		 form manipulation libraries {
			jQuery Enhanced Form Library [autocomplete/suggest [http://code.drewwilson.com/entry/autosuggest-jquery-plugin], tooltips, masking, click to clear/defaults, clear button on textfields, ]		
			finish lib_extendedform
			see also: ajaxform .js
			see also: jquery ui
			encryption w/o ssl? [http://www.dreamcss.com/2009/08/javascript-html-form-encryption-plugin.html]
			ajax checking
			tooltips
			access key highlighter http://archive.plugins.jquery.com/project/AccessKeyHighlighter
			character limit counter
			require-if (similar to show-if)
					
			enhancements:
				masking (http://digitalbush.com/projects/masked-input-plugin/)
				checkbox: clearer style (http://archive.plugins.jquery.com/project/geogoer_vchecks), on-off switches (checkbox class="switch")
				checkbox[multple]: all/none, max [show in label]
				select: multilevel (http://www.givainc.com/labs/mcdropdown_jquery_plugin.htm), icons (http://www.marghoobsuleman.com/jquery-image-dropdown)
				select[multiple]: all/none, max [show in label], http://code.google.com/p/jquery-asmselect/, http://quasipartikel.at/multiselect/
				{text,phone,country,state,fullname,email,url,number}: image buttons in fields (http://www.jankoatwarpspeed.com/post/2008/11/26/Make-image-buttons-a-part-of-input-fields.aspx)
				color: picker (http://www.eyecon.ro/colorpicker/#about)
				time: picker (http://haineault.com/media/jquery/ui-timepickr/page/)
				date: picker (jquery.ui)
				birthdate: masking, picker
				country: autocomplete by db
				state: autocomplete by db
				phone: masking
				fullname: masking
				email: masking
				url: masking
				number: masking
				birthdate: masking
				gender: images
				password: generator, strength indicator (http://bassistance.de/jquery-plugins/jquery-plugin-password-validation/), reveal, virtual keyboard (http://www.ajaxblender.com/jquery-virtual-keyboard.html)
				range: slider
				code: http://ace.ajax.org/
				richeditor: ckeditor
				bbcode: ckeditor/bbcode 
				imagecrop: cropper
		 }
*/

class FormExtended implements Iterator, ArrayAccess
{
	// Parameter Variable
	protected $params = array();
	
	// Fields Variable
	protected $fields = array();
	
	// Errors Variable
	protected $errors = array();
	
	// Database Translation Variable
	protected $db_trans = array();
	
	// Validators Variable
	protected $validators = array();
	
	// Form POST/GET Data Variable
	protected $data = array();
	
	// Months (STATIC)
	protected $months = array(1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');
	
	// Encode Multipart Types (STATIC)
	protected $multipart = array('file','multifile','imagecrop','webcam','miltiwebcam','webcamvideo','soundclip');
	
	// Database Variable (@pointer instanceof Database)
	protected $database = false;
	
	// Initialization
	public function __construct($params,$fields,$db_translate=false)
	{
		// Set Parameters
		$this->params = $params;
		
		// Set Fields
		foreach($fields as $name => $data)
		{
			// Check Field Validity
			if(is_array($data) && isset($data['type']))
			{
				// Convert Fields to Uppercase
				$this->fields[strtoupper($name)] = $data;
				
				// Set Default Value if Neccessary
				if(!isset($this->fields[strtoupper($name)]['value']))
					$this->fields[strtoupper($name)]['value'] = '';
				
				if(in_array($data['type'],$this->multipart))
					$this->params['multipart'] = true;
			}
		}
		
		// Set Database Translation (if enabled)
		if($db_translate != false && is_array($db_translate))
		{
			$this->database = Handler::getDatabase();
			$this->db_trans = $db_translate;
		}
		
		// Assign Fields if Submitted
		if($this->submitted())
		{
			// GET Submissions
			if($this->params['method'] == 'GET')
			{
				// Purge variables not in the field list
				foreach($_GET as $k => $v)
					if(isset($this->fields[$k]))
						$this->data[$k] = $v;
			}
			// POST Submissions
			else
			{
				// Purge variables not in the field list
				foreach($_POST as $k => $v)
					if(isset($this->fields[$k]))
						$this->data[$k] = $v;
			}
			
			// Disabled Checkbox Hotfix
			foreach($this->fields as $field => $data)
			{
				if($data['type'] == 'checkbox'
				&& isset($data['disabled'])
				&& $data['disabled']
				&& $data['value'])
				{
					$this->data[$field] = '1';
				}
			}
		}
		
		// Load Dependencies
		Handler::getTheme()->addJS('jquery/jquery.min.js');
		Handler::getTheme()->addJS('jquery/jquery.tools.min.js');
		Handler::getTheme()->addJS('http://cdn.'.DOMAIN.'/ext/form/form_extended.js');
		Handler::getTheme()->addCSS('http://cdn.'.DOMAIN.'/ext/form/form_extended.css');
	}
	
	// Import Form Data from a Save State
	public function f_import($serial)
	{
		$data = @unserialize($serial);
	
		if(!is_array($data))
			return false;
		
		// Enforce Valid Format	
		foreach($data as $k => $v)
			if(!isset($this->fields[$k]))
				return false;
		
		$this->data = $data;
		return true;
	}
	
	// Returns whether the form has been submitted.
	public function submitted()
	{
		return (isset($_REQUEST[md5($this->params['name']).'_submit']) && $_REQUEST[md5($this->params['name']).'_submit'] == '1');
	}
	
	// Sets the forms target.
	public function set_action($action)
	{
		$this->params['action'] = $action;
		return true;
	}
	
	public function set_field($field,$key,$value)
	{
		if(!isset($this->fields[strtoupper($field)]))
			return false;
		$this->fields[strtoupper($field)][$key] = $value;
		return;
	}
	
	public function add_field($field,$data)
	{
		if(!is_array($data) || isset($this->fields[strtoupper($field)]))
			return false;
			
		$this->fields[strtoupper($field)] = $data;
		return true;
	}
	
	public function hasErrors($field=false)
	{
		if(!$field)
			return (sizeof($this->errors) > 0);
			
		if(!isset($this->errors[strtoupper($field)]))
			return 0;
			
		return (sizeof($this->errors[strtoupper($field)]) > 0);
	}
	
	public function getErrors($field=false)
	{
		if(!$field)
			return $this->errors;
		
		if(!isset($this->errors[strtoupper($field)]))
			return array();
		
		return $this->errors[strtoupper($field)];
	}
	
	public function addError($field,$error)
	{
		if(!isset($this->fields[strtoupper($field)]))
			return false;
			
		$this->errors[strtoupper($field)][] = $error;
		return;
	}
	
	public function f_serialize()
	{
		return serialize($this->data);
	}
	
	public function f_get($field)
	{
		if(!isset($this->fields[strtoupper($field)]) || !$this->submitted())
			return false;
			
		if($this->fields[strtoupper($field)]['type'] == 'checkbox')
		{
			if($this->f_isset($field))
				return true;
			return false;
		}
			
		return $this->data[strtoupper($field)];
	}
	
	public function f_isset($field)
	{
		if(!isset($this->fields[strtoupper($field)]) || !$this->submitted())
			return false;
			
		return (isset($this->data[strtoupper($field)]));
	}
	
	public function f_empty($field)
	{
		if(!isset($this->fields[strtoupper($field)]) || !$this->submitted())
			return false;
		
		if(!isset($this->data[strtoupper($field)]))
			return false;
			
		return (empty($this->data[strtoupper($field)]));
	}
	
	public function f_validate($field)
	{
		if(!isset($this->fields[strtoupper($field)]['type']))
			return false;
			
		$function = '_val_'.$this->fields[strtoupper($field)]['type'];
		
		if(!method_exists($this,$function))
			return false;
		
		$this->_val_basic($field);
		$this->$function($field);
		if(isset($this->validators[strtoupper($field)]) && is_array($this->validators[strtoupper($field)]))
		{
			foreach($this->validators[strtoupper($field)] as $i => $function)
			{
				$eval = $function($this->f_get($field),$this);
				
				if($eval != true)
					$this->addError($field,$eval);
				unset($eval);
			}
		}
		
		return $this->hasErrors($field);
	}
	
	public function add_validator($field,$func)
	{
		$this->validators[strtoupper($field)][] = $func;
		return true;
	}
	
	public function add_gvalidator($func)
	{
		$this->validators['__GLOBAL__'][] = $func;
	}
	
	public function validate()	
	{
		// Global Validators
		if(isset($this->validators['__GLOBAL__']))
		{
			foreach($this->validators['__GLOBAL__'] as $i => $fn)
			{
				$eval = $fn($this);
				
				if($eval != true)
					$this->addError('__GLOBAL__',$eval);
				unset($eval);
			}
		}
		
		// Field Validators
		foreach($this->fields as $field => $data)
		{
			$this->f_validate($field);
		}
		
		// Return Success or Failure
		return (sizeof($this->hasErrors()) > 0);
	}
	
	public function get_field($field)
	{
		$function = '_html_'.$this->fields[strtoupper($field)]['type'];
		
		if(!method_exists($this,$function))
			return false;
		
		return $this->$function($field);
	}
	
	// Returns the Form Header HTML
	public function get_head()
	{
		$s = '
		<form class="form_style" action="';
		
		$s .= (isset($this->params['action']) && $this->params['action'] != '__SELF__' ? htmlentities($this->params['action']) : 'http'.(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 's' : '').'://'.htmlentities($_SERVER['SERVER_NAME']).''.htmlentities($_SERVER['REQUEST_URI']));
		
		$s .= '" method="'.$this->params['method'].'" name="'.htmlentities($this->params['name']).'"';
		
		if(isset($this->params['multipart']) && $this->params['multipart'])
			$s .= ' enctype="multipart/form-data"';
		
		$s .= ' novalidate="true">
			<input type="hidden" name="'.md5($this->params['name']).'_submit" value="1" />
		';		
		return $s;
	}
	
	// Returns the Form Footer HTML
	public function get_foot()
	{
		$s = '
		</form>
		';
		return $s;
	}
	
	// Returns the Submit Button HTML
	public function get_submit()
	{
		return('<input type="submit" value="'.(isset($this->params['button']) ? htmlentities($this->params['button']) : 'Submit Query').'" />');
	}
	
	// Returns a Form Field Label HTML
	public function get_label($field,$showstar=true)
	{
		return(htmlentities($this->fields[strtoupper($field)]['label']).($this->fields[strtoupper($field)]['required'] == 'true' && $showstar ? ' *' : ''));
	}
	
	// Returns the Errors for a Field in HTML
	public function get_errors($field)
	{
		$s = '';
		foreach($this->errors[strtoupper($field)] as $i => $error)
			$s .= $error.'<br />';
			
		return $s;
	}
	
	// Checks Character Types
	protected function check_ctype($type,&$string)
	{
		if($type == 'all')
			return true;
			
		elseif($type == 'alnum')
		{
			if(ctype_alnum($string))
				return true;
		}
		
		elseif($type == 'num')
		{
			if(is_numeric($string))
				return true;
		}
		
		elseif($type == 'int')
		{
			if(ctype_digit($string))
				return true;
		}
		
		elseif($type == 'alpha')
		{
			if(ctype_alpha($string))
				return true;
		}
		
		elseif($type == 'email')
		{
			if($this->isValidEmail($string))
				return true;
		}
		
		elseif($type == 'url')
		{
			if(preg_match('/^(((http|https|ftp):\/\/)?([[a-zA-Z0-9]\-\.])+(\.)([[a-zA-Z0-9]]){2,4}([[a-zA-Z0-9]\/+=%&_\.~?\-]*))*$/',$string))
				return true;
		}
		
		elseif($type == 'alnum_punct_spaces')
		{
			if(preg_match('/[^a-zA-Z0-9.-_ ]/'))
				return true;	
		}
		
		return false;
	}
	
	// Checks Email Validity
	protected function isValidEmail($n)
	{
		if(empty($n))
			return false;

		if(strpos($n, "@") === false)
			return false;

		$user = substr($n, 0, strpos($n, "@"));
		$site = substr($n, strpos($n, "@") + 1);

		if(strpos($site, ".") === false)
			return false;

		if(empty($user) || empty($site))
			return false;

		if($this->containsInvalidCharacters($user))
			return false;
			
		if($this->containsInvalidCharacters($site))
			return false;
			
		$banned = array('zippymail','dodgit','mailinator','mailinator2','sogetthis','mailin8r','spamherelots','thisisnotmyrealemail','trashmail');
			
		foreach($banned as $dom)
		{
			if(strpos($site,$dom) !== false)
				return false;
		}
		
		return true;
	}
	
	// Checks for Standard Characters
	protected function containsInvalidCharacters($string)
	{
		$string = preg_match('/[^a-zA-Z0-9.-_]/', $string);
		return (intval($string) === 0) ? false : true;
	}
	
	public function __toString()
	{
		return $this->get_html();
	}
	
	public function get_html()
	{
		$s = $this->get_head();
		$showifjs = '';
		
		$s .= '<table>';
		
			// Handle Global Errors
			if($this->hasErrors('__GLOBAL__'))
				$s .= '
				<tr>
					<td class="notice_global">'.$this->get_errors('__GLOBAL__').'</td>
				</tr>';
		
			foreach($this->fields as $field => $data)
			{
				// Handle Show-If Condition
				$show = true;
				$js = 'true && ';
				if(isset($data['show-if']) && is_array($data['show-if']))
				{
					// Determine Default State
					foreach($data['show-if'] as $cond)
					{
						if(!isset($this->fields[strtoupper($cond[0])]))
							continue;
						
						$val = ($this->submitted() ? $this->f_get($cond[0]) : $this->fields[strtoupper($cond[0])]['value']);
						$tshow = false;
						if($val == true)
							$val = '1';
						elseif($val == false)
							$val = '0';
						
						if($cond[1] == '=')
							$cond[1] = '==';
						
						if($cond[1] == '>' && $val > $cond[2])
							$tshow = true;
						elseif($cond[1] == '>=' && $val >= $cond[2])
							$tshow = true;
						elseif($cond[1] == '<' && $val < $cond[2])
							$tshow = true;
						elseif($cond[1] == '<=' && $val <= $cond[2])
							$tshow = true;
						elseif($cond[1] == '!=' && $val != $cond[2])
							$tshow = true;
						elseif($cond[1] == '==' && $val == $cond[2])
							$tshow = true;
							
						if(!$tshow)
							$show = false;
						
						if($this->fields[strtoupper($cond[0])]['type'] == 'checkbox')
						{
							// != 1 OR == 0 --> Not Checked
							if(($cond[1] == '!=' && $cond[2] == '1') || ($cond[1] == '==' && $cond[2] == '0'))
								$js .= '!$("form[name='.htmlentities($this->params['name']).'] input[name='.$cond[0].']").is(":checked") && ';
							
							// != 0 OR == 1 ==> Checked
							elseif(($cond[1] == '!=' && $cond[2] == '0') || ($cond[1] == '==' && $cond[2] == '1'))
								$js .= '$("form[name='.htmlentities($this->params['name']).'] input[name='.$cond[0].']").is(":checked") && ';
						}
						else
							$js .= '$("form[name='.htmlentities($this->params['name']).'] input[name='.$cond[0].']").val() '.$cond[1].' "'.$cond[2].'" && ';
					}
					
					// Output <SCRIPT> Function
					$showifjs .= 'if('.substr($js,0,-4).')
					{
						$("form[name='.htmlentities($this->params['name']).'] td[rel='.$field.']").fadeIn("slow");
					}
					else
					{
						$("form[name='.htmlentities($this->params['name']).'] td[rel='.$field.']").hide();
					}
					';
				}
				
				if(isset($data['label']) && !empty($data['label']))
					$s .= '
				<tr>
					<td class="label" rel="'.$field.'"'.(!$show ? ' style="display: none;"' : '').'>'.$this->get_label($field).'</td>
				</tr>	
				';
				$s .= '
				<tr>
					<td class="input" rel="'.$field.'"'.(!$show ? ' style="display: none;"' : '').'>'.$this->get_field($field).'</td>
				</tr>';
				
				if($this->hasErrors($field))
					$s .= '
				<tr>
					<td class="notice" rel="'.$field.'"'.(!$show ? ' style="display: none;"' : '').'>'.$this->get_errors($field).'</td>
				</tr>
				';
			}
			
			$s .= '
			<tr>
				<td class="submit">'.$this->get_submit().'</td>
			</tr>
			
			';
		
		$s .= '</table>';
		
		// Handle Tooltips
		$s .= '
		<script type="text/javascript">
		$("form[name='.htmlentities($this->params['name']).'] :input[title]").tooltip({
			position: "center right",
			offset: [-2, 10],
			effect: "fade",
			tipClass: "form_extended_tooltip",
			opacity: 0.7
		});
		</script>
		';
		
		// Handle Show-If Functions
		if(strlen($showifjs) > 0)
			$s .= '
			<script type="text/javascript">
			$(document).ready(function(){
				$("form[name='.htmlentities($this->params['name']).'] input").change(function(){
					'.$showifjs.'
				});
			});								
			</script>
			';
		
		$s .= $this->get_foot();
		return $s;
	}
	
	public function _html_checkbox($field)
	{
		$checked = (isset($this->fields[$field]['value']) && $this->fields[$field]['value'] ? true : false);
		if($this->submitted())
		{
			if($this->f_get($field))
				$checked = true;
			else
				$checked = false;
		}
		
		return('
		<label'.(isset($this->fields[$field]['disabled']) && $this->fields[$field]['disabled'] ? ' class="disabled"' : '').''.($this->submitted() && $this->hasErrors($field) ? ' class="notice"' : '').'>
			<input 
				name="'.htmlentities($field).'" 
				type="checkbox" 
				'.(isset($this->fields[$field]['switch']) && $this->fields[$field]['switch'] ? ' class="switch"' : '').'
				'.(isset($this->fields[$field]['required']) && $this->fields[$field]['required'] ? ' required="required"' : '').'
				'.(isset($this->fields[$field]['tooltip']) ? ' title="'.htmlentities($this->fields[$field]['tooltip']).'"' : '').'
				value="1"
				'.($checked ? 'checked="checked" ' : '').'
				'.(isset($this->fields[$field]['disabled']) && $this->fields[$field]['disabled'] ? ' disabled="disabled"' : '').'
				'.(isset($this->fields[$field]['equals']) ? ' data-equals="'.$this->fields[$field]['equals'].'"' : '').'
				 /> '.(isset($this->fields[$field]['text']) ? htmlentities($this->fields[$field]['text']) : '').'
		</label>
		');
	}
	
	public function _val_checkbox($field)
	{
		// Validate REQUIRED
		if(isset($this->fields[$field]['required']) && $this->fields[$field]['required'] && !$this->f_get($field))
			$this->errors[$field][] = 'This field is required.';
	}
	
	public function _html_text($field)
	{
		return('
		<input type="text" 
			name="'.htmlentities($field).'" 
			value="'.htmlentities(($this->submitted() ? $this->f_get($field) : $this->fields[$field]['value'])).'"
			'.(isset($this->fields[$field]['minlength']) ? ' minlength="'.$this->fields[$field]['minlength'].'"' : '').'
			'.(isset($this->fields[$field]['maxlength']) ? ' maxlength="'.$this->fields[$field]['maxlength'].'"' : '').'
			'.(isset($this->fields[$field]['ctype']) ? ' ctype="'.$this->fields[$field]['ctype'].'"' : '').'
			'.(isset($this->fields[$field]['min']) ? ' min="'.$this->fields[$field]['min'].'"' : '').'
			'.(isset($this->fields[$field]['max']) ? ' max="'.$this->fields[$field]['max'].'"' : '').'
			'.(isset($this->fields[$field]['equals']) ? ' data-equals="'.$this->fields[$field]['equals'].'"' : '').'
			'.(isset($this->fields[$field]['required']) && $this->fields[$field]['required'] ? ' required="required"' : '').'
			'.(isset($this->fields[$field]['disabled']) && $this->fields[$field]['disabled'] ? ' disabled="disabled"' : '').'
			'.(isset($this->fields[$field]['tooltip']) ? ' title="'.htmlentities($this->fields[$field]['tooltip']).'"' : '').'
			'.($this->submitted() && $this->hasErrors($field) ? ' class="notice"' : '').'
			/>
		');
	}
	
	public function _val_text($field)
	{
		// Validate REQUIRED
		if(isset($this->fields[$field]['required']) && $this->fields[$field]['required'] && strlen($this->f_get($field)) == 0)
			$this->errors[$field][] = 'This field is required.';
	}
	
	// --------- Incomplete Functions ------------------------------------------------------------------------------------------------------------------------------------------------
	
	
	
	public function _html_select($field) {}
	public function _html_password($field) {}
	public function _html_email($field) {}
	public function _html_url($field) {}
	public function _html_range($field) {}
	public function _html_captcha($field) {}
	public function _html_radio($field) {}
	public function _html_file($field) {}
	public function _html_textarea($field) {}
	public function _html_richeditor($field) {}
	public function _html_bbcode($field) {}
	public function _html_number($field) {}
	public function _html_date($field) {}
	public function _html_birthdate($field) {}
	public function _html_hidden($field) {} // prevent modification
	public function _html_placeholder($field) {}
	public function _html_boolean($field) {}
	public function _html_multiselect($field) {}
	public function _html_salt($field) {}
	public function _html_multifile($field) {}
	public function _html_multicheck($field) {}
	public function _html_phone($field) {}
	public function _html_country($field) {}
	public function _html_state($field) {}
	public function _html_fullname($field) {} // prefix first middle last suffix
	public function _html_gender($field) {}
	public function _html_color($field) {}
	public function _html_time($field) {}
	public function _html_code($field) {}
	public function _html_imagecrop($field) {}
	public function _html_webcam($field) {}
	public function _html_multiwebcam($field) {}
	public function _html_webcamvideo($field) {}
	public function _html_soundclip($field) {}
	
	// Handles Shared Validators
	public function _val_basic($field)
	{
		// Validate EQUALS
		if(isset($this->fields[$field]['equals']) && isset($this->fields[$this->fields[$field]['equals']]))
		{
			if($this->f_get($field) != $this->f_get($this->fields[$field]['equals']))
				$this->errors[$field][] = $this->get_label($field,false).' must match '.$this->get_label($this->fields[$field]['equals'],false).'.';
		}
		
		// Validate DISABLED
		if(isset($this->fields[$field]['disabled']) && $this->fields[$field]['disabled'])
		{
			if($this->f_get($field) != $this->fields[$field]['value'])
				$this->errors[$field][] = 'You can not modify a disabled field.';
		}
		
		// Validate MINLENGTH, MAXLENGTH
		
		// Validate CTYPE
		
		// Validate MIN, MAX
		
		return true;
	}
	public function _val_select($field) {}
	public function _val_password($field) {}
	public function _val_email($field) {}
	public function _val_url($field) {}
	public function _val_range($field) {}
	public function _val_captcha($field) {}
	public function _val_radio($field) {}
	public function _val_file($field) {}
	public function _val_textarea($field) {}
	public function _val_richeditor($field) {}
	public function _val_bbcode($field) {}
	public function _val_number($field) {}
	public function _val_date($field) {}
	public function _val_birthdate($field) {}
	public function _val_hidden($field) {}
	public function _val_placeholder($field) {}
	public function _val_boolean($field) {}
	public function _val_multiselect($field) {}
	public function _val_salt($field) {}
	public function _val_multifile($field) {}
	public function _val_multicheck($field) {}
	public function _val_phone($field) {}
	public function _val_country($field) {}
	public function _val_state($field) {}
	public function _val_fullname($field) {}
	public function _val_gender($field) {}
	public function _val_color($field) {}
	public function _val_time($field) {}
	public function _val_code($field) {}
	public function _val_imagecrop($field) {}
	public function _val_webcam($field) {}
	public function _val_multiwebcam($field) {}
	public function _val_webcamvideo($field) {}
	public function _val_soundclip($field) {}
	
	// Database Functions ------------------------------------------------
	public function db_load()
	{
	}
	
	public function db_save($return=false)
	{
	}
	
	protected function db_nextKey()
	{
	}
	
	// Iterator and Access Methods
	public function __len()
	{
		return sizeof($this->data);
	}
	
	public function __value()
	{
		return $this->data;
	}
	
	public function __get($key)
	{		
		if(isset($this->data[$key]))
			return $this->data[$key];
		return null;
	}
	
	public function __toString()
	{
		return 'MagicClass{'.EOL.print_r($this->data, true).EOL.'}';
	}
	
	public function __set($key, $value)
	{
		$this->data[$key] = $value;
	}
	
	public function __isset($key)
	{
		return isset($this->data[$key]);
	}
	
	public function __unset($key)
	{
		unset($this->data[$key]);
	}
	
	// -- Iterator Methods
	protected $__position = 0;
	
	public function rewind() {
		$this->__position = 0;
	}
	
	public function current() {
		return $this->rows[$this->__position];
	}
	
	public function key() {
		return $this->__position;
	}
	
	public function next() {
		++$this->__position;
	}
	
	public function valid() {
		return isset($this->rows[$this->__position]);
	}
	
	// -- ArrayAcess Methods
	public function offsetSet($offset, $value) {
		if(is_null($offset)) {
			$this->data[] = $value;
		}
		else {
			$this->data[$offset] = $value;
		}
	}
	
	public function offsetExists($offset) {
		return isset($this->data[$offset]);
	}
	
	public function offsetGet($offset) {
		return isset($this->data[$offset]) ? $this->data[$offset] : null;
	}
	
	public function offsetUnset($offset) {
		unset($this->data[$offset]);
	}
	
	
	/*/ --
	public function __invoke()
	{
		return null;
	}
	
	public function __call($name, $args)
	{
	}
	
	__destruct

	/*/
}