<?php

class ImageExt
{
	var $desc = array(
		array("Index plugin", "gives list of pages in the wiki.")
	);

	var $dir;
	var $quality = 85;

	function ImageExt()
	{
		$this->dir = $VAR_DIR . "images/";
	}

	function subPagesLoaded()
	{
		global $CON;

		$rg_url = "[0-9a-zA-Z\.\#/~\-_%=\?\&,\+\:@;!\(\)\*\$' ]*";
		preg_match_all("#\[((https?://)?$rg_url\.(jpeg|jpg|gif|png))(\|[^\]]+)?\]#", $CON, $this->imgs, PREG_SET_ORDER);

		foreach($this->imgs as $img)
			$CON = str_replace($img[0], "{IMAGE}", $CON);
	}

	function formatFinished()
	{
		global $CON, $action;

		if($action != "")
			return;

		foreach($this->imgs as $img) {
			preg_match_all("/\|([^\]\|=]+)(=([^\]\|\"]+))?(?=[\]\|])/", $img[0], $options, PREG_SET_ORDER);

			$link = $i_attr = $a_attr = $center = $tag = $style = "";
			$width = $height = 0;

			foreach($options as $o)
				if($o[1] == "center") $center = true;
				else if($o[1] == "right" || $o[1] == "left") $style .= "float:$o[1];";
				else if($o[1] == "link") $link = substr($o[3], 0, 4) == "http" ? $o[3] : "$self?page=" . u($o[3]);
				else if($o[1] == "alt") $i_attr .= " alt=\"$o[3]\"";
				else if($o[1] == "title") $a_attr .= " title=\"$o[3]\"";
				else if($o[1] == "width") $width = $o[3];
				else if($o[1] == "height") $height = $o[3];
				else if($o[1] == "class") $i_attr .= " class=\"$o[3]\"";
				else if($o[1] == "id") $i_attr .= " id=\"$o[3]\"";
				else if($o[1] == "style") $style .= "$o[3];";
				else if($o[1] == "noborder") $style .= "border:0;outline:0;";
				else if($o[1] == "thumb") $link = $img[1];

			if($width || $height)
				$img[1] = $this->scaleImage($img[1], $width, $height);

			$tag = "<img src=\"$img[1]\" style=\"$style\" alt=\"$alt\"$i_attr/>";

			if($link)   $tag = "<a href=\"$link\"$a_attr>$tag</a>";
			if($center) $tag = "<div style=\"text-align:center\">$tag</div>";

			$CON = preg_replace("/\{IMAGE\}/", $tag, $CON, 1);
		}
	}
	
	function rstrtrim($str, $remove)
	{
		$len = strlen($remove);
		$offset = strlen($str) - $len;

		while($offset > 0 && $offset == strpos($str, $remove, $offset))
		{
			$str = substr($str, 0, $offset);
			$offset = strlen($str) - $len;
		}

		return rtrim($str);
	}

	function pathToFilename($path)
	{
		$path = sanitizeFilename($path);

		if(substr($path, 0, 7) == "http://")
			$path = substr($path, 7);

		$ext = substr($path, -4);

		if(!strcasecmp($ext, ".png") || !strcasecmp($ext, ".gif") || !strcasecmp($ext, ".jpe"))
			$path = substr($path, 0, -4);

		if(!strcasecmp($ext, ".jpeg") || !strcasecmp($ext, ".jfif"))
			$path = substr($path, 0, -5);

		$path = str_replace("/", "_", $path);
		$path = str_replace(".", "_", $path);

		return $path . ".jpg";
	}

	function scaleImage($path, $nx, $ny)
	{
		if(!file_exists($this->dir))
			mkdir(rtrim($this->dir, "/"), 0777);

		if(substr($path, 0, 2) == "./")
			$path = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["PATH_INFO"] . $path;

		$filename = $this->pathToFilename($path);

		if(!file_exists($this->dir) . $filename) {
			if(!strcasecmp(substr($path, -4), ".png"))
				$img = imagecreatefrompng($path);
			else if(!strcasecmp(substr($path, -4), ".gif"))
				$img = imagecreatefromgif($path);
			else if(!strcasecmp(substr($path, -4), ".jpg") || !strcasecmp(substr($path, -5), ".jpeg") || !strcasecmp(substr($path, -4), ".jpe"))
				$img = imagecreatefromjpeg($path);

			$ox = imagesx($img);
			$oy = imagesy($img);

			if($nx && !$ny)
				$ny = (int) ($oy * $nx / $ox);
			else if(!$nx && $ny)
				$nx = (int) ($ox * $ny / $oy);

			$thumb = imagecreatetruecolor($nx, $ny);

			imagecopyresampled($thumb, $img, 0, 0, 0, 0, $nx, $ny, $ox, $oy);

			imagejpeg($thumb, $this->dir . $filename, $this->quality);
		}

		return "./" . $this->dir . $filename;
	}

}