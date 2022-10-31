![GitHub repo logo](/dist/img/logo.png)

# phpIMAP
![License](https://img.shields.io/github/license/LouisOuellet/php-imap?style=for-the-badge)
![GitHub repo size](https://img.shields.io/github/repo-size/LouisOuellet/php-imap?style=for-the-badge&logo=github)
![GitHub top language](https://img.shields.io/github/languages/top/LouisOuellet/php-imap?style=for-the-badge)
![Version](https://img.shields.io/github/v/release/LouisOuellet/php-imap?label=Version&style=for-the-badge)

## Features
 - IMAP Authentication
 - Management of IMAP mailbox

## Why you might need it
If you are looking for an easy way to authenticate users against an IMAP server or if you are looking to easily manage a mailbox. This PHP Class is for you.

## Can I use this?
Sure!

## License
This software is distributed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.en.html) license. Please read [LICENSE](LICENSE) for information on the software availability and distribution.

## Requirements
PHP >= 5.5.0

## Security
Please disclose any vulnerabilities found responsibly – report security issues to the maintainers privately.

## Installation
Using Composer:
```sh
composer require laswitchtech/php-imap
```

### Methods
To make it easier, we will assume you have already initialized the class in ```$phpIMAP```.

#### getFolders
This method simply list the folders available.

#### read
This method simply set the read flag to a message.

#### delete
This method simply delete a message.

#### isConnected
This method is used to test if a connection was established to the IMAP server.
```php
if($phpIMAP->isConnected()){
  // Connection is Successful
}
```

#### get
This method retrieves a list of email stored in a folder. The list is pretty extensive as it contains everything in the email and header. Including file attachments, unquoted bodies and stripped subjects.
```php
$phpIMAP->get(); // will fetch all emails from the INBOX by default
```
If you want to look in a specific folder:
```php
$phpIMAP->get("Sent");
```
Finally optionally you can specify if you want to retrieve only new email like this:
```php
$phpIMAP->get(['new'=>true]);
$phpIMAP->get("Sent",['new'=>true]);
```
To retreive messages:
```php
$phpIMAP->get()->messages; // An array containing all messages
```
Handling messages:
```php
end($phpIMAP->get()->messages)->ID; // ID of the message
end($phpIMAP->get()->messages)->UID; // UID of the message
end($phpIMAP->get()->messages)->Header; // Complete header information
end($phpIMAP->get()->messages)->From; // From email address
end($phpIMAP->get()->messages)->Sender; // Sender email address
end($phpIMAP->get()->messages)->To; // Array of the To addresses
end($phpIMAP->get()->messages)->CC; // Array of the CC addresses
end($phpIMAP->get()->messages)->BCC; // Array of the BCC addresses
end($phpIMAP->get()->messages)->Subject->Full; // Subject of the message
end($phpIMAP->get()->messages)->Subject->PLAIN; // Original subject
end($phpIMAP->get()->messages)->Body->Meta; // Message structure
end($phpIMAP->get()->messages)->Body->Content; // Message body (HTML if present otherwise plain text)
end($phpIMAP->get()->messages)->Body->Unquoted; // Message body without quote
end($phpIMAP->get()->messages)->Attachments; // Message attachments stored in an array
```
Handling attachments:
```php
end(end($phpIMAP->get()->messages)->Attachments)['filename']; // filename of attachment
end(end($phpIMAP->get()->messages)->Attachments)['name']; // name of attachment
end(end($phpIMAP->get()->messages)->Attachments)['bytes']; // size of attachment in bytes
end(end($phpIMAP->get()->messages)->Attachments)['attachment']; // content of the attachment already decoded
end(end($phpIMAP->get()->messages)->Attachments)['encoding']; // encoding type of the attachment
```

#### saveAttachment
You can use this method to save your attachment locally. If file is saved, the method will return the full path of the file.
```php
// $phpIMAP->saveAttachment([ARRAY of File],[Destination Directory])
foreach(end($phpIMAP->get()->messages)->Attachments as $file){
  if($path = $phpIMAP->saveAttachment($file,"tmp/")){ echo "Saved in ".$path; }
}
```

## Example

```php

//Import SMTP class into the global namespace
//These must be at the top of your script, not inside a function
use LaswitchTech\IMAP\phpIMAP;

//Load Composer's autoloader
require 'vendor/autoload.php';

$phpIMAP = new phpIMAP("mail.domain.com","993","ssl","username@domain.com","*******************",true);

echo "Establishing Connection!\n";
// Check Connection Status
if($phpIMAP->isConnected()){
  echo "Connection Established!\n";
  // Retrieve INBOX
  $results = $phpIMAP->get();
  // Create a storage area for attachments
  $store = dirname(__FILE__) . '/tmp/';
  if(!is_dir($store)){mkdir($store);}
  $store .= 'imap/';
  if(!is_dir($store)){mkdir($store);}
  $store .= $phpIMAP->getUsername().'/';
  if(!is_dir($store)){mkdir($store);}
  // Output some information of the last message
  if(!empty($results->messages)){
    echo "Message Retrived!\n";
    echo "=========================================================================================\n";
    echo "ID: ".end($results->messages)->Header->message_id."\n";
    if(isset(end($results->messages)->Header->in_reply_to)){echo "REPLY-TO: ".end($results->messages)->Header->in_reply_to."\n";}
    if(isset(end($results->messages)->Header->references)){echo "REFERENCES: ".end($results->messages)->Header->references."\n";}
    if(isset(end($results->messages)->Subject->PLAIN)){echo "SUBJECT: ".end($results->messages)->Subject->PLAIN."\n";}
    if(isset(end($results->messages)->Attachments->Count)){echo "ATTACHMENTS: ".end($results->messages)->Attachments->Count."\n";}
    echo "#########################################################################################\n";
    echo end($results->messages)->Body->Unquoted."\n";
    echo "#########################################################################################\n";
    // Output all the attachements details
    foreach(end($results->messages)->Attachments->Files as $file){
      echo "-----------------------------------------------------------------------------------------\n";
      if(isset($file['filename'])){echo "FILENAME: ".$file['filename']."\n";}
      if(isset($file['name'])){echo "NAME: ".$file['name']."\n";}
      if(isset($file['bytes'])){echo "BYTES: ".$file['bytes']."\n";}
      // Create a storage area for message
      if(!is_dir($store.end($results->messages)->UID.'/')){mkdir($store.end($results->messages)->UID.'/');}
      if($path = $phpIMAP->saveAttachment($file,$store.end($results->messages)->UID.'/')){ echo "FILE: ".$path."\n"; }
    }
    echo "-----------------------------------------------------------------------------------------\n";
    print_r(end($results->messages)->Meta->References->Plain);
    echo "\n";
    print_r(end($results->messages)->Meta->References->Formatted);
    echo "\n";
  } else {echo "No Messages!\n";}
} else { echo "Connection Error!\n"; }
```
