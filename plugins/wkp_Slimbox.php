<?php

/** Plugin Slimbox
 * Usage:
 * [images/thumb/thumbnail.png|images/picture.jpg|Caption of the image]
 * */
class Slimbox
{
  var $desc = array(
		array("Slimbox plugin", "plugin provides Slimbox galleries using syntax: [images/thumb/thumbnail.png|images/picture.jpg|Caption of the image].")
	);
	
	function template()
	{
		global $HEAD;
		
		$HEAD .= "
<script type=\"text/javascript\" src=\"/plugins/data/Slimbox/js/jquery.js\"></script>
<script type=\"text/javascript\" src=\"/plugins/data/Slimbox/js/slimbox2.js\"></script>
<link rel=\"stylesheet\" href=\"/plugins/data/Slimbox/css/slimbox.css\" />		
";

		return false;
	}
   
	function formatBegin()
	{
		global $CON;
		
		$rg_url = "[0-9a-zA-Z\.\#/~\-_%=\?\&,\+\:@;!\(\)\*\$']*";
		$rg_img_local = "(".$rg_url."\.(jpeg|jpg|gif|png))";
		
		$regex = "#\[".$rg_img_local."\|".$rg_img_local."\|(.+)\]#U";
		
		$CON = preg_replace($regex, '<a href="$3" class="lightbox" rel="lightbox[]" title="$5"><img src="$1" alt="$5"/></a>', $CON);
	 } // formatBegin ()
}

?>
