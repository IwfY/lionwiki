<?php
/*
 * SyntaxHighlighter plugin is a integrating plugin to the powerful GeSHI syntax highlighter.
 *
 * As of 13. 9. 2009 it is:
 * - stable and functioning
 * - not very configurable (given the awesome possibilities of GeSHI)
 * - alpha - it's possible that it will change rapidly in the near future
 * - trimmed. GeSHI contains by default a huge number of languages, most of them are obscure and have little practical
 * use. If this plugin does not contain support for you language of choice, check GeSHI website, it's quite possible
 * that it's part of the official release but I didn't include it here. Then simply copy language file to plugins/SyntaxHighlighter/
 * and it's gonna work.
 *
 * Not part of LionWiki official distribution, must be downloaded separately.
 *
 * (c) Adam Zivner 2009, adam.zivner@gmail.com, GPL'd
 *
 * GeSHI copyright:
 * (c) 2004-2007 Nigel McNie,
 * (c) 2007-2008 Benny Baumann
 * (c) 2008 Milian Wolff
 */
class SyntaxHighlighter
{
	/*
	 * Firefox and other gecko based browsers contains bug which makes copy and paste of code with line numbers unusable.
	 * There are three possible workarounds:
	 * - ignore firefox :)
	 * - turn off line_numbers
	 * - provide $plain_text_link which contains link to "almost plain text" version of the code which is possible
	 * to copy&paste in firefox.
	 */

	var $line_numbers = true;
	var $language_name = true; // language name in the footer
	var $plain_text_link = true; // link to the plain text version, for usable copy&paste in FF with line numbers turned on

	var $version = "1.0";

	var $desc = array(
		array("SyntaxHighlighter plugin", "plugin provides syntax highlighting for a lot of programming languages. Syntax: {source php} echo 'This is my code!'; {/source}.")
	);

	function template()
	{
		global $HEAD;

		return false;
	}

	function subPagesLoaded()
	{
		global $CON;

		/*
		 * We must put aside the source codes to "protect them" from being interpreted and formatted by LionWiki core.
		 * After LionWiki does its job, we'll substitute it back (in formatEnd() method).
		 */

		$this->n_codes = preg_match_all("/[^\^](\{syntax (.+)\}(.+)\{\/syntax\})/Ums", $CON, $this->codes, PREG_SET_ORDER);

		foreach($this->codes as $code)
			$CON = str_replace($code[1], "{SYNTAX}", $CON);

		if($_GET["plaincode"]) // "almost plain text"
			die("<pre>" . htmlspecialchars($this->codes[(int) $_GET["num"]][3]) . "</pre>");
	}

	function formatEnd()
	{
		global $CON, $PLUGINS_DIR, $page;

		if($this->n_codes > 0) {
			include_once $PLUGINS_DIR . 'SyntaxHighlighter/geshi.php';

			$i = 0;

			foreach($this->codes as $code) {
				$geshi = new GeSHi($code[3], $code[2]);

				// Here you can play with various GeSHI configuration possibilities, see http://qbnz.com/highlighter/geshi-doc.html

				$geshi->set_header_type(GESHI_HEADER_PRE);

				if($this->line_numbers)
					$geshi->enable_line_numbers(GESHI_NORMAL_LINE_NUMBERS);

				if($this->language_name || $this->plain_text_link) {
					$header_template = '<div style="float: right;">';

					if($this->language_name)
						$header_template .= "{LANGUAGE} ";

					if($this->plain_text_link)
						$header_template .= '(<a href="'.$self.'?page='.urlencode($page).'&amp;plaincode=1&amp;num='.$i.'">plain</a>)';

					$geshi->set_header_content($header_template);
				}

				$CON = preg_replace("/{SYNTAX}/Us", $geshi->parse_code(), $CON, 1);

				$i++;
			}
		}
	}
}