#!/bin/bash

version=$1
path=$2
orig_dir=`pwd`

work=$path/$version-nc

rm -rf $work
mkdir $work

cp -r * $work

find $work -name .svn -exec rm -rf {} \;

rm $work/plugins/wkp_Slimbox.php
rm -rf $work/plugins/Slimbox
rm $work/plugins/wkp_SyntaxHighlighter.php
rm -rf $work/plugins/SyntaxHighlighter
rm -rf $work/var/*
rm $work/release.sh*
rm $work/install.sh*
rm -rf $work/nbproject
rm $work/plugins/wkp_Upload.php
rm $work/plugins/wkp_Script.php

release=$path/$version

rm -rf $release
mkdir $release

cd $work

tar cf $release/$version.tar *
bzip2 -z -9 $release/$version.tar

cp $work/index.php $release/index.txt
