<?php

/** @var CPrescia $core Runtime payload context injected by the framework. */
/** @var CModule $this Runtime module context injected by the framework. */

	$this->layout = 2;
	if (CONS_ONSERVER && (is_file("maint.txt") || is_file("heavymaint.html"))) echo "n";
	else echo "y";

	$core->close();