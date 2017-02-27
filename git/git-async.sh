#!/bin/bash

# Git repository path
GREPO=$1
# Git commit message
GCOMM=$2
# Debugging enabled?
DEBUG=$3
# Errors?
ERROR=0

git config --global user.email "netdump@localhost"
git config --global user.name  "netdump"

# Create repository directory (if not exists)
[ -d "$GREPO" ] || mkdir -p "$GREPO"
ERROR=$?

# Enter and init the repository (if not initialized)
if [ $ERROR -eq 0 ] && [ -d "$GREPO" ]
then
	cd "$GREPO" 
	[ -d "$GREPO/.git" ] || git init
	ERROR=$?
fi

if [ $ERROR -eq 0 ] 
then
	# First commit?
	git log -1 2>/dev/null 1>/dev/null
	ALREADY_COMMITED=$?

	# Is this a first commit?
	if [ $ALREADY_COMMITED -ne 0 ]
	then 
		if [ ! -z "$DEBUG" ]; then echo "$GCOMM (Initial commit)"; fi
		git add -A && git commit -m "$GCOMM (Initial commit)"
		ERROR=$?
	else
		if [ ! -z "$DEBUG" ]; then git diff; fi
		# Not first, but are there any differences?
		if [ $(git diff | wc -l) -ne 0 ]
		then 	
			git add -A && git commit -m "$GCOMM"
			ERROR=$?
		fi
	fi
fi

exit $ERROR
