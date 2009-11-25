<?php
/* LionWiki created by Adam Zivner adam.zivner@gmail.com http://lionwiki.0o.cz
 Based on WiKiss, http://wikiss.tuxfamily.org, itself based on TigerWiki
 Licensed under GPL v2, http://www.gnu.org/licenses/gpl-2.0.html */

foreach($_REQUEST as $key => $value)
	unset($$key); // register_globals = off

// SETTINGS - default settings, can be overridden in config.php

$WIKI_TITLE = "My new wiki"; // name of the site
$PASSWORD = ""; // if left blank, no password is required to edit.

$TEMPLATE = "templates/dandelion.html";  // presentation template
$PROTECTED_READ = false; // if true, you need to fill password for reading pages too
$NO_HTML = false; // XSS protection, meaningful only when password protection is enabled
$USE_HISTORY = true; // If you don't want to keep history of pages, change to false

$START_PAGE = "Main page"; // Which page should be default (start page)?
$SYNTAX_PAGE = "http://lionwiki.0o.cz/?page=Syntax+reference";

$DATE_FORMAT = "Y/m/d H:i";
$LOCAL_HOUR = "0";

@ini_set("default_charset", "UTF-8");
header("Content-type: text/html; charset=UTF-8");
umask(0);
@error_reporting(E_ERROR | E_WARNING | E_PARSE);
set_magic_quotes_runtime(0);

if(get_magic_quotes_gpc()) // magic_quotes_gpc can't be turned off
	for($i = 0, $_SG = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST), $c = count($_SG); $i < $c; ++$i)
		$_SG[$i] = array_map("stripslashes", $_SG[$i]);

$self = $_SERVER['PHP_SELF'];
$REAL_PATH = realpath(dirname(__FILE__)) . "/";
$VAR_DIR = "var/";
$PAGES_DIR = $VAR_DIR."pages/";
$HISTORY_DIR = $VAR_DIR."history/";
$PLUGINS_DIR = "plugins/";
$PLUGINS_DATA_DIR = $VAR_DIR."plugins/";
$LANG_DIR = "lang/";

@include("config.php"); // config file is not required, see settings above

$WIKI_VERSION = "LionWiki 3.2.0";

// some strings may not be translated, in that case, we'll use english translation, which should be always complete
$T_HOME = "Main page";
$T_SYNTAX = "Syntax";
$T_DONE = "Save changes";
$T_DISCARD_CHANGES = "Discard changes";
$T_PREVIEW = "Preview";
$T_SEARCH = "Search";
$T_SEARCH_RESULTS = "Search results";
$T_LIST_OF_ALL_PAGES = "List of all pages";
$T_RECENT_CHANGES = "Recent changes";
$T_LAST_CHANGED = "Last changed";
$T_HISTORY = "History";
$T_RESTORE = "Restore";
$T_REV_DIFF = "<b>Difference between revisions from {REVISION1} and {REVISION2}.</b>";
$T_REVISION = "'''This revision is from {TIME}. You can {RESTORE} it.'''\n\n";
$T_PASSWORD = "Password";
$T_EDIT = "Edit";
$T_EDIT_SUMMARY = "Summary of changes";
$T_EDIT_CONFLICT = "Edit conflict: somebody saved this page after you started editing. It is strongly encouraged to see last {DIFF} before saving it. After reviewing and possibly merging changes, you can save page by clicking on save button.";
$T_SHOW_SOURCE = "Show source";
$T_SHOW_PAGE = "Show page";
$T_ERASE_COOKIE = "Erase cookies";
$T_MOVE_TEXT = "New name";
$T_DIFF = "diff";
$T_CREATE_PAGE = "Create page";
$T_PROTECTED_READ = "You need to enter password to view content of site: ";
$TE_WRONG_PASSWORD = "Password is incorrect.";

if(!empty($_GET["lang"])) {
	$LANG = sanitizeFilename($_GET["lang"]);
	setcookie('LW_LANG', $LANG, time() + 365 * 86400);
}
else if($_COOKIE["LW_LANG"])
	$LANG = sanitizeFilename($_COOKIE["LW_LANG"]);
else
	list($LANG) = explode(",", $_SERVER['HTTP_ACCEPT_LANGUAGE']);

if(@file_exists($LANG_DIR . $LANG . ".php"))
	@include $LANG_DIR . $LANG . ".php";
else if(@file_exists($LANG_DIR . substr($LANG, 0, 2) . ".php"))
	@include $LANG_DIR . substr($LANG, 0, 2) . ".php";
else
	$LANG = "en";

if(!file_exists($VAR_DIR) && !mkdir(rtrim($VAR_DIR, "/")))
	die("Can't create directory $VAR_DIR. Please create $VAR_DIR with 0777 rights.");

foreach(array($PAGES_DIR, $HISTORY_DIR, $PLUGINS_DATA_DIR) as $DIR)
	if(!file_exists($DIR)) {
		mkdir(rtrim($DIR, "/"), 0777);
		$f = fopen($DIR . ".htaccess", "w"); fwrite($f, "deny from all"); fclose($f);
	}

if($_GET["erasecookie"]) // remove cookie without reloading
	foreach($_COOKIE as $key => $value)
		if(substr($key, 0, 3) == "LW_") {
			setcookie($key);
			unset($_COOKIE[$key]);
		}

$plugins = array();

