<?php
/*
 * Slimbox plugin for LionWiki
 * Usage: [images/thumb/thumbnail.png|images/picture.jpg|Caption of the image]
 * 
 * Not part of LionWiki official distribution, must be downloaded separately.
 */
class Slimbox
{
	var $desc = array(
		array("Slimbox plugin", "plugin provides Slimbox galleries using syntax: [images/thumb/thumbnail.png|images/picture.jpg|Caption of the image].")
	);

	var $version = "1.1";

	function template()
	{
		$GLOBALS["HEAD"] .= '
<script type="text/javascript" src="plugins/Slimbox/js/jquery.js"></script>
<script type="text/javascript" src="plugins/Slimbox/js/slimbox2.js"></script>
<link rel="stylesheet" href="plugins/Slimbox/css/slimbox2.css" />';

		return false;
	}

	function formatBegin()
	{
		global $CON;

		$rg_img_local = "([^\]\|]+\.(jpeg|jpg|gif|png))";

		$regex = "#\[$rg_img_local\|link=$rg_img_local\|title=(.+)\]#U";

		$CON = preg_replace($regex, '<a href="$3" class="lightbox" rel="lightbox[]" title="$5"><img src="$1" alt="$5"/></a>', $CON);
	 }
}