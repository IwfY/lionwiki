<?php
/*
 * Menu plugin for LionWiki, (c) 2009 Adam Zivner, adam.zivner@gmail.com
 * 
 * This plugin provides simple way to create one level menus, use it like this:
 * 
 * {menu(default.html) [Hauptseite|Main page] [Home]}
 * 
 * or
 * 
 * {menu [Hauptseite|Main page] [Home]}
 * 
 * Parameter "default.html" is a template file for menu (located in plugins/Menu/),
 * it's content is probably self explanatory.
 * 
 * If you don't explicitly write a template, "default.html" is used, which is
 * in default distribution.
 */

class Menu
{
	var $desc = array(
		array("Menu", "provides syntax for simple one level menus.")
	);

	var $template_dir;
	var $default_template = "default.html";

	var $menus = array();

	function Menu()
	{
		$this->template_dir = $GLOBALS["PLUGINS_DIR"] . "Menu/";
	}

	function formatBegin()
	{
		global $CON;

		// First we need to save it, otherwise main parsing algorithm would mess with it

		preg_match_all("/\{menu(\(([^)]*)\))? (.*)\}/Us", $CON, $this->menus, PREG_SET_ORDER);

		foreach($this->menus as $menu)
			$CON = str_replace($menu[0], "{MENU}", $CON);
	}

	function formatEnd()
	{
		global $CON;

		foreach($this->menus as $m) {
			$template_file = $m[2];
			$item_string = $m[3];

			$template_file = sanitizeFilename($template_file);

			if(empty($template_file) || !file_exists($this->template_dir . $template_file))
				$template_file = $this->default_template; // use default.html template if none is provided or does not exist

			$tmpl = file_get_contents($this->template_dir . $template_file);

			$item_tmpl = "";

			if(preg_match("/\{item\}(.*)\{\/item\}/Us", $tmpl, $m))
				$item_tmpl = $m[1];

			$items = array();

			if(preg_match_all("/\[([^\]]+)\]/U", $item_string, $matches))
				$items = $matches[1];

			$items_str = "";

			for($i = 0, $c = count($items); $i < $c; $i++) {
				$parts = explode("|", $items[$i]);

				if(empty($parts[1]))
					$parts[1] = $parts[0];

				list($name, $link) = $parts;

				if(substr($link, 0, 7) != "http://" && substr($link, 0, 7) != "http://"
					&& substr($link, 0, 2) != "./" && $link[0] != "/")
					$link = $GLOBALS["self"] . "?page=" . urlencode($link) . $suffix;

				$class = "";

				if($i == 0)
					$class = "first";

				if($i == $c - 1)
					$class .= "last";

				$items_str .= strtr($item_tmpl, array(
					"{class}" => $class,
					"{name}" => htmlspecialchars($parts[0]),
					"{link}" => $link
				));
			}

			$menu_str = preg_replace("/\{item\}.*\{\/item\}/Us", $items_str, $tmpl);

			$CON = preg_replace("/\{MENU\}/", $menu_str, $CON, 1);
		}
	}
}