#!/bin/bash
set -e

# Get latest commit in master, in plain text
LATEST_MASTER_COMMIT=$(curl -H "Accept: application/vnd.github.sha" -X GET https://api.github.com/repos/shlinkio/shlink-docker-image/commits/master)

# Create new tag and a ref to the tag, which will trigger image build on it
curl -u acelaya:${GITHUB_OAUTH_KEY} \
    -H "Content-Type: application/json" \
    --data "{ \"tag\": \"${TRAVIS_TAG}\", \"message\": \"${TRAVIS_TAG}\", \"object\": \"${LATEST_MASTER_COMMIT}\", \"type\": \"commit\" }" \
    -X POST https://api.github.com/repos/shlinkio/shlink-docker-image/git/tags
curl -u acelaya:${GITHUB_OAUTH_KEY} \
    -H "Content-Type: application/json" \
    --data "{ \"ref\": \"refs/tags/${TRAVIS_TAG}\", \"sha\": \"${LATEST_MASTER_COMMIT}\" }" \
    -X POST https://api.github.com/repos/shlinkio/shlink-docker-image/git/refs

# Trigger image build for "latest
curl -H "Content-Type: application/json" \
    --data '{ "docker_tag": "latest" }' \
    -X POST https://registry.hub.docker.com/u/shlinkio/shlink/trigger/${DOCKER_TRIGGER_TOKEN}/
