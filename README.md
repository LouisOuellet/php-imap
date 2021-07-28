# IMAP Class for PHP
This class provides functions to manage an IMAP mailbox more easily.

## Content
 - IMAP Class : Located here src/lib/imap.php
 - settings.json : A JSON file that stores the IMAP server settings

## IMAP Class
### Initialization
```PHP
<?php
// Import Class
require_once dirname(__FILE__) . '/src/lib/imap.php';

// Import Configurations
$settings=json_decode(file_get_contents(dirname(__FILE__) . '/settings.json'),true);

// Init Class
$IMAP = new apiIMAP($settings['imap']['host'],$settings['imap']['port'],$settings['imap']['encryption'],$settings['imap']['username'],$settings['imap']['password'],$settings['imap']['isSelfSigned']);
```
### Parameters
 - ```$IMAP->Status``` : Stores the status of the connection. Or error in the event of a failure.
 - ```$IMAP->Folders``` : Stores the list of folders available in an array.
### Methods
To make it easier, we will assume you have already initialized the class in ```$IMAP```.
#### read
This method simply set the read flag to a message.
#### delete
This method simply delete a message.
### isConnected
This method is used to test if a connection was established to the IMAP server.
```PHP
if($IMAP->isConnected()){
  // Connection is Successful
} else {
  echo $IMAP->Status;
}
```
#### get
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
To retreive messages:
```PHP
$IMAP->get()->messages; // An array containing all messages
```
Handling messages:
```PHP
$IMAP->get()->messages[0]->ID; // ID of the message
$IMAP->get()->messages[0]->UID; // UID of the message
$IMAP->get()->messages[0]->Header; // Complete header information
$IMAP->get()->messages[0]->From; // From email address
$IMAP->get()->messages[0]->Subject->Full; // Subject of the message
$IMAP->get()->messages[0]->Subject->PLAIN; // Original subject
$IMAP->get()->messages[0]->Body->Meta; // Message structure
$IMAP->get()->messages[0]->Body->Content; // Message body (HTML if present otherwise plain text)
$IMAP->get()->messages[0]->Body->Unquoted; // Message body without quote
$IMAP->get()->messages[0]->Attachments; // Message attachments stored in an array
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
