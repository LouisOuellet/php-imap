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
  // Output the body of the last message
  if(!empty($results->messages)){echo end($results->messages)->Body->Unquoted;}
  else{echo "No message!";}
} else { echo $IMAP->Status; }
