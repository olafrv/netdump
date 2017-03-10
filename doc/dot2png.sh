#!/bin/bash
# apt-get install graphviz
[ ! -f netdump.png ] || rm netdump.png
dot -Tpng netdump.dot -o netdump.png
