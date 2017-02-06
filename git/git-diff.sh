#!/bin/bash

# Git repository path
GREPO=$1

cd "$GREPO"
git whatchanged -p | colordiff
