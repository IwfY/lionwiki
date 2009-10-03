<?php
/*
 * WhatLinksHere plugin for LionWiki searches for backreferences to current page
 *
 * (c) Adam Zivner 2008, adam.zivner@gmail.com, GPL'd
 */

class WhatLinksHere {
	var $desc = array(
		array("WhatLinksHere plugin", "gives list of pages linking to selected article. Function is triggered by action=\"whatlinkshere\" with properly set parameter page.")
	);

	var $link_page_title = true; // replace page title with the link to What links here?

	function action($a)
	{
		global $TITLE, $page, $PAGES_DIR, $CON;

		if($a == "whatlinkshere") {
			$CON = "<ul>";

			$editable = false;
			$dir = opendir($PAGES_DIR);

			while($file = readdir($dir)) {
				if(preg_match("/\.txt$/", $file)) {
					@$con = file_get_contents($PAGES_DIR . $file);
					$query = preg_quote($page);

					if(@preg_match("/\[([^|\]]+\|)? *$query(#[^\]]+)? *\]/i", $con))
					$files[] = substr($file, 0, strlen($file) - 4);
				}
			}

			if(is_array($files)) {
				sort($files);

				foreach($files as $file)
					$CON .= "<li><a href=\"$self?page=".urlencode($file)."\">".htmlspecialchars($file)."</a></li>";
			}

			$CON .= "</ul>";

			$TITLE = "What links to ".htmlspecialchars($page)."? (".count($files).")";

			return true;
		}
		else
			return false;
	}

	function template()
	{
		global $html, $page, $page_nolang, $START_PAGE, $WIKI_TITLE, $TITLE;

		if(!empty($page)) {
			$html = template_replace("plugin:WHAT_LINKS_HERE", "<a href=\"$self?action=whatlinkshere&amp;page=".urlencode($page)."\" rel=\"nofollow\">What links here?</a>", $html);
			$html = template_replace("PAGE_TITLE", "<a href=\"$self?action=whatlinkshere&amp;page=".urlencode($page)."\" rel=\"nofollow\" title=\"What links to this page?\">".htmlspecialchars($page_nolang == $START_PAGE ? $WIKI_TITLE : $TITLE)."</a>", $html);
		}
	}
}
