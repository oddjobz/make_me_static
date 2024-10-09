#!/bin/bash
#
cd ~/scm/wp-plugin-svn
version=`cat ../make_me_static/scripts/VERSION`
rsync -rav ../make_me_static/build/make-me-static/* trunk/
cp ../make_me_static/build/make-me-static/assets/blueprints/blueprint.json assets/blueprints/
svn cp trunk tags/${version}
svn ci -m "Updating and tagging ${version}"
