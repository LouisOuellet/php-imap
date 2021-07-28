# IMAP Class for PHP
This class provides functions to manage an IMAP mailbox more easily.

## Content
 - example.php : Example PHP script that uses the IMAP class.
 - settings.json : A JSON file that stores all scripts settings

## example.php
```PHP
<?php
// Import Librairies
require_once dirname(__FILE__) . '/src/lib/imap.php';

// Import Configurations
$settings=json_decode(file_get_contents(dirname(__FILE__) . '/settings.json'),true);

// Adding Librairies
$IMAP = new apiIMAP($settings['imap']['host'],$settings['imap']['port'],$settings['imap']['encryption'],$settings['imap']['username'],$settings['imap']['password'],$settings['imap']['isSelfSigned']);

// Check Connection Status
if(is_bool($IMAP->Status) && $IMAP->Status){
  // Retrieve INBOX
  $results = $IMAP->get();
} else { echo $IMAP->Status."\n"; }
?>
```

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
