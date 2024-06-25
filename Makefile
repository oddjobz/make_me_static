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
	all build

all:
	@echo "Nothing to see here - try 'make build'"

version:
	@./scripts/roll_version.py

build: version build_clean build_base build_php build_vue build_zip

build_clean:
#
#	Clean build location
#
	@rm -rf ${BUILD}

build_base:
#
#	Install the documentation
#
	@mkdir -p ${BUILD}
	@cp readme.txt ${BUILD}
	@cp LICENSE ${BUILD}
	@cp README.md ${BUILD}

build_php:
#
#	Now the files
#
	@mkdir -p ${BUILD}/public/php-sitemap-generator
	@cp ${PHPSRC}/index.php ${BUILD}
	@cp ${PHPSRC}/make-me-static.php ${BUILD}
	@cp -ra ${PHPSRC}/admin ${BUILD}
	@cp -ra ${PHPSRC}/public ${BUILD}
	@cp -ra ${PHPSRC}/languages ${BUILD}
	@cp -ra ${PHPSRC}/includes ${BUILD}
	@mkdir -p ${BUILD}/vendor/icamys/php-sitemap-generator/
	@cp -ra ${PHPSRC}/vendor/autoload.php ${BUILD}/vendor/
	@cp -ra ${PHPSRC}/vendor/composer ${BUILD}/vendor/
	@cp -ra ${PHPSRC}/vendor/icamys/php-sitemap-generator/ ${BUILD}/vendor/icamys

build_vue:
#
#	Install unscoped CSS
#
	@mkdir -p ${BUILD}/admin/css
	@cd src_vue && npm run build
	@cp -ra src_vue/public/theme.css ${BUILD}/admin/css/mm-static-theme.css
	@cp -ra src_vue/public/admin.css ${BUILD}/admin/css/mm-static-admin.css
#
#	We 'could' include this in the repo and use it locally. It makes more sense from a maintenance
#	perspective to load it from the directory servier as this reduces the frequency with which the
#	user will need to update the plugin. All the code should be GPLv2 compliant.
#
# 	@cp -ra ../mms_directory/client/dist/assets ${BUILD}

build_zip:
#
#	Make the ZIP file
#
	@(cd ${BUILDROOT} && zip -rq make-me-static.zip make-me-static)
	@echo "Build complete."
#