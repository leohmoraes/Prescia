<?  # -------------------------------- Custom config

	date_default_timezone_set("America/Sao_Paulo");
	ini_set("allow_url_fopen", 0); // for safety, leave 0 unless you need it

	define("CONS_USE_I18N",true); // use i18n language systems or not. If the site have only one language, better disable it for performance!
	define("CONS_DEFAULT_LANG","en"); // default language for this site (always required even if i18n disabled)
	define("CONS_POSSIBLE_LANGS","pt-br,en"); // which languages can be selected for this site, comma separated (ex.: "pt-br,en") (if CONS_USE_I18N)
		// if you add multiple languages, remember to disable $this->forceLang
	define("CONS_DEFAULT_FAVICON",true); // if TRUE will use default favicon if none found

	if (CONS_ONSERVER) { // <-- settings for your online "final" server
		define("CONS_INSTALL_ROOT","/"); // if Simpla is installed in other than the ROOT folder, fill this (must end and start with /)
		define("CONS_DB_HOST","localhost"); // leave empty if not using a database. The framework will enter dbless mode
		define("CONS_DB_BASE","");
		define("CONS_DB_USER","");
		define("CONS_DB_PASS","");
		// If this site has multiple domains AND we want each domain to foward to a different FOLDER, translate domain=>folder here:
		$this->domainTranslator = array(#'www.prescia.net' => 'aff',
										#'aff.daisuki.net.br' => 'aff',
										#'www.prescia.net' => '',
										#'www.daisuki.net.br' => '',
										); // <-- if none are found will use root

		// You should have this enabled to help you while creating/debbuging the site, then disable it for performance, or keep only on production
		//$dev = $this->addPlugin('bi_dev');

	} else { // <------------- settings for your local production machine
		define("CONS_INSTALL_ROOT","/"); // if AFF is installed in other than the ROOT folder, fill this (must end and start with /)
		define("CONS_DB_HOST","localhost"); // leave empty if not using a database. The framework will enter dbless mode
		define("CONS_DB_BASE","");
		define("CONS_DB_USER","root"); // Overridden by master, if set
		define("CONS_DB_PASS","root"); // Overridden by master, if set
		// If this site has multiple domains AND we want each domain to foward to a different FOLDER, translate domain=>folder here:
		$this->domainTranslator = array(#'localhost' => '',
										#'127.0.0.1' => '',
										); // <-- if none are found will use root

		// you should have this enabled to help you while creating the site, then disable it for performance, or keep only on production (a.k.a. right here =p)
		$dev = $this->addPlugin('bi_dev');
	}

	// which is the front page of this site (usefull when frames change it or an error wants to foward to the front page, or this site is inside a frameset)
	define ("CONS_SITE_ENTRYPOINT","index");

	// Add whatever modules this site will use
	$this->addPlugin('bi_groups'); // required for AUTH
	$this->addPlugin('bi_auth'); // user/auth system
	#$this->addPlugin('bi_seo'); // SEO system
	#$this->addPlugin('bi_undo'); // history/UNDO system
	$advadm = $this->addPlugin('bi_adm'); // Administrative pane
		#$advadm->admFolder = "adm";
		#$advadm->admRestrictionLevel = 10; // minimum level to access admin
		
	#$this->addPlugin('bi_cms'); // suggest to leave later so it's also the last to handle 404
	$stats = $this->addPlugin('bi_stats'); // statistics (must be always the last)
		#$stats->admFolder = "adm";
		#$stats->admRestrictionLevel = 10; // what we consider an admin level
		#$stats->doNotLogAdmins = false; // set true not to count people logged with admin level
		#$stats->logBOTS = false; // FOR DEBUG, NEVER TURN THIS ON, YOU WERE WARNED =p read bi_stats on this

	// Uncomment and change as needed
	#$this->languageTL = array("en" => "en", "pt" => "pt-br"); # url/PATH/[subdir/] => url/[subdir/]?lang=PATH
	#$this->forceLang = "en"; # force this language (kinda spoils i18n settings, this is used mostly for debugging)
	#$this->charset = "utf-8"; # default charset (default is utf-8 already)
