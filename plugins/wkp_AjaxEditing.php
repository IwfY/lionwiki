<?php
/**
 * AjaxEditing plugin for LionWiki, licensed under GNU/GPL
 * 
 * (c) Adam Zivner 2009 <adam.zivner@gmail.com>
 */ 

class AjaxEditing
{
  var $desc = array(
		array("AjaxEditing", "")
	);
	
	/**
	 * Number of rows of edit textarea reflects number of lines of edited paragraphs.
	 * Too big or too small edit textarea wouldn't be good, so here are the limits:	 
	 */	 	
	
	var $rows_min = 3;
	var $rows_max = 20;
	
	/**
	 * Substitute edit template
	 */	 	

	function editTemplate()
	{
		global $CON, $html, $self, $showsource, $page, $econfprot, $esum, $error, $preview, $par;
		global $T_PASSWORD, $T_MOVE_TEXT, $T_EDIT_SUMMARY, $LAST_CHANGED_TIMESTAMP, $T_PREVIEW, $T_DONE, $T_DISCARD_CHANGES;
		
		$rows = count(explode("\n", $CON));
		
		if($this->rows_min > $rows)
			$rows = $this->rows_min;
		else if($this->rows_max < $rows)
			$rows = $this->rows_max;
	
		if(!authentified() && !$showsource) { // if not logged on, require password
			$FORM_PASSWORD = $T_PASSWORD;
			$FORM_PASSWORD_INPUT = "<input class=\"ajaxPasswordInput\" type=\"password\" name=\"sc\" />";
		}

		if(!$showsource) {
			$RENAME_TEXT = $T_MOVE_TEXT;
			$RENAME_INPUT = "<input class=\"ajaxRenameInput\" type=\"text\" name=\"moveto\" value=\"" . htmlspecialchars($moveto ? $moveto : $page) . "\" />";
		}

		$CON_FORM_BEGIN = "<form action=\"$self\" class=\"ajaxForm\" method=\"post\"><input type=\"hidden\" name=\"action\" value=\"save\" /><input class=\"ajaxLastChanged\" type=\"hidden\" name=\"last_changed\" value=\"$LAST_CHANGED_TIMESTAMP\" /><input class=\"ajaxShowSource\" type=\"hidden\" name=\"showsource\" value=\"$showsource\" /><input type=\"hidden\" name=\"par\" value=\"$par\" />";
		
		if(empty($econfprot))
			$CON_FORM_BEGIN .= "<input type=\"hidden\" class=\"ajaxEconfProt\" name=\"econfprot\" value=\"1\" />";
		
		$CON_FORM_END = "</form>";

		$CON_TEXTAREA = "<textarea name=\"content\" class=\"ajaxContentTextarea\" cols=\"83\" rows=\"$rows\">" . htmlspecialchars($CON) . "</textarea><input type=\"hidden\" id=\"ajaxPage\" name=\"page\" value=\"$page\" />";
		
		if(!$showsource) {
			$CON_SUBMIT = "<input class=\"submit ajaxContentSubmit\" onclick=\"ajaxAction($par, 'save');return false;\" type=\"submit\" value=\"$T_DONE\" />";
			
			$EDIT_SUMMARY_TEXT = $T_EDIT_SUMMARY;
			$EDIT_SUMMARY = "<input type=\"text\" name=\"esum\" class=\"ajaxEsum\" value=\"".htmlspecialchars($esum)."\" />";
		}
			
		$CON_PREVIEW = "<input class=\"ajaxContentPreview\" class=\"submit\" onclick=\"ajaxAction($par, 'edit&preview=1');return false;\" type=\"submit\" name=\"preview\" value=\"$T_PREVIEW\" /> <input type=\"submit\" onclick=\"ajaxAction($par, '');return false;\" value=\"$T_DISCARD_CHANGES\" />";
		
		$subs = array(
			array("CONTENT_FORM", $CON_FORM_BEGIN),
			array("\/CONTENT_FORM", $CON_FORM_END),
			array("CONTENT_TEXTAREA", $CON_TEXTAREA),
			array("FORM_PASSWORD", $FORM_PASSWORD),
			array("FORM_PASSWORD_INPUT", $FORM_PASSWORD_INPUT),
			array("EDIT_SUMMARY_TEXT", $EDIT_SUMMARY_TEXT),
			array("EDIT_SUMMARY_INPUT", $EDIT_SUMMARY),
			array("CONTENT_SUBMIT", $CON_SUBMIT),
			array("CONTENT_PREVIEW", $CON_PREVIEW),
			array("ERROR", $error)
		);
	
		$html = @file_get_contents("plugins/AjaxEditing/template.html");
		
		plugin_call_method("template"); // plugin specific template substitutions
	
		foreach($subs as $s)
			$html = template_replace($s[0], $s[1], $html);
			
		$html = preg_replace("/\{([^}]* )?plugin:.+( [^}]*)?\}/U", "", $html); // getting rid of absent plugin tags
		
		if(!$preview)
			die($html);
	}
	
	function pageLoaded()
	{
		global $action, $CON;
		
		if($_REQUEST["ajax"] && $action == "edit")		
			$this->editTemplate();
	}
	
	function formatEnd()
	{
		global $CON, $html;
		
		if($_REQUEST["ajax"])
			die($CON . $html);
	}

	function formatBegin() {
		global $HEAD;
	
		$HEAD .= '<script type="text/javascript" src="plugins/AjaxEditing/ajax.js"></script>';
	}
}
?>
