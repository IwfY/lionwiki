<?php
/*
 * Comments plugin for LionWiki, (c) 2009 Adam Zivner, adam.zivner@gmail.com
 *
 * There are two ways to add comments section to your pages:
 * 1) insert {plugin:COMMENTS} on appropriate place in your template
 *	If you then don't want comments on some particular page, insert {NO_COMMENTS} into that page content.
 * 2) insert {COMMENTS} into your page content. Good if you want discussion only on some pages.
 *
 * Comments are almost totally templatable, see plugins/Comments/template.html, it
 * should be quite self explanatory.
 *
 * Comments are stored in var/comments/ in the same directory structure as history.
 */

class Comments
{
	var $desc = array(
		array("Comments", "adds support for comments.")
	);

	var $sorting_order = "asc"; // "asc" means from oldest to newest, "desc" means newest first

	var $data_dir;
	var $comments_dir;
	var $template = "template.html";

	function Comments()
	{
		$this->data_dir = $GLOBALS["PLUGINS_DIR"] . "Comments/";
		$this->comments_dir = $GLOBALS["VAR_DIR"] . "comments/";

		$this->localize();
	}

	/*
	 * Process comment content, i.e. turn wiki syntax into HTML.
	 *
	 * Because of LionWiki core structure, we need to duplicate the functionality :(
	 *
	 * Supported features are: bold, italics, underscore, strikethrough, link
	 */

	function processComment($txt)
	{
    global $PAGES_DIR;

		$rg_url = "[0-9a-zA-Z\.\#/~\-_%=\?\&,\+\:@;!\(\)\*\$']*";
		$rg_link_local = "(" . $rg_url . ")";
		$rg_link_http = "h(ttps?://" . $rg_url . ")";

		$txt = preg_replace('#\[([^\]]+)\|' . $rg_link_http . '\]#U', '<a href="xx$2" class="url external">$1</a>', $txt);
		// local links has to start either with / or ./
		$txt = preg_replace('#\[([^\]]+)\|\.\/' . $rg_link_local . '\]#U', '<a href="$2" class="url">$1</a>', $txt);
		$txt = preg_replace('#' . $rg_link_http . '#i', '<a href="$0" class="url external">xx$1</a>', $txt);
		$txt = preg_replace('#xxttp#', 'http', $txt);

		preg_match_all("/\[([^|\]]+\|)?([^\]#]+)(#[^\]]+)?\]/", $txt, $matches, PREG_SET_ORDER); // matching Wiki links

		foreach($matches as $match) {
			if(empty($match[1])) // is page label same as its name?
				$match[1] = $match[2];
			else
				$match[1] = rtrim($match[1], "|");

			if($match[3]) // link to the heading
				$match[3] = "#" . preg_replace("/[^\da-z]/i", "_", urlencode(substr($match[3], 1, strlen($match[3]) - 1)));

			if(file_exists($PAGES_DIR . "$match[2].txt"))
				$txt = str_replace($match[0], '<a href="'.$self.'?page=' . urlencode($match[2]) . $match[3] . '">' . $match[1] . '</a>', $txt);
			else
				$txt = str_replace($match[0], '<a href="'.$self.'?page=' . urlencode($match[2]) . '&amp;action=edit" class="pending" rel="nofollow">' . $match[1] . '</a>', $txt);
		}

    $txt = preg_replace('#([0-9a-zA-Z\./~\-_]+@[0-9a-z/~\-_]+\.[0-9a-z\./~\-_]+)#i', '<a href="mailto:$0">$0</a>', $txt);

		$txt = preg_replace("/'--(.*)--'/Um", '<del>$1</del>', $txt); // strikethrough

		$txt = str_replace("--", "&mdash;", $txt); // --

		$txt = preg_replace("/'__(.*)__'/Um", '<u>$1</u>', $txt); // underlining

		$txt = preg_replace("/'''(.*)'''/Um", '<strong>$1</strong>', $txt); // bold
		$txt = preg_replace("/''(.*)''/Um", '<em>$1</em>', $txt); // italic

		$txt = preg_replace("/(\r\n|\r)/", "\n", $txt); // unifying newlines to Unix ones
		$txt = str_replace("\n", "<br />", $txt);

		return $txt;
	}

