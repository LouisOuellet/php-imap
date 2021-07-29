<?php

// Import Class
require_once dirname(__FILE__) . '/src/lib/imap.php';

// Import Configurations
$settings=json_decode(file_get_contents(dirname(__FILE__) . '/settings.json'),true);

// Init Class
$IMAP = new PHPIMAP($settings['imap']['host'],$settings['imap']['port'],$settings['imap']['encryption'],$settings['imap']['username'],$settings['imap']['password'],$settings['imap']['isSelfSigned']);

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
  // Saving attachments
  foreach($results->messages as $msg){
    // Output all the attachements details
    foreach($msg->Attachments->Files as $file){
      // Create a storage area for message
      if(!is_dir($store.$msg->UID.'/')){mkdir($store.$msg->UID.'/');}
      // Save File
      if($path = $IMAP->saveAttachment($file,$store.$msg->UID.'/')){ echo "Saved in ".$path; }
    }
  }
} else { echo $IMAP->Status; }
