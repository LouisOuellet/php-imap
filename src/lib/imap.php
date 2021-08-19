<?php

// Import Librairies

class PHPIMAP{

	protected $Host;
	protected $Port;
	protected $Encryption;
	protected $Username;
	protected $Password;
	protected $Connection;

	public $Status;
	public $Folders = [];

	public function __construct($Host,$Port,$Encryption,$Username,$Password,$isSelfSigned = true){

    // Increase PHP memory limit
    ini_set('memory_limit', '1024M');

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
				$return->messages = [];
				if(!empty($ids)){
					foreach($ids as $id){
						// Handling Meta Data
						$msg = imap_headerinfo($IMAP,$id);
						$msg->ID = $id;
						$msg->UID = imap_uid($IMAP,$id);
						$msg->Header = imap_header($IMAP,$id);
						$msg->From = $msg->Header->from[0]->mailbox . "@" . $msg->Header->from[0]->host;
						$msg->Sender = $msg->Header->sender[0]->mailbox . "@" . $msg->Header->sender[0]->host;
						$msg->To = [];
						if(isset($msg->Header->to)){
							foreach($msg->Header->to as $to){ array_push($msg->To,$to->mailbox . "@" . $to->host); }
						} else { array_push($msg->To,$this->Username); }
						$msg->CC = [];
						if(isset($msg->Header->cc)){
							foreach($msg->Header->cc as $cc){ array_push($msg->CC,$cc->mailbox . "@" . $cc->host); }
						}
						$msg->BCC = [];
						if(isset($msg->Header->bcc)){
							foreach($msg->Header->bcc as $bcc){ array_push($msg->BCC,$bcc->mailbox . "@" . $bcc->host); }
						}
						// Handling Subject Line
						if(isset($msg->subject)){$sub = $msg->subject;}
						if(isset($msg->Subject)){$sub = $msg->Subject;}
						$sub = imap_utf8($sub);
						$msg->Subject = new stdClass();
						$msg->Subject->Full = $sub;
						$msg->Subject->PLAIN = trim(preg_replace("/Re\:|re\:|RE\:|Fwd\:|fwd\:|FWD\:/i", '', $sub),' ');
						$msg->Subject->Meta = [];
						$meta = $msg->Subject->PLAIN;
						$replace = ['---','--','CID:','CNTR-','PARS-','UTF-8','CCN:','CN:','OTHER:','PO:','REF:','NBR:','INV:','OTHER:','(',')','<','>','{','}','[',']',';','"',"'",'#','_','=','+','.',',','!','?','@','$','%','^','&','*','\\','/','|'];
				    foreach($replace as $str1){ $meta = str_replace($str1,' ',strtoupper($meta)); }
						foreach(explode(' ',$meta) as $string){
            	if(mb_strlen($string)>=3 && preg_match('~[0-9]+~', $string) && substr($string, 0, 1) !== '=' && substr($string, 0, 1) !== '?'){ array_push($msg->Subject->Meta,$string);}
            }
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
							    $body = $body->item(0);
							    $msg->Body->Content = $html->saveHtml($body);
							} else {
								$msg->Body->Content = $html->saveHtml($html);
							}
							$msg->Body->Content = preg_replace("/<\\/?body(.|\\s)*?>/",'',$msg->Body->Content);
							$html = new DOMDocument();
							libxml_use_internal_errors(true);
							$html->loadHTML($htmlBody);
							libxml_use_internal_errors(false);
							$this->removeElementsByTagName('script', $html);
							$this->removeElementsByTagName('style', $html);
							$this->removeElementsByTagName('head', $html);
							$this->removeElementsByTagName('blockquote', $html);
							$body = $html->getElementsByTagName('body');
							if( $body && 0<$body->length ){
							    $body = $body->item(0);
							    $msg->Body->Unquoted = $html->saveHtml($body);
							} else {
								$msg->Body->Unquoted = $html->saveHtml($html);
							}
							$msg->Body->Unquoted = preg_replace("/<\\/?body(.|\\s)*?>/",'',$msg->Body->Unquoted);
							if(strpos($msg->Body->Unquoted, 'From:') !== false){
								$msg->Body->Unquoted = explode('From:',$msg->Body->Unquoted)[0];
							}
						} else {
							$msg->Body->Unquoted = "";
							foreach(explode("\n",$msg->Body->Content) as $line){
								if(substr($line, 0, 1) != '>'){ $msg->Body->Unquoted .= $line."<br>"; }
							}
							$msg->Body->Content = str_replace("\n","<br>",$msg->Body->Content);
						}
						$trims = ["\n","<br>"];
						foreach($trims as $trim){ $msg->Body->Unquoted = trim($msg->Body->Unquoted,$trim);$msg->Body->Content = trim($msg->Body->Content,$trim); }
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
						$return->messages[$msg->ID] = $msg;
						// Resetting Flag
						if(isset($opt["new"]) && is_bool($opt["new"]) && $opt["new"]){ imap_clearflag_full($IMAP,$id, "\\Seen"); }
					}
				}
				// Close IMAP Connection
				imap_close($IMAP);
				// Return
				return $return;
			} else { return end(imap_errors()); }
		} else { return $this->Status; }
	}

	public function isConnected(){
		return is_bool($this->Status) && $this->Status ? true:false;
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
		error_reporting(0);
		if($IMAP = imap_open($this->Connection, $this->Username, $this->Password)){
			error_reporting(-1);
			// Delete Email
			imap_mail_copy($IMAP,$uid,'Trash',FT_UID);
			imap_delete($IMAP,$uid,FT_UID);
			imap_expunge($IMAP);
			// Close IMAP Connection
			imap_close($IMAP);
		} else { error_reporting(-1); }
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
			$body = imap_utf8($body);
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

	protected function convertUTF8( $string ) {
    if(strlen(utf8_decode($string)) == strlen($string)){
      return iconv("ISO-8859-1", "UTF-8", $string);
    } else {
      return $string;
    }
	}

	protected function removeElementsByTagName($tagName, $document) {
	  $nodeList = $document->getElementsByTagName($tagName);
	  for ($nodeIdx = $nodeList->length; --$nodeIdx >= 0; ) {
	    $node = $nodeList->item($nodeIdx);
	    $node->parentNode->removeChild($node);
	  }
	}

	protected function createPartArray($structure, $prefix="") {
    if (sizeof($structure->parts) > 0) {
      foreach ($structure->parts as $count => $part) { $this->addPart2Array($part, $prefix.($count+1), $part_array); }
    }else{ $part_array[] = array('part_number' => $prefix.'1', 'part_object' => $obj); }
   return $part_array;
	}

	function addPart2Array($obj, $partno, & $part_array) {
    $part_array[] = array('part_number' => $partno, 'part_object' => $obj);
    if($obj->type == 2){
      if(isset($obj->parts) && is_array($obj->parts) && sizeof($obj->parts) > 0){
        foreach($obj->parts as $count => $part){
          if(isset($part->parts) && sizeof($part->parts) > 0){
            foreach($part->parts as $count2 => $part2){ $this->addPart2Array($part2, $partno.".".($count2+1), $part_array); }
          }else{ $part_array[] = array('part_number' => $partno.'.'.($count+1), 'part_object' => $obj); }
        }
      }else{ $part_array[] = array('part_number' => $partno, 'part_object' => $obj); }
    }else{
      if(isset($obj->parts) && is_array($obj->parts) && sizeof($obj->parts) > 0){
        foreach($obj->parts as $count => $p){ $this->addPart2Array($p, $partno.".".($count+1), $part_array); }
      }
    }
	}
}
