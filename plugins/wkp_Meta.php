<?php
/*
 * Adds meta tags keyword and description to the head section of page. Simple stupid.
 * It can be used only in page content and not in templates because it wouldn't make sense.
 *
 * Use syntax:
 *
 * {description:This page is about cats and dogs}
 * {keywords:Cats, Dogs}
 *
 * (c) Adam Zivner 2008, 2009, <adam.zivner@gmail.com>. GPL'd
 */

class Meta
{
	var $desc = array(
		array("Meta", "adds meta tags keywords and description to the page. Syntax {description:This page is about cats and dogs} and {keywords:Cats, Dogs}.")
	);

	function formatBegin()
	{
		global $HEAD, $CON;

		if(preg_match("/\{description:(.*)\}/U", $CON, $match)) {
			$HEAD .= "<meta name=\"description\" content=\"".strip_tags($match[1])."\" />\n";

			$CON = str_replace($match[0], "", $CON);
		}

		if(preg_match("/\{keywords:(.*)\}/U", $CON, $match)) {
			$HEAD .= "<meta name=\"keywords\" content=\"".strip_tags($match[1])."\" />\n";

			$CON = str_replace($match[0], "", $CON);
		}
	}
}