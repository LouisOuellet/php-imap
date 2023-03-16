<?php

//Declaring namespace
namespace LaswitchTech\IMAP;

//Import phpLogger class into the global namespace
use LaswitchTech\phpLogger\phpLogger;

//Import Exception class into the global namespace
use \Exception;

class Attachment{

	// Default Data Directory
	const DataDirectory = 'data/imap/attachements';

	// Logger
	private $Logger;

	// Connection
	private $Connection = null;

	// Attachment
	private $Attachment = null;
	private $Filename = null;
	private $Filetype = null;
	private $Disposition = null;
	private $Encoding = null;
	private $Id = null;
	private $Content = null;
	private $ContentType = null;

	// Data Directory
	private $Directory = self::DataDirectory;

  /**
   * Create a new Message instance.
   *
   * @param  boolean|null  $debug
   * @return void
   */
  public function __construct($Attachment, $Connection, $Logger, $Directory){
    $this->Attachment = $Attachment;
    $this->Connection = $Connection;
    $this->Logger = $Logger;
    $this->Directory = $Directory;
  }

	/**
	 * Identify the disposition of this attachement.
	 *
	 * @return string|void
   * @throws Exception
	 */
	public function getDisposition(){

    // Check if Disposition was already retrieved
    if($this->Disposition){

      // Return Disposition
      return $this->Disposition;
		} else {
			try{
				if(preg_match('/Content-Disposition: "?(.*?)"?(\s|$)/i', $this->Attachment, $results)){
					if(isset($results[1])){

						// Sanitize Result
						$result = trim($results[1]);
						$result = trim($result,';');
						$result = trim($result);

						// Debug Information
						$this->Logger->debug("disposition: {$result}");

						// Save Disposition
						$this->Disposition = $result;

						// Return Disposition
						return $this->Disposition;
					} else {
						throw new Exception("Could not identify the disposition of the attachement");
					}
				} else {
					throw new Exception("Unable to retrieve the disposition of the attachement");
				}
			} catch (Exception $e) {

				// Log error
				$this->Logger->error('IMAP Error: '.$e->getMessage());
			}
		}
	}

	/**
	 * Identify the encoding of this attachement.
	 *
	 * @return string|void
   * @throws Exception
	 */
	public function getEncoding(){

    // Check if Encoding was already retrieved
    if($this->Encoding){

      // Return Encoding
      return $this->Encoding;
		} else {
			try{
				if(preg_match('/Content-Transfer-Encoding: "?(.*?)"?(\s|$)/i', $this->Attachment, $results)){
					if(isset($results[1])){

						// Sanitize Result
						$result = trim($results[1]);
						$result = trim($result,';');
						$result = trim($result);

						// Debug Information
						$this->Logger->debug("encoding: {$result}");

						// Save Encoding
						$this->Encoding = $result;

						// Return Encoding
						return $this->Encoding;
					} else {
						throw new Exception("Could not identify the encoding of the attachement");
					}
				} else {
					throw new Exception("Unable to retrieve the encoding of the attachement");
				}
			} catch (Exception $e) {

				// Log error
				$this->Logger->error('IMAP Error: '.$e->getMessage());
			}
		}
	}

	/**
	 * Identify the id of this attachement.
	 *
	 * @return string|void
   * @throws Exception
	 */
	public function getId(){

    // Check if Id was already retrieved
    if($this->Id){

      // Return Id
      return $this->Id;
		} else {
			try{
				if(preg_match('/Content-Id: "?(.*?)"?(\s|$)/i', $this->Attachment, $results)){
					if(isset($results[1])){

						// Sanitize Result
						$result = trim($results[1]);
						$result = trim($result,';');
						$result = trim($result);

						// Debug Information
						$this->Logger->debug("id: {$result}");

						// Save Id
						$this->Id = $result;

						// Return Id
						return $this->Id;
					} else {
						throw new Exception("Could not identify the id of the attachement");
					}
				} else {
					throw new Exception("Unable to retrieve the id of the attachement");
				}
			} catch (Exception $e) {

				// Log error
				$this->Logger->error('IMAP Error: '.$e->getMessage());
			}
		}
	}

	/**
	 * Identify the name of this attachement.
	 *
	 * @return string|void
   * @throws Exception
	 */
	public function getFilename(){

    // Check if Filename was already retrieved
    if($this->Filename){

      // Return Filename
      return $this->Filename;
		} else {
			try{

				// Initialize FileName
				$filename = null;

				// Find file's filename
				if(!$filename && preg_match('/filename="(.*?)"/', $this->Attachment, $filenames)){
					if(isset($filenames[1])){
						$filename = trim($filenames[1]);
					}
				}

				// Find file's filename
				if(!$filename && preg_match('/name="(.*?)"/', $this->Attachment, $filenames)){
					if(isset($filenames[1])){
						$filename = trim($filenames[1]);
					}
				}

				// Find file's filename
				if(!$filename && preg_match('/filename="?(.*?)"?(\s|$)/i', $this->Attachment, $filenames)){
					if(isset($filenames[1])){
						$filename = trim($filenames[1]);
					}
				}

				// Find file's filename
				if(!$filename && preg_match('/name="?(.*?)"?(\s|$)/i', $this->Attachment, $filenames)){
					if(isset($filenames[1])){
						$filename = trim($filenames[1]);
					}
				}

				// Check if filename was found
				if($filename){

					// Debug Information
					$this->Logger->debug("filename: {$filename}");

					// Save Filename
					$this->Filename = $filename;

					// Return Filename
					return $this->Filename;
				} else {
					throw new Exception("Unable to retrieve the filename of the attachement");
				}
			} catch (Exception $e) {

				// Log error
				$this->Logger->error('IMAP Error: '.$e->getMessage());
			}
		}
	}

