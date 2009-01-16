<?php
class Install
{
	function Install()
	{
		global $PLUGINS_DIR, $PLUGINS_DATA_DIR, $PAGES_DIR, $HISTORY_DIR, $USE_HISTORY, $HISTORY_COMPRESSION;
	
		$core = array();
		$plugins = array();
		
		if(version_compare(phpversion(), "4.3.0") < 0)
			$core[] = "You're running on PHP version " . phpversion() . " but LionWiki requires PHP version greater or equal 4.3.0. In order to use LionWiki, you must upgrade to newer version.";
		
		if(!is_writable(trim($PAGES_DIR, "/")))
			$core[] = "$PAGES_DIR is not writable, you can't write any pages. Set $PAGES_DIR writable (usually by command chmod 777) and preferably everything this directory contains.";
			
		if($USE_HISTORY && !is_writable(trim($HISTORY_DIR, "/")))
			$core[] = "$HISTORY_DIR is not writable and LionWiki is configured to use history. Either set $HISTORY_DIR writable (usually by command chmod 777) or set \$USE_HISTORY=false in your config file (_config.php).";
			
		if($HISTORY_COMPRESSION == "bzip2" && !extension_loaded("bz2"))
			$core[] = "LionWiki is configured to store page revisions using bzip2 compression but bz2 extension is not loaded. Change \$HISTORY_COMPRESSION to either \"plain\" or \"gzip\" in your config file (_config.php).";
			
		if(!is_writable(trim($PLUGINS_DATA_DIR, "/")))
			$plugins[] = "$PLUGINS_DATA_DIR is not writable. Some plugins store data in this directory and won't function properly unless this directory is set as writable. You can make it writable using command chmod 777. Preferably make writable also everything this directory contains.";
			
		function pluginDataCheck($filename)
		{
			global $PLUGINS_DATA_DIR;
			
			if(is_writable($PLUGINS_DATA_DIR . $filename) || (!file_exists($PLUGINS_DATA_DIR . $filename) && is_writable(trim($PLUGINS_DATA_DIR, "/"))))
				return true;
			else
				return false;
		}
		
		if(file_exists($PLUGINS_DIR . "wkp_RSS.php") && !is_writable("rss.xml"))
			$plugins[] = "RSS plugin is installed but rss.xml doesn't exist or is not writable. Create it (if it doesn't exist yet) and make it writable (using command chmod 777).";
			
		$plugin_files = array(
			array("Tags", "tags.txt"),
			array("Admin", "Admin_blacklist.txt"),
			array("Admin", "Admin_blockedips.txt"),
			array("Admin", "Admin_pages.txt"),
			array("Admin", "Admin_plugins.txt")
		);
			
		foreach($plugin_files as $file)
			if(!pluginDataCheck($file[1]))
				$plugins[] = "$file[0] plugin is installed but $PLUGINS_DATA_DIR/$file[1] is not writable. Create it (if it doesn't exist yet) and make it writable (using command chmod 777).";
		if(file_exists($PLUGINS_DIR . "wkp_RSS.php") && !is_writable("data"))
			$plugins[] = "Upload plugin is installed but \"data\" dir doesn't exist or is not writable. Create it (if it doesn't exist yet) and make it writable (using command chmod 777).";
	}
}
?>
