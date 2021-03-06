<?

	$core->addLink('validators.js');

	if (isset($_POST['haveinfo'])) {
		$core->template->fill($_POST);
		if (isset($_POST['ulogin'])) $core->template->assign("login",$_POST['ulogin']);
	}

	if ($core->logged()) {
		$core->template->assign("name",$_SESSION[CONS_SESSION_ACCESS_USER]['name']);
		$core->template->assign("email",$_SESSION[CONS_SESSION_ACCESS_USER]['email']);
		$core->template->assign("ipp",$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['pfim']);
		$core->template->assign("lang",$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']['lang']);
		$image = CONS_PATH_PAGES.$_SESSION['CODE'].'/files/users/t/image_'.$_SESSION[CONS_SESSION_ACCESS_USER]['id']."_2";
		if ($_SESSION[CONS_SESSION_ACCESS_USER]['image']=='n' || !locateFile($image,$ext)) {
			$core->template->assign("_imageyes");
		} else {
			$core->template->assign("_imageno");
			$core->template->assign("image",$image);
		}
		$userLang = isset($_SESSION[CONS_SESSION_ACCESS_USER]['lang'])?$_SESSION[CONS_SESSION_ACCESS_USER]['lang']:$_SESSION[CONS_SESSION_LANG];
	} else {
		$userLang = $_SESSION[CONS_SESSION_LANG];
		$core->template->assign("_imageyes");
		$core->template->fill($_POST);
		$core->tCaptcha('captcha');
		if (isset($_POST['ulogin'])) $core->template->assign("login",$_POST['ulogin']);
	}

	$output ="";
	foreach (explode(",",CONS_POSSIBLE_LANGS) as $lang) {
		$output .= "<option value='$lang'".($lang==$userLang?' selected="selected"':"").">".$core->langOut($lang)." ($lang)</option>";
	}
	$core->template->assign("langout",$output);
	$core->template->assign("r",rand(0,999)); // force images to always be refreshed