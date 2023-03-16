<?php

//Declaring namespace
namespace LaswitchTech\IMAP;

//Import phpLogger class into the global namespace
use LaswitchTech\phpLogger\phpLogger;

//Import Attachment class into the global namespace
use LaswitchTech\IMAP\Attachment;

//Import Exception class into the global namespace
use \Exception;

class Message{

	// Default Data Directory
	const DataDirectory = 'data/imap/emls';

	// List of Flags
	const Flags = ['Seen', 'Answered', 'Flagged', 'Deleted', 'Draft'];

	// Logger
	private $Logger;

	// Connection
	private $Connection = null;
	private $Username = null;
	private $Folders = [];
	private $Folder = null;

	// Message
	private $Message = null;
	private $Headers = null;
	private $Overview = null;
	private $UID = null;
	private $MID = null;
	private $Date = null;
	private $To = null;
	private $From = null;
	private $Sender = null;
	private $Cc = null;
	private $Bcc = null;
	private $ReplyTo = null;
	private $Subject = null;
	private $Body = null;

	// Data Directory
	private $Directory = self::DataDirectory;

  /**
   * Create a new Message instance.
   *
   * @param  boolean|null  $debug
   * @return void
   */
  public function __construct($UID, $Message, $Connection, $Logger, $Directory){
    $this->UID = $UID;
    $this->Message = $Message;
    $this->Connection = $Connection;
    $this->Logger = $Logger;
    $this->Directory = $Directory;
  }

  /**
   * This method return a boolean value indicating if a connection was established or not.
   *
   * @return boolean
   */
	private function isConnected(){
		return ($this->Connection);
	}

