<?php
/*
	Tags plugin for LionWiki, (c) Adam Zivner 2008, licensed under GNU GPL

	Tags plugin provides nonhiearchical categorizing. It can display Tag list and/or Tag cloud
	
	Syntax: insert {tags:Biography, LionWiki, Another tag}
	
	Tags are case insensitive
	
	Tags plugin supports both Tag List (shows tags for current page) and Tag Cloud.
	You can use them in template (will be displayed on every page) by inserting
	{plugin:TAG_LIST} or {plugin:TAG_CLOUD}, or in page by inserting {TAG_LIST} or {TAG_CLOUD}

	Internals: tags for all pages are stored in one file - usually plugins/data/tags.txt
	This file has simple format - it's filled with pairs of lines. First line is name of 
	the page and second is comma separated list of tags belonging to this page.
*/

class Tags
{
  var $desc = array(
		array("Tags", "supports assigning tags to pages, can create list of tags and/or tag cloud.")
	);
	
	var $tagfile;
	
	var $tag_cloud_max = 20; // number of tags in cloud
	var $font_min = 10, $font_max = 14;
	
	function __construct()
	{
		$this->tagfile = dirname(__FILE__) . "/data/tags.txt";
	}
	
	function Tags() // PHP 4 contructor/destructor emulation
	{
		$argcv = func_get_args();
		call_user_func_array(array(&$this, '__construct'), $argcv); // constructor
	
		//register_shutdown_function(array(&$this, '__destruct')); // destructor
	}
	
	// returns tag array of given page
	
	function getTags($page)
	{
		if(!file_exists($this->tagfile))
			return array();
	
		$f = fopen($this->tagfile, "rb");
		
		if(!$f)
			return array();
		
		while($line = fgets($f)) {		
			if(trim($line) == $page)
				return array_map("trim", explode(",", fgets($f)));
			else
				fgets($f);
		}
		
		return array();
	}
	
	// displays tag search results page
	
	function action($action)
	{
		if($action != "tagsearch")
			return;
			
		global $TITLE, $CON;
	
		$tag = trim($_GET["tag"]);
	
		$TITLE = 'List of pages tagged with "' . htmlspecialchars($tag) . '"';
		
		$f = fopen($this->tagfile, "rb");
		
		if(!$f)
			return;
			
		$results = array();
			
		while($page = fgets($f)) {
			$tags = array_map("trim", explode(",", fgets($f)));
			
			if($this->inCaseArray($tag, $tags))
				$results[] = trim($page);
		}
		
		if(empty($results))
			$CON = "'''No pages are tagged with this tag.'''"; // shouldn't happen at all
		else {
			$CON = "<ul>\n";
			
			foreach($results as $r)
				$CON .= "	<li><a href=\"?page=".urlencode($r)."\">".htmlspecialchars($r)."</a></li>\n";
				
			$CON .= "</ul>\n";
			
			return true;
		}
	}
	
	function pageWritten()
	{
		global $page, $content;
		
		$tags = array();
		
		preg_match_all("/\{tags:(.+)\}/U", $content, $matches, PREG_SET_ORDER);
		
		foreach($matches as $match)
			$tags = array_merge($tags, explode(",", $match[1]));
			
		$tags = array_unique(array_map("trim", $tags));
		
		$file_tags = $this->getTags($page);
		
		$same = true;
		
		foreach($tags as $tag)
			if(!$this->inCaseArray($tag, $file_tags)) {			
				$same = false;
				break;
			}
			
		if(count($tags) != count($file_tags) || !$same) { // if tags are same, don't bother writing
			if(!file_exists($this->tagfile))
				touch($this->tagfile);
				
			$f = fopen($this->tagfile, "rb");
			
			$file_lines = array();
			
			while($line = fgets($f))
				$file_lines[] = $line;
				
			fclose($f);
			
			$f = fopen($this->tagfile, "wb");
			
			$line_count = count($file_lines);
			
			for($i = 0; $i < $line_count; $i += 2)
				if(trim($file_lines[$i]) != trim($page))
					fputs($f, $file_lines[$i] . $file_lines[$i + 1]);
					
			fputs($f, $page . "\n");
			fputs($f, implode(",", $tags) . "\n");
			
			fclose($f);
		}
	}

	function template()
	{
	  global $CON, $html, $page, $action;
	  
	  if(!empty($action))
	  	return;
	  
	  $CON = preg_replace("/\{tags:.+\}/U", "", $CON);
	  
	  if(template_match("plugin:TAG_LIST", $html, $match_html) || template_match("TAG_LIST", $CON, $match_con)) {
	  
			$tags = $this->getTags($page);
					
			$tag_array = array();
				
			foreach($tags as $tag)
				$tag_array[] = "<a class=\"tagLink\" href=\"?action=tagsearch&amp;tag=".urlencode(trim($tag))."\">".htmlspecialchars($tag)."</a>";
				
			if(empty($tag_array))
				$t = "";
			else
				$t = "<div class=\"tagList\">Tags: \n" . implode(", ", $tag_array) . "</div>\n";
			
			$CON = str_replace($match_con[0], $t, $CON);	
			$html = str_replace($match_html[0], $t, $html);
		}
		
		if(template_match("plugin:TAG_CLOUD", $html, $match_html) || template_match("TAG_CLOUD", $CON, $match_con)) {
			$f = @fopen($this->tagfile, "rb");
			
			if(!$f) {
				$CON = template_replace("TAG_CLOUD", "", $CON);
				
				return;
			}
			
			$tag_counts = array();
			
			while(fgets($f)) {
				$tags = explode(",", @fgets($f));
				
				foreach($tags as $tag) {
					$tag = trim($tag);
				
					if(empty($tag_counts["$tag"]))
						$tag_counts["$tag"] = 1;
					else
						$tag_counts["$tag"]++;
				}
			}
			
			if(empty($tag_counts))
				return;
			
			arsort($tag_counts);
			
			for($i = 0; $i < max(0, count($tag_counts) - $this->tag_cloud_max); $i++)
				array_pop($tag_counts);
				
			$count_max = reset($tag_counts);
			$count_min = end($tag_counts);

			$tag_counts = array_merge(array_flip(array_rand($tag_counts, count($tag_counts))), $tag_counts); // shuffle and preserve key => value relationship
		
			$t = "<div class=\"tagCloud\"><b>Tag cloud</b><br />\n";
			
			foreach($tag_counts as $tag => $count)
				$t .= "<a class=\"tagCloudLink\" style=\"font-size:".
				floor(($count - $count_min) / ($count_max - $count_min) * ($this->font_max - $this->font_min) + $this->font_min)
				."px\" href=\"?action=tagsearch&amp;tag=".urlencode($tag)."\">".htmlspecialchars($tag)."</a>\n";
			
			$t .= "</div>\n";
			
			$CON = str_replace($match_con[0], $t, $CON);
			$html = str_replace($match_html[0], $t, $html);
		}
	}
	
	function inCaseArray($needle, $arr)
	{
		if(is_array($arr))
			foreach($arr as $item)
				if(!strcasecmp($item, $needle))
					return true;
					
		return false;
	}
}
?>
