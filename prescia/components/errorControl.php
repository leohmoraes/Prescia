<?	# -------------------------------- Prescia error control

# Level 0 (lowest level, might not even be logged)
define ("CONS_ERROR_NOTICE",0); # Hidden notice
define ("CONS_ERROR_NOTICE_SHOW",1); # Shown notice
define ("CONS_ERROR_MESSAGE",2); # Just show to user, no log
# Level 1 (errors or low level hack attempts, always logged)
define ("CONS_ERROR_WARNING",10); # Hidden Warning (a notice that should be checked by admins)
define ("CONS_ERROR_WARNING_SHOW",11); # Shown Warning
define ("CONS_ERROR_SEC",12);
define ("CONS_ERROR_SEC_SHOW",13);
define ("CONS_ERROR_NOTICESTOP",15); # Just a notice (shows to user), but should stop the script
# Level 2
define ("CONS_ERROR_ERROR",20); # Hidden error
define ("CONS_ERROR_ERROR_SHOW",21); # Shown error
define ("CONS_ERROR_FATAL",22); # Fatal error, will abort script immediatly
define ("CONS_ERROR_FATAL_NOLOG",23); # Fatal error, will abort script immediatly, but won't log
define ("CONS_ERROR_FATAL_MAIL",24); # Fatal error, will abort script immediatly but will send mail to admin first
define ("CONS_ERROR_NOTIFYMAIL",25); # Not an error but really important, will log in highest level AND send a mail to admin, but not abort the script

define ("CONS_MAX_ERRORS",100); # Number of maximum allowed errors in one single script before a hard-abort (to prevent a loop)
define ("CONS_MAX_LOGFILESIZE",100000); # Maximum size of a DAILY error log

class CErrorControl {

	var $parent = null;
	var $ERRORS = array();
	var $errorCount = 0;

