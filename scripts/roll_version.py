#!/usr/bin/env python3
from json import loads, dumps

VERSION_FILE = '.version'

with open(VERSION_FILE) as io:
    version = loads(io.read())
    
version['sub'] += 1
with open(VERSION_FILE, 'w') as io:
    io.write(dumps(version, indent=4))


def Process(filename, label, newline):
    with open(filename) as io:
        text = io.read()
        lines = text.split("\n")
        output = []
        for line in lines:
            if line.startswith(label):
                line = newline
            output.append(line)
    with open(filename, 'w') as io:  
        io.write("\n".join(output))
        
v = f'{version["major"]}.{version["minor"]}.{version["sub"]}'

Process('src_php/make-me-static.php', "define( 'MMS_VERSION", f"define( 'MMS_VERSION', '{v}' );")
Process('src_php/make-me-static.php', ' * Version:', f' * Version:           	{v}')
Process('src_vue/package.json', '  "version":', f'  "version": "{v}",')

print(f'Updated to: {version["major"]}.{version["minor"]}.{version["sub"]}')
with open('scripts/VERSION', 'w') as io:
    io.write(v)