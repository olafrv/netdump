#!/bin/bash

# Git repo URL?
GREPO=$1
# Where to clone?
CLONEROOT=$2
# Commit version hash?
GCOMM=$3
# Git clone directory? 
CLONE=$(basename "${GREPO}" ".git")

git clone $GREPO $CLONEROOT \
	&& cd "$CLONEROOT" \
		&& git reset --hard $GCOMM