	function __construct(&$parent) {
		$this->parent = &$parent;
		$this->ERRORS = array( #00xx basic load errors
								1 => CONS_ERROR_FATAL, # master loadMedatada or loadPageSettings error, check log for details
								2 => CONS_ERROR_FATAL, # Plugin not found (core::addPlugin)
								3 => CONS_ERROR_FATAL, # Error loading permission metadata cache (core::loadPermissions)
								4 => CONS_ERROR_FATAL, # A given plugin cannot run due to lack of required parameters (add at the module.php of your plugin)
								5 => CONS_ERROR_FATAL, # A plugin was loaded with a specified new name, but no relatetoModule was specified
								6 => CONS_ERROR_ERROR, # cache error on reading locale file
								7 => CONS_ERROR_ERROR, # master error on reading locale file
								8 => CONS_ERROR_NOTICE_SHOW, # addLink failed


								#01xx internal errors and warnings
								100 => CONS_ERROR_FATAL_MAIL, # domains file is corrupt (core::domainlock)
								101 => CONS_ERROR_FATAL_NOLOG, # Domain not found not using cache (core::domainlock)
								102 => CONS_ERROR_FATAL_NOLOG, # Domain not found using cache (core::domainlock)
								103 => CONS_ERROR_NOTICE, # 404 non-fast close, usually a soft 404 GENERATED by the system
								104 => CONS_ERROR_WARNING, # Database down, trying to run offline (core::dbconnect)
								105 => CONS_ERROR_FATAL_MAIL, # Database down, aborting script (core::dbconnect)
								106 => CONS_ERROR_WARNING, # _modules metadata corrupt, attempting to debug (core::loadMetadata)
								//107 => CONS_ERROR_WARNING, # automato (PageSettings) cache corrupt, attempting to debug (core::loadPageSettings)
								108 => CONS_ERROR_FATAL, # Incorrect permission check: SQL FROM differs from module (auth::forcePermissions)
								109 => CONS_ERROR_WARNING, # Last CRON timed out (core::cronCheck)
								110 => CONS_ERROR_WARNING, # Quota exceeded (core::cronCheck)
								111 => CONS_ERROR_WARNING, # Time out on Cron-D (core::cronCheck)
								112 => CONS_ERROR_WARNING, # Time out on Cron-H (core::cronCheck)
								113 => CONS_ERROR_NOTICE, # Performing DB optimization and backup (core::cronCheck)
								114 => CONS_ERROR_NOTICE , # Context/action not found 404 (core::renderPage)
								115 => CONS_ERROR_NOTICE_SHOW, # internalFoward aborted due to config.php options (headerControl::internalFoward)
								116 => CONS_ERROR_FATAL, # Pages XML corrupt (coreFull::loadPageSettings)
								//117 => CONS_ERROR_FATAL, # Automato defined in .XML not found (coreFull::loadPageSettings)
								118 => CONS_ERROR_FATAL, # Install is corrupt (missing files or something) (coreFull::loadMetadata)
								119 => CONS_ERROR_FATAL, # metadata (merger of all) XML corrupt (coreFull::loadMetadata)
								120 => CONS_ERROR_FATAL, # Two modules using the same database name! (coreFull::loadMetadata)
								121 => CONS_ERROR_FATAL, # Explicit field has no content (coreFull::loadMetadata)
								122 => CONS_ERROR_FATAL, # Database not present and failed to create (coreFull::dbconnect)
								123 => CONS_ERROR_FATAL, # Failed to connect to client database just as it was created (coreFull::dbconnect)
								124 => CONS_ERROR_ERROR_SHOW,  # Failed to save metadata cache in disk (corefull::save_model)
								125 => CONS_ERROR_SEC, # Possible hack attempt came in a field to be stored in database - expected number, came HTML tag (module::check_mandatory)
								126 => CONS_ERROR_FATAL, # An object was sent as an action on runAction (module::runAction)
								127 => CONS_ERROR_NOTICE_SHOW, # Missing mandatory fields
								128 => CONS_ERROR_NOTICE_SHOW, # Mandatory parent key missing or caused ciclic parenting
								129 => CONS_ERROR_NOTICE_SHOW, # Invalid login-like field
								130 => CONS_ERROR_NOTICE_SHOW, # Invalid e-mail field
								131 => CONS_ERROR_NOTICE_SHOW, # Invalid path/file field
								132 => CONS_ERROR_NOTICE_SHOW, # Invalid youTube/vimeo-like field
								133 => CONS_ERROR_NOTICE_SHOW, # Invalid time-like field
								134 => CONS_ERROR_NOTICE_SHOW, # Invalid date format
								135 => CONS_ERROR_WARNING_SHOW, # Invalid HTML (Word?) pasted into HTML field
								136 => CONS_ERROR_WARNING_SHOW, # Error while updating item
								137 => CONS_ERROR_NOTICE_SHOW, # Error while updating item: already exists
								138 => CONS_ERROR_WARNING_SHOW, # No SQL on insert!
								139 => CONS_ERROR_NOTICE_SHOW, # Missing parent field on create
								140 => CONS_ERROR_WARNING_SHOW, # Error while inserting item
								141 => CONS_ERROR_NOTICE_SHOW, # Error while inserting item: already exists
								142 => CONS_ERROR_WARNING_SHOW, # No SQL on update!
								143 => CONS_ERROR_WARNING_SHOW, # Unable to delete item
								144 => CONS_ERROR_NOTICESTOP, # Possible SQL injection or exploit search - aborting
								145 => CONS_ERROR_ERROR_SHOW, # FREE ERROR CODE
								146 => CONS_ERROR_WARNING_SHOW, # SQL error on SELECT
								147 => CONS_ERROR_WARNING, # Slow query
								148 => CONS_ERROR_WARNING_SHOW, # SELECT command with no key
								149 => CONS_ERROR_WARNING_SHOW, # Permission denied on DELETE
								150 => CONS_ERROR_WARNING_SHOW, # Permission denied on INCLUDE
								151 => CONS_ERROR_WARNING_SHOW, # Permission denied on UPDATE
								152 => CONS_ERROR_WARNING_SHOW, # Permission denied on SELECT
								153 => CONS_ERROR_WARNING_SHOW, # module not found on checkPermissions (bi_auth)
								154 => CONS_ERROR_WARNING_SHOW, # trying to change something owned by a higher level user (bi_auth)
								155 => CONS_ERROR_FATAL, # Checking permissions from a module while running SQL on another module! (bi_auth)
								156 => CONS_ERROR_WARNING_SHOW, # Permissin denied while checking credentials (bi_auth)
								157 => CONS_ERROR_NOTICE_SHOW, # Aborting operation due to cascading error
								158 => CONS_ERROR_FATAL, # Module not found running runContent
								159 => CONS_ERROR_WARNING_SHOW, # Unexpected output (probably error message) when running tc:fullpage
								160 => CONS_ERROR_WARNING, # Loading dinamic config BACKUP
								161 => CONS_ERROR_WARNING, # Loading status config BACKUP
								162 => CONS_ERROR_FATAL_MAIL, # Error loading dinamic or status config
								163 => CONS_ERROR_FATAL, # Error loading module metadata (core::loaded or core::loadAllModules)
								164 => CONS_ERROR_ERROR, # System tryed to save an empty dinamic/status config!
								165 => CONS_ERROR_FATAL, # Error saving dinamic/status config
								166 => CONS_ERROR_NOTICE, # 404 fastclose error
								167 => CONS_ERROR_WARNING, # Script nearing time-out (core::nearTimeLimit)
								168 => CONS_ERROR_MESSAGE, # a Plugin prevented database change based on edit_parse (modules::runAction // [script]::edit_parse)
								169 => CONS_ERROR_FATAL_MAIL, # on RunContent, tried to use fast counting and failed
								170 => CONS_ERROR_FATAL, # Script not found on onMeta, check if it's name is set,
								171 => CONS_ERROR_WARNING, # FMANAGER request arrived to index.html
								178 => CONS_ERROR_FATAL_MAIL, # too many errors/possible error loop
								179 => CONS_ERROR_WARNING, # 404 error was unable to be stored in 404.log
								180 => CONS_ERROR_FATAL, # custom.xml (merger of all) corrupt
								181 => CONS_ERROR_FATAL, # A file upload CUSTOM field does not have a location tag OR it starts with /
								182 => CONS_ERROR_FATAL, # SQL arrived in array format, except it is not an SQL array
								183 => CONS_ERROR_FATAL, # Invalid template for E-mail (file not found)
								184 => CONS_ERROR_WARNING_SHOW, # Failed to load file in frame (core::frame)
								185 => CONS_ERROR_FATAL, # Unable to process friendlyUrl (core::friendlyurl)
								186 => CONS_ERROR_FATAL, # Unable to process UDM (core::udm)
								187 => CONS_ERROR_ERROR, # send only a value to runAction instead of a valid SQL/assoc
								188 => CONS_ERROR_WARNING_SHOW, # error while performing unserialize on a serialized field
								189 => CONS_ERROR_WARNING_SHOW, # error processing a serialized array (input) into a serialized field

								# upload and validation errors
								200 => CONS_ERROR_MESSAGE, # Upload error 0
								201 => CONS_ERROR_MESSAGE, # Upload error 1
								202 => CONS_ERROR_MESSAGE, # Upload error 2
								203 => CONS_ERROR_MESSAGE, # Upload error 3
								204 => CONS_ERROR_MESSAGE, # Upload error 4
								205 => CONS_ERROR_MESSAGE, # Upload error 5 (invalid extension)
								206 => CONS_ERROR_MESSAGE, # Upload error 6 (resize error)
								207 => CONS_ERROR_MESSAGE, # Upload error 7 (file extension and contents differ)
								208 => CONS_ERROR_MESSAGE, # Upload error 8 (error creating thumbnails)
								209 => CONS_ERROR_MESSAGE, # Upload error 9 ( near timelimit - aborted)
								210 => CONS_ERROR_MESSAGE, # Upload error 10 (quota exceeded)
								211 => CONS_ERROR_MESSAGE, # Expected field not found or invalid (core::queryOk)
								212 => CONS_ERROR_MESSAGE, # Upload error: swf dimensions larger than allowed

								# 03xx Action logs
								300 => CONS_ERROR_NOTICE, # A user took an action that (sucessfully) changed the database
								301 => CONS_ERROR_NOTICE, # A user just logged in
								302 => CONS_ERROR_NOTICE, # A user just logged out
								303 => CONS_ERROR_NOTICE_SHOW, # Login attempt failed: login inactive
								304 => CONS_ERROR_NOTICE_SHOW, # Login attempt failed: login expired
								305 => CONS_ERROR_NOTICE_SHOW, # Login attempt failed: unknown login/password pair
								306 => CONS_ERROR_WARNING, # A user took an action that did not suceed

								# 04xx available

								# 05xx Prescia original plugins
								500 => CONS_ERROR_FATAL_MAIL, # Group module not found while checking guest login (bi_auth)
								501 => CONS_ERROR_FATAL_MAIL, # Unable to load guest group settings (bi_auth)
								502 => CONS_ERROR_ERROR, # Unable to reuse authentication cookie (bi_auth)
								503 => CONS_ERROR_ERROR_SHOW, # Login or password have invalid characters (bi_auth)
								504 => CONS_ERROR_ERROR_SHOW, # Unexpected error logging in on session manager or main table (bi_auth)
								505 => CONS_ERROR_ERROR_SHOW, # Unable to log inv (bi_auth)
								506 => CONS_ERROR_MESSAGE, # Folder created (bi_xmladm)
								507 => CONS_ERROR_NOTICE_SHOW, # Folder deleted (bi_xmladm)
								508 => CONS_ERROR_WARNING_SHOW, # Quota Exceeded (bi_xmladm)
								509 => CONS_ERROR_MESSAGE, # Upload notice (bi_xmladm)
								510 => CONS_ERROR_NOTICE_SHOW, # Delete ok (bi_xmladm)
								511 => CONS_ERROR_WARNING_SHOW, # Delete fail (bi_xmladm)
								512 => CONS_ERROR_WARNING, # Module not found or not specified (bi_xmladm)
								513 => CONS_ERROR_ERROR_SHOW, # ME detected keys and proceeded, but uppon reading (SQL) the items it returned nothing (or error) (addbi_xmladm)
								514 => CONS_ERROR_ERROR_SHOW, # monitor.xml detected but failed on load (bi_adm)
								515 => CONS_ERROR_WARNING, # Module or SQL not found in one items inside monitor.xml (bi_adm)
								516 => CONS_ERROR_WARNING_SHOW, # SQL error while detecting monitored itens (bi_adm)
								517 => CONS_ERROR_FATAL, # No admin.xml for administrative pane (bi_adm)
								518 => CONS_ERROR_FATAL, # Module in a UNDO field is invalid (bi_adm/bi_undo)
								519 => CONS_ERROR_FATAL, # History data in undo table is corrupt (bi_adm/bi_undo)
								520 => CONS_ERROR_WARNING_SHOW, # include/update of a CMS with same code,page,lang as other is not allowed (bi_cms)
								521 => CONS_ERROR_WARNING_SHOW, # cannot delete a CMS marked with locked (only master)
								522 => CONS_ERROR_NOTICE, # bi_dev cron reports errors
								523 => CONS_ERROR_NOTICE, # bi_dev cron reports php variables post_max_size or upload_max_filesize too low
								524 => CONS_ERROR_WARNING_SHOW, # bi_adm multiple uploads reached time limit and aborted
								525 => CONS_ERROR_SEC, # bi_stats reports too many bot hits
								526 => CONS_ERROR_FATAL, # checkPermission called with an invalid $owner variable (must be either false or a 4-lenght array)

								598 => CONS_ERROR_FATAL, # generic fatal plugin error
								599 => CONS_ERROR_ERROR, # generic non-fatal plugin error

								# 06xx errors are from captured PHP errors/exceptions
								600 => CONS_ERROR_ERROR, # PHP common notice/warning
								601 => CONS_ERROR_ERROR_SHOW, # PHP error
								602 => CONS_ERROR_ERROR_SHOW, # PHP exception
								603 => CONS_ERROR_FATAL_MAIL, # Unexpected error (raise with invalid code)
								604 => CONS_ERROR_NOTIFYMAIL, # Cron reports php error log is huge
								605 => CONS_ERROR_NOTIFYMAIL, # Cron reports too many system errors
								606 => CONS_ERROR_ERROR_SHOW, # Database error

								#7xx to 9xx can be used by sites/plugins. Do not use here, and check for conflicts if a site/plugin use them

								# 10xx Debug notification
								1000 => CONS_ERROR_NOTICE, # Debugmode: recreating metacache

					);

	}
	function raise($errCode,$parameter="",$module="",$extended="") {
		if (!CONS_ONSERVER && $errCode == 1000) return; # this will happen every single hit on development mode
		$this->errorCount++;
		if ($this->errorCount==CONS_MAX_ERRORS) $errCode = 178; // abort (gracefully)
		if ($this->errorCount>CONS_MAX_ERRORS) die("178 too many errors, error during error report found");
		#-- quickly set the fatal error flag
		if (!isset($this->ERRORS[$errCode])) {
			$parameter = $errCode;
			$errCode = 603;
		}
		if (!is_dir(CONS_PATH_LOGS)) safe_mkdir(CONS_PATH_LOGS);
		if (($this->ERRORS[$errCode] == CONS_ERROR_FATAL_MAIL || $this->ERRORS[$errCode] == CONS_ERROR_NOTIFYMAIL) && !CONS_ONSERVER) {
			if (isMail(CONS_MASTERMAIL))
				@mail(CONS_MASTERMAIL,"Fatal error at ".(isset($_SESSION['CODE'])?$_SESSION['CODE']:"Unknown domain")." err $errCode","Data: $parameter\nModule:$module",CONS_MASTERMAIL);
		}
		#-- 404 errors ...
		if ($errCode == 103 || $errCode == 114 || $errCode == 166 || $errCode == 171) {
			$fd = fopen (CONS_PATH_LOGS.$_SESSION['CODE']."/404.log", "a");
			if ($fd) {
				fwrite($fd,date("Y-m-d H:i:s")." e$errCode ".$this->parent->context_str.$this->parent->action." (".$this->parent->original_action.") referer=".(isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:"-")."\n");
				fclose($fd);
				return;
			} else {
				$errCode = 179;
				$parameter = $this->parent->context_str.$this->parent->action;
			}

		}
		#-- ok normal errors ...
		if (is_object($module))
			$module = $module->name;

		$showToUser = CONS_DEVELOPER ||
					  $this->ERRORS[$errCode] == CONS_ERROR_NOTICE_SHOW ||
					  $this->ERRORS[$errCode] == CONS_ERROR_WARNING_SHOW ||
					  $this->ERRORS[$errCode] == CONS_ERROR_ERROR_SHOW ||
					  $this->ERRORS[$errCode] == CONS_ERROR_SEC_SHOW ||
					  $this->ERRORS[$errCode] == CONS_ERROR_NOTICESTOP ||
					  $this->ERRORS[$errCode] == CONS_ERROR_MESSAGE;
		$lowLog= $this->ERRORS[$errCode] == CONS_ERROR_WARNING ||
				 $this->ERRORS[$errCode] == CONS_ERROR_WARNING_SHOW;
		$securityLog = $this->ERRORS[$errCode] == CONS_ERROR_SEC ||
				 		$this->ERRORS[$errCode] == CONS_ERROR_SEC_SHOW;
		$highLog = $this->ERRORS[$errCode] == CONS_ERROR_ERROR ||
					$this->ERRORS[$errCode] == CONS_ERROR_ERROR_SHOW ||
					$this->ERRORS[$errCode] == CONS_ERROR_FATAL ||
					$this->ERRORS[$errCode] == CONS_ERROR_NOTIFYMAIL;
		$actionLog = $errCode >= 300 && $errCode < 400;
		$stopScript = $this->ERRORS[$errCode] == CONS_ERROR_FATAL ||
						$this->ERRORS[$errCode] == CONS_ERROR_FATAL_NOLOG ||
						$this->ERRORS[$errCode] == CONS_ERROR_NOTICESTOP ||
						$this->ERRORS[$errCode] == CONS_ERROR_FATAL_MAIL;
		$storeInWarning = ($this->ERRORS[$errCode] != CONS_ERROR_MESSAGE);
		$redWarning = $this->ERRORS[$errCode] != CONS_ERROR_NOTICE_SHOW && $this->ERRORS[$errCode] != CONS_ERROR_NOTICE && !$actionLog; # These are logs that, once displayed to the users, should be in red (actual errors)

		#--
		$errstr = $this->parent->langOut('e'.$errCode)." (e$errCode) $module $parameter $extended".($redWarning?"!":"");
		$errstrfull = $errCode."|".$module."|".$parameter."|".$extended."|".implode("|",$this->parent->log);
		# Error file:
		# date|client|uri|errCode|module|parameters|extended parameters|log[|...]
		# Action file:
		# YmdHismodule|parameter|extended parameters

		$status = date("d/m/Y H:i:s")."|".(isset($_SESSION['CODE'])?$_SESSION['CODE']:'?')."|".$_SERVER['REQUEST_URI'];
		if ($showToUser) $this->parent->log[] = $errstr;
		if ($storeInWarning) $this->parent->warning[] = $errstr;
		if ($lowLog || $securityLog || $highLog) {
			if (isset($_SESSION['CODE'])) {
				if (isset($_SESSION['CODE']) && ! is_dir(CONS_PATH_LOGS.$_SESSION['CODE']."/")) safe_mkdir(CONS_PATH_LOGS.$_SESSION['CODE']."/");
				if (!is_file(CONS_PATH_LOGS.$_SESSION['CODE']."/err".date("Ymd").".log") || filesize(CONS_PATH_LOGS.$_SESSION['CODE']."/err".date("Ymd").".log") < CONS_MAX_LOGFILESIZE) {
					$fd = fopen (CONS_PATH_LOGS.$_SESSION['CODE']."/err".date("Ymd").".log", "a");
				  	if ($fd) {
					 	fwrite($fd,$status."|".$errstrfull."\n");
					 	fclose($fd);
				  	}
				}
			  	if ($highLog) {
				  	if (isset($this->parent->dimconfig['_cronD']) && $this->parent->dimconfig['_cronD'] == date("d"))
						$this->parent->dimconfig['_errcontrol'] = isset($this->parent->dimconfig['_errcontrol'])?$this->parent->dimconfig['_errcontrol']+1:1;
					else
						$this->parent->dimconfig['_errcontrol'] = 1;
					$this->parent->saveConfig(true);
			  	}
			}
			# centralized log (the framework supports multiple domains, this log is a single log for all domains)
			if ($highLog && (!is_file(CONS_PATH_LOGS."err".date("Ymd").".log") || (filesize(CONS_PATH_LOGS."err".date("Ymd").".log") < CONS_MAX_LOGFILESIZE))) {
				$fd = fopen (CONS_PATH_LOGS."err".date("Ymd").".log", "a");
			  	if ($fd) {
				 	fwrite($fd,$status."|".$errstrfull."\n");
				 	fclose($fd);
			  	}
			}
		}


		if ($actionLog) {
			if (isset($_SESSION['CODE']) && !is_dir(CONS_PATH_LOGS.$_SESSION['CODE']."/")) safe_mkdir(CONS_PATH_LOGS.$_SESSION['CODE']."/");
			$fd = fopen (CONS_PATH_LOGS.$_SESSION['CODE']."/act".date("Ymd").".log", "a");
		  	if ($fd) {
				if ($errCode >= 301 && $errCode <= 305)	{
					$parameter = "e".$errCode;
					fwrite($fd,date("YmdHis").$module."|$parameter|$extended|$extended"."\n");
				}	else {
					$parameter = $parameter==CONS_ACTION_INCLUDE?"include":($parameter==CONS_ACTION_UPDATE?"edit":($parameter==CONS_ACTION_DELETE?"delete":$parameter));
					fwrite($fd,date("YmdHis").$module."|$parameter|$extended|".($this->parent->logged()?$_SESSION[CONS_SESSION_ACCESS_USER]['login']:"GUEST")."\n");
				}

			 	fclose($fd);
		  	}
		}

		if ($stopScript) {
			$this->parent->headerControl->showHeaders('500',true);
			echo "<div style='border:1px solid #FFCCCC;padding:10px;margin:20px;'>
				<b>$parameter</b> ($errCode)
			  	<div style='border-top: 1px solid #CCCCCC;'>".nl2br($this->errorToMessage($errCode,$parameter,$module,$extended))."</div>".
			  	"<div style='border-top: 1px solid #CCCCCC;'>SystemLog:<br/><div style='font-size:10px'>".implode("<br/>",$this->parent->log)."</div></div>".
			  	($this->parent->debugmode?"<div style='border-top: 1px solid #CCCCCC;'>DBLog:<br/><div style='font-size:10px'>".implode("<br/>",$this->parent->dbo->log)."</div></div>":"").
			  	($this->parent->offlineMode?"<div style='border-top: 1px solid #CCCCCC;'>DB DOWN</div>":"").
			  "</div>Prescia";
			$this->parent->close(true);
		}
	} # raise

