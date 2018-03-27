#!/bin/bash

docker stop api
docker rm api
docker build -t api .
docker run -dit --name api -p 80:80 -p 3306:3306 -p 6379:6379 -v `pwd`:/data/apps/api api /bin/bash
docker exec -it api mkdir -p /data/apps/api/storage
docker exec -it api chmod +777 -R /data/apps/api/storage
docker exec -it api lnmp start
docker exec -it api /usr/local/redis/bin/redis-server /usr/local/redis/etc/redis.conf
docker exec -it api mysql -uroot -p123456 -e "create database bento_dev"
docker exec -it api mysql -uroot -p123456 -e "use bento_dev; source /data/apps/api/bento.schema.sql;"