	function template()
	{
		global $CON, $html, $action, $preview, $page, $PAGES_DIR, $HEAD, $self, $comments_html, $comment_captcha_failed;

		/*
		 * Include comments if:
		 * - {plugin:COMMENTS} is in template and {NO_COMMENTS} is not in page content
		 * - {COMMENTS} is in page content
		 */

		if($action == "" && !$preview && ((template_match("plugin:COMMENTS", $html, $null) && strpos($CON, "{NO_COMMENTS}") === false)
			|| strpos($CON, "{COMMENTS}") !== false)) {

			$HEAD .= '<script type="text/javascript" src="plugins/Comments/comments.js"></script>';
			$HEAD .= '<style type="text/css" media="all">@import url("plugins/Comments/comments.css");</style>';

			$tmpl = file_get_contents($this->data_dir . $this->template);

			$tmpl = strtr($tmpl, array(
				"{FORM_NAME}" => $this->TP_FORM_NAME,
				"{FORM_EMAIL}" => $this->TP_FORM_EMAIL,
        "{FORM_CONTENT}" => $this->TP_FORM_CONTENT,
        "{FORM_NAME_VALUE}" => $comment_captcha_failed ? htmlspecialchars($_POST["name"]) : "",
				"{FORM_EMAIL_VALUE}" => $comment_captcha_failed ? htmlspecialchars($_POST["email"]) : "",
        "{FORM_CONTENT_VALUE}" => $comment_captcha_failed ? htmlspecialchars($_POST["content"]) : "",
				"{FORM_SUBMIT}" => $this->TP_FORM_SUBMIT,
				"{FORM_SELF}" => htmlspecialchars($self),
				"{FORM_PAGE}" => htmlspecialchars($page),
				"{COMMENTS}" => $this->TP_COMMENTS
			));

			$items_str = "";

			if($dir = @opendir($this->comments_dir . $page)) {
				$item_tmpl = "";

				if(preg_match("/\{item\}(.*)\{\/item\}/Us", $tmpl, $m))
					$item_tmpl = $m[1];

				$filenames = array();

				while($filename = @readdir($dir))
					if(preg_match("/([0-9]{8}-[0-9]{4}-[0-9]{2})\.txt/", $filename, $m))
						$filenames[] = $filename;

				if($this->sorting_order == "asc")
					sort($filenames);
				else if($this->sorting_order == "desc")
					rsort($filenames);

        $comment_num = 0;

				foreach($filenames as $filename) {
          $comment_num++;

					$file = file_get_contents($this->comments_dir . $page . "/" . $filename);

					$delimiter = strpos($file, "\n");

					$meta = substr($file, 0, $delimiter);
					$content = substr($file, $delimiter + 1);

					list($ip, $name, $email) = explode("\t", $meta);

					$processed_content = $this->processComment($content);

					$items_str .= strtr($item_tmpl, array(
						"{CONTENT}" => $processed_content,
						"{NAME}" => htmlspecialchars($name),
						"{EMAIL}" => htmlspecialchars($email),
            "{NAME_TO_EMAIL}" => $email == "" ? $name : ("<a href=\"mailto:".htmlspecialchars($email)."\">" . htmlspecialchars($name) . "</a>"),
						"{IP}" => $ip,
						"{DATE}" => revTime(basename($filename, ".txt")),
						"{ID}" => basename($filename, ".txt"),
            "{NUMBER}" => $comment_num
					));
				}

				$tmpl = str_replace("{NUMBER_OF_COMMENTS}", count($filenames), $tmpl);
			}

			$comments_html = preg_replace("/\{item\}.*\{\/item\}/Us", $items_str, $tmpl);

			plugin_call_method("commentsTemplate");

			$html = template_replace("plugin:COMMENTS", $comments_html, $html);
		}
	}

	function actionBegin()
	{
		global $page, $LOCAL_HOUR, $plugins, $action, $plugin_saveok, $error, $comment_captcha_failed;

		if($action == "save-comment") {
      if(isset($plugins["Captcha"])) {
        $plugins["Captcha"]->checkCaptcha();

        if($plugin_saveok == false) {
          $comment_captcha_failed = true;
          $action = "";
          $error = ""; // suppress error messages

          unset($_REQUEST["qid"]); // don't check captcha again

          return true;
        }
      }

			if(!is_dir($this->comments_dir))
				mkdir($this->comments_dir);

			$c_dir = $this->comments_dir . urldecode($page);

			if(!is_dir($c_dir))
				mkdir($c_dir);

			function prepare($txt) {
				return strtr($txt, array(
					"\t" => "",
					"\n" => ""
				));
			}

			$meta = prepare($_SERVER["REMOTE_ADDR"]) . "\t" . prepare($_POST["name"]) . "\t" . prepare($_POST["email"]) . "\n";

			$rightnow = date("Ymd-Hi-s", time() + $LOCAL_HOUR * 3600);

			$h = fopen($c_dir . "/" . $rightnow . ".txt", "w");

			if(!$h)
				return;

			fwrite($h, $meta);
			fwrite($h, $_POST["content"]);

			fclose($h);

			header("Location: $self?page=$page#$rightnow");
      die();
		}
	}

	// Localization strings

	var $cs_strings = array(
		array("TP_FORM_NAME", "Jméno"),
		array("TP_FORM_EMAIL", "E-mail"),
    array("TP_FORM_CONTENT", "Obsah"),
    array("TP_FORM_SUBMIT", "Přidat"),
		array("TP_REPLY_TO", "Odpovědět na tento komentář"),
		array("TP_COMMENTS", "Komentáře")
	);

	var $en_strings = array(
		array("TP_FORM_NAME", "Name"),
		array("TP_FORM_EMAIL", "E-mail"),
    array("TP_FORM_CONTENT", "Content"),
    array("TP_FORM_SUBMIT", "Save"),
		array("TP_REPLY_TO", "Reply to this comment"),
		array("TP_COMMENTS", "Comments")
	);


	function localize()
	{
		global $LANG;

		foreach($this->en_strings as $str)
			$this->$str[0] = $str[1];

		if($LANG != "en" && isset($this->{$LANG . "_strings"}))
			foreach($this->{$LANG . "_strings"} as $str)
				$this->$str[0] = $str[1];
	}
}