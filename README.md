# Batmail

Send a newsletter based on an html file with local image folder.
This tool should only be used for test.

## Setup

````sh
wget -O- https://github.com/smalot/batmail/releases/download/v0.2/batmail.phar > batmail
chmod +x batmail
````

## Use

````sh
./batmail send index.html --to=test@example.com --inline
````
