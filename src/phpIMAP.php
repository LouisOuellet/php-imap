<?php

//Declaring namespace
namespace LaswitchTech\IMAP;

//Import phpConfigurator class into the global namespace
use LaswitchTech\phpConfigurator\phpConfigurator;

//Import phpLogger class into the global namespace
use LaswitchTech\phpLogger\phpLogger;

//Import phpNet class into the global namespace
use LaswitchTech\phpNet\phpNet;

//Import Message class into the global namespace
use LaswitchTech\IMAP\Message;

//Import Exception class into the global namespace
use \Exception;

class phpIMAP{

	// Default Data Directory
	const DataDirectory = 'data/imap';

	// Default Log Prefix
	const Prefix = 'IMAP';

	// Logger
	private $Logger;
	private $Level = 1;

  // Configurator
  private $Configurator = null;

	// NetTools
	private $NetTools;

	// Saved Connection
	private $Connection = null;
	private $String = null;

	// Saved Connection Information
	private $Host = null;
	private $Port = null;
	private $Encryption = null;
	private $Flag = null;
	private $Username = null;
	private $Password = null;
	private $Folders = [];
	private $Folder = null;

	// Data Directory
	private $Directory = self::DataDirectory;

  /**
   * Create a new phpIMAP instance.
   *
   * @param  boolean|null  $debug
   * @return void
   */
	public function __construct(){

    // Initialize Configurator
    $this->Configurator = new phpConfigurator('imap');

    // Retrieve Log Level
    $this->Level = $this->Configurator->get('logger', 'level') ?: $this->Level;

    // Initiate phpLogger
    $this->Logger = new phpLogger('imap');

    // Initiate phpNet
    $this->NetTools = new phpNet();
	}

  /**
   * This method closes the IMAP connection when the object is destroyed.
   *
   * @return void
   */
	public function __destruct(){
		$this->close();
	}

  /**
   * Configure Library.
   *
   * @param  string  $option
   * @param  bool|int  $value
   * @return void
   * @throws Exception
   */
  public function config($option, $value){
		try {
			if(is_string($option)){
	      switch($option){
	        case"level":
	          if(is_int($value)){

							// Logging Level
	            $this->Level = $value;

							// Configure phpLogger
					    $this->Logger->config('level',$this->Level);

							// Configure phpNet
              $this->NetTools->config('level',$this->Level);
	          } else{
	            throw new Exception("2nd argument must be an integer.");
	          }
	          break;
	        default:
	          throw new Exception("unable to configure $option.");
	          break;
	      }
	    } else{
	      throw new Exception("1st argument must be as string.");
	    }
		} catch (Exception $e) {
			$this->Logger->error(self::Prefix . ' Error: '.$e->getMessage());
		}

    return $this;
  }

  /**
   * Connect to an IMAP Server.
   *
   * @param  string  $username
   * @param  string  $password
   * @param  string  $host
   * @param  string|int|null  $port
   * @param  string|null  $encryption
   * @param  boolean|null  $isSelfSigned
   * @return string|void
   * @throws Exception
   */
	public function connect($username, $password, $host, $port = 993, $encryption = 'ssl', $isSelfSigned = false){
		try {

			// If a connection is already established return it
			if ($this->Connection) {
				return $this->Connection;
			}

      // Checking for an open port
      if(!$this->NetTools->scan($host,$port)){
        throw new Exception("IMAP port on {$host} is closed or blocked.");
      }

			// Debug Information
			$this->Logger->debug(self::Prefix . " Host: {$host}");
			$this->Logger->debug(self::Prefix . " Port: {$port}");
			$this->Logger->debug(self::Prefix . " Encryption: {$encryption}");
			$this->Logger->debug(self::Prefix . " Flag: {$isSelfSigned}");
			$this->Logger->debug(self::Prefix . " Username: {$username}");
			$this->Logger->debug(self::Prefix . " Password: {$password}");

			// Build connection string
			$connectionString = $this->buildConnectionString($host, $port, $encryption, $isSelfSigned);
			$this->Logger->debug(self::Prefix . " Connection String: {$connectionString}");

			// Connect to IMAP server
			$Connection = imap_open($connectionString, $username, $password, OP_READONLY, 0);

			// Check if connection was established
			if($Connection){

				// Save the connection information
				$this->Connection = $Connection;
				$this->String = $connectionString;
				$this->Host = $host;
				$this->Port = $port;
				$this->Encryption = $encryption;
				$this->Flag = $isSelfSigned;
				$this->Username = $username;
				$this->Password = $password;
				$this->getFolders();

				// Log Success
				$this->Logger->success(self::Prefix . ' connection established');
			} else {
				throw new Exception("Unable to connect to the IMAP server");
			}

			// Return Connection
			return $Connection;
		} catch (Exception $e) {
			$this->Logger->error(self::Prefix . ' Error: '.$e->getMessage());
			return false;
		}
	}

