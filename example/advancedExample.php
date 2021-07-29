<?php

// Import Class
require_once dirname(__FILE__) . '/src/lib/imap.php';

// Import Configurations
$settings=json_decode(file_get_contents(dirname(__FILE__) . '/settings.json'),true);

// Init Class
$IMAP = new apiIMAP($settings['imap']['host'],$settings['imap']['port'],$settings['imap']['encryption'],$settings['imap']['username'],$settings['imap']['password'],$settings['imap']['isSelfSigned']);

// Check Connection Status
if($IMAP->isConnected()){
  // Retrieve INBOX
  $results = $IMAP->get();
  // Create a storage area for attachments
  $store = dirname(__FILE__) . '/tmp/';
  if(!is_dir($store)){mkdir($store);}
  $store .= 'imap/';
  if(!is_dir($store)){mkdir($store);}
  $store .= $settings['imap']['username'].'/';
  if(!is_dir($store)){mkdir($store);}
  // Output ids and subject of all messages retrieved
  foreach($results->messages as $msg){
    echo "=========================================================================================\n";
    echo "ID: ".$msg->Header->message_id."\n";
    if(isset($msg->Header->in_reply_to)){echo "REPLY-TO: ".$msg->Header->in_reply_to."\n";}
    if(isset($msg->Header->references)){echo "REFERENCES: ".$msg->Header->references."\n";}
    if(isset($msg->Subject->PLAIN)){echo "SUBJECT: ".$msg->Subject->PLAIN."\n";}
    if(isset($msg->Attachments->Count)){echo "ATTACHMENTS: ".$msg->Attachments->Count."\n";}
    // Output the body of the message
    echo "#########################################################################################\n";
    echo $msg->Body->Unquoted;
    echo "#########################################################################################\n";
    // Output all the attachements details
    foreach($msg->Attachments->Files as $file){
      echo "-----------------------------------------------------------------------------------------\n";
      if(isset($file['filename'])){echo "FILENAME: ".$file['filename']."\n";}
      if(isset($file['name'])){echo "NAME: ".$file['name']."\n";}
      if(isset($file['bytes'])){echo "BYTES: ".$file['bytes']."\n";}
      // Create a storage area for message
      if(!is_dir($store.$msg->UID.'/')){mkdir($store.$msg->UID.'/');}
      $IMAP->saveAttachment($file,$store.$msg->UID.'/');
    }
  }
} else { echo $IMAP->Status; }
