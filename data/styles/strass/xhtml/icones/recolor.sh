#!/bin/bash

for m in *.png ; do
	convert $m -color-matrix '6x3: 1 0 0 0 1 .08
0 1 0 0 1 .04
0 0 1 0 1 0 ' $m

done
