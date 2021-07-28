<?php

// Import Librairies
require_once dirname(__FILE__) . '/src/lib/imap.php';

// Import Configurations
$settings=json_decode(file_get_contents(dirname(__FILE__) . '/settings.json'),true);

// Adding Librairies
$IMAP = new apiIMAP($settings['imap']['host'],$settings['imap']['port'],$settings['imap']['encryption'],$settings['imap']['username'],$settings['imap']['password'],$settings['imap']['isSelfSigned']);

// Check Connection Status
if(is_bool($IMAP->Status) && $IMAP->Status){
  // Retrieve INBOX
  $results = $IMAP->get();
} else { echo $IMAP->Status."\n"; }
