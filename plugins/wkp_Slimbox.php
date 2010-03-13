<?php
/*
 * Slimbox plugin for LionWiki
 * Usage: [images/thumb/thumbnail.png|link=images/picture.jpg|title=Caption of the image]
 * (or other similar syntax)
 * 
 * Not part of LionWiki official distribution, must be downloaded separately.
 * Homepage: http://lionwiki.0o.cz/index.php?page=UserGuide%3A+Slimbox+plugin
 */
class Slimbox
{
	var $desc = array(
		array("Slimbox plugin", "plugin provides Slimbox galleries using syntax.")
	);

	var $version = "1.2";

	function template()
	{
		$GLOBALS["HEAD"] .= '
<script type="text/javascript" src="plugins/Slimbox/js/jquery.js"></script>
<script type="text/javascript" src="plugins/Slimbox/js/slimbox2.js"></script>
<link rel="stylesheet" href="plugins/Slimbox/css/slimbox2.css" />';

		return false;
	}

	function formatFinished()
	{
		global $CON;

		$CON = preg_replace('/<a href="([^"]*\.(jpeg|jpg|gif|png))"([^>]*)><img/', '<a href="$1" class="lightbox" rel="lightbox[]"$3><img', $CON);
	 }
}