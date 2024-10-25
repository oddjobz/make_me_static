#!/usr/bin/env python3

from json import loads, dumps
from sys import argv

with open ("blueprint.local.json") as io:
    input = io.read()

conf = loads (input)
conf['steps'][2]['pluginZipFile']['url'] = f'https://test.makemestatic.com/downloads/make-me-static-{argv[1]}.zip'

with open ("blueprint.local.json", 'w') as io:
    io.write((dumps(conf, indent=4)))

print (f'Blueprint set to : {argv[1]}')