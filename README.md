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

## How to

### Initialize
```php

//Import SMTP class into the global namespace
//These must be at the top of your script, not inside a function
use LaswitchTech\IMAP\phpIMAP;

//Load Composer's autoloader
require 'vendor/autoload.php';

$phpIMAP = new phpIMAP("imap.domain.com","993","ssl","username@domain.com","password");
```

### Methods
To make it easier, we will assume you have already initialized the class in ```$phpIMAP```.

#### getUsername()
If the IMAP connection is stored within the class, this method will return the username of the account connected.

##### Example
```php
$phpIMAP->getUsername();
// Return String or Null
```

#### getFolders()
If the IMAP connection is stored within the class, this method will return the list of available folders of the account connected.

##### Example
```php
$phpIMAP->getFolders();
// Return Array or Null
```

#### isConnected()
If the IMAP connection is stored within the class, this method will return true if a connection was established to the IMAP server.

##### Example
```php
if($phpIMAP->isConnected()){
  // Connection is Successful
}
```

#### buildConnectionString($host,$port = null,$encryption = null,$isSelfSigned = true)
This method is used to generate a connection string that imap_open() can read.

##### Example
```php
$phpIMAP->buildConnectionString("imap.domain.com",993,'ssl');
// Return String
```

#### connect($username = null,$password = null,$connection = null,$store = false)
This method is used to connect to a mailbox. Additionally a flag can be added to store the connection within the class for reuse.

##### Example
```php
$phpIMAP->connect("username@domain","password",$phpIMAP->buildConnectionString("imap.domain.com",993,'ssl'));
// Return IMAP Object
```

#### login($username,$password,$host = null,$port = null,$encryption = null,$isSelfSigned = true)
This method is used to test authentication on an IMAP Server.

##### Example
```php
$phpIMAP->login("username@domain","password","imap.domain.com",993,'ssl');
// Return BOOLEAN
```

#### close($IMAP = null)
If the IMAP connection is stored within the class and $IMAP is Null, this method will close the connection to the IMAP server.
If an IMAP connection is provided, it method will close the provided connection.

##### Example
```php
$phpIMAP->close();
// Return Null
```

#### get($IMAP = null, $Options = [])
This method is used to fetch a list of emails from an IMAP mailbox. You can supply a mailbox to check or use one stored when using the method connect(). Note that arguments can be interchanged.

##### Options
There are 3 options available.
 * folder: STRING containing the name of the folder to fetch. Default is "INBOX".
 * format: BOOLEAN indicating whether or not to format each retrieved message or not using the format() method. Default is false.
 * filter: STRING containing filters to apply to imap_search(). See [PHP.net](https://www.php.net/manual/en/function.imap-search.php) for the list of available criteria. Default is "ALL".

##### Example without formatting
```php
$object = $phpIMAP->get();
// Return Object
var_dump($object->messages);
// Return Array of Message IDs
```

##### Example with formatting
```php
$object = $phpIMAP->get(['format' => true]);
// Return Object

var_dump($object->messages);
// Return Array of Messages Objects

// Handling messages
end($object->messages)->ID; // ID of the message
end($object->messages)->UID; // UID of the message
end($object->messages)->Header; // Complete header information
end($object->messages)->From; // From email address
end($object->messages)->Sender; // Sender email address
end($object->messages)->To; // Array of the To addresses
end($object->messages)->CC; // Array of the CC addresses
end($object->messages)->BCC; // Array of the BCC addresses
end($object->messages)->Subject->Full; // Subject of the message
end($object->messages)->Subject->PLAIN; // Original subject
end($object->messages)->Body->Meta; // Message structure
end($object->messages)->Body->Content; // Message body (HTML if present otherwise plain text)
end($object->messages)->Body->Unquoted; // Message body without quote
end($object->messages)->Attachments; // Message attachments stored in an array

// Handling attachments
end(end($object->messages)->Attachments)['filename']; // filename of attachment
end(end($object->messages)->Attachments)['name']; // name of attachment
end(end($object->messages)->Attachments)['bytes']; // size of attachment in bytes
end(end($object->messages)->Attachments)['attachment']; // content of the attachment already decoded
end(end($object->messages)->Attachments)['encoding']; // encoding type of the attachment
```

##### Example with a specific folder
If you want to look in a specific folder:
```php
$phpIMAP->get(["folder" => "Sent"]);
```

#### format($List = [], $IMAP = null)
This method will format a list of message IDs and is use when the option ```format``` is set in the get() method. Note that you can also provide a single ID to the method to format a single message.

##### Example
```php
$object = $phpIMAP->get();
// Return Object

var_dump($object->messages);
// Return Array of Message IDs

var_dump($phpIMAP->format($object->messages));
// Return Array of Messages Objects

// Handling messages
end($object->messages)->ID; // ID of the message
end($object->messages)->UID; // UID of the message
end($object->messages)->Header; // Complete header information
end($object->messages)->From; // From email address
end($object->messages)->Sender; // Sender email address
end($object->messages)->To; // Array of the To addresses
end($object->messages)->CC; // Array of the CC addresses
end($object->messages)->BCC; // Array of the BCC addresses
end($object->messages)->Subject->Full; // Subject of the message
end($object->messages)->Subject->PLAIN; // Original subject
end($object->messages)->Body->Meta; // Message structure
end($object->messages)->Body->Content; // Message body (HTML if present otherwise plain text)
end($object->messages)->Body->Unquoted; // Message body without quote
end($object->messages)->Attachments; // Message attachments stored in an array

// Handling attachments
end(end($object->messages)->Attachments)['filename']; // filename of attachment
end(end($object->messages)->Attachments)['name']; // name of attachment
end(end($object->messages)->Attachments)['bytes']; // size of attachment in bytes
end(end($object->messages)->Attachments)['attachment']; // content of the attachment already decoded
end(end($object->messages)->Attachments)['encoding']; // encoding type of the attachment
```

#### read($UID,$IMAP = null)
This method simply set the SEEN flag to a message.

##### Example
```php
$phpIMAP->read(1);
// Return Null
```

#### delete($UID,$IMAP = null)
This method simply delete a message from the mailbox.

##### Example
```php
$phpIMAP->delete(1);
// Return TRUE or DIE Error
```

#### saveEml($eml, $IMAP = null)
This method save the content of an email to the specified mailbox.

##### Example
```php
$phpIMAP->saveEml("From: me@example.com\r\n"
                . "To: you@example.com\r\n"
                . "Subject: test\r\n"
                . "\r\n"
                . "this is a test message, please ignore\r\n");
// Return BOOLEAN
```

#### getEml($UID, $IMAP = null)
This method retrieves a message and returns a blob. This can be used to save messages as files(.eml).

##### Example
```php
$phpIMAP->getEml(1);
// Return BLOB
```

#### folder($folder, $IMAP = null)
This method is used to change folder within a mailbox.

##### Example
```php
$phpIMAP->folder('Sent');
// Return IMAP Object
```

#### saveAttachment($attachment,$directory)
This method is to save file attachments into a specified directory.

##### Example
```php
foreach(end($phpIMAP->get()->messages)->Attachments as $file){
  $phpIMAP->saveAttachment($file,"tmp/");
  // Return STRING containing the path of the file
}
```
