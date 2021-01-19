#!/bin/bash

source ~/.bashrc

if [ -f PROGRESS.DOWN.ITF ]
then
	exit
fi

touch PROGRESS.DOWN.ITF

now=`date +%s`

cat /dev/null > download.itf.log
php ../src/down.php itf-men 1>> download.itf.log 2>> download.itf.log
php ../src/down.php itf-women 1>> download.itf.log 2>> download.itf.log

rm PROGRESS.DOWN.ITF
