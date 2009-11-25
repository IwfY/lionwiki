<?php
/* Configuration file for LionWiki. */

$WIKI_TITLE = "My new wiki"; // name of the site

// MD5 hash of password. If empty (or commented out), no password is required
// $PASSWORD = md5("my_password");

$TEMPLATE = "templates/dandelion.html"; // presentation template

// if true, you need to fill password for reading pages too
// before setting to true, read http://lionwiki.0o.cz/index.php?page=UserGuide%3A+How+to+use+PROTECTED_READ
$PROTECTED_READ = false;

$NO_HTML = false; // XSS protection