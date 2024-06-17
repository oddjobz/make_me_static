#
#	Makefile - build the make_me_static plugin (.zip) file
#
#
PHPSRC=src_php
VUESRC=src_vue
BUILDROOT=build
BUILD=${BUILDROOT}/make-me-static
DISTFILE=make-me-static.zip

.phoney:
	all builda sync

all:
	@echo "Nothing to see here - try 'build'"

build: build_clean build_base build_vue build_php build_zip

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
#	Install Javascript assets
#
	@echo Pass
	@mkdir -p ${BUILD}/assets
#	@cp -ra ../mms_directory/client/dist/assets ${BUILD}
#	@cp -ra ../mms_directory/client/public/theme.css ${BUILD}/admin/css/mm-static-theme.css
#	@cp -ra ../mms_directory/client/public/admin.css ${BUILD}/admin/css/mm-static-admin.css

build_zip:
#
#	Make the ZIP file
#
	@(cd ${BUILDROOT} && zip -rq make-me-static.zip make-me-static)
	@echo "Build complete."
#
#	
#
sync:
	rsync -rav ${BUILD}/* /var/www/live_linuxcouk/wp-content/plugins/make-me-static/
	chown -R www-data:www-data /var/www/live_linuxcouk/wp-content/plugins/make-me-static/