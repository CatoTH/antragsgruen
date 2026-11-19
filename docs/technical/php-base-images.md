# PHP Docker base image updates

The [FPM Dockerfile](../../docker/php/Dockerfile) and [Apache base Dockerfile](../../docker/apache-single-container/Dockerfile.baseimage)
pin official PHP images by patch tag and multi-platform digest. Dependabot
checks these pins weekly for updates on the current PHP minor line. Review PHP
minor and major upgrades separately. For a manual pin update, use the index
digest for the exact tag and check that it includes Linux amd64 and arm64.

The [scheduled base-image build](../../.github/workflows/docker-baseimage.yml)
uses the digest checked in on the branch it builds. For an urgent Apache PHP
patch, merge the new pin and dispatch the base-image workflow. Then run the
[application image build](../../.github/workflows/docker-build.yml) if updated
application images need to be published before the next release.
