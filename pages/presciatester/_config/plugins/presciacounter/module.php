<?	# -------------------------------- Presciatester counter test

class mod_presciacounter extends CscriptedModule  {

	function __construct(&$parent,$moduleRelation="") {
		$this->parent = &$parent; // framework object
		$this->moduleRelation = $moduleRelation;
		$this->loadSettings();
	}

	function loadSettings() {
		$this->name = "presciacounter";
		$this->parent->onMeta[] = $this->name;
		#$this->parent->onActionCheck[] = $this->name;
		#$this->parent->onRender[] = $this->name;
		#$this->parent->on404[] = $this->name;
		#$this->parent->onShow[] = $this->name;
		#$this->parent->onEcho[] = $this->name;
		#$this->parent->onCron[] = $this->name;
		#$this->parent->registerTclass($this,'');
		#$this->customFields = array();
	}


	function onMeta() {
		# Run this function during meta-load (debugmode >>ONLY<<)
		###### -> Construct should add this module to the onMeta array
		if (isset($_REQUEST['debugmode']))
			$this->parent->dimconfig['i_am_alive'] = "presciacounter plugin is alive <3";
	}

	function onCheckActions() {
		# Run this function first thing at checkActions. Usefull to prepare data to be used or re-route the code
		# Equivalent (though earlier than normal) as action/ phps
		###### -> Construct should add this module to the onActionCheck array
	}

	function onRender() {
		# Run this function first thing at renderPage. First thing on core:renderPage.
		# Run before everything in renderPage, usefull for last-minute template-file change
		###### -> Construct should add this module to the onRender array
	}

	function on404($action, $context = "") {
		# Run this function if a 404 error is raised. Return a new filename to handle this request or FALSE
		###### -> Construct should add this module to the on404 array
		return false;
	}

	function onShow(){
		# Runs right as the script is about to end and echo. Last thing on renderPage Usefull for anything you want to do before the template is parsed
		# Equivalent to something on content/ php
		###### -> Construct should add this module to the onShow array
	}

	function onEcho(&$PAGE){
		# Happens just after the template has been parsed, runs on index.php and not inside the core object (note it received the page as a STRING now), after this, is ECHO and DIE
		###### -> Construct should add this module to the onEcho array
	}

	function onCron($isDay=false) { # cron Triggered, isDay or isHour
		###### -> Construct should add this module to the onCron array
	}

	function edit_parse($action,&$data) {
		# happens before runAction so the personalized system can fix informations on this field (only for INSERT and UPDATE)
		# return TRUE if data is ready for runAction, FALSE on error or permission denied
		return true;
	}

	function field_interface($field,$isADD,&$data) { // REMEMBER: fields must be declared in the construct, at customFields array
		# checks if this field should be displayed differently or not at all on an administrative enviroment
		# return TRUE to use default interface, FALSE not to display or the STRING that will replace the area
		return true;
	}

	function notifyEvent(&$module,$action,$data,$startedAt="",$earlyNofity =false) {
		# notify followup for this field (happens before standard notify)
		# this is called TWICE: one BEFORE (earlyNotify) and one AFTER the action. Delete parsers should always focus on the earlyNotify pass
		if (!$earlyNofity && $module->name == 'presciator' && isset($data['alpha']) && isset($data['beta'])) {
			$myobj = $this->parent->loaded($this->moduleRelation);
			$sql = "UPDATE ".$myobj->dbname." SET changesintor=changesintor+1 WHERE id_tor='".$data['alpha']."' AND id_tor_beta='".$data['beta']."'";
			if ($this->parent->dbo->simpleQuery($sql))
				$this->parent->log[] = "We got that change alright!";
			else
				$this->parent->log[] = "Something went wrong at notifyEvent at presciacounter!";
		}
	}

	function tclass($function, $params, $content,$arrayin=false) {
		# this class adds a template class. Register it using tClass array
	}

	function devCheck() {
		# implement this to raise errors during meta-developer checks if the plugins is not properly installed or configured
	}
}

