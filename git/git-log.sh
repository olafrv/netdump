#!/bin/bash

# Git repository path
GREPO=$1

cd "$GREPO"
git log --pretty="format:%h %ci %s"