  /**
   * This method closes the IMAP connection.
   *
   * @return void
   */
	public function close(){
		// Check if a connection exist
		if($this->Connection){

			// Close the active connection
			imap_close($this->Connection);

			// Clear any connection information
			$this->Connection = null;
			$this->String = null;
			$this->Host = null;
			$this->Port = null;
			$this->Encryption = null;
			$this->Flag = null;
			$this->Username = null;
			$this->Password = null;
			$this->Folders = [];
			$this->Folder = null;

			// Log the closing
			$this->Logger->success(self::Prefix . " connection closed");
		}
	}

  /**
   * Construct a connection string.
   *
   * @param  string  $host
   * @param  string|int|null  $port
   * @param  string|null  $encryption
   * @param  boolean|null  $isSelfSigned
   * @return string|void
   * @throws Exception
   */
	private function buildConnectionString($host, $port = 993, $encryption = 'ssl', $isSelfSigned = false){
		try {

			// Open connection string
			$connection = '{';

			// Add host
			if(is_string($host)){
				$connection .= $host.':';
			} else {
				throw new Exception("Invalid host");
			}

			// Add port
			if(is_string($port) || is_int($port)){
				$connection .= $port.'/imap';
			} else {
				throw new Exception("Invalid port");
			}

			// Add encryption
			if(is_string($encryption)){
				$encryption = strtolower($encryption);
				if(!in_array($encryption, ['ssl','tls','none'])){
					throw new Exception("Invalid encryption type");
				}
				if($encryption === 'none'){
					$encryption = 'notls';
				}
				$connection .= '/'.$encryption;
			} else {
				throw new Exception("Invalid encryption");
			}

			// Add Flag
			if(is_bool($isSelfSigned)){
				if($isSelfSigned){
					$connection .= '/novalidate-cert';
				}
			} else {
				throw new Exception("Invalid flag");
			}

			// Close connection string
			$connection .= '}';

			// Return connection string
			return $connection;
		} catch (Exception $e) {
			$this->Logger->error(self::Prefix . ' Error: '.$e->getMessage());
		}
	}

  /**
   * This method logs in to the IMAP server using the specified credentials and encryption type and can be use as an authentication method.
   *
   * @param  string  $username
   * @param  string  $password
   * @param  string  $newHost
   * @param  int|string|null  $newPort
   * @param  string|null  $newEncryption
   * @param  boolean|null  $newIsSelfSigned
   * @return boolean
   * @throws Exception
   */
	public function login($username,$password,$newHost = null,$newPort = 993,$newEncryption = 'ssl', $newIsSelfSigned = false){

		// Initiate variables
		$Connection = null;
		$String = null;
		$Host = null;
		$Port = null;
		$Encryption = null;
		$Flag = null;
		$Username = null;
		$Password = null;
		$Folders = [];

		// Check if a connection was already established. If so, let's store it for later.
		if($this->Connection){
			$Connection = $this->Connection;
			$String = $this->String;
			$Host = $this->Host;
			$Port = $this->Port;
			$Encryption = $this->Encryption;
			$Flag = $this->Flag;
			$Username = $this->Username;
			$Password = $this->Password;
			$Folders = $this->Folders;
			$Folder = $this->Folder;
		}

		// Check if a IMAP server connection information was provided.
		// If none are provided, and a connection already exist, use the same connection information as the one stored.
		if($newHost === null && $this->Connection){
			$newHost = $Host;
			$newPort = $Port;
			$newEncryption = $Encryption;
			$newIsSelfSigned = $Flag;
		}

		// Lets attempt a connection with the provided connection information
		$result = $this->connect($username,$password,$newHost,$newPort,$newEncryption,$newIsSelfSigned);
		$this->close();

		// Let's restore the saved connection
		if($Connection){
			$this->Connection = $Connection;
			$this->String = $String;
			$this->Host = $Host;
			$this->Port = $Port;
			$this->Encryption = $Encryption;
			$this->Flag = $Flag;
			$this->Username = $Username;
			$this->Password = $Password;
			$this->Folders = $Folders;
			$this->Folder = $Folder;
		}

		// Return
		return $result;
	}

