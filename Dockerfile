FROM registry-vpc.cn-shanghai.aliyuncs.com/bento/api:0.0.4

MAINTAINER fising <fising@qq.com>

EXPOSE 80 3306 6379

VOLUME ["/data/apps/api"]
