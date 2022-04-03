#!/usr/bin/sh

case $# in
	1) param="$1" ;;
	2) param="$1/$2" ;;
	3) param="$1/$2??$3" ;;
esac

php Tools/cmd/debug.php $param

