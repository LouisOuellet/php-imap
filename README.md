# IMAP Class for PHP
This class provides functions to manage an IMAP mailbox more easily.

## Content
 - IMAP Class : Located here src/lib/imap.php
 - example.php : Example PHP script that uses the IMAP class.
 - settings.json : A JSON file that stores all scripts settings

## IMAP Class
### Initialization
```PHP
<?php
// Import Library
require_once dirname(__FILE__) . '/src/lib/imap.php';

// Import Configurations
$settings=json_decode(file_get_contents(dirname(__FILE__) . '/settings.json'),true);

// Init Library
$IMAP = new apiIMAP($settings['imap']['host'],$settings['imap']['port'],$settings['imap']['encryption'],$settings['imap']['username'],$settings['imap']['password'],$settings['imap']['isSelfSigned']);

// Check Connection Status
if(is_bool($IMAP->Status) && $IMAP->Status){
  // Connection is Successfull
} else {
  // Print Connection Error
  echo $IMAP->Status."\n";
}
```

## example.php
This file contains a working example.

## settings.json
### Create settings
To create the file simply use your favorite editor and copy/paste the example.
```BASH
nano settings.json
```
### Example
```JSON
{
    "imap":{
        "host": "imap.domain.com",
        "port": "993",
        "encryption": "SSL",
        "isSelfSigned": true,
        "username": "username@domain.com",
        "password": "password"
    }
}
```
