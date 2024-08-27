#!/usr/bin/env python3
from json import loads, dumps
from sys import argv

def Process1(filename, host):
    with open(filename) as io:
        text = io.read()
        lines = text.split("\n")
        output = []
        for line in lines:
            if line.strip().startswith('private $directory'):
                line = f"\tprivate $directory = 'https://{host}.madpenguin.uk/';"
            output.append(line)
    with open(filename, 'w') as io:  
        io.write("\n".join(output))
        
def Process2(filename, host):
    with open(filename) as io:
        text = io.read()
    model = loads(text)
    model['parameters']['host'] = f'https://{host}.madpenguin.uk'
    with open(filename, 'w') as io:  
        io.write(dumps(model, indent=4))


Process1('src_php/admin/make-me-static-admin.php', argv[1])
Process2('src_vue/package.json', argv[1])
print(f'Target set to: {argv[1]}')