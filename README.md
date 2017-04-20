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

## Options

Use the `to` option to specify the mail destination.

The `inline` option is usefull to improve both Gmail and Outlook support by copying css from `<style>` tags to inline attribute style.

## Update

````sh
./batmail self-update
````