	/**
	 * Identify the name of this attachement.
	 *
	 * @return string|void
   * @throws Exception
	 */
	public function getFiletype(){

    // Check if Filetype was already retrieved
    if($this->Filetype){

      // Return Filetype
      return $this->Filetype;
		} else {
			try{

				// Retrieve the file name
				$filename = $this->getFilename();

				// Retrieve the file type
				$filetype = explode('.',$filename);
				$filetype = end($filetype);

				// Debug Information
				$this->Logger->debug("filetype: {$filetype}");

				// Save Filetype
				$this->Filetype = $filetype;

				// Return Filetype
				return $this->Filetype;
			} catch (Exception $e) {

				// Log error
				$this->Logger->error('IMAP Error: '.$e->getMessage());
			}
		}
	}

	/**
	 * Identify the Content-Type of this attachement.
	 *
	 * @return string|void
   * @throws Exception
	 */
	public function getContentType(){

    // Check if ContentType was already retrieved
    if($this->ContentType){

      // Return Id
      return $this->ContentType;
		} else {
			try{
				if(preg_match('/Content-Type: "?(.*?)"?(\s|$)/i', $this->Attachment, $results)){
					if(isset($results[1])){

						// Sanitize Result
						$result = trim($results[1]);
						$result = trim($result,';');
						$result = trim($result);

						// Debug Information
						$this->Logger->debug("ContentType: {$result}");

						// Save ContentType
						$this->ContentType = $result;

						// Return Id
						return $this->ContentType;
					} else {
						throw new Exception("Could not identify the ContentType of the attachement");
					}
				} else {
					throw new Exception("Unable to retrieve the ContentType of the attachement");
				}
			} catch (Exception $e) {

				// Log error
				$this->Logger->error('IMAP Error: '.$e->getMessage());
			}
		}
	}

	/**
	 * Identify the content of this attachement.
	 *
	 * @return blob|void
   * @throws Exception
	 */
	public function getContent(){

    // Check if Content was already retrieved
    if($this->Content){

      // Return Content
      return $this->Content;
		} else {
			try{

				// Debug Information
				$this->Logger->debug("Attachment: " . PHP_EOL . json_encode($this->Attachment, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

				// Split the message into an array of lines
				$sections = preg_split('/\r\n\r\n|\n\n/', $this->Attachment);

				// Find the content
				if(isset($sections[1])){

					// Remove Attachment Headers
					unset($sections[0]);

					// Construct Content
					$content = implode("\r\n\r\n",$sections);

					// Verify Content-Type
					switch($this->getContentType()){
						case"text/plain":
						case"text/html":
						case"multipart/alternative":
						case"multipart/mixed":
						case"multipart/related":
						case"message/rfc822":
						case"application/octet-stream":
						case"application/pdf":
						case"image/jpeg":
						case"image/png":
						case"audio/mpeg":
						case"video/mp4":
							break;
						default:
							break;
					}

					// Check for Encoding
					switch($this->getEncoding()){
						case"base64":
							// Attempt Decoding
							$content = base64_decode($content, true);
							if(!$content){
								throw new Exception("An error occured while decoding the content of the attachement");
							}
							break;
						default:
							break;
					}

					// Debug Information
					$this->Logger->debug("content: {$content}");

					// Save Content
					$this->Content = $content;

					// Return Content
					return $this->Content;
				} else {
					throw new Exception("Unable to retrieve the content of the attachement");
				}
			} catch (Exception $e) {

				// Log error
				$this->Logger->error('IMAP Error: '.$e->getMessage());
			}
		}
	}

	/**
	 * Save the attachment in the specified directory.
	 *
	 * @return string|boolean File path if the file was saved successfully, false otherwise.
   * @throws Exception
	 */
	public function save() {
		try{

			// Generate directory
      $directory = $this->Directory;

			// Generate file path
	    $filepath = $directory . DIRECTORY_SEPARATOR . md5(trim($this->getContent())) . '.' . $this->getFiletype();

			// Create the directory recursively if it doesn't exist
      if (!is_dir($directory)) {
        if (!mkdir($directory, 0777, true)) {
          throw new Exception("Unable to create directory {$directory}");
        }
      }

			// Validate the directory and create the file
      if (is_dir($directory) && is_writable($directory)) {
				if(!is_file($filepath)){
          if (file_put_contents($filepath, trim($this->getContent()))) {
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
}
