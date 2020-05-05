#!/usr/bin/env bash

set -ex

# install latest docker version
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable"
apt-get update
apt-get -y -o Dpkg::Options::="--force-confnew" install docker-ce

# enable multiarch execution
docker run --rm --privileged multiarch/qemu-user-static --reset -p yes