  /**
   * Get the list of available folders.
   *
   * @return array|void
   * @throws Exception
   */
	private function getFolders(){
		try {

			// Check if a connection was established
			if(!$this->isConnected()){
				throw new Exception("No connection are established");
			}

			// Return the existing folders if already retrieved
			if(count($this->Folders) > 0){
				return $this->Folders;
			}

			// Get the list of available folders
			$this->Folders = imap_list($this->Connection, $this->String, '*');

			// Validate Folders
			if(!$this->Folders) {
				$this->Folders = array();
			}

			// Sanitize Folders
			$Folders = [];
			foreach($this->Folders as $Folder){
				$Folders[] = str_replace($this->String,'',$Folder);
			}
			$this->Folders = $Folders;

			// Debug Information
			$this->Logger->debug("IMAP Folders: " . PHP_EOL . json_encode($this->Folders, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

			// Return the list of available folders
			return $this->Folders;
		} catch (Exception $e) {
			$this->Logger->error('IMAP Error: '.$e->getMessage());
		}
	}

	/**
	 * Get the headers of a message.
	 *
	 * @return array An associative array of the headers.
	 */
	private function getHeaders(){

    // Check if message headers was already retrieved
    if($this->Headers){

      // Return Headers
      return $this->Headers;
    } else {
      $headers = imap_rfc822_parse_headers($this->Message);
      $result = array();

      foreach ($headers as $header => $value) {
        if (is_array($value)) {
          foreach ($value as $subvalue) {
            $result[$header][] = $subvalue;
          }
        } else {
          $result[$header] = $value;
        }
      }

  		// Debug Information
  		$this->Logger->debug("IMAP Message Headers: " . PHP_EOL . json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

      // Store Headers
      $this->Headers = $result;

      // Return Headers
      return $this->Headers;
    }
	}

	/**
	 * Get the To addresses of a message.
	 *
	 * @return array|void
	 */
	public function getTo(){

    // Check if message To was already retrieved
    if($this->To){

      // Return To
      return $this->To;
    } else {
      // Retrieve Headers
      $headers = $this->getHeaders();

      // Check if headers contains To
      if(isset($headers['to'])){

        // Initialize To
        $To = [];

        // Sanitize To
        foreach($headers['to'] as $array){

          // Convert to array
          $array = json_decode(json_encode($array),true);

          // Add Address
          if(isset($array['mailbox'],$array['host'])){
            $To[] = $array['mailbox'] . '@' . $array['host'];
          }
        }

    		// Debug Information
        $this->Logger->debug("IMAP Message To: " . PHP_EOL . json_encode($To, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        // Store To
        $this->To = $To;

        // Return To
        return $this->To;
      } else {
        throw new Exception("Unable to retrieve the message To");
      }
    }
	}

	/**
	 * Get the ReplyTo addresses of a message.
	 *
	 * @return array|void
	 */
	public function getReplyTo(){

    // Check if message ReplyTo was already retrieved
    if($this->ReplyTo){

      // Return ReplyTo
      return $this->ReplyTo;
    } else {
      // Retrieve Headers
      $headers = $this->getHeaders();

      // Check if headers contains ReplyTo
      if(isset($headers['reply_to'])){

        // Initialize ReplyTo
        $ReplyTo = [];

        // Sanitize ReplyTo
        foreach($headers['reply_to'] as $array){

          // Convert to array
          $array = json_decode(json_encode($array),true);

          // Add Address
          if(isset($array['mailbox'],$array['host'])){
            $ReplyTo[] = $array['mailbox'] . '@' . $array['host'];
          }
        }

    		// Debug Information
        $this->Logger->debug("IMAP Message ReplyTo: " . PHP_EOL . json_encode($ReplyTo, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        // Store ReplyTo
        $this->ReplyTo = $ReplyTo;

        // Return ReplyTo
        return $this->ReplyTo;
      } else {
        // Store ReplyTo
        $this->ReplyTo = [];

        // Debug Information
        $this->Logger->debug("IMAP Message ReplyTo: " . PHP_EOL . json_encode($this->ReplyTo, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        // Return ReplyTo
        return $this->ReplyTo;
      }
    }
	}

	/**
	 * Get the From addresses of a message.
	 *
	 * @return array|void
	 */
	public function getFrom(){

    // Check if message From was already retrieved
    if($this->From){

      // Return From
      return $this->From;
    } else {
      // Retrieve Headers
      $headers = $this->getHeaders();

      // Check if headers contains From
      if(isset($headers['from'])){

        // Initialize From
        $From = [];

        // Sanitize From
        foreach($headers['from'] as $array){

          // Convert to array
          $array = json_decode(json_encode($array),true);

          // Add Address
          if(isset($array['mailbox'],$array['host'])){
            $From[] = $array['mailbox'] . '@' . $array['host'];
          }
        }

    		// Debug Information
        $this->Logger->debug("IMAP Message From: " . PHP_EOL . json_encode($From, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        // Store From
        $this->From = $From;

        // Return From
        return $this->From;
      } else {
        throw new Exception("Unable to retrieve the message From");
      }
    }
	}

	/**
	 * Get the Sender addresses of a message.
	 *
	 * @return array|void
	 */
	public function getSender(){

    // Check if message Sender was already retrieved
    if($this->Sender){

      // Return Sender
      return $this->Sender;
    } else {
      // Retrieve Headers
      $headers = $this->getHeaders();

      // Check if headers contains Sender
      if(isset($headers['sender'])){

        // Initialize Sender
        $Sender = [];

        // Sanitize Sender
        foreach($headers['sender'] as $array){

          // Convert to array
          $array = json_decode(json_encode($array),true);

          // Add Address
          if(isset($array['mailbox'],$array['host'])){
            $Sender[] = $array['mailbox'] . '@' . $array['host'];
          }
        }

    		// Debug Information
        $this->Logger->debug("IMAP Message Sender: " . PHP_EOL . json_encode($Sender, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        // Store Sender
        $this->Sender = $Sender;

        // Return Sender
        return $this->Sender;
      } else {
        throw new Exception("Unable to retrieve the message Sender");
      }
    }
	}

	/**
	 * Get the Cc addresses of a message.
	 *
	 * @return array|void
	 */
	public function getCc(){

    // Check if message Cc was already retrieved
    if($this->Cc){

      // Return Cc
      return $this->Cc;
    } else {
      // Retrieve Headers
      $headers = $this->getHeaders();

      // Check if headers contains Cc
      if(isset($headers['cc'])){

        // Initialize Cc
        $Cc = [];

        // Sanitize Cc
        foreach($headers['cc'] as $array){

          // Convert to array
          $array = json_decode(json_encode($array),true);

          // Add Address
          if(isset($array['mailbox'],$array['host'])){
            $Cc[] = $array['mailbox'] . '@' . $array['host'];
          }
        }

    		// Debug Information
        $this->Logger->debug("IMAP Message Cc: " . PHP_EOL . json_encode($Cc, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        // Store Cc
        $this->Cc = $Cc;

        // Return Cc
        return $this->Cc;
      } else {

        // Store Cc
        $this->Cc = [];

        // Debug Information
        $this->Logger->debug("IMAP Message Cc: " . PHP_EOL . json_encode($this->Cc, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        // Return Cc
        return $this->Cc;
      }
    }
	}

	/**
	 * Get the Cc addresses of a message.
	 *
	 * @return array|void
	 */
	public function getBcc(){

    // Check if message Bcc was already retrieved
    if($this->Bcc){

      // Return Bcc
      return $this->Bcc;
    } else {
      // Retrieve Headers
      $headers = $this->getHeaders();

      // Check if headers contains Bcc
      if(isset($headers['bcc'])){

        // Initialize Bcc
        $Bcc = [];

        // Sanitize Bcc
        foreach($headers['bcc'] as $array){

          // Convert to array
          $array = json_decode(json_encode($array),true);

          // Add Address
          if(isset($array['mailbox'],$array['host'])){
            $Bcc[] = $array['mailbox'] . '@' . $array['host'];
          }
        }

    		// Debug Information
        $this->Logger->debug("IMAP Message Bcc: " . PHP_EOL . json_encode($Bcc, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        // Store Bcc
        $this->Bcc = $Bcc;

        // Return Bcc
        return $this->Bcc;
      } else {

        // Store Bcc
        $this->Bcc = [];

        // Debug Information
        $this->Logger->debug("IMAP Message Bcc: " . PHP_EOL . json_encode($this->Bcc, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        // Return Bcc
        return $this->Bcc;
      }
    }
	}

	/**
	 * Get the uid of a message.
	 *
	 * @return string|void
	 */
	public function getUid(){

    // Check if message id was already retrieved
    if($this->UID){

      // Debug Information
      $this->Logger->debug("IMAP Message UID: {$this->UID}");

      // Return Uid
      return $this->UID;
    } else {
      throw new Exception("Unable to retrieve the message uid");
    }
	}

	/**
	 * Get the id of a message.
	 *
	 * @return string|void
	 */
	public function getId(){

    // Check if message id was already retrieved
    if($this->MID){

      // Return Id
      return $this->MID;
    } else {
      // Retrieve Headers
      $headers = $this->getHeaders();

      if(isset($headers['message_id'])){

        // Sanitize Id
        $id = $headers['message_id'];
        $id = trim($id,'<');
        $id = trim($id,'>');

    		// Debug Information
    		$this->Logger->debug("IMAP Message Id: {$id}");

        // Store Id
        $this->MID = $id;

        // Return Id
        return $this->MID;
      } else {
        throw new Exception("Unable to retrieve the message id");
      }
    }
	}

	/**
	 * Get the subject of a message.
	 *
	 * @return string|void
	 */
	public function getSubject(){

    // Check if message Subject was already retrieved
    if($this->Subject){

      // Return Subject
      return $this->Subject;
    } else {
      // Retrieve Headers
      $headers = $this->getHeaders();

      if(isset($headers['subject'],$headers['Subject'])){

        // Initialize Subject
        $Subject = null;

        // Find Subject
        if(isset($headers['subject'])){
          $Subject = $headers['subject'];
        }
        if(isset($headers['Subject'])){
          $Subject = $headers['Subject'];
        }

        // Sanitize Subject
        $Subject = trim($Subject);

    		// Debug Information
    		$this->Logger->debug("IMAP Message Subject: {$Subject}");

        // Store Subject
        $this->Subject = $Subject;

        // Return Subject
        return $this->Subject;
      } else {
        throw new Exception("Unable to retrieve the message subject");
      }
    }
	}

	/**
	 * Get the date of a message.
	 *
	 * @return string|void
	 */
	public function getDate(){

    // Check if message date was already retrieved
    if($this->Date){

      // Return Date
      return $this->Date;
    } else {
      // Retrieve Headers
      $headers = $this->getHeaders();

      if(isset($headers['date'],$headers['Date'])){

        // Initialize Date
        $Date = null;

        // Find Date
        if(isset($headers['date'])){
          $Date = $headers['date'];
        }
        if(isset($headers['Date'])){
          $Date = $headers['Date'];
        }

        // Sanitize Date
        $Date = trim($Date);

    		// Debug Information
    		$this->Logger->debug("IMAP Message Date: {$Date}");

        // Store Date
        $this->Date = $Date;

        // Return Date
        return $this->Date;
      } else {
        throw new Exception("Unable to retrieve the message date");
      }
    }
	}

	/**
	 * Retrieve the body of a message.
	 *
	 * @return array|null
	 * @throws Exception
	 */
	private function getParts($message = null) {

		try{

      // Check if an argument was supplied
      if($message === null){
        $message = $this->Message;
      }

      // Check if a string was provided
      if(!is_String($message)){
        throw new Exception("An error occured while parsing this message");
      }

			// Find the content type header and extract the boundary.
			$boundary = null;
			$partsArray = [];
			$multipart = preg_match('/boundary="?(.*?)"?(\s|$)/i', $message, $matches);
			if($multipart) {
	      $boundary = $matches[1];
			}

			// Debug Information
			$this->Logger->debug("boundary: {$boundary}");

			// If this is a multipart message, retrieve
			if($multipart){
	      // Split the message into parts based on the boundary.
	      $parts = preg_split('/--' . preg_quote($boundary, '/') . '\r?\n/', $message);

        // Debug Information
        $this->Logger->debug("Number of parts: " . count($parts));

				// Sanitize parts
				foreach($parts as $key => $part){
					$part = trim(str_replace('--' . $boundary . '--','',$part));
					$part = trim(str_replace('--' . $boundary,'',$part));
					$parts[$key] = $part;
				}

				// Identify the contentType of each parts
				$contentTypes = [];
				foreach($parts as $key => $part){

					// Find the content type of the part
		      preg_match('/Content-Type:\s*([^\s;]+)/i', $part, $matches);

					// Filter contentTypes
		      if(isset($matches[1]) && strpos($matches[1], "/") !== false){
	          $contentType = strtolower(trim($matches[1]));

						// Check Recursively
						if(strpos($contentType, "multipart") !== false){

							// Debug Information
							$this->Logger->debug("multipart: {$matches[0]}");

							// Sanitize $part
							$part = trim($part);
							$part = trim($part,"Content-Type:");
							$part = trim($part);
							$part = trim($part,$matches[0]);
							$part = trim($part);
							$part = trim($part,';');
							$part = trim($part);

							// Debug Information
              $this->Logger->debug("Sanitized \$part: " . PHP_EOL . $part);

							// Run Recursively
							foreach($this->getParts($part) as $innerPart){

								// Add to partsArray
								$partsArray[] = $innerPart;
							}
						} else {

							// Debug Information
							$this->Logger->debug("other: {$contentType}");

							// Add to partsArray
							$partsArray[] = trim($part);
						}
		      }
				}
			} else {

				// Remove everything before the word.
				$part = strstr($message, 'Content-Type:');

				// Add to partsArray
				$partsArray[] = trim($part);
			}

			// Debug Information
			$this->Logger->debug("partsArray: " . PHP_EOL . json_encode($partsArray, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

			// Return $partsArray
			return $partsArray;
		} catch (Exception $e) {
			$this->Logger->error('IMAP Error: '.$e->getMessage());
		}
	}

	/**
	 * Retrieve the body of a message.
	 *
	 * @return string|null
	 * @throws Exception
	 */
	public function getBody() {

    // Check if message body was already retrieved
    if($this->Body){

      // Return Body
      return $this->Body;
    } else {
      try{

  			// Initialize $bodies
  			$bodies = [];

  			// Find Message Parts
  			$parts = $this->getParts($this->Message);

  			// Find Body Parts
  			foreach($parts as $part){
  				// Find the content type of the part
  				preg_match('/Content-Type:\s*([^\s;]+)/i', $part, $matches);
  				if(isset($matches[1])){
  					$contentType = strtolower(trim($matches[1]));

  					// Debug Information
  					$this->Logger->debug("contentType: {$contentType}");

  					// Keep body parts if usefull
  					if(in_array($contentType,['text/plain','text/html'])){
  						$bodies[$contentType] = $part;
  					}
  				}
  			}

  			// Select a part as the message
  			if(isset($bodies['text/html'])){
  				$content = $bodies['text/html'];
  			} else {
  				if(isset($bodies['text/plain'])){
  					$content = $bodies['text/plain'];
  				} else {
  					throw new Exception("Unable to identify the body");
  				}
  			}

  			// Split the message into an array of lines
  			$lines = preg_split('/\r\n|\r|\n/', $content);

  			// Debug Information
  			$this->Logger->debug("IMAP Message Lines: " . PHP_EOL . json_encode($lines, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

  			// Find the first line of the body
  			$start = false;
  			$body = '';
  			foreach($lines as $line){
  				if($line !== null && $line === ""){
  					// Found the start of the body
  					$start = true;
  				}
  				if($start){
  					// Concatenate the lines to form the body
  					$body .= $line . PHP_EOL;
  				}
  			}

  			// Trim $body
  			$body = trim($body);

  			// Debug Information
  			$this->Logger->debug("IMAP Message Body: " . PHP_EOL . $body);

        // Store Body
        $this->Body = $body;

  			// Return Body
  			return $this->Body;
  		} catch (Exception $e) {
  			$this->Logger->error('IMAP Error: '.$e->getMessage());
  		}
    }
	}

	/**
	 * Get Overview of message.
	 *
	 * @param  boolean  $refresh
	 * @return array Returns the list of flags.
	 * @throws Exception
	 */
	private function getOverview($refresh = false) {
		try {

			// Check if Overview was already retrieved
			if($this->Overview && !$refresh){

				// Return Overview
				return $this->Overview;
			}

			// Get the Overview of the message
			$Overview = imap_fetch_overview($this->Connection, $this->getUid(), FT_UID);

			// Check if anything was returned
			if($Overview){

				// Sanitize Overview
				$Overview = current($Overview);
				$Overview = [
					"size" => $Overview->size,
					"recent" => $Overview->recent,
					"flagged" => $Overview->flagged,
					"answered" => $Overview->answered,
					"deleted" => $Overview->deleted,
					"seen" => $Overview->seen,
					"draft" => $Overview->draft,
				];

  			// Debug Information
				$this->Logger->debug("IMAP Message Overview: " . PHP_EOL . json_encode($Overview, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        // Store Overview
        $this->Overview = $Overview;

  			// Return Overview
  			return $this->Overview;
			} else {
				throw new Exception("Unable to retrieve Overview");
			}
		} catch (Exception $e) {
			$this->Logger->error('IMAP Error: '.$e->getMessage());
		}
	}

	/**
	 * Set flag to message.
	 *
	 * @param  string  $flag
	 * @return bool Returns true on success, false on failure.
	 * @throws Exception
	 */
	private function setFlag($flag) {
		try {

      // Validate Flag
      if(!in_array($flag,self::Flags)){
        throw new Exception("Invalid flag: {$flag}");
      }

			// Set the SEEN flag on the message identified by the UID.
	    $result = imap_setflag_full($this->Connection, $this->UID, "\\{$flag}", ST_UID);

	    // Return true if the SEEN flag was successfully set, false otherwise.
	    return $result !== false;
		} catch (Exception $e) {
			$this->Logger->error('IMAP Error: '.$e->getMessage());
		}
	}

	/**
	 * Clear flag of message.
	 *
	 * @param  string  $flag
	 * @return bool Returns true on success, false on failure.
	 * @throws Exception
	 */
	private function clearFlag($flag) {
		try {

      // Validate Flag
      if(!in_array($flag,self::Flags)){
        throw new Exception("Invalid flag: {$flag}");
      }

	    // Set the SEEN flag to false.
	    $result = imap_clearflag_full($this->Connection, $this->UID, "\\{$flag}", ST_UID);

	    // Return true if the flag was set successfully.
	    return $result;
		} catch (Exception $e) {
			$this->Logger->error('IMAP Error: '.$e->getMessage());
		}
	}

	/**
	 * Mark a message as read.
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function read() {
    return $this->setFlag('Seen');
	}

	/**
	 * Mark a message as unread.
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function unread() {
    return $this->clearFlag('Seen');
	}

	/**
	 * Mark a message as flagged.
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function flag() {
    return $this->setFlag('Flagged');
	}

	/**
	 * Mark a message as unflagged.
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function unflag() {
    return $this->clearFlag('Flagged');
	}

	/**
	 * Mark a message as draft.
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function draft() {
    return $this->setFlag('Draft');
	}

	/**
	 * Mark a message as undraft.
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function undraft() {
    return $this->clearFlag('Draft');
	}

	/**
	 * Mark a message as answered.
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function answer() {
    return $this->setFlag('Answered');
	}

	/**
	 * Mark a message as unanswered.
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function unanswer() {
    return $this->clearFlag('Answered');
	}

	/**
	 * Get the size of the message.
	 *
	 * @return int Returns size in bytes.
	 */
	public function size() {
    return $this->getOverview()['size'];
	}

	/**
	 * Check if a message is read.
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function isRead() {
    return $this->getOverview()['seen'];
	}

	/**
	 * Check if a message is flagged.
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function isFlagged() {
    return $this->getOverview()['flagged'];
	}

	/**
	 * Check if a message is recent.
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function isRecent() {
    return $this->getOverview()['recent'];
	}

	/**
	 * Check if a message is answered.
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function isAnswered() {
    return $this->getOverview()['answered'];
	}

	/**
	 * Check if a message is draft.
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function isDraft() {
    return $this->getOverview()['draft'];
	}

	/**
	 * Check if a message is deleted.
	 *
	 * @return bool Returns true on success, false on failure.
	 */
	public function isDeleted() {
    return $this->getOverview()['deleted'];
	}

	/**
	 * Delete a message.
	 *
	 * @return bool Returns true on success, false on failure.
	 * @throws Exception
	 */
	public function delete(){
		try {
			// Mark the message as deleted
	    $result = imap_delete($this->Connection, $this->UID, FT_UID);

	    if ($result) {
	      // Expunge deleted messages
	      imap_expunge($this->Connection);
	    }

	    return $result;
		} catch (Exception $e) {
			$this->Logger->error('IMAP Error: '.$e->getMessage());
		}
	}

	/**
	 * Copy this message to a different folder.
	 *
	 * @param  string  $folder
	 * @return bool
	 */
	public function copy($folder){
		try {

			// Check if the connection is still active
      if (!$this->isConnected()) {
        throw new Exception("No connection are established");
      }

			// Validate Folder
      if(!is_string($folder)) {
        throw new Exception("This folder is invalid");
      }
      if(!in_array($folder,$this->getFolders())) {
        throw new Exception("This folder does not exist");
      }

			// Copy the message
			$result = imap_mail_copy($this->Connection, $this->getUid(), $folder, CP_UID);

			if($result){
				// Expunge deleted messages
	      imap_expunge($this->Connection);

				// Return true
				return true;
			} else {
				throw new Exception("Unable to copy message into the folder specified");
			}

		} catch (Exception $e) {

			// Log error
      $this->Logger->error('IMAP Error: '.$e->getMessage());
			return false;
	  }
	}

	/**
	 * Move this message to a different folder.
	 *
	 * @param  string  $folder
	 * @return bool
	 */
	public function move($folder){
		try {

			// Check if the connection is still active
      if (!$this->isConnected()) {
        throw new Exception("No connection are established");
      }

			// Validate Folder
      if(!is_string($folder)) {
        throw new Exception("This folder is invalid");
      }
      if(!in_array($folder,$this->getFolders())) {
        throw new Exception("This folder does not exist");
      }

			// Move the message
			$result = imap_mail_move($this->Connection, $this->getUid(), $folder, CP_UID);

			if($result){
				// Expunge deleted messages
	      imap_expunge($this->Connection);

				// Return true
				return true;
			} else {
				throw new Exception("Unable to copy message into the folder specified");
			}

		} catch (Exception $e) {

			// Log error
      $this->Logger->error('IMAP Error: '.$e->getMessage());
			return false;
	  }
	}

	/**
	 * Save the email in the specified directory.
	 *
	 * @return string|boolean File path if the file was saved successfully, false otherwise.
   * @throws Exception
	 */
	public function save() {
		try{

			// Generate directory
      $directory = $this->Directory . DIRECTORY_SEPARATOR . $this->getUid();

			// Generate file path
	    $filepath = $directory . DIRECTORY_SEPARATOR . $this->getId() . ".eml";

			// Create the directory recursively if it doesn't exist
      if (!is_dir($directory)) {
        if (!mkdir($directory, 0777, true)) {
          throw new Exception("Unable to create directory {$directory}");
        }
      }

			// Validate the directory and create the file
      if (is_dir($directory) && is_writable($directory)) {
        if(!is_file($filepath)){
          if (file_put_contents($filepath, trim($this->Message))) {
            $this->Logger->success("File Saved: {$filepath}");
            return $filepath;
          } else {
            throw new Exception("Unable to create {$filepath}");
          }
        } else {
          throw new Exception("File {$filepath} already exist.");
        }
      } else {
        throw new Exception("Invalid directory specified: {$directory}");
      }
		} catch (Exception $e) {

			// Log error
      $this->Logger->error('IMAP Error: '.$e->getMessage());
      return false;
	  }
	}

	/**
	 * Retrieve the attachments of a message.
	 *
	 * @return string|null
	 * @throws Exception
	 */
	public function getAttachments() {
		try{

			// Initialize $files
			$files = [];

			// Find Message Parts
			$parts = $this->getParts($this->Message);

			// Identify the files
			foreach($parts as $key => $part){

				// Check if it's a file
				if(preg_match('/Content-Disposition: "?(.*?)"?(\s|$)/i', $part, $dispositions)){
					if(isset($dispositions[1])){
						$disposition = trim($dispositions[1]);
						$disposition = trim($disposition,';');
						$disposition = trim($disposition);

						// If it's a file
						if(in_array($disposition,["attachment","inline"])){

              // Save it
              $directory = $this->Directory . DIRECTORY_SEPARATOR . $this->getUid();
              $files[] = new Attachment($part, $this->Connection, $this->Logger, $directory);
						}
					}
				}
			}

			// Debug Information
			$this->Logger->debug("files: " . PHP_EOL . json_encode($files, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

			// Return $files
			return $files;
		} catch (Exception $e) {
			$this->Logger->error('IMAP Error: '.$e->getMessage());
		}
	}
}
