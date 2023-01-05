<?php

//Declaring namespace
namespace LaswitchTech\IMAP;

//Import stdClass & DOMDocument classes into the global namespace
use \stdClass;
use \DOMDocument;

class phpIMAP{

	protected $Username = null;
	protected $Password = null;
	protected $Connection = null;
	protected $IMAP = null;

	protected $Status = false;
	protected $Folders = [];

	public function __construct($host = null,$port = null,$encryption = null,$username = null,$password = null,$isSelfSigned = true){

		// Retrieve Constants
		if($host == null && defined("IMAP_HOST")){ $host = IMAP_HOST; }
		if($port == null && defined("IMAP_PORT")){ $port = IMAP_PORT; }
		if($encryption == null && defined("IMAP_ENCRYPTION")){ $encryption = IMAP_ENCRYPTION; }
		if($username == null && defined("IMAP_USERNAME")){ $username = IMAP_USERNAME; }
		if($password == null && defined("IMAP_PASSWORD")){ $password = IMAP_PASSWORD; }
		if($isSelfSigned == null && defined("IMAP_SELFSIGNED")){ $isSelfSigned = IMAP_SELFSIGNED; }

		// Setup Connection
		if($host != null){
			$connection = $this->buildConnectionString($host,$port,$encryption,$isSelfSigned);
			$this->connect($username,$password,$connection,true);
		}
	}

  public function __call($name, $arguments) {
    $this->output($name, array('HTTP/1.1 501 Not Implemented'));
  }

	public function __destruct(){ $this->close(); }

	// Printers

  protected function output($data, $httpHeaders=array()){
    header_remove('Set-Cookie');
    if (is_array($httpHeaders) && count($httpHeaders)) {
      foreach ($httpHeaders as $httpHeader) {
        header($httpHeader);
      }
    }
    echo $data;
    exit;
  }

	// Getters

	public function getUsername(){ return $this->Username; }

	public function getFolders(){ return $this->Folders; }

	// Conditions

	protected function isHTML($string){
	 return $string != strip_tags($string) ? true:false;
	}

	public function isConnected(){
		return is_bool($this->Status) && $this->Status ? true:false;
	}

	// Configurations

	public function buildConnectionString($host,$port = null,$encryption = null,$isSelfSigned = true){
		// Setup Connection String
		if(substr($host, 0, 1) === '{'){
			$connection = $host;
		} else {
			$connection = '{'.$host.':';
			if($port != null){ $connection .= $port.'/imap'; } else { $connection .= '143/imap'; }
			if($encryption != null){ $connection .= '/'.strtolower($encryption); } else { $connection .= '/notls'; }
			if($isSelfSigned){ $connection .= '/novalidate-cert'; }
			$connection .= '}';
		}
		return $connection;
	}

	public function connect($username = null,$password = null,$connection = null,$store = false){
		// Setup Connection String
		if($connection == null){ $connection = $this->Connection; }
		if($password == null){ $password = $this->Password; }
		if($username == null){ $username = $this->Username; }
		// Connect IMAP
		$level = error_reporting();
		error_reporting(0);
		if($IMAP = imap_open($connection, $username, $password,OP_SILENT,0)){
			error_reporting($level);
			if($store){
				$this->Connection = $connection;
				$this->Username = $username;
				$this->Password = $password;
				$this->IMAP = $IMAP;
				$this->Status = true;
				$this->Folders = [];
				$folders = imap_list($this->IMAP, $this->Connection, "*");
				if(is_array($folders)){ foreach($folders as $folder){ array_push($this->Folders,str_replace($connection,'',imap_utf7_decode($folder))); } }
				return true;
			} else { return $IMAP; }
		} else {
			error_reporting(-1);return false;
		}
	}

	public function close($IMAP = null){
		if($IMAP == null){
			if($this->Status){ imap_close($this->IMAP); }
		} elseif($IMAP != $this->IMAP){ imap_close($IMAP); }
	}

	// Helpers

