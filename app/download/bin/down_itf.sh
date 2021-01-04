#!/bin/bash

source ~/.bashrc

if [ -f PROGRESS.DOWN.ITF ]
then
	exit
fi

touch PROGRESS.DOWN.ITF

now=`date +%s`

php ../src/down.php itf-men
php ../src/down.php itf-women

rm PROGRESS.DOWN.ITF
