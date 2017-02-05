#!/bin/bash
# $1 Git repository directory for config file backup
# $2 Config file to backup (save) in git repository
# $3 Commit message for saving action

git config --global user.email "netdump@localhost"
git config --global user.name "netdump"

[ -d "$1" ] || mkdir -p "$1" \
	&& cd "$1" \
	&& [ -d "$1/.git" ] || git init \
	&& cp "$2" "$3"
ERROR=$?

if [ $ERROR -eq 0 ] && [ $(git diff | wc -l) -ne 0 ]
then
	git add -A && git commit -m "$4"
	ERROR=$?
fi

exit $ERROR
