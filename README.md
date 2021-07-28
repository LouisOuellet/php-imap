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
### Parameters
 - ```$IMAP->Status``` : Stores the status of the connection. Or error in the event of a failure.
 - ```$IMAP->Folders``` : Stores the list of folders available.
### Methods
To make it easier, we will assume you have already initialized the class in ```$IMAP```.
#### READ
This method simply set the read flag to a message.
#### DELETE
This method simply delete a message.
#### GET
This method retrieves a list of email stored in a folder. The list is pretty extensive as it contains everything in the email and header. Including file attachments, unquoted bodies and stripped subjects.
```PHP
$IMAP->get(); // will fetch all emails from the INBOX by default
```
If you want to look in a specific folder:
```PHP
$IMAP->get("Sent");
```
Finally optionally you can specify if you want to retrieve only new email like this:
```PHP
$IMAP->get(['new'=>true]);
$IMAP->get("Sent",['new'=>true]);
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
