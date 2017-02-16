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
	[ ! -f "$GITDF" ]
	FIRST=$?

	# Copy the dump to the repository
	cp "$SRCDF" "$GITDF" 
	ERROR=$?
	if [ $ERROR -eq 0 ] 
	then
		# Is this a first commit?
		if [ $FIRST -eq 0 ]
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
fi

exit $ERROR
