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
  // Saving attachments
  foreach($results->messages as $msg){
    // Output all the attachements details
    foreach($msg->Attachments->Files as $file){
      // Save File
      $IMAP->saveAttachment($file,$store);
    }
  }
} else { echo $IMAP->Status; }
