.PHONEY: dist run_dev

build:
	npm run build

license: package.json
	npx generate-license-file --input package.json --output dist/client-third-party-licenses.txt --overwrite

link:
	npx link ../../orbit-component-base/client/

run_dev:
	npm run dev
