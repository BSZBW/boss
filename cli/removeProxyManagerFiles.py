__author__ = 'swissbib'

import glob, os


directory = '/tmp'
os.chdir(directory)
files=glob.glob('Proxy*')
for filename in files:
    os.unlink(filename)


#files=glob.glob('lessphp*')
#for filename in files:
#    os.unlink(filename)