for($dir = @opendir($PLUGINS_DIR); $dir && $file = readdir($dir);)
	if(preg_match("/^.*wkp_(.+)\.php$/", $file, $matches) > 0) {
		require $PLUGINS_DIR . $file;
		$plugins[$matches[1]] = new $matches[1]();

		if(isset($$matches[1]))
			foreach($$matches[1] as $name => $value)
				$plugins[$matches[1]]->$name = $value;
	}

plugin("pluginsLoaded");

$req_conv = array("action", "query", "sc", "content", "page", "moveto", "restore", "f1", "f2", "error", "time", "esum", "preview", "last_changed", "gtime", "showsource", "par");

foreach($req_conv as $req) // export variables to main namespace
	$$req = $_REQUEST[$req];

$TITLE = $page = sanitizeFilename($page); $moveto = sanitizeFilename($moveto); $gtime = sanitizeFilename($gtime);
$f1 = sanitizeFilename($f1); $f2 = sanitizeFilename($f2);

plugin("actionBegin");

// does user need password to read content of site. If yes, ask for it.
if($PROTECTED_READ && !authentified()) {
	$CON = "<form action=\"$self\" method=\"post\"><p>$T_PROTECTED_READ <input id=\"passwordInput\" type=\"password\" name=\"sc\"/> <input class=\"submit\" type=\"submit\"/></p></form>";
	$action = "view-html";
}
else if($page || empty($action)) { // setting $PAGE_TITLE
	if(!$page)
		die(header("Location:$self?page=" . u($START_PAGE)));
	else if($action == "" && file_exists("$PAGES_DIR$page.$LANG.txt")) // language variant
		die(header("Location:$self?page=" . u("$page.$LANG")));
	else if(!file_exists("$PAGES_DIR$page.txt") && $action == "")
		$action = "edit"; // create page if it doesn't exist

	if($preview)
		$TITLE = "$T_PREVIEW: $page";
}
else if($action == "search")
	$TITLE = empty($query) ? $T_LIST_OF_ALL_PAGES : "$T_SEARCH_RESULTS $query";
else if($action == "recent")
	$TITLE = $T_RECENT_CHANGES;

if($action == "save" && !$preview && authentified()) { // do we have page to save?
	if(trim($content) == "" && !$par)
		@unlink($PAGES_DIR . $page . ".txt");
	elseif($last_changed < @filemtime($PAGES_DIR . $page . ".txt")) {
		$action = "edit";
		$error = str_replace("{DIFF}", "<a href=\"$self?page=".u($page)."&amp;action=diff\">$T_DIFF</a>", $T_EDIT_CONFLICT);
		$CON = $content;
	}
	else if(!plugin("writingPage")) { // are plugins OK with page? (e.g. checking for spam)
		if($par) {
			$c = @file_get_contents($PAGES_DIR . $page . ".txt");
			$content = str_replace(getParagraph($c, $par), $content, $c);
		}

		if(!$file = @fopen($PAGES_DIR . $page . ".txt", "w"))
			die("Could not write page $PAGES_DIR$page.txt!");

		fwrite($file, $content, strlen($content));
		fclose($file);

		if($USE_HISTORY) { // let's archive previous revision
			$complete_dir = $HISTORY_DIR . $page;

			if(!is_dir($complete_dir))
				mkdir($complete_dir);

			$rightnow = date("Ymd-Hi-s", time() + $LOCAL_HOUR * 3600);

			if(!$bak = @fopen($complete_dir . "/" . $rightnow . ".bak", "w"))
				die("Could not write backup $complete_dir of page!");

			fwrite($bak, $content, strlen($content));
			fclose($bak);

			$es = fopen($complete_dir . "/meta.dat", "ab");

			fwrite($es, "!" . $rightnow .
				str_pad($_SERVER['REMOTE_ADDR'], 16, " ", STR_PAD_LEFT) .
				str_pad(filesize($PAGES_DIR . $page . ".txt"), 11, " ", STR_PAD_LEFT) . " " .
				str_pad(substr($esum, 0, 128), 128 + 2)) . "\n"; // Strings are in UTF-8, it's dangerous to just cut off piece of string, therefore +2

			fclose($es);
		}

		plugin("pageWritten", $file);

		if($moveto != $page && strlen($moveto))
			if(file_exists($PAGES_DIR . $moveto . ".txt"))
				die("Error: target filename already exists. Page was not moved.");
			else if(!rename($PAGES_DIR . $page . ".txt", $PAGES_DIR . $moveto . ".txt"))
				die("Unknown error! Page was not moved.");
			else if(!rename($HISTORY_DIR . $page, $HISTORY_DIR . $moveto)) {
				rename($PAGES_DIR . $moveto . ".txt", $PAGES_DIR . $page . ".txt"); // revert previous change
				die("Unknown error2! Page was not moved.");
			} else
				$page = $moveto;
		
		die(header("Location:$self?page=" . u($page) . ($par ? "&par=$par" : "") . ($_REQUEST["ajax"] ? "&ajax=1" : "")));
	} else { // there's some problem with page, give user a chance to fix it (do not throw away submitted content)
		$CON = $content;
		$action = "edit";
	}
} else if($action == "save" && !$preview) { // wrong password, give user another chance (do not throw away submitted content)
	$error = $TE_WRONG_PASSWORD;
	$CON = $content;
	$action = "edit";
}

if(@file_exists($PAGES_DIR . $page . ".txt")) {
	$last_changed_ts = @filemtime($PAGES_DIR . $page . ".txt");
	$LAST_CHANGED = date($DATE_FORMAT, $last_changed_ts + $LOCAL_HOUR * 3600);

	if(!$CON) {
		$CON = @file_get_contents($PAGES_DIR . $page . ".txt");

		if($par)
			$CON = getParagraph($CON, $par);

		if(substr($CON, 0, 10) == "{redirect:" && $action == "")
			die(header("Location:$self?page=".u(substr($CON, 10, strpos($CON, "}") - 10))));
	}
}

