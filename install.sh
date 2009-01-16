#!/bin/sh

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

			if ! [ -f "plugins/data/tags.txt" ]; then
				mkdir "plugins/data/tags.txt"
			fi

			chmod 777 "plugins/data/tags.txt"
		elif [ $file = "wkp_Upload.php" ]; then
			if ! [ -d "data" ]; then
				mkdir "data"
			fi

			chmod -R 777 "data"
		fi
	done
fi

echo "Installation was completed. You can try to load your site in web browser."
