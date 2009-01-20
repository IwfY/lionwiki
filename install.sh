#!/bin/sh

# This is installation script for LionWiki - minimalist wiki engine (http://lionwiki.0o.cz)
# It just creates neccessary directories and sets them writable.
# (c) 2009 Adam Zivner, <adam.zivner@gmail.com>

if ! [ -d "pages" ]; then
	mkdir "pages"
	
	echo "Core: Directory 'pages' was created and set to 777 permissions."
fi

chmod -R 777 "pages"

if ! [ -d "history" ]; then
	mkdir "history"

	echo "Core: Directory 'history' was created and set to 777 permissions."
fi

chmod -R 777 "history"

if [ -d "plugins" ]; then
	FILES=`ls plugins`
	
	for file in $FILES
	do
		if [ $file = "wkp_Admin.php" ]; then
			if ! [ -d "plugins/data" ]; then
				mkdir "plugins/data"
			fi

			chmod 777 "plugins/data"

			# if they exist, chmod'd them, if not, don't bother
			chmod 777 "plugins/data/admin-blockip.txt" 2> /dev/null
			chmod 777 "plugins/data/admin-blacklist.txt" 2> /dev/null
			chmod 777 "plugins/data/admin-pages.txt" 2> /dev/null
			chmod 777 "plugins/data/admin-plugins.txt" 2> /dev/null
		elif [ $file = "wkp_RSS.php" ]; then
			if ! [ -f "rss.xml" ]; then
				touch "rss.xml"
			fi

			chmod 777 "rss.xml"
		elif [ $file = "wkp_Tags.php" ]; then
			if ! [ -d "plugins/data" ]; then
				mkdir "plugins/data"
			fi

			chmod 777 "plugins/data"

			# if it exists, chmod it, if not, don't bother
			chmod 777 "plugins/data/tags.txt" 2> /dev/null
		elif [ $file = "wkp_Upload.php" ]; then
			if ! [ -d "data" ]; then
				mkdir "data"
			fi

			chmod -R 777 "data"
		fi
	done
fi

echo "Installation was completed. You can try to load your site in web browser."