// Restoring old version of page
if($gtime && ($restore || $action == "rev")) {
	$CON = file_get_contents($HISTORY_DIR.$page."/".$gtime);

	if($action == "rev") {
		$rev_restore = "[$T_RESTORE|./$self?page=".u($page)."&amp;action=edit&amp;gtime=$gtime&amp;restore=1]";

		$CON = str_replace(array("{TIME}", "{RESTORE}"), array(revTime($gtime), $rev_restore), $T_REVISION) . $CON;
	}
}

plugin("pageLoaded");

if($action)
	$HEAD .= '<meta name="robots" content="noindex, nofollow"/>';

if($action == "edit" || $preview) {
	if(!authentified() && !$showsource) { // if not logged on, require password
		$FORM_PASSWORD = $T_PASSWORD;
		$FORM_PASSWORD_INPUT = '<input id="passwordInput" type="password" name="sc"/>';
	}

	if(!$showsource && !$par) {
		$RENAME_TEXT = $T_MOVE_TEXT;
		$RENAME_INPUT = '<input id="renameInput" type="text" name="moveto" value="'.h($page).'"/>';
	}

	$CON_FORM_BEGIN = "<form action=\"$self\" id=\"contentForm\" method=\"post\"><input type=\"hidden\" name=\"action\" value=\"save\"/><input type=\"hidden\" name=\"last_changed\" value=\"$last_changed_ts\"/><input type=\"hidden\" name=\"showsource\" value=\"$showsource\"/><input type=\"hidden\" name=\"par\" value=\"$par\"/><input type=\"hidden\" name=\"page\" value=\"".h($page)."\"/>";
	$CON_FORM_END = "</form>";
	$CON_TEXTAREA = '<textarea id="contentTextarea" class="contentTextarea" name="content" cols="83" rows="30">'.h($CON).'</textarea>';

	if(!$showsource) {
		$CON_SUBMIT = "<input id=\"contentSubmit\" class=\"submit\" type=\"submit\" value=\"$T_DONE\"/>";

		$EDIT_SUMMARY_TEXT = $T_EDIT_SUMMARY;
		$EDIT_SUMMARY = '<input type="text" name="esum" id="esum" value="'.h($esum).'"/>';
	}

	$CON_PREVIEW = "<input id=\"contentPreview\" class=\"submit\" type=\"submit\" name=\"preview\" value=\"$T_PREVIEW\"/>";

	if($preview) {
		$action = "";
		$CON = $content;
	}
} elseif($action == "history") { // show whole history of page
	$complete_dir = $HISTORY_DIR . $page . "/";

	if($opening_dir = @opendir($complete_dir)) {
		while($filename = @readdir($opening_dir))
			if(substr($filename, -4) == ".bak")
				$files[] = $filename;

		rsort($files);

		$CON = '<form action="'.$self.'" method="get"><input type="hidden" name="action" value="diff"/><input type="hidden" name="page" value="'.h($page).'"/>';

		$meta = @fopen($complete_dir . "meta.dat", "rb");

		for($i = 0, $c = count($files); $i < $c; $i++) {
			$m = meta_getline($meta, $i + 1);

			if($m && !strcmp(basename($files[$i], ".bak"), $m[0])) {
				$ip = $m[1];
				$size = " - ($m[2] B)";
				$esum = h($m[3]);

				$i++;
			} else
				$ip = $size = $esum = "";

			$CON .= "<input type=\"radio\" name=\"f1\" value=\"".h($files[$i])."\"/><input type=\"radio\" name=\"f2\" value=\"".h($files[$i])."\"/>";
			$CON .= "<a href=\"$self?page=".u($page)."&amp;action=rev&amp;gtime=".$files[$i]."\" rel=\"nofollow\">".revTime($files[$i])."</a> $size $ip <i>$esum</i><br />";
		}

		$CON .= "<input id=\"diffButton\" type=\"submit\" class=\"submit\" value=\"$T_DIFF\"/></form>";
	} else
		$CON = $NO_HISTORY;
} elseif($action == "diff") {
	if(empty($f1) && $opening_dir = @opendir($HISTORY_DIR . $page . "/")) { // diff is made on two last revisions
		while($filename = @readdir($opening_dir))
			if(preg_match('/\.bak$/', $filename))
				$files[] = $filename;

		rsort($files);

		die(header("Location:$self?action=diff&page=".u($page)."&f1=$files[0]&f2=$files[1]"));
	}

	$r1 = "<a href=\"$self?page=".u($page)."&action=rev&gtime=$f1\" rel=\"nofollow\">".revTime($f1)."</a>";
	$r2 = "<a href=\"$self?page=".u($page)."&action=rev&gtime=$f2\" rel=\"nofollow\">".revTime($f2)."</a>";

	$CON = str_replace(array("{REVISION1}", "{REVISION2}"), array($r1, $r2), $T_REV_DIFF);

	$CON .= diff($f1, $f2);
} elseif($action == "search") {
	// offer to create page if it doesn't exist
	if($query && !file_exists($PAGES_DIR . $query . ".txt"))
		$CON = "<p><i><a href=\"$self?action=edit&amp;page=".u($query)."\" rel=\"nofollow\">$T_CREATE_PAGE ".h($query)."</a>.</i></p><br />";

	for($dir = opendir($PAGES_DIR); $file = readdir($dir);)
		if(substr($file, -4) == ".txt" && (@$con = file_get_contents($PAGES_DIR . $file)))
			if(empty($query) || stristr($con, $query) !== false || stristr($file, $query) !== false)
				$files[] = substr($file, 0, strlen($file) - 4);

	sort($files);

	foreach($files as $file) {
		if(is_writable($PAGES_DIR . $file . ".txt")) {
			$link_text = $T_EDIT;
			$s_source = "";
		} else {
			$link_text = $T_SHOW_SOURCE;
			$s_source = "&amp;showsource=1";
		}

		$CON .= "<a href=\"$self?page=".u($file).'" rel="nofollow">'.h($file)."</a> (<a href=\"$self?page=".u($file)."&amp;action=edit$s_source\">$link_text</a>)<br />";
	}

	$TITLE .= " (".count($files).")";
} elseif($action == "recent") { // recent changes
	for($dir = opendir($PAGES_DIR), $filetime = array(); $file = readdir($dir);)
		if(substr($file, -4) == ".txt" )
			$filetime[basename($file, ".txt")] = filemtime($PAGES_DIR . $file);

	arsort($filetime);

	foreach(array_slice($filetime, 0, 100) as $filename => $timestamp) { // just first 100 changed files
		if($meta = @fopen($HISTORY_DIR . basename($filename, ".txt") . "/meta.dat", "r")) {
			$m = meta_getline($meta, 1);
			fclose($meta);

			$ip = $m[1];
			$size = "$m[2] B";
			$esum = strlen($m[3]) ? " - " . h($m[3]) : "";
		} else
			$ip = $size = $esum = "";

		$recent .= "<tr><td class=\"rc-diff\"><a href=\"$self?page=".u($filename)."&amp;action=diff\">$T_DIFF</a></td><td class=\"rc-date\" nowrap>".date($DATE_FORMAT, $timestamp + $LOCAL_HOUR * 3600)."</td><td class=\"rc-ip\">$ip</td><td class=\"rc-page\"><a href=\"$self?page=".u($filename)."\">".h($filename)."</a> <span class=\"rc-size\">($size)</span><i class=\"rc-esum\">$esum</i></td></tr>";
	}

	$CON .= "<table>$recent</table>";
} else if(!plugin("action", $action) && $action != "view-html")
	$action = "";

