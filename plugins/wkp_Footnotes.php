<?php
/**
 * Footnotes plugin for LionWiki, (c) 2009 Adam Zivner, GPL'd
 * 
 * {footnote}anything{/footnote} will be substituted with little [1] link which links to the:
 * {plugin:FOOT_NOTES} will produce <ol> list (numbered) of all footnotes. Simple, stupid :) 
 */ 

class Footnotes {
  var $desc = array(
		array("Footnotes", "")
	);
	
	var $ft = array();
		
	function footnote($occurence)
	{
		$this->ft[]	= $occurence[1];
		
		return "<sup><a href=\"#footnote_" . count($this->ft) . "\">[" . count($this->ft) . "]</a></sup>";
	}
		
	function formatEnd()
	{
		global $CON;
		
		$CON = preg_replace_callback("/\{footnote\}(.*)\{\/footnote\}/U", array($this, "footnote"), $CON);
	}
	
	function template()
	{
		global $CON;
	
		$footnotes = array("<ol id=\"footnotes\">");
		
		foreach($this->ft as $idx => $f)
			$footnotes[] = "<li><a name=\"footnote_" . ($idx + 1) . "\">&nbsp;</a>$f</li>";
			
		$footnotes[] = "</ol>";
			
		$CON = template_replace("plugin:FOOT_NOTES",  implode("\n", $footnotes), $CON);
	}
}
?>