	protected function stripLine($text, $nbr = 1) {
    for($count = 1; $count <= $nbr; $count ++){
      $text = substr($text, strpos($text, "\n") + 1);
    }
    return $text;
	}

	protected function getStringBetween($string, $start, $end){
    $string = ' '.$string;
    $ini = strpos($string, $start);
    if($ini == 0){ return ''; }
		else {
			$ini += strlen($start);
	    $len = strpos($string, $end, $ini) - $ini;
	    return substr($string, $ini, $len);
		}
	}

	protected function convertUTF8( $string ) {
    if(strlen(utf8_decode($string)) == strlen($string)){
      return iconv("ISO-8859-1", "UTF-8", $string);
    } else {
      return $string;
    }
	}

	protected function innerHTML(DOMNode $element){
    $innerHTML = "";
    $children  = $element->childNodes;
    foreach($children as $child){
    	$innerHTML .= $element->ownerDocument->saveHTML($child);
    }
    return $innerHTML;
	}

	protected function convertHTMLSymbols($str_in){
		$list = get_html_translation_table(HTML_ENTITIES);
		unset($list['"']);
		unset($list['<']);
		unset($list['>']);
		unset($list['&']);
		$search = array_keys($list);
		$values = array_values($list);
		return str_replace($search, $values, $str_in);
	}

