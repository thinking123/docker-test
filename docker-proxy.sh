#!/usr/bin/env bash
docker-machine create -d virtualbox \
    --engine-env HTTP_PROXY=http://127.0.0.1:1080 \
    --engine-env HTTPS_PROXY=https://127.0.0.1:1080 \
    --engine-env NO_PROXY=192.168.99.100 \
    docker1