<?php
/* Configuration file for LionWiki. */

$WIKI_TITLE = "My new wiki"; // name of the site
$PASSWORD = ""; // if left blank, no password is required to edit. Consider also $PASSWORD_MD5 below
// More secure way to use password protection, just insert MD5 hash into $PASSWORD_MD5
// if not empty, $PASSWORD is ignored and $PASSWORD_MD5 is used instead
$PASSWORD_MD5 = ""; // use e.g. http://www.md5.cz to generate MD5 hash

$TEMPLATE = "templates/dandelion.html"; // presentation template

$PROTECTED_READ = false; // if true, you need to fill password for reading pages too

$NO_HTML = false; // XSS protection