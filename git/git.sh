#!/bin/bash

# Git repository path
GREPO=$1
# Configuration dump file path
SRCDF=$2
# Configuration dump file path (git)
GITDF=$3
# Git commit message
GCOMM=$4
# Debugging enabled?
DEBUG=$5
# Errors?
ERROR=0

git config --global user.email "netdump@localhost"
git config --global user.name  "netdump"

[ -d "$GREPO" ] || mkdir -p "$GREPO"
ERROR=$?

if [ $ERROR -eq 0 ] && [ -d "$GREPO" ]
then
	cd "$GREPO" 
	if [ ! -z "$DEBUG" ]; then echo "Initial commit: $GCOMM"; fi
	[ -d "$GREPO/.git" ] || git init
	ERROR=$?
fi

if [ $ERROR -eq 0 ] && [ ! -f "$GITDF" ]
then
	if [ ! -z "$DEBUG" ]; then echo "Initial commit: $GCOMM"; fi
	cp "$SRCDF" "$GITDF" && git add -A && git commit -m "$GCOMM"
	ERROR=$?
fi

if [ $ERROR -eq 0 ] && [ $(git diff | wc -l) -ne 0 ]
then
	if [ ! -z "$DEBUG" ]; then git diff; fi
	git add -A && git commit -m "$GCOMM"
	ERROR=$?
fi

exit $ERROR
