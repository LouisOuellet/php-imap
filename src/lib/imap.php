<?php

// Import Librairies

class apiIMAP{

	protected $Host;
	protected $Port;
	protected $Encryption;
	protected $Username;
	protected $Password;
	protected $Connection;

	public $Status;
	public $Folders = [];

	public function __construct($Host,$Port,$Encryption,$Username,$Password,$isSelfSigned = false){
		// Save Configuration
		$this->Host = $Host;
		$this->Port = $Port;
		$this->Encryption = $Encryption;
		$this->Username = $Username;
		$this->Password = $Password;
		$this->isSelfSigned = $isSelfSigned;

		// Setup Connection
		$Connection = '{'.$Host.':'.$Port.'/imap/'.strtolower($Encryption);
		if($isSelfSigned){ $Connection .= '/novalidate-cert'; }
		$Connection .= '}';
		$this->Connection = $Connection;

		// Test Connection
		error_reporting(0);
		if(!$IMAP = imap_open($Connection, $Username, $Password)){
			$this->Status = end(imap_errors());
		} else {
			$this->Status = true;
			$this->Connection = $Connection;
			error_reporting(-1);
			$folders = imap_list($IMAP, $Connection, "*");
			if(is_array($folders)){ foreach($folders as $folder){ array_push($this->Folders,str_replace($Connection,'',imap_utf7_decode($folder))); } }
			// Close IMAP Connection
			imap_close($IMAP);
		}
	}

	public function get($folder = "INBOX", $opt = []){
		if(is_array($folder)){ $opt = $folder;$folder = "INBOX"; }
		if($this->isConnected()){
			// Init Return
			$return = new stdClass();
			// Connect to Folder
			error_reporting(0);
			if(in_array($folder, $this->Folders) && $IMAP = imap_open($this->Connection.$folder, $this->Username, $this->Password)){
				error_reporting(-1);
				// Building Meta Data
				$return->Meta = imap_check($IMAP);
				$new = imap_search($IMAP, 'UNSEEN');
				if(is_array($new)){ $return->Meta->Recent = count(imap_search($IMAP, 'UNSEEN')); }
				else { $return->Meta->Recent = 0; }
				$return->Meta->All = imap_num_msg($IMAP);
				if(isset($opt["new"]) && is_bool($opt["new"]) && $opt["new"]){
					$ids = imap_search($IMAP,"UNSEEN");
				} else { $ids = imap_search($IMAP,"ALL"); }
				foreach($ids as $id){
					$msg = imap_headerinfo($IMAP,$id);
					$msg->ID = $id;
					$msg->UID = imap_uid($IMAP,$id);
					$msg->Header = imap_header($IMAP,$id);
					$msg->From = $msg->Header->from[0]->mailbox . "@" . $msg->Header->from[0]->host;
					// Handling Subject Line
					$sub = $msg->Subject;
					$msg->Subject = new stdClass();
					$msg->Subject->Full = $sub;
					$msg->Subject->PLAIN = trim(preg_replace("/Re\:|re\:|RE\:|Fwd\:|fwd\:|FWD\:/i", '', $sub),' ');
					// Handling Body
					$msg->Body = new stdClass();
					$msg->Body->Meta = imap_fetchstructure($IMAP,$id);
					$msg->Body->Content = $this->getBody($IMAP,$msg->UID);
					if($this->isHTML($msg->Body->Content)){
						$html = new DOMDocument();
						$html->loadHTML($msg->Body->Content,LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
						$this->removeElementsByTagName('script', $html);
						$this->removeElementsByTagName('style', $html);
						$this->removeElementsByTagName('head', $html);
						$msg->Body->Content = str_replace("<html>","",str_replace("</html>","",str_replace("<body>","",str_replace("</body>","",$html->saveHtml()))));
						$html = new DOMDocument();
						$html->loadHTML($msg->Body->Content,LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
						$this->removeElementsByTagName('blockquote', $html);
						$msg->Body->Unquoted = str_replace("<html>","",str_replace("</html>","",str_replace("<body>","",str_replace("</body>","",$html->saveHtml()))));
					} else {
						$msg->Body->Unquoted = "";
						foreach(explode("\n",$msg->Body->Content) as $line){
							if($line[0] != '>'){ $msg->Body->Unquoted .= $line; }
						}
					}
					$return->messages[$msg->ID] = $msg;
					// Handling Attachments
					$msg->Attachments = new stdClass();
					$msg->Attachments->Files = [];
					if(isset($msg->Body->Meta->parts) && is_array($msg->Body->Meta->parts)){
						$msg->Attachments->Count = count($msg->Body->Meta->parts);
						foreach($msg->Body->Meta->parts as $key => $part){
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
								$msg->Attachments->Files[$key]['attachment'] = imap_fetchbody($IMAP,$id, $key+1);
								$msg->Attachments->Files[$key]['encoding'] = $part->encoding;
	              if($part->encoding == 3){
	                $msg->Attachments->Files[$key]['attachment'] = base64_decode($msg->Attachments->Files[$key]['attachment']);
	              } elseif($part->encoding == 4){
	                $msg->Attachments->Files[$key]['attachment'] = quoted_printable_decode($msg->Attachments->Files[$key]['attachment']);
	              }
							}
						}
					}
					// Resetting Flag
					if(isset($opt["new"]) && is_bool($opt["new"]) && $opt["new"]){ imap_clearflag_full($IMAP,$id, "\\Seen"); }
				}
				// Close IMAP Connection
				imap_close($IMAP);
				// Return
				return $return;
			} else {
				return end(imap_errors());
			}
		} else { return $this->Status; }
	}

	public function isConnected(){
		if(is_bool($this->Status) && $this->Status){ return true; } else { return false; }
	}

	public function read($uid){
		// Connect IMAP
		$IMAP = imap_open($this->Connection, $this->Username, $this->Password);
		// Read Message
		imap_body($IMAP,$uid,FT_UID);
		// Close IMAP Connection
		imap_close($IMAP);
	}

	public function delete($uid){
		// Connect IMAP
		$IMAP = imap_open($this->Connection, $this->Username, $this->Password);
		// Delete Email
		imap_mail_copy($IMAP,$uid,'Trash',FT_UID);
		imap_delete($IMAP,$uid,FT_UID);
		imap_expunge($IMAP);
		// Close IMAP Connection
		imap_close($IMAP);
	}

	protected function isHTML($string){
	 return $string != strip_tags($string) ? true:false;
	}

	protected function getMimeType($structure){
    $primaryMimetype = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];
    if ($structure->subtype){
      return $primaryMimetype[(int)$structure->type] . "/" . $structure->subtype;
    }
    return "TEXT/PLAIN";
	}

	protected function getBody($imap, $uid){
	    $body = $this->getPart($imap, $uid, "TEXT/HTML");
	    if($body == ""){ $body = $this->getPart($imap, $uid, "TEXT/PLAIN"); }
	    return $body;
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

	protected function removeElementsByTagName($tagName, $document) {
	  $nodeList = $document->getElementsByTagName($tagName);
	  for ($nodeIdx = $nodeList->length; --$nodeIdx >= 0; ) {
	    $node = $nodeList->item($nodeIdx);
	    $node->parentNode->removeChild($node);
	  }
	}
}
