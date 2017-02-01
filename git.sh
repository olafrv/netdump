#!/bin/bash
# $1 Git repository directory for config file backup
# $2 Config file to backup (save) in git repository
# $3 Commit message for saving action
[ -d "$1" ] || mkdir -p "$1" \
	&& cd "$1" \
	&& [ -d "$1/.git" ] || git init \
	&& cp "$2" "$3" \
	&& git add -A \
	&& git commit -m "$4"

