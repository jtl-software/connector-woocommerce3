#!/usr/bin/env bash
#
# Sync dist/woo-jtl-connector/ to the wp.org SVN trunk and create a
# release tag <VERSION>. 1:1 port of the GitLab deploy step (E5a.5).
# The extra `svn delete --force` for "missing" files fixes a latent bug
# in the GitLab source: without it, files removed from the build stay
# tracked in SVN forever.
#
# Required environment variables (set by the workflow):
#   SVN_USERNAME, SVN_PASSWORD, VERSION, SVN_URL

set -euo pipefail

svn checkout --username "$SVN_USERNAME" --password "$SVN_PASSWORD" --non-interactive \
    "$SVN_URL" --depth immediates
svn checkout --username "$SVN_USERNAME" --password "$SVN_PASSWORD" --non-interactive \
    "$SVN_URL/trunk" woo-jtl-connector/trunk/ --depth infinity

rm -f -R woo-jtl-connector/trunk/*
cp -R dist/woo-jtl-connector/* woo-jtl-connector/trunk/

cd woo-jtl-connector/trunk
svn status
svn add --force ./*
svn status | grep '^!' | awk '{print $2}' | xargs -r svn delete --force

if svn info --non-interactive "$SVN_URL/tags/$VERSION" >/dev/null 2>&1; then
  svn delete --username "$SVN_USERNAME" --password "$SVN_PASSWORD" --non-interactive \
      --force "$SVN_URL/tags/$VERSION" -m "Removing old Tag $VERSION"
fi

svn commit --username "$SVN_USERNAME" --password "$SVN_PASSWORD" --non-interactive \
    -m "Tagging $VERSION"
svn copy --username "$SVN_USERNAME" --password "$SVN_PASSWORD" --non-interactive \
    "$SVN_URL/trunk" "$SVN_URL/tags/$VERSION" -m "Tagging $VERSION"
