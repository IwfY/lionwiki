<?php
/*
 * LatexImg plugin for LionWiki, (c) Matthew Leifer <matt@mattleifer.info>, 2009
 * Licensed under GNU GPLv2.0
 *
 * LatexImg plugin provides ability to insert snippets of LaTeX into a page that are 
 * rendered as either .gif or .png images.  This is useful for adding mathematical 
 * equations to a wiki page.
 *
 * LatexImg plugin is built upon LaTeX Rendering Class 0.8, (c) 2003/2004 Benjamin Zeiss
 * See the file "LatexImg/class.latexrender.php" for licensing and further details.
 * 
 * Syntax: insert {tex}Some Latex markup, e.g. E = mc^2{/tex}
 *
 * Explanation of error codes:
 *
 *   0 OK
 *   1 Formula longer than 500 characters
 *   2 Includes a blacklisted tag
 *   3 (Not used) Latex rendering failed
 *   4 Cannot create DVI file
 *   5 Picture larger than 500 x 500 followed by x x y dimensions
 *   6 Cannot copy image to pictures directory
 *
 */

class LatexImg {
	// Description array for the ListPlugins plugin
	var $desc = array(
		array("LatexImgv0.1", "supports the insertion of snippets of LaTeX into a wiki page that are rendered as .gif or .png images.")
	);

	// This holds the LatexRender object
	var $latex;

	// Data array to hold details of Latex snippets between first and second parse
	var $latex_data = array();

	/* Constructor */
	function LatexImg()
	{
		global $REAL_PATH, $PLUGINS_DIR, $PLUGINS_DATA_DIR;

		// Setup important directories for LatexImg
		$lateximg_dir = $PLUGINS_DIR . 'LatexImg';
		$lateximg_data_dir = $PLUGINS_DATA_DIR . 'LatexImg';
		$pictures_dir = $lateximg_data_dir . '/pictures';
		$tmp_dir = $lateximg_data_dir . '/tmp';

		// Include LatexImg configuration variables
		include_once($REAL_PATH . $lateximg_dir . '/config.php');
		// Include the LatexRender class
		include_once($REAL_PATH . $lateximg_dir . '/class.latexrender.php');

		// Check existence of data and tmp directories
		foreach(array($lateximg_data_dir , $tmp_dir) as $DIR)
			if(!file_exists($DIR)) {
				mkdir($DIR, 0777);
				$f = fopen($REAL_PATH . $DIR . "/.htaccess", "w");
				fwrite($f, "deny from all");
				fclose($f);
			}

		// Check existence of pictures directory.  Has to allow http requests.
		if(!file_exists($pictures_dir)) {
			mkdir($pictures_dir, 0777);
			$f = fopen($REAL_PATH . $pictures_dir . "/.htaccess", "w");
			fwrite($f, "allow from all");
			fclose($f);
		}

		// Get a new latex renderer
		$this->latex = new LatexRender($REAL_PATH . $pictures_dir, $pictures_dir, $REAL_PATH . $tmp_dir);

		// Set output image format
		$this->latex->_image_format = $LATEXIMG_IMAGE_FORMAT;

		// Set up the paths to the utilities that latexrender needs to run
		$this->latex->_latex_path = $LATEXIMG_LATEX_PATH;
		$this->latex->_dvips_path = $LATEXIMG_DVIPS_PATH;
		$this->latex->_convert_path = $LATEXIMG_CONVERT_PATH;
		$this->latex->_identify_path = $LATEXIMG_IDENTIFY_PATH;
	}

	/* Template hook */
	function template()
	{
		global $HEAD, $PLUGINS_DIR;
		// Include css for minimal styling on Latex images
		$HEAD .= '<style type="text/css" media="all">@import url("' . $PLUGINS_DIR . 'LatexImg/LatexImg.css");</style>';
	}

	/* subPagesLoaded hook.
	 * Main processing of LaTeX markup.  This is done before the main LionWiki syntax parsing
	 * because many of the elements of LionWiki syntax constructions, e.g. { ,^ and }, commonly
	 * occur inside LaTeX markup and would be misinterpreted. */
	function subPagesLoaded()
	{
		// Import the content to be processed
		global $CON;

		// Detect all latex markup
		preg_match_all("/(?<!\^)\{tex\}(.*?)\{\/tex\}/s", $CON, $tex_matches);

		// Go through each instance of latex markup, latex it, store data
		// and replace it with {tex} placeholder
		$this->latex_data = array();
		for ($i = 0, $c = count($tex_matches[0]); $i < $c; $i++) {
			$pos = strpos($CON, $tex_matches[0][$i]);
			$latex_formula = $tex_matches[1][$i];

			// Get a link to the image (this also makes the image if it doesn't exist yet)
			$this->latex_data[0][$i] = $this->latex->getFormulaURL($latex_formula);

			// Build a string for the alt attribute that displays the original LaTeX markup
			$alt_latex_formula = htmlentities($latex_formula, ENT_QUOTES);
			$alt_latex_formula = str_replace("\r", "&#13;", $alt_latex_formula);
			$alt_latex_formula = str_replace("\n", "&#10;", $alt_latex_formula);
			$this->latex_data[1][$i] = $alt_latex_formula;

			// Check whether latexrenderer successfully returned an image
			if ($this->latex_data[0][$i] != false)
				// If so, replace the LaTeX markup with {tex} placeholder
				$CON = substr_replace($CON, "{TEX}", $pos, strlen($tex_matches[0][$i]));
			else
				// If not, display the error code
				$CON = substr_replace($CON, "[LatexImg Error" . $this->latex->_errorcode . " " . $this->latex->_errorextra . "]", $pos, strlen($tex_matches[0][$i]));
		}
	}

	/* formatEnd hook.  Replaces {tex} placeholders with images */
	function formatEnd()
	{
		// Import the content to be processed
		global $CON;

		// Detect all {TEX} placeholders
		preg_match_all("/\{TEX\}/", $CON, $tex_matches);

		// Go through each {TEX} placeholder and replace it with an image
		for ($i = 0; $i < count($tex_matches[0]); $i++) {
			$pos = strpos($CON, $tex_matches[0][$i]);
			$CON = substr_replace($CON, "<img class = 'latex' src='" . $this->latex_data[0][$i] . "' title='" . $this->latex_data[1][$i] . "' alt='" . $this->latex_data[1][$i] ."'  />", $pos, strlen($tex_matches[0][$i]));
		}
	}
}