<?php 

class WhatLinksHere
{
  var $desc = array(
		array("WhatLinksHere plugin", "gives list of pages linking to selected article. Function is triggered by action=\"whatlinkshere\" with properly set parameter page.")
	);

	function action($a)
	{
	  global $TITLE, $page, $PAGES_DIR, $CON;

	  if($a == "whatlinkshere")
	  {
	    $CON = "";
	    
	    $editable = false;
    	$dir = opendir(getcwd() . "/$PAGES_DIR");

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
	        $CON .= "<a href=\"./?page=" . $file . "\">" . $file . "</a><br />";
	    }

	    $TITLE = "What links to $page? (" . count($files) . ")";
	     
	    return true;
	  }
	  else
	  	return false;
	}

	function template()
	{
	  global $html, $page;

		if(!empty($page))		
			$html = template_replace("plugin:WHAT_LINKS_HERE", "<a href=\"?action=whatlinkshere&amp;page=".urlencode($page)."\" rel=\"nofollow\">What links here?</a>", $html);
	}
}

?>
