#
#	MMS Wordpress Plugin / Directory Client
#	(c) Mad Penguin Consulting Ltd 2024
#
PHPSRC=src_php
VUESRC=src_vue
BUILDROOT=build
BUILD=${BUILDROOT}/make-me-static
DISTFILE=make-me-static.zip

.phoney:
	all build build_plugin build_playground

all:
	@echo "Nothing to see here - try 'make build'"

version:
	@./scripts/roll_version.py

build_playground: set_target_3 build_clean build_base build_php build_vue build_zip deploy_playground_zip

build_plugin:     set_target_1 build_clean build_base build_php build_vue build_zip

set_target_1:
	@echo Setting target to: mms-directory-1
	@./scripts/set_target.py mms-directory-1

set_target_3:
	@echo Setting target to: mms-directory-3
	@./scripts/set_target.py mms-directory-3

deploy_playground_zip:
	$(eval VERSION := $(shell cat scripts/VERSION))
	@echo Copying zip file to /downloads on test.makemestatic.com @ ${VERSION}
	@scp build/make-me-static.zip root@aaa:/home/mms_pages/.local/mms_pages/sites/41d9bffcf58d7c04000ca728000487a9/public/downloads/make-me-static-${VERSION}.zip
	@scripts/set_playground_version.py ${VERSION}

build_clean:
	@echo Cleaning build root
	@rm -rf ${BUILDROOT}

build_base:
	@echo Building base structure
	@mkdir -p ${BUILD}
	@cp readme.txt ${BUILD}
	@cp LICENSE ${BUILD}
	@mkdir -p ${BUILD}/assets/blueprints
	# @cp images/mms.jpeg ${BUILD}/assets/screenshot-1.jpeg
	@cp blueprint.json ${BUILD}/assets/blueprints/

build_php:
	@echo Building PHP files
	@cp ${PHPSRC}/index.php ${BUILD}
	@cp ${PHPSRC}/make-me-static.php ${BUILD}
	@cp ${PHPSRC}/../composer.json ${BUILD}
	@cp -ra ${PHPSRC}/admin ${BUILD}
	@cp -ra ${PHPSRC}/public ${BUILD}
	@cp -ra ${PHPSRC}/languages ${BUILD}
	@cp -ra ${PHPSRC}/includes ${BUILD}
	@cp -ra vendor ${BUILD}

build_vue:
	@echo Building VUE files
	@mkdir -p ${BUILD}/admin/css
	@mkdir -p ${BUILD}/admin/js
	@cd src_vue && npm run build
	@cp -ra src_vue/public/theme.css ${BUILD}/admin/css/make-me-static-theme.css
	@cp -ra src_vue/public/admin.css ${BUILD}/admin/css/make-me-static-admin.css
	@cp -ra src_vue/dist/assets/index.js ${BUILD}/admin/js/index.js
#
build_zip:
	@echo Making ZIP file
	@rm -f make-me-static.zip
	@(cd ${BUILDROOT} && zip -rq make-me-static.zip make-me-static)
	@echo "Build complete."
