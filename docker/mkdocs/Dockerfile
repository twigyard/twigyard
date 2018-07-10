FROM            debian:latest

RUN             apt-get update && apt-get install -y python-pip
RUN             pip install mkdocs-material
RUN             mkdir /workspace
RUN             useradd -s /bin/bash docker-container-user

COPY            ./init-container.sh /root/init-container.sh
WORKDIR         /workspace
