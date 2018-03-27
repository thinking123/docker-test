FROM registry-vpc.cn-shanghai.aliyuncs.com/bento/api:0.0.4

MAINTAINER fising <fising@qq.com>

RUN sed 's/daemonize yes/# daemonize yes/' /usr/local/redis/etc/redis.conf > /etc/redis.conf

EXPOSE 80 3306 6379

VOLUME ["/data/apps/api"]
