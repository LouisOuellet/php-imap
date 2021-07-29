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
  // Output the body of the last message
  echo "#########################################################################################\n";
  echo end($results->messages)->Body->Unquoted;
  echo "#########################################################################################\n";
  // Output ids and subject of all messages retrieved
  foreach($results->messages as $msg){
    echo "=========================================================================================\n";
    echo "ID: ".$msg->Header->message_id."\n";
    if(isset($msg->Header->in_reply_to)){echo "REPLY-TO: ".$msg->Header->in_reply_to."\n";}
    if(isset($msg->Header->references)){echo "REFERENCES: ".$msg->Header->references."\n";}
    if(isset($msg->Subject->PLAIN)){echo "SUBJECT: ".$msg->Subject->PLAIN."\n";}
    // Output all the attachements details
    foreach($msg->Attachments->Files as $file){
      echo "-----------------------------------------------------------------------------------------\n";
      if(isset($file['filename'])){echo "FILENAME: ".$file['filename']."\n";}
      if(isset($file['name'])){echo "NAME: ".$file['name']."\n";}
      if(isset($file['bytes'])){echo "BYTES: ".$file['bytes']."\n";}
    }
  }
} else { echo $IMAP->Status; }
