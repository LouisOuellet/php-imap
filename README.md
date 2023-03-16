![GitHub repo logo](/dist/img/logo.png)

# phpIMAP
![License](https://img.shields.io/github/license/LouisOuellet/php-imap?style=for-the-badge)
![GitHub repo size](https://img.shields.io/github/repo-size/LouisOuellet/php-imap?style=for-the-badge&logo=github)
![GitHub top language](https://img.shields.io/github/languages/top/LouisOuellet/php-imap?style=for-the-badge)
![Version](https://img.shields.io/github/v/release/LouisOuellet/php-imap?label=Version&style=for-the-badge)

## Description
phpIMAP is a PHP library that provides an easy-to-use interface for interacting with IMAP email servers. It includes two main classes: the Message class and the Attachment class.

The Message class allows you to retrieve and manipulate email messages from an IMAP server. It provides methods for retrieving the message headers, body, and attachments, as well as for marking messages as read or deleted. Additionally, it includes a constructor method that takes an email message as its input and parses it into a Message object.

The Attachment class is used to handle email attachments. It includes methods for identifying the attachment's filename, type, and encoding, as well as for retrieving and saving the attachment's content. This class is typically used in conjunction with the Message class's getAttachments method, which retrieves all attachments associated with a given email message.

## Features
 - Retrieve email messages from IMAP servers
 - Get details of email messages such as sender, recipient, subject, date, and more
 - Get the body of email messages in plain text or HTML format
 - Get attachments from email messages and save them to disk
 - Delete email messages from the server
 - Move email messages to different mailboxes on the server
 - Search for email messages on the server based on various criteria such as sender, recipient, subject, and date
 - Mark email messages as read or unread on the server
 - Flag email messages for follow-up on the server
 - Handle errors and exceptions gracefully with detailed error messages and logging
 - Customizable logging with phpLogger integration.

## Why you might need it
The library provides a simple and powerful way to work with IMAP email servers in PHP, allowing developers to easily retrieve, manipulate, and store email messages and attachments.

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

## How do I use it?
### Examples
### Initialize
```php

//Import SMTP class into the global namespace
//These must be at the top of your script, not inside a function
use LaswitchTech\IMAP\phpIMAP;

//Load Composer's autoloader
require 'vendor/autoload.php';

$phpIMAP = new phpIMAP();
$phpIMAP->connect("username@domain.com","*******************","imap.domain.com","993","ssl");
```

### Methods
To make it easier, we will assume you have already initialized the class in ```$phpIMAP```.

#### phpIMAP
##### config()
This method allows you to change some internal configurations such as log level.
 * 0: No Logging
 * 1: Error Logging
 * 2: Warning Logging
 * 3: Success Logging
 * 4: Info Logging
 * 5: Debug Logging

```php
$phpIMAP->config("level",5);
```

##### connect()
This method creates a connection to an IMAP server.
```php
$phpIMAP->connect("username@domain.com","*******************","imap.domain.com","993","ssl");
```

##### close()
This method close an active connection of an IMAP server.
```php
$phpIMAP->close();
```

##### login()
This method allows to test a connection to an IMAP server.
```php
$phpIMAP->login("username@domain.com","*******************","imap.domain.com","993","ssl");
```

##### isConnected()
This method return a boolean indicating if a connection is currently active or not.
```php
$phpIMAP->isConnected();
```

##### getUsername()
This method returns the username of the active connection.
```php
$phpIMAP->getUsername();
```

##### getHost()
This method returns the host of the active connection.
```php
$phpIMAP->getHost();
```

##### getFolders()
This method returns a list of folders available.
```php
$phpIMAP->getFolders();
```

##### setFolder()
This method selects a folder.
```php
$phpIMAP->setFolder("INBOX");
```

##### createFolder()
This method creates a folder.
```php
$phpIMAP->createFolder("New Folder");
```

##### deleteFolder()
This method deletes a folder.
```php
$phpIMAP->deleteFolder("Old Folder");
```

##### getMessages()
This method returns a list of messages in the selected folder.
```php
foreach($phpIMAP->getMessages() as $message){}
```

#### Message
##### getTo()
This provides all TO addresses of a message.
```php
$message->getTo();
```

##### getReplyTo()
This provides all REPLY-TO addresses of a message.
```php
$message->getReplyTo();
```

##### getFrom()
This provides all FROM addresses of a message.
```php
$message->getFrom();
```

##### getSender()
This provides all SENDER addresses of a message.
```php
$message->getSender();
```

##### getCc()
This provides all CC addresses of a message.
```php
$message->getCc();
```

##### getBcc()
This provides all BCC addresses of a message.
```php
$message->getBcc();
```

##### getUid()
This returns the UID of a message.
```php
$message->getUid();
```

##### getId()
This returns the Message-Id of a message.
```php
$message->getId();
```

##### getSubject()
This returns the Subject of a message.
```php
$message->getSubject();
```

##### getDate()
This returns the Date of a message.
```php
$message->getDate();
```

##### getBody()
This returns the Body of a message. HTML is returned if available, otherwise TEXT is returned.
```php
$message->getBody();
```

##### read()
Set `Seen` flag on message.
```php
$message->read();
```

##### unread()
Clear `Seen` flag on message.
```php
$message->unread();
```

##### flag()
Set `Flagged` flag on message.
```php
$message->flag();
```

##### unflag()
Clear `Flagged` flag on message.
```php
$message->unflag();
```

##### draft()
Set `Draft` flag on message.
```php
$message->draft();
```

##### undraft()
Clear `Draft` flag on message.
```php
$message->undraft();
```

##### answer()
Set `Answered` flag on message.
```php
$message->answer();
```

##### unanswer()
Clear `Answered` flag on message.
```php
$message->unanswer();
```

##### size()
Get the size of the message in bytes.
```php
$message->size();
```

##### isRead()
Check if the flag `Seen` is set on message.
```php
$message->isRead();
```

##### isFlagged()
Check if the flag `Flagged` is set on message.
```php
$message->isFlagged();
```

##### isRecent()
Check if the flag `Recent` is set on message.
```php
$message->isRecent();
```

##### isAnswered()
Check if the flag `Answered` is set on message.
```php
$message->isAnswered();
```

##### isDraft()
Check if the flag `Draft` is set on message.
```php
$message->isDraft();
```

##### isDeleted()
Check if the flag `Deleted` is set on message.
```php
$message->isDeleted();
```

##### delete()
This will delete the message.
```php
$message->delete();
```

##### copy()
Copy this message to a different folder.
```php
$message->copy("New Folder");
```

##### move()
Move this message to a different folder.
```php
$message->move("New Folder");
```

##### save()
This will save a message locally. Returns the file path.
```php
$message->save();
```

##### getAttachments()
This method returns a list of attachments in the message.
```php
foreach($message->getAttachments() as $attachment){}
```

#### Attachment
##### getDisposition()
This returns the Disposition of an attachment.
```php
$attachment->getDisposition();
```

##### getEncoding()
This returns the Encoding of an attachment.
```php
$attachment->getEncoding();
```

##### getId()
This returns the Content-Id of an attachment.
```php
$attachment->getId();
```

##### getFilename()
This returns the Filename of an attachment.
```php
$attachment->getFilename();
```

##### getFiletype()
This returns the Filetype of an attachment.
```php
$attachment->getFiletype();
```

##### getContentType()
This returns the Content-Type of an attachment.
```php
$attachment->getContentType();
```

##### getContent()
This returns the Content of an attachment as a blob of data. It will also decode the data if an encoding is found.
```php
$attachment->getContent();
```

##### save()
This will save an attachment locally. Returns the file path.
```php
$attachment->save();
```