	function errorToMessage($errCode,$parameter,$module,$extended) {
		return (isset($this->parent)?$this->parent->langOut($errCode):$errCode)."(x$errCode)\n".
		"Flag: $parameter\nModule:$module\nExtended Flags:$extended";
	}

	function dumpUnexpectedOutput($error) {
		// logs Unexpected output (php errors, echo, print etc)
		// ONLY IF DEBUGMODE IS SET IT WILL DISPLAY ON SCREEN, otherwise only in file
		if (CONS_DEVELOPER || $this->parent->debugmode)
			$out = "<div style='border:1px solid #FFCCCC;padding:10px;margin:20px;'><b>Unexpected Output</b><div style='border-top: 1px solid #CCCCCC;'>$error</div></div>";
		else
			$out = "";
		$report = "Unexpected Output ".AFF_VERSION." (".AFF_BUILD."):\n".
		 		  "Request: ".date("h:i:s d/m/Y")." ".$_SERVER['REQUEST_URI']."\n".
				  "Output:\n".$error."\n".
				  "-----------------------------**\n";
		$fd = fopen (CONS_PATH_LOGS.$_SESSION['CODE']."/out".date("Ymd").".log", "a");
  		if ($fd) {
	 		fwrite($fd,$report);
	 		fclose($fd);
  		}
		$this->parent->warning[] = "Unexpected Output";
  		return $out;
	} # dumpUnexpectedOutput

}