if($action == "") { // substituting $CON to be viewed as HTML
	$CON = "\n$CON\n";

	// Subpages
	while(preg_match("/([^\^]){include:([^}]+)}/Um", $CON, $match)) {
		if(!strcmp($match[2], $page)) // limited recursion protection
			$CON = str_replace($match[0], "'''Warning: subpage recursion!'''", $CON);
		elseif(file_exists($PAGES_DIR . $match[2] . ".txt")) {
			$tpl = file_get_contents($PAGES_DIR . $match[2] . ".txt");

			$CON = str_replace($match[0], $match[1] . $tpl, $CON);
		} else
			$CON = str_replace($match[0], "'''Warning: subpage $match[2] was not found!'''", $CON);
	}

	plugin("subPagesLoaded");

	// save content not intended for substitutions ({html} tag)
	if($NO_HTML == false) { // XSS protection
		$n_htmlcodes = preg_match_all("/[^\^](\{html\}(.+)\{\/html\})/Ums", $CON, $htmlcodes, PREG_PATTERN_ORDER);

		foreach($htmlcodes[1] as $hcode)
			$CON = str_replace($hcode, "{HTML}", $CON);
	}

	$CON = preg_replace("/[^\^]<!--.*-->/U", "", $CON); // internal comments
	$CON = preg_replace("/\^(.)/e", "'&#'.ord('$1').';'", $CON);
	$CON = str_replace(array("<", "&"), array("&lt;", "&amp;"), $CON);
	$CON = preg_replace("/&amp;([a-z]+;|\#[0-9]+;)/U", "&$1", $CON); // keep HTML entities
	$CON = preg_replace("/(\r\n|\r)/", "\n", $CON); // unifying newlines to Unix ones

	// {{CODE}}
	$nbcode = preg_match_all("/{{(.+)}}/Ums", $CON, $matches_code, PREG_PATTERN_ORDER);
	$CON = preg_replace("/{{(.+)}}/Ums", "<pre><code>{{CODE}}</code></pre>", $CON);

	preg_match_all("/\{([\.#][^\s\"\}]*)(\s([^\}\"]*))?\}/m", $CON, $spans, PREG_SET_ORDER);

	foreach($spans as $m) {
		$class = $id = "";

		$parts = preg_split("/([\.#])/", $m[1], -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

		for($i = 0, $c = count($parts); $c > 1 && $i < $c; $i += 2)
			if($parts[$i] == ".")
				$class .= $parts[$i + 1] . " ";
			else
				$id = $parts[$i + 1];

		$CON = str_replace($m[0], "<span".($id ? " id=\"$id\"" : "").($class ? " class=\"$class\"" : "").($m[3] ? " style=\"$m[3]\"" : "").">", $CON);
	}

	$CON = str_replace("{/}", "</span>", $CON);

	plugin("formatBegin");

	// substituting special characters
	$CON = strtr($CON, array("&lt;-->" => "&harr;", "-->" => "&rarr;", "&lt;--" => "&larr;", "(c)" => "&copy;", "(r)" => "&reg;"));
	$CON = preg_replace("/\{small\}(.*)\{\/small\}/U", "<small>$1</small>", $CON); // small
	$CON = preg_replace("/\{su([bp])\}(.*)\{\/su([bp])\}/U", "<su$1>$2</su$3>", $CON); // sup and sub

	$CON = preg_replace("/^([^!\*#\n][^\n]+)$/Um", "<p>$1</p>", $CON); // paragraphs

	if(preg_match('/\{title:([^}\n]*)\}/U', $CON, $m)) {
		$TITLE = html_entity_decode($m[1]);
		$CON = str_replace($m[0], "", $CON);
	}

	$rg_url = "[0-9a-zA-Z\.\#/~\-_%=\?\&,\+\:@;!\(\)\*\$' ]*";

	preg_match_all("#\[((https?://)?$rg_url\.(jpeg|jpg|gif|png))(\|[^\]]+)?\]#", $CON, $imgs, PREG_SET_ORDER);

	foreach($imgs as $img) {
		preg_match_all("/\|([^\]\|=]+)(=([^\]\|\"]+))?(?=[\]\|])/", $img[0], $options, PREG_SET_ORDER);

		$link = $i_attr = $a_attr = $center = $tag = "";

		foreach($options as $o)
			if($o[1] == "center") $center = true;
			else if($o[1] == "right" || $o[1] == "left") $i_attr .= " style=\"float:$o[1]\"";
			else if($o[1] == "link") $link = substr($o[3], 0, 4) == "http" ? $o[3] : "$self?page=" . u($o[3]);
			else if($o[1] == "alt") $i_attr .= " alt=\"$o[3]\"";
			else if($o[1] == "title") $a_attr .= " title=\"$o[3]\"";

		$tag = "<img src=\"$img[1]\" alt=\"$alt\"$i_attr/>";

		if($link)   $tag = "<a href=\"$link\"$a_attr>$tag</a>";
		if($center) $tag = "<div style=\"text-align:center\">$tag</div>";

		$CON = str_replace($img[0], $tag, $CON);
	}

	$CON = preg_replace('#([0-9a-zA-Z\./~\-_]+@[0-9a-z/~\-_]+\.[0-9a-z\./~\-_]+)#i', '<a href="mailto:$0">$0</a>', $CON); // mail recognition

	// LINKS
	$CON = preg_replace("#\[([^\]]+)\|(\./($rg_url)|(https?://$rg_url))\]#U", '<a href="$2" class="external">$1</a>', $CON);
	$CON = preg_replace("#(?<!\")(https?://$rg_url)#i", '<a href="$0" class="external">$1</a>', $CON);

	preg_match_all("/\[([^|\]]+\|)?([^\]#]+)(#[^\]]+)?\]/", $CON, $matches, PREG_SET_ORDER); // matching Wiki links

	foreach($matches as $match) {
		$match[1] = empty($match[1]) ? $match[2] : rtrim($match[1], "|"); // is page label same as its name?

		if($match[3]) // link to the heading
			$match[3] = "#" . preg_replace("/[^\da-z]/i", "_", u(substr($match[3], 1, strlen($match[3]) - 1)));

		$attr = file_exists("$PAGES_DIR$match[2].txt") ? $match[3] : '&amp;action=edit" class="pending" rel="nofollow';

		$CON = str_replace($match[0], '<a href="'.$self.'?page='.u($match[2]).$attr.'">'.$match[1].'</a>', $CON);
	}

	for($i = 10; $i >= 1; $i--) { // LIST, ordered, unordered
		$CON = preg_replace('/^'.str_repeat('\*', $i).'(.*)(\n)/Um', str_repeat("<ul>", $i)."<li>$1</li>".str_repeat("</ul>", $i)."$2", $CON);
		$CON = preg_replace('/^'.str_repeat('\#', $i).'(.*)(\n)/Um', str_repeat("<ol>", $i)."<li>$1</li>".str_repeat("</ol>", $i)."$2", $CON);
		$CON = preg_replace('#(</ol>\n?<ol>|</ul>\n?<ul>)#', "", $CON);
	}

	// Following three lines fix only XHTML validity of lists
	$CON = preg_replace('#</li><([uo])l>#', "<$1l>", $CON);
	$CON = preg_replace('#</([uo])l><li>#', "</$1l></li><li>", $CON);
	$CON = preg_replace('#<(/?)([uo])l></?[uo]l>#', "<$1$2l><$1li><$1$2l>", $CON);

	function remove_a($link) { // remove anchors from a text
		preg_match_all("#<a.+>([^<>]+)</a>#", $link, $txt);
		return trim(join("", $txt[1]));
	}

	$heading_id = $par ? $par : 1;
	$headings = $head_stack = array();

	function addHeadings($matches) { // replace headings
		global $headings, $heading_id, $head_stack, $T_EDIT, $page, $PAGES_DIR;

		$headings[] = $h = array(strlen($matches[1]) + 1, preg_replace("/[^\da-z]/i", "_", remove_a($matches[2])), $matches[2]);

		for($ret = ""; !empty($head_stack) && $head_stack[count($head_stack) - 1] >= $h[0]; array_pop($head_stack))
			$ret .= "</div>";

		$head_stack[] = $h[0];

		$ret .= "<div class=\"par-div\" id=\"par-$heading_id\"><h$h[0]><a class=\"section-edit\" name=\"$h[1]\">$h[2]</a>";

		if(is_writable($PAGES_DIR . $page . ".txt"))
			$ret .=  "<span class=\"par-edit\">(<a href=\"$self?action=edit&amp;page=".u($page)."&amp;par=$heading_id\">$T_EDIT</a>)</span>";

		$heading_id++;

		return $ret . "</h$h[0]>";
	}

	$CON = preg_replace_callback('/^(!+?)(.*)$/Um', "addHeadings", $CON);
	$CON = preg_replace('/(-----*)/', '<hr />', $CON); // horizontal line
	$CON = preg_replace("/<\/([uo])l>\n\n/U", "</$1l>", $CON);
	$CON = preg_replace('#(</h[23456]>)<br />#', "$1", $CON);
	$CON = preg_replace("/'--(.*)--'/Um", '<del>$1</del>', $CON); // strikethrough
	$CON = str_replace("--", "&mdash;", $CON); // --
	$CON = preg_replace("/'__(.*)__'/Um", '<u>$1</u>', $CON); // underlining
	$CON = preg_replace("/'''(.*)'''/Um", '<strong>$1</strong>', $CON); // bold
	$CON = preg_replace("/''(.*)''/Um", '<em>$1</em>', $CON); // italic
	$CON = str_replace("{br}", '<br style="clear:both"/>', $CON); // new line

	foreach($headings as $h)
		$TOC .= str_repeat("<ul>", $h[0] - 2).'<li><a href="'.$self.'?page='.u($page).'#'.u($h[1]).'">'.remove_a($h[2]).'</a></li>'.str_repeat("</ul>", $h[0] - 2);

	for($i = 0; $i < 5; $i++) // five possible headings
		$TOC = preg_replace('/<\/ul>\n*<ul>/', '', $TOC);

	$TOC = "<ul id=\"toc\">$TOC</ul>";

	$TOC = str_replace('</li><ul>', '<ul>', $TOC);
	$TOC = str_replace('</ul><li>', '</ul></li><li>', $TOC);
	$TOC = preg_replace('/<(\/?)ul><\/?ul>/', '<$1ul><$1li><$1ul>', $TOC);

	if($nbcode > 0) // return content of {{CODE}}
		$CON = preg_replace(array_fill(0, $nbcode, '/{{CODE}}/'), $matches_code[1], $CON, 1);

	if($NO_HTML == false && $n_htmlcodes > 0) // {html} tag
		$CON = preg_replace(array_fill(0, $n_htmlcodes, '/{HTML}/'), $htmlcodes[2], $CON, 1);

	while(array_pop($head_stack))
		$CON .= "</div>";

	plugin("formatEnd");
}

plugin("formatFinished");

// Loading template. If does not exist, use built-in default
$html = file_exists($TEMPLATE) ? file_get_contents(sanitizeFilename($TEMPLATE)) : fallback_template();

// including pages in pure HTML
while(preg_match("/{include:([^}]+)}/U", $html, $match)) {
	$inc = @file_get_contents($PAGES_DIR . $match[1] . ".txt");
	$inc = str_replace(array("{html}", "{/html}"), array("", ""), $inc);
	$html = str_replace($match[0], $inc, $html);
}

plugin("template"); // plugin specific template substitutions

$html = preg_replace("/\{([^}]* )?plugin:.+( [^}]*)?\}/U", "", $html); // getting rid of absent plugin tags

if($page || empty($action))
	$EDIT = "<a rel=\"nofollow\" href=\"$self?page=".u($page)."&amp;action=edit".(is_writable("$PAGES_DIR$page.txt") ? "\">$T_EDIT</a>" : "&showsource=1\">$T_SHOW_SOURCE</a>");

$tpl_subs = array(
	"HEAD" => $HEAD,
	"SEARCH_FORM" => '<form action="'.$self.'" method="get" id="searchForm"><span><input type="hidden" name="action" value="search"/><input type="submit" style="display:none;"/>',
	"\/SEARCH_FORM" => "</span></form>",
	"SEARCH_INPUT" => '<input type="text" id="searchInput" name="query" value="'.h($query).'"/>',
	"SEARCH_SUBMIT" => "<input class=\"submit\" type=\"submit\" value=\"$T_SEARCH\"/>",
	"HOME" => "<a href=\"$self?page=".u($START_PAGE)."\">$T_HOME</a>",
	"RECENT_CHANGES" => "<a href=\"$self?action=recent\">$T_RECENT_CHANGES</a>",
	"ERROR" => $error,
	"HISTORY" => !empty($page) ? "<a href=\"$self?page=".u($page)."&amp;action=history\" rel=\"nofollow\">$T_HISTORY</a>" : "",
	"PAGE_TITLE" => h($page == $START_PAGE ? $WIKI_TITLE : $TITLE),
	"PAGE_TITLE_HEAD" => h($page == $START_PAGE ? "" : $TITLE),
	"PAGE_URL" => u($page),
	"EDIT" => $EDIT,
	"WIKI_TITLE" => h($WIKI_TITLE),
	"LAST_CHANGED_TEXT" => $LAST_CHANGED ? $T_LAST_CHANGED : "",
	"LAST_CHANGED" => $LAST_CHANGED,
	"CONTENT" => $action != "edit" ? $CON : "",
	"TOC" => $TOC,
	"LANG" => $LANG,
	"LIST_OF_ALL_PAGES" => "<a href=\"$self?action=search\">$T_LIST_OF_ALL_PAGES</a>",
	"WIKI_VERSION" => $WIKI_VERSION,
	"SYNTAX" => $action == "edit" || $preview ? "<a href=\"$SYNTAX_PAGE\">$T_SYNTAX</a>" : "",
	"SHOW_PAGE" => $action == "edit" || $preview ?  "<a href=\"$self?page=".u($page)."\">$T_SHOW_PAGE</a>" : "",
	"COOKIE" => '<a href="'.$self.'?page='.u($page).'&amp;action='.u($action).'&amp;erasecookie=1" rel="nofollow">'.$T_ERASE_COOKIE.'</a>',
	"CONTENT_FORM" => $CON_FORM_BEGIN,
	"\/CONTENT_FORM" => $CON_FORM_END,
	"CONTENT_TEXTAREA" => $CON_TEXTAREA,
	"CONTENT_SUBMIT" => $CON_SUBMIT,
	"CONTENT_PREVIEW" => $CON_PREVIEW,
	"RENAME_TEXT" => $RENAME_TEXT,
	"RENAME_INPUT" => $RENAME_INPUT,
	"EDIT_SUMMARY_TEXT" => $EDIT_SUMMARY_TEXT,
	"EDIT_SUMMARY_INPUT" => $EDIT_SUMMARY,
	"FORM_PASSWORD" => $FORM_PASSWORD,
	"FORM_PASSWORD_INPUT" => $FORM_PASSWORD_INPUT
);

foreach($tpl_subs as $tpl => $rpl) // substituting values
	$html = template_replace($tpl, $rpl, $html);

echo $html; // voila

// Function library

function h($t) { return htmlspecialchars($t); }
function u($t) { return urlencode($t); }

function template_replace($what, $subs, $where) { return preg_replace("/\{(([^}]*) )?$what( ([^}]*))?\}/U", empty($subs) ? "" : "\${2}".str_replace("$", "&#36;", trim($subs))."\${4}", $where); }
function template_match($what, $where, &$dest) { return preg_match("/\{(([^}]*) )?$what( ([^}]*))?\}/U", $where, $dest); }

function sanitizeFilename($filename) {
	for($i = 0, $ret = "", $c = strlen($filename); $i < $c; $i++)
		if(!ctype_cntrl($filename[$i]))
			$ret .= $filename[$i];

	return trim(str_replace("..", "", $ret), "/");
}

function revTime($time) {
	preg_match("/([0-9][0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])-([0-9][0-9])([0-9][0-9])-([0-9][0-9])/U", $time, $m);

	return date($GLOBALS["DATE_FORMAT"], mktime($m[4], $m[5], $m[6], $m[2], $m[3], $m[1]));
}

// Get number of exclamation marks at the beginning of the line
function exclNum($l) {
	for($i = 1, $c = strlen($l); $i < $c && $l[$i] == "!"; $i++);

	return $i;
}

// get paragraph number $par_id.
function getParagraph($text, $par_id) {
	$par = array(); // paragraph

	$count = 1; // paragraph count
	$par_excl = 0; // number of !
	$inside_code = $inside_html = false; // exclamation marks inside {{}} and {html}{/html} are not headings

	$lines = explode("\n", $text);

	foreach($lines as $line) {
		if($line[0] == "!" && !$inside_html && !$inside_code) {
			if($count == $par_id) {
				$par[] = $line;

				$par_excl = exclNum($line);
			}
			else if($par_excl)
				if(exclNum($line) > $par_excl)
					$par[] = $line;
				else
					break;

			$count++;
		}
		else if($par_excl)
			$par[] = $line;

		if(preg_match("/(?<!\^)\{html\}/", $line)) $inside_html = true;
		if(preg_match("/(?<!\^)\{\/html\}/", $line)) $inside_html = false;
		if(preg_match("/(?<!\^)\{\{/", $line)) $inside_code = true;
		if(preg_match("/(?<!\^)\}\}/", $line)) $inside_code = false;
	}

	return implode("\n", $par);
}

function diff($f1, $f2) {
	if($f2 < $f1)
		list($f1, $f2) = array($f2, $f1);

	$dir = $GLOBALS["HISTORY_DIR"] . $GLOBALS["page"] . "/";

	return plugin("diff", $dir.$f1, $dir.$f2) ? $GLOBALS["plugin_ret_diff"] : diff_builtin($dir.$f1, $dir.$f2);
}

function diff_builtin($f1, $f2) {
	$a1 = explode("\n", @file_get_contents($f1));
	$a2 = explode("\n", @file_get_contents($f2));

	$d1 = array_diff($a1, $a2);
	$d2 = array_diff($a2, $a1);

	for($i = 0, $ret = ''; $i <= max(count($a2), count($a1)); $i++) {
		if($r1 = array_key_exists($i, $d1)) $ret .= "<del>".h(trim($d1[$i]))."</del>\n";
		if($r2 = array_key_exists($i, $d2)) $ret .= "<ins>".h(trim($d2[$i]))."</ins>\n";
		if(!$r1 && !$r2) $ret .= h(trim($a2[$i]))."\n";
	}

	return "<pre id=\"diff\">$ret</pre>";
}

function authentified() {
	global $PASSWORD, $PROTECTED_READ, $sc;

	if(empty($PASSWORD) || !strcasecmp($_COOKIE['LW_AUT'], $PASSWORD) || !strcasecmp(md5($sc), $PASSWORD)) {
		setcookie('LW_AUT', $PASSWORD, time() + ($PROTECTED_READ ? 4 * 3600 : 365 * 86400));

		return true;
	} else
		return false;
}

// returns "line" from meta.dat files. $lnum is number of line from the end of file starting with 1
function meta_getline($file, $lnum) {
	if(fseek($file, -($lnum * 175), SEEK_END) != 0)
		return false;

	$line = fread($file, 175);

	if($line[0] != "!") // control character
		return false;

	$date = substr($line, 1, 16);
	$ip = trim(substr($line, 19, 15));
	$size = trim(substr($line, 35, 10));
	$esum = trim(substr($line, 45, 128));

	return array($date, $ip, $size, $esum);
}

// Call a method for all plugins, second to last arguments are forwarded to plugins as arguments
function plugin($method) {
	$ret = false;
	$args = array_slice(func_get_args(), 1);

	foreach($GLOBALS["plugins"] as $plugin)
		if(method_exists($plugin, $method))
			$ret |= call_user_func_array(array($plugin, $method), $args);

	return $ret; // returns true if treated by a plugin
}

function fallback_template() { return '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="{LANG}" lang="{LANG}">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8"/>
	<title>{WIKI_TITLE} {-  PAGE_TITLE_HEAD}</title>
	<style type="text/css">
*{margin:0;padding:0;}
body{font-size:12px;line-height:16px;padding:10px 20px 20px 20px;}
a{color:#006600;text-decoration:none;border-bottom:1px dotted #006600;}
p{margin: 5px 0 5px 0;}
a.pending{color:#990000;}
a.external:after{content: "\2197";}
pre{border:1px dotted #ccc;padding:4px;overflow:auto;margin:3px;}
img,a img{border:0px}
h1,h2,h3,h4,h5,h6{letter-spacing:2px;font-weight:normal;margin:15px 0 15px 0px;color:#006600;}
h1 a:hover,h2 a:hover,h3 a:hover,h4 a:hover,h5 a:hover,h6 a:hover{color:#006600;}
h1 a,h2 a,h3 a,h4 a,h5 a,h6 a{border-bottom:none;}
h2 span.par-edit, h3 span.par-edit, h4 span.par-edit, h5 span.par-edit, h6 span.par-edit {visibility:hidden;font-size:x-small;}
h2:hover span.par-edit, h3:hover span.par-edit, h4:hover span.par-edit, h5:hover span.par-edit, h6:hover span.par-edit {visibility:visible;}
h1{margin:18px 0 15px 15px;font-size : 22px;}
hr{margin:10px 0 10px 0;height:0px;overflow:hidden;border:0px;border-top:1px solid #006600;}
ul,ol{padding:5px 0px 5px 20px;}
table{text-align:left;}
.error{color:#F25A5A;font-weight:bold;}
form{display:inline}
#renameForm{display:block;margin-bottom:6px;}
.submit{margin-top:6px;}
.contentTextarea{width:100%;}
input,select,textarea{border:1px solid #AAAAAA;padding:2px;font-size:12px;}
.submit{padding:1px;}
textarea{padding:3px;}
#toc{border:1px dashed #006600;margin:5px 0 5px 10px;padding:6px 5px 7px 0px;float:right;padding-right:2em;list-style:none;}
#toc ul{list-style:none;padding:3px 0 3px 10px;}
#toc li{font-size:11px;padding-left:10px;}
#toc ul li{font-size:10px;}
#toc ul ul li{font-size:9px;}
#toc ul ul ul li{font-size:8px;}
#toc ul ul ul ul li{font-size:7px;}
#diff{padding:1em;white-space:pre-wrap;word-wrap:break-word;white-space:-moz-pre-wrap;white-space:-pre-wrap;white-space:-o-pre-wrap;width:97%;}
#diff ins{color:green;text-decoration:none;font-weight:bold;}
#diff del{color:red;text-decoration:line-through;}
#diff .orig{color:#666;font-size:90%;}
/* Plugins */
.tagList{padding:0.2em 0.4em 0.2em 0.4em;margin-top:0.5em;border:1px dashed #006600;clear:right;}
.tagCloud{float:right;width:200px;padding:0.5em;margin:1em;border:1px dashed #006600;clear:right;}
.pageVersionsList{letter-spacing:0px;font-variant:normal;font-size:12px;}
.resizeTextarea a{border-bottom:none;}
	</style>
	{HEAD}
</head>
<body>
<table border="0" width="100%" cellpadding="4" cellspacing="0">
	<tr id="headerLinks">
		<td colspan="2">{HOME} {RECENT_CHANGES}</td>
		<td style="text-align:right">{EDIT} {SYNTAX} {HISTORY}</td>
	</tr>
	<tr><th colspan="3"><hr /><h1 id="page-title">{PAGE_TITLE} {<span class="pageVersionsList">( plugin:VERSIONS_LIST )</span>}</h1></th></tr>
	<tr>
		<td id="mainContent" colspan="3">
			{<div class="error"> ERROR </div>}
			{CONTENT} {plugin:TAG_LIST}
			{CONTENT_FORM} {RENAME_TEXT} {RENAME_INPUT <br /><br />} {CONTENT_TEXTAREA}
			<p style="float:right;margin:6px">{FORM_PASSWORD} {FORM_PASSWORD_INPUT} {plugin:CAPTCHA_QUESTION} {plugin:CAPTCHA_INPUT}
			{EDIT_SUMMARY_TEXT} {EDIT_SUMMARY_INPUT} {CONTENT_SUBMIT} {CONTENT_PREVIEW}</p>{/CONTENT_FORM}
		</td>
	</tr>
	<tr><td colspan="3"><hr /></td></tr>
	<tr>
		<td><div>{SEARCH_FORM}{SEARCH_INPUT}{SEARCH_SUBMIT}{/SEARCH_FORM}</div></td>
		<td>Powered by <a href="http://lionwiki.0o.cz/">LionWiki</a>. {LAST_CHANGED_TEXT}: {LAST_CHANGED} {COOKIE}</td>
		<td style="text-align:right">{EDIT} {SYNTAX} {HISTORY}</td>
	</tr>
</table>
</body>
</html>'; }