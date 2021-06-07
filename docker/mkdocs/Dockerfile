FROM            debian:buster

RUN             apt-get update && apt-get install -y python3-pip
RUN             pip3 install 'mkdocs-material>=6.0.0,<7.0.0'
RUN             mkdir /workspace
RUN             useradd -s /bin/bash docker-container-user

COPY            ./init-container.sh /root/init-container.sh
WORKDIR         /workspace