	protected function getMimeType($structure){
    $primaryMimetype = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];
    if ($structure->subtype){
      return $primaryMimetype[(int)$structure->type] . "/" . $structure->subtype;
    }
    return "TEXT/PLAIN";
	}

	protected function getInnerSubstring($substring,$left,$right){
	  $string = explode($left,$substring);
	  if(count($string) > 1){
	    return explode($right,$string[1])[0];
	  }
	}

	protected function getReferences($substring,$left,$right){
	  $array = [];
	  $strings = explode($left,$substring);
	  unset($strings[0]);
	  foreach($strings as $string){
	    if(str_contains($string,$right)){
	      array_push($array,explode($right,$string)[0]);
	    }
	  }
	  return $array;
	}

	protected function mergeReferences($array,$delimiter){
	  $references = [];
	  foreach($array as $string){
	    if(str_contains($string,$delimiter)){
	      $data = explode($delimiter,$string);
	      $references[$data[0]][] = $data[1];
	    }
	  }
	  return $references;
	}

	protected function getBody($imap, $uid){
	    $body = $this->getPart($imap, $uid, "TEXT/HTML");
	    if($body == ""){ $body = $this->getPart($imap, $uid, "TEXT/PLAIN"); }
			$body = imap_utf8($body);
	    return $body;
	}

	protected function removeElementsByTagName($tagName, $document) {
	  $nodeList = $document->getElementsByTagName($tagName);
	  for ($nodeIdx = $nodeList->length; --$nodeIdx >= 0; ) {
	    $node = $nodeList->item($nodeIdx);
	    $node->parentNode->removeChild($node);
	  }
	}

	protected function getPart($imap, $uid, $mimetype, $structure = false, $partNumber = false){
    if(!$structure){ $structure = imap_fetchstructure($imap, $uid, FT_UID); }
    if($structure){
      if($mimetype == $this->getMimeType($structure)){
        if(!$partNumber){ $partNumber = 1; }
        $text = imap_fetchbody($imap, $uid, $partNumber, FT_UID);
        switch ($structure->encoding) {
          case 3: return imap_base64($text);
          case 4: return imap_qprint($text);
          default: return $text;
        }
      }
      // multipart
      if($structure->type == 1){
        foreach($structure->parts as $index => $subStruct){
          $prefix = "";
          if($partNumber){ $prefix = $partNumber . "."; }
          $data = $this->getPart($imap, $uid, $mimetype, $subStruct, $prefix . ($index + 1));
          if($data){ return $data; }
        }
      }
    }
    return false;
	}

	protected function createPartArray($structure, $prefix="") {
    if (sizeof($structure->parts) > 0) {
      foreach ($structure->parts as $count => $part) { $this->addPart2Array($part, $prefix.($count+1), $part_array); }
    } else { $part_array[] = array('part_number' => $prefix.'1', 'part_object' => $obj); }
   return $part_array;
	}

	protected function addPart2Array($obj, $partno, & $part_array) {
    $part_array[] = array('part_number' => $partno, 'part_object' => $obj);
    if($obj->type == 2){
      if(isset($obj->parts) && is_array($obj->parts) && sizeof($obj->parts) > 0){
        foreach($obj->parts as $count => $part){
          if(isset($part->parts) && sizeof($part->parts) > 0){
            foreach($part->parts as $count2 => $part2){ $this->addPart2Array($part2, $partno.".".($count2+1), $part_array); }
          } else { $part_array[] = array('part_number' => $partno.'.'.($count+1), 'part_object' => $obj); }
        }
      } else { $part_array[] = array('part_number' => $partno, 'part_object' => $obj); }
    } else {
      if(isset($obj->parts) && is_array($obj->parts) && sizeof($obj->parts) > 0){
        foreach($obj->parts as $count => $p){ $this->addPart2Array($p, $partno.".".($count+1), $part_array); }
      }
    }
	}

	// Methods

	public function login($username,$password,$host = null,$port = null,$encryption = null,$isSelfSigned = true){
		// Setup Connection
		if($host == null){
			$connection = null;
		} elseif($host != null && $port != null && $encryption != null && is_bool($isSelfSigned)){
			$connection = $this->buildConnectionString($host,$port,$encryption,$isSelfSigned);
		} else {
			return false;
		}
		if($IMAP = $this->connect($username,$password,$connection)){
			$this->close($IMAP);
			return true;
		} else {
			return false;
		}
	}

	public function get($Connection = null, $Options = []){
		$IMAP = null;
		$opt = [];
		$criteria = "ALL";
		if(is_object($Options)){ $IMAP = $Options; }
		elseif(is_object($Connection)){ $IMAP = $Connection; }
		if(is_array($Connection)){ $opt = $Connection; }
		elseif(is_array($Options)){ $opt = $Options; }
		if($IMAP == null){ $IMAP = $this->IMAP; }
		if(isset($opt['folder'])){ $folder = $opt['folder']; } else { $folder = "INBOX"; }
		if(isset($opt['format'])){ $format = $opt['format']; } else { $format = false; }
		if(isset($opt['filter'])){ $criteria = $opt['filter']; } else { $criteria = "ALL"; }
		if($this->isConnected()){
			// Init Return
			$return = new stdClass();
			// Save IMAP Connection
			$return->IMAP = $IMAP;
			// Connect to Folder
			$return->IMAP = $this->folder($folder,$return->IMAP);
			$return->Folder = $folder;
			// Meta Data
			$return->Meta = imap_mailboxmsginfo($return->IMAP);
			// Get Messages
			$return->messages = imap_search($return->IMAP,$criteria);
			if(!empty($return->messages) && $format){
				$return->messages = $this->format($return->messages, $return->IMAP);
			}
			// Return
			return $return;
		} else { return $this->Status; }
	}

	public function format($List = [], $Connection = null){
		$IMAP = null;
		$ids = [];
		if(is_object($List)){ $IMAP = $List; }
		elseif(is_object($Connection)){ $IMAP = $Connection; }
		if(is_array($Connection)){ $ids = $Connection; }
		elseif(is_array($List)){ $ids = $List; }
		if(is_int($Connection)){ $ids = [$Connection]; }
		elseif(is_int($List)){ $ids = [$List]; }
		if($IMAP == null){ $IMAP = $this->IMAP; }
		$messages = [];
		foreach($ids as $id){
			// Handling Meta Data
			$msg = imap_headerinfo($IMAP,$id);
			$msg->ID = $id;
			$msg->UID = imap_uid($IMAP,$id);
			$msg->Header = imap_headerinfo($IMAP,$id);
			$msg->Date = $msg->Header->date;
			$msg->From = $msg->Header->from[0]->mailbox . "@" . $msg->Header->from[0]->host;
			$msg->Sender = $msg->Header->sender[0]->mailbox . "@" . $msg->Header->sender[0]->host;
			$msg->Receiver = [];
			$msg->To = [];
			if(isset($msg->Header->to)){
				foreach($msg->Header->to as $to){
					if(property_exists($to, 'host') && property_exists($to, 'mailbox')){
						array_push($msg->To,$to->mailbox . "@" . $to->host);
						array_push($msg->Receiver,$to->mailbox . "@" . $to->host);
					}
				}
			} elseif($this->Username != null) { array_push($msg->To,$this->Username); }
			$msg->CC = [];
			if(isset($msg->Header->cc)){
				foreach($msg->Header->cc as $cc){
					if(property_exists($cc, 'host') && property_exists($cc, 'mailbox')){
						array_push($msg->CC,$cc->mailbox . "@" . $cc->host);
						array_push($msg->Receiver,$cc->mailbox . "@" . $cc->host);
					}
				}
			}
			$msg->BCC = [];
			if(isset($msg->Header->bcc)){
				foreach($msg->Header->bcc as $bcc){
					if(property_exists($bcc, 'host') && property_exists($bcc, 'mailbox')){
						array_push($msg->BCC,$bcc->mailbox . "@" . $bcc->host);
						array_push($msg->Receiver,$bcc->mailbox . "@" . $bcc->host);
					}
				}
			}
			$msg->Meta = new stdClass();
			$msg->Meta->References = new stdClass();
			$msg->Meta->References->Plain = [];
			$msg->Meta->References->Formatted = [];
			// Handling Subject Line
			if(isset($msg->subject)){$sub = $msg->subject;}
			if(isset($msg->Subject)){$sub = $msg->Subject;}
			$sub = imap_utf8($sub);
			$msg->Subject = new stdClass();
			$msg->Subject->Full = str_replace('~','-',$sub);
			$msg->Subject->PLAIN = trim(preg_replace("/Re\:|re\:|RE\:|Fwd\:|fwd\:|FWD\:/i", '', $msg->Subject->Full),' ');
			$meta = $msg->Subject->PLAIN;
			$replace = ['---','--','CID:','UTF-8','(',')','<','>','{','}','[',']',';','"',"'",'_','=','~','+','!','?','@','$','%','^','&','*','\\','/','|'];
			foreach($replace as $str1){ $meta = str_replace($str1,' ',strtoupper($meta)); }
			foreach(explode(' ',$meta) as $string){
				if(mb_strlen($string)>=3 && (preg_match('~[0-9]+~', $string) || strpos($string, '-') !== false) && substr($string, 0, 1) !== '=' && substr($string, 0, 1) !== '?'){ array_push($msg->Meta->References->Plain,$string);}
			}
			$msg->Meta->References->Formatted = $this->mergeReferences($this->getReferences(strtoupper($msg->Subject->PLAIN),"[","]"),':');
			// Handling Body
			$msg->Body = new stdClass();
			$msg->Body->Meta = imap_fetchstructure($IMAP,$id);
			$msg->Body->Content = $this->getBody($IMAP,$msg->UID);
			if($this->isHTML($msg->Body->Content)){
				$htmlBody = $this->convertHTMLSymbols($msg->Body->Content);
				$html = new DOMDocument();
				libxml_use_internal_errors(true);
				$html->loadHTML($htmlBody);
				libxml_use_internal_errors(false);
				$this->removeElementsByTagName('script', $html);
				$this->removeElementsByTagName('style', $html);
				$this->removeElementsByTagName('head', $html);
				$body = $html->getElementsByTagName('body');
				if( $body && 0<$body->length ){
					$msg->Body->Content = $html->saveHtml($body->item(0));
				} else {
					$msg->Body->Content = $html->saveHtml($html);
				}
				$msg->Body->Unquoted = $this->convertHTMLSymbols($msg->Body->Content);
				if(strpos($msg->Body->Unquoted, 'From:') !== false){
					$msg->Body->Unquoted = explode('From:',$msg->Body->Unquoted)[0];
					$msg->Body->Unquoted = str_replace("From:","",$msg->Body->Unquoted);
				}
				if(strpos($msg->Body->Unquoted, 'Wrote:') !== false){
					$msg->Body->Unquoted = explode('Wrote:',$msg->Body->Unquoted)[0];
					$msg->Body->Unquoted = str_replace("Wrote:","",$msg->Body->Unquoted);
				}
				if(strpos($msg->Body->Unquoted, '------ Original Message ------') !== false){
					$msg->Body->Unquoted = explode('------ Original Message ------',$msg->Body->Unquoted)[0];
					$msg->Body->Unquoted = str_replace("------ Original Message ------","",$msg->Body->Unquoted);
				}
				if(strpos($msg->Body->Unquoted, '------ Forwarded Message ------') !== false){
					$msg->Body->Unquoted = explode('------ Forwarded Message ------',$msg->Body->Unquoted)[0];
					$msg->Body->Unquoted = str_replace("------ Forwarded Message ------","",$msg->Body->Unquoted);
				}
				$html = new DOMDocument();
				libxml_use_internal_errors(true);
				$html->loadHTML($msg->Body->Unquoted);
				libxml_use_internal_errors(false);
				$this->removeElementsByTagName('blockquote', $html);
				$body = $html->getElementsByTagName('body');
				if( $body && 0<$body->length ){
					$msg->Body->Unquoted = $html->saveHtml($body->item(0));
				} else {
					$msg->Body->Unquoted = $html->saveHtml($html);
				}
			} else {
				$msg->Body->Unquoted = "";
				foreach(explode("\n",$msg->Body->Content) as $line){
					if(substr($line, 0, 1) != '>'){ $msg->Body->Unquoted .= $line."\n"; }
				}
			}
			if($refs = $this->getReferences(strtoupper($msg->Body->Content),"[","]")){
				if(count($refs) > 0){
					foreach($this->mergeReferences($refs,':') as $type => $references){
						foreach($references as $reference){
							if(!array_key_exists($type, $msg->Meta->References->Formatted)){ $msg->Meta->References->Formatted[$type] = []; }
							if(!in_array($reference,$msg->Meta->References->Formatted[$type])){ $msg->Meta->References->Formatted[$type][] = $reference; }
						}
					}
				}
			}
			// Handling Attachments
			$msg->Attachments = new stdClass();
			$msg->Attachments->Files = [];
			$parts = [];
			if(isset($msg->Body->Meta->parts) && is_array($msg->Body->Meta->parts) && count($msg->Body->Meta->parts) > 0){
				$parts = $this->createPartArray($msg->Body->Meta);
				$msg->Attachments->Count = 0;
				foreach($parts as $key => $objects){
					$part = $objects['part_object'];
					if($part->ifdparameters){
						foreach($part->dparameters as $object){
							if(strtolower($object->attribute) == 'filename'){
								$msg->Attachments->Files[$key]['filename'] = $object->value;
								$msg->Attachments->Files[$key]['is_attachment'] = true;
							}
						}
					}
					if($part->ifparameters){
						foreach($part->parameters as $object){
							if(strtolower($object->attribute) == 'name'){
								$msg->Attachments->Files[$key]['name'] = $object->value;
								$msg->Attachments->Files[$key]['is_attachment'] = true;
							}
						}
					}
					if((isset($msg->Attachments->Files[$key]))&&($msg->Attachments->Files[$key]['is_attachment'])){
						$msg->Attachments->Count++;
						$msg->Attachments->Files[$key]['attachment'] = imap_fetchbody($IMAP,$id, $objects['part_number']);
						$msg->Attachments->Files[$key]['encoding'] = $part->encoding;
						if(isset($part->bytes)){$msg->Attachments->Files[$key]['bytes'] = $part->bytes;}
						if($part->encoding == 3){
							$msg->Attachments->Files[$key]['attachment'] = base64_decode($msg->Attachments->Files[$key]['attachment']);
						} elseif($part->encoding == 4){
							$msg->Attachments->Files[$key]['attachment'] = quoted_printable_decode($msg->Attachments->Files[$key]['attachment']);
						}
					}
				}
			}
			$messages[$msg->UID] = $msg;
			// Resetting Flag
			if(isset($opt["new"]) && is_bool($opt["new"]) && $opt["new"]){ imap_clearflag_full($IMAP,$id, "\\Seen"); }
		}
		return $messages;
	}

	public function folder($folder, $IMAP = null){
		if($IMAP == null){ $IMAP = $this->IMAP; }
		$meta = imap_mailboxmsginfo($IMAP);
		$connection = substr($meta->Mailbox, 0, strpos($meta->Mailbox, "}")+1);
		if($IMAP != $this->IMAP){
			$folders = [];
			$lists = imap_list($this->IMAP, $this->Connection, "*");
			if(is_array($lists)){ foreach($lists as $folder){ array_push($folders,str_replace($connection,'',imap_utf7_decode($list))); } }
		} else { $folders = $this->Folders; }
		if(in_array($folder, $folders)){ imap_reopen($IMAP, $connection.$folder); }
		return $IMAP;
	}

	public function read($ID,$Connection = null){
		$IMAP = null;
		$uid = null;
		if(is_object($ID)){ $IMAP = $ID; }
		elseif(is_object($Connection)){ $IMAP = $Connection; }
		if(is_int($Connection)){ $uid = $Connection; }
		elseif(is_int($ID)){ $uid = $ID; }
		if($IMAP == null){ $IMAP = $this->IMAP; }
		// Read Message
		imap_body($IMAP,$uid,FT_UID);
	}

	public function saveEml($File, $Connection = null){
		$IMAP = null;
		$eml = null;
		if(is_object($File)){ $IMAP = $File; }
		elseif(is_object($Connection)){ $IMAP = $Connection; }
		if(is_string($Connection)){ $eml = $Connection; }
		elseif(is_string($File)){ $eml = $File; }
		if($IMAP == null){ $IMAP = $this->IMAP; }
		$connection = imap_mailboxmsginfo($IMAP)->Mailbox;
		if(imap_append($IMAP, $connection, $eml)){
			return true;
		} else {
			return false;
		}
	}

	public function getEml($ID,$Connection = null){
		$IMAP = null;
		$uid = null;
		if(is_object($ID)){ $IMAP = $ID; }
		elseif(is_object($Connection)){ $IMAP = $Connection; }
		if(is_int($Connection)){ $uid = $Connection; }
		elseif(is_int($ID)){ $uid = $ID; }
		if($IMAP == null){ $IMAP = $this->IMAP; }
		// Fetch Email
		$headers = imap_fetchheader($IMAP, $uid, FT_UID);
		$body = imap_body($IMAP, $uid, FT_UID);
		// Return Blob
		return $headers."\r\n".$body;
	}

	public function delete($ID,$Connection = null){
		$IMAP = null;
		$uid = null;
		if(is_object($ID)){ $IMAP = $ID; }
		elseif(is_object($Connection)){ $IMAP = $Connection; }
		if(is_int($Connection)){ $uid = $Connection; }
		elseif(is_int($ID)){ $uid = $ID; }
		if($IMAP == null){ $IMAP = $this->IMAP; }
		// Delete Email
		imap_mail_copy($IMAP,$uid,'Trash',FT_UID);
		imap_delete($IMAP,$uid,FT_UID);
		imap_expunge($IMAP);
		return true;
	}

	public function saveAttachment($file,$destination){
		// Saving Attachment
		if($file['is_attachment']){
			$filename = time().".dat";
			if(isset($file['filename'])){ $filename = $file['filename']; }
			if(isset($file['name'])){ $filename = $file['name']; }
			$save = fopen(rtrim($destination,"/") . "/" . $filename, "w+");
			fwrite($save, $file['attachment']);
			fclose($save);
			return rtrim($destination,"/") . "/" . $filename;
		} else { return false; }
	}
}
