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
 */

// CONFIGURATION VARIABLES (edit these as necessary)

// Preference for .gif vs .png image files (change to "gif" for .gif)
$LATEXIMG_IMAGE_FORMAT = "png";

/* The remaining variables should work as they are on most hosts, but check
 * them if you are getting errors. */

// Run "which latex" in a command line shell to determine the path to put here
$LATEXIMG_LATEX_PATH = "/usr/bin/latex";
// Run "which dvips" in a command line shell to determine the path to put here
$LATEXIMG_DVIPS_PATH = "/usr/bin/dvips";
// Run "which convert" in a command line shell to determine the path to put here
$LATEXIMG_CONVERT_PATH = "/usr/bin/convert";
// Run "which identify" in a command line shell to determine the path to put here
$LATEXIMG_IDENTIFY_PATH = "/usr/bin/identify";

/* Note that it is also essential that the Ghostscript exectuable (i.e. the file "gs") 
 * is in a directory that  your webserver searches for exectuables, e.g. the Apache PATH
 * variable.  This is because ImageMagick calls it indirectly.  This is the case on most
 * hosts. */