  /**
   * This method return a boolean value indicating if a connection was established or not.
   *
   * @return boolean
   */
	public function isConnected(){
		return ($this->Connection);
	}

  /**
   * Get the Username of the active connection.
   *
   * @return string|void
   */
	public function getUsername(){
		if($this->isConnected()){
			return $this->Username;
		}
	}

  /**
   * Get the Host of the active connection.
   *
   * @return string|void
   */
	public function getHost(){
		if($this->isConnected()){
			return $this->Host;
		}
	}

  /**
   * Get the list of available folders.
   *
   * @return array|void
   * @throws Exception
   */
	public function getFolders(){
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
			$this->Logger->debug(self::Prefix . " Folders: " . PHP_EOL . json_encode($this->Folders, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

			// Return the list of available folders
			return $this->Folders;
		} catch (Exception $e) {
			$this->Logger->error(self::Prefix . ' Error: '.$e->getMessage());
		}
	}

	/**
	 * Change the current folder to a new one.
	 *
	 * @param  string  $folder
	 * @return boolean|void
	 * @throws Exception
	 */
	public function setFolder($folder){
		try {

      // Check if a connection was established
      if (!$this->isConnected()) {
        throw new Exception("No connection are established");
      }

			// Validate the folder
			if (!in_array($folder,$this->getFolders())) {
				throw new Exception("The folder {$folder} does not exist.");
			}

      // Open the folder
      if (!imap_reopen($this->Connection, $this->String . $folder, OP_READONLY, 0)) {
        throw new Exception("Unable to open folder {$folder}");
      }

			// Set Folder
			$this->Folder = $folder;

			// Debug Information
			$this->Logger->debug(self::Prefix . " Folder set to: {$this->Folder}");

			return true;
		} catch (Exception $e) {
			$this->Logger->error(self::Prefix . ' Error: '.$e->getMessage());
		}
	}

	/**
	 * Create a folder inside the mailbox.
	 *
	 * @param  string  $folder
	 * @return boolean|void
	 * @throws Exception
	 */
	public function createFolder($folder){
		try {

      // Check if a connection was established
      if (!$this->isConnected()) {
        throw new Exception("No connection are established");
      }

			// Create the folder
			if(imap_createmailbox($this->Connection, imap_utf7_encode($folder))){

				// Return True
				return true;
			} else {
				throw new Exception("Unable to create the folder {$folder}");
			}
		} catch (Exception $e) {
			$this->Logger->error(self::Prefix . ' Error: '.$e->getMessage());
		}
	}

	/**
	 * Delete a folder inside the mailbox.
	 *
	 * @param  string  $folder
	 * @return boolean|void
	 * @throws Exception
	 */
	public function deleteFolder($folder){
		try {

      // Check if a connection was established
      if (!$this->isConnected()) {
        throw new Exception("No connection are established");
      }

			// Validate the folder
			if (!in_array($folder,$this->getFolders())) {
				throw new Exception("The folder {$folder} does not exist.");
			}

			// Create the folder
			if(imap_deletemailbox($this->Connection, imap_utf7_encode($folder))){

				// Return True
				return true;
			} else {
				throw new Exception("Unable to delete the folder {$folder}");
			}
		} catch (Exception $e) {
			$this->Logger->error(self::Prefix . ' Error: '.$e->getMessage());
		}
	}

	/**
	 * Validate the search criteria.
	 *
	 * @param string|null $criteria
	 * @return bool
	 */
	private function validateSearchCriteria($criteria = null){
    // Validate the $criteria parameter.
    // Return true if $criteria is null.
    if (!$criteria) {
      return true;
    }

		// Return false if $criteria is not a string.
		if(!is_string($criteria)){
			return false;
		}

    // List of valid search criteria strings.
    $validCriteria = [
      'ALL',
      'ANSWERED',
      'BCC',
      'BEFORE',
      'BODY',
      'CC',
      'DELETED',
      'FLAGGED',
      'FROM',
      'KEYWORD',
      'NEW',
      'OLD',
      'ON',
      'RECENT',
      'SEEN',
      'SINCE',
      'SUBJECT',
      'TEXT',
      'TO',
      'UNANSWERED',
      'UNDELETED',
      'UNFLAGGED',
      'UNKEYWORD',
      'UNSEEN',
    ];

		// List of search criteria that requires a strings.
		$validString = [
      'BCC',
      'BEFORE',
      'BODY',
      'CC',
      'FROM',
      'KEYWORD',
      'ON',
      'SINCE',
      'SUBJECT',
      'TEXT',
      'TO',
      'UNKEYWORD',
		];

    // Split the $criteria string by spaces.
    $criteriaList = explode(' ', trim($criteria));

    // Check if each search criteria in the $criteriaList is valid.
		$skip = false;
    foreach($criteriaList as $key => $searchCriteria){

			// Debug Information
			$this->Logger->debug(self::Prefix . " Criteria Key: {$key}");
			$this->Logger->debug(self::Prefix . " Criteria: {$searchCriteria}");

			// Skip if requested
			if($skip){
				$skip = false;
				continue;
			}

			// Validate if it's a criteria
      if(!in_array($searchCriteria, $validCriteria)) {
        return false;
      }

			// Validate if it's suppose to come with some string
      if(in_array($searchCriteria, $validString)) {
        // Validate the presence of the string
				$nextKey = $key + 1;
				if(!isset($criteriaList[$nextKey])){
					return false;
				}

				// Validate the string
				if(substr($criteriaList[$nextKey], 0, 1) === '"' && substr($criteriaList[$nextKey], -1) === '"'){
					$skip = true;
				} else {
					$this->Logger->error(self::Prefix . " Error: {$searchCriteria} criteria has to be followed by a filter string.");
					return false;
				}
      }
    }

    // All search criteria are valid.
    return true;
	}

	/**
	 * Retrieve all messages in a folder and return as an associative array using UID as key.
	 *
	 * @param  string|null  $folder
	 * @param  string|null  $criteria
	 * @param  int|null  $limit
	 * @param  boolean|null  $reverse
	 * @return array|void
	 * @throws Exception
	 */
	public function getMessages($folder = null, $criteria = 'ALL', $limit = 0, $reverse = false) {
    try {
      // Check if a connection was established
      if (!$this->isConnected()) {
        throw new Exception("No connection are established");
      }

			// Validate the folder
			if($folder !== null && is_string($folder)){
				$this->setFolder($folder);
			}

			// Validate the criteria
			if (!$this->validateSearchCriteria($criteria)) {
		    throw new Exception("Invalid search criteria: $criteria");
			}

			// Debug Information
			// Log the Criteria
			$this->Logger->debug(self::Prefix . " Criteria: " . PHP_EOL . json_encode($criteria, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

			// Mailbox Info
			$info = imap_mailboxmsginfo($this->Connection);

			// Log the Mailbox Info
			$this->Logger->debug(self::Prefix . " Mailbox Info: " . PHP_EOL . json_encode($info, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

			// Search for all messages in the folder
			$uids = imap_search($this->Connection, $criteria, SE_UID);

      // Reverse order if needed
      if($reverse){
				$uids = array_reverse($uids);
      }

			// Debug Information
			$this->Logger->debug(self::Prefix . " Order Reversed");

      // Retrieve messages
      $messages = [];
			$count = 0;
			if(is_array($uids) && count($uids) > 0){
				foreach($uids as $uid){
					$count++;
					if($count <= $limit || $limit === 0){
						$message = imap_fetchheader($this->Connection, $uid, FT_UID) . imap_body($this->Connection, $uid, FT_UID);
						$directory = $this->Directory . DIRECTORY_SEPARATOR . $this->Username . DIRECTORY_SEPARATOR . $this->Folder;
						$message = new Message($uid, $message, $this->Connection, $this->Logger, $directory);
						$messages[] = $message;
					} else {
						break;
					}
				}
			}

			// Debug Information
			$this->Logger->debug(self::Prefix . " Messages: " . PHP_EOL . json_encode($messages, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

			$this->Logger->success(self::Prefix . " Messages Retrieved");

      // Return the list of messages
      return $messages;
    } catch (Exception $e) {
      $this->Logger->error(self::Prefix . ' Error: '.$e->getMessage());
    }
	}
}
