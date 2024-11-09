#!/usr/bin/env sh
docker run --name spenderella -p 8080:80 --rm -it $(docker build -q .)
