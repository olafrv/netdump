#!/bin/bash

# Git repository path
GREPO=$1
COMMR=$2

cd "$GREPO"
git log -p $COMMR | colordiff
