<?php
/*
 * Script plugin provides inserting both inline and external JavaScript.
 * Written by Swen Wetzel <xanatoswetzel@web.de>, some minor changes made by Adam Zivner
 *
 * Example syntax:
 *
 * {script}alert('Hello world!');{/script}
 * Will obviously insert this script into the page (and therefore activate it)
 *
 * {script label="Say hello!" title="Greeting" key="g"}alert('Hello world!');{/script}
 * Will create link with text "Say hello!", tooltip "Greeting" and access key "g". It will activate after clicking on this link.
 *
 * {script src="path_to_javascript_file.js"}{/script}
 * Will insert this external JavaScript file into the page.
 *
 * {script show}alert('Hello world!');{/script}
 * Will do the same but also displays source code.
 *
 * Most attributes can be combined freely, but their order is fixed! Order is:
 * src, label, title, key, show
 *
 * http://www.TiddlyTools.com/#InlineJavascriptPlugin
*/

class Script
{
	var $desc = array(
		array("Script", "provides flexible and powerful syntax to write inline JavaScript. Help how to use it is located <a href=\"http://www.TiddlyTools.com/#InlineJavascriptPlugin\">here</a>.")
	);

	var $version = "1.0";

	function subPagesLoaded()
	{
		global $CON, $NO_HTML;

		function scriptHandler($occurence)
		{
			list($n, $char, $src, $label, $title, $key, $show, $code) = $occurence;

			if($label != "") {
				$attr = "";

				if($title != "")
					$attr .= " title=\"" . h($title) . "\"";

				if($key != "")
					$attr .= " accesskey=\"$key\"";

				$jscript = "<a href=\"javascript:" . h($code) . "\"$attr>" . h($label) . "</a>";
			} else
				$jscript = "<script type=\"text/javascript\"" . ($src != "" ? " src=\"$src\"" : "") . ">$code</script>";

			$jscript = "{html}$jscript{/html}";

			return trim($show) == "show" ? "$char{{{$code}}} {br}$jscript" : $char . $jscript;
		}

		if($NO_HTML == false)
			$CON = preg_replace_callback('/([^\^])\{script(?: +src=\"((?:.|\n)*?)\")?(?: +label=\"((?:.|\n)*?)\")?(?: +title=\"((?:.|\\n)*?)\")?(?: +key=\"((?:.|\n)*?)\")?( +show)? *\}((?:.|\n)*?)\{\/script\}/', "scriptHandler", $CON);
	}
}