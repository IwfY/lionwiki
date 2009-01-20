<?php
	/*
	  Configuration file for LionWiki. Lower the options are, lesser the need to change it. For 90% or users should be fine to change just first one or two.
	*/

	$WIKI_TITLE = "My new wiki"; // name of the site
	$PASSWORD = "aaa"; // if left blank, no password is required to edit. Consider also $PASSWORD_MD5 below
	$USE_AUTOLANG = true; // should we try to detect language from browser?
	$LANG = "en"; // language code you want to use, used only when $USE_AUTOLANG = false
	$TEMPLATE = "template_dandelion.html";
	
	// More secure way to use password protection, just insert MD5 hash into $PASSWORD_MD5
  // if not empty, $PASSWORD is ignored and $PASSWORD_MD5 is used instead
  $PASSWORD_MD5 = "";
	$PROTECTED_READ = false; // if true, you need to fill password for reading pages too
  $HISTORY_COMPRESSION = "gzip"; // possible values: bzip2, gzip and plain
	$NO_HTML = false; // XSS protection, meaningful only when password protection is enabled

	$USE_META = true; // use and create meta data. Small overhead, but edit summary and IP info
	$USE_HISTORY = true; // If you don't want to keep history of pages, change to false

	$START_PAGE = "Main page"; // Which page should be default (start page)?
  $SYNTAX_PAGE = "Syntax reference"; // Which page contains help informations?
  
	$COOKIE_LIFE_WRITE = 365 * 24 * 86400; // lifetime of cookies when password protection applies only to writing
  $COOKIE_LIFE_READ = 4 * 3600; // lifetime of cookies when $PROTECTED_READ = true
?>
