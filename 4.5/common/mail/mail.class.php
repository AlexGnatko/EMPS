<?php
require_once 'PHPMailer/PHPMailerAutoload.php';

class EMPS_Mail {
	public $attachments = array();
	public $inline = array();
	
	public function mail_smtp($to, $subject, $body, $smtp_data, $params){
		global $smarty;
		
		$rv = false;
		
		if(isset($smtp_data['attachments']))
			$this->attachments = $smtp_data['attachments'];

		if(isset($smtp_data['inline']))
			$this->inline = $smtp_data['inline'];

		$mail = new PHPMailer(true);
		try {
			$mail->isSMTP();
			//Enable SMTP debugging
			// 0 = off (for production use)
			// 1 = client messages
			// 2 = client and server messages
			$mail->SMTPDebug = 2;
			//Ask for HTML-friendly debug output
			$mail->Debugoutput = 'echo';
			
			$host = $smtp_data['host'];
			$x = explode("://", $host);
			if($x[1]){
				$host = $x[1];
				$mail->SMTPSecure = $x[0];
			}
			
			//Set the hostname of the mail server
			$mail->Host = $host;
			//Set the SMTP port number - likely to be 25, 465 or 587
			$mail->Port = $smtp_data['port'];
			//Whether to use SMTP authentication
			$mail->SMTPAuth = $smtp_data['auth'];
			$mail->SMTPAutoTLS = false;
			
			$mail->Username = $smtp_data['username'];                 // SMTP username
			$mail->Password = $smtp_data['password'];                           // SMTP password
		//	$mail->SMTPSecure = 'tls'; 
			
			//Set who the message is to be sent from
			$from_whom = $params['Who'];
			if(!$from_whom){
				$from_whom = $smarty->fetch("db:msg/sitename");
			}
			
			$mail->setFrom($params['From'], $from_whom);
			
			if(isset($params['Reply-To'])){
				$mail->addReplyTo($params['Reply-To']);

			}

			$x = explode("; charset=", $params['Content-Type']);
			
			if($x[1]){
				$mail->CharSet = $x[1];
			}

			$mail->ContentType = $x[0];
			
			//Set who the message is to be sent to
			
			$x = explode(",", $to);
			foreach($x as $v){
				$mail->addAddress(trim($v), '');
			}
			
			//Set the subject line
			$mail->Subject = $subject;
			
			$mail->Body = $body;
			
			// Attachments, if any
			if(!empty($this->attachments))
				foreach($this->attachments as $att){
					$mail->AddAttachment($att['path'], $att['filename']);
				}

			// inline images
			if(!empty($this->inline))
				foreach($this->inline as $inl)
					if(isset($inl['path']) and isset($inl['cid']))
						$mail->addEmbeddedImage($inl['path'], $inl['cid']);
			
			//send the message
			//Note that we don't need check the response from this because it will throw an exception if it has trouble
			$mail->send();
//			echo "Message sent!\r\n";
			$rv = true;
		} catch (phpmailerException $e) {
			echo $e->errorMessage(); //Pretty error messages from PHPMailer
		} catch (Exception $e) {
			echo $e->getMessage(); //Boring error messages from anything else!
		}
	
		return $rv;
	}
	
	public function check_email($email){
		$pattern = "/^[a-zа-яA-ZА-Я0-9_.+-]+@[a-zа-яA-ZА-Я0-9-]+\.[a-zа-яA-ZА-Я0-9-.]+$/u";
		$match = preg_match($pattern, $email);
//		echo "match: ".$match." ".$email;
		return $match;
	}
	
	public function send_message_ex($user_id, $template, $title, $mode){
		global $emps, $emps_smtp_data, $emps_smtp_params, $smarty, $SET;
		
		if(!$user_id) return -50;
	
		if($mode==2 || $mode==3){
			$email = $user_id;
		}else{
			$user=$emps->auth->load_user($user_id);
			if($user['email']){
				$email = $user['email'];
			}else{
				$email = $user['username'];
			}
		}
	
		$smarty->assign("BaseURL", $emps->base_url_by_ctx($emps->website_ctx));
	
		if(!$email){
			return -1;		
		}
		
		if($mode!=2 && $mode!=3){
			if($user_id<=0){
				return -2;
			}
		}
	
		$full = $user['fullname'];
		$smarty->assign("FullName",$full);
	
		if($user){
			$smarty->assign("udata",$user);
		}
		$k_message=$smarty->fetch($template);
	
		$r=false;
		
		if($mode==2 || $mode==3){
			$to = $user_id;
		}else{
			$to = $email;
		}
	
		if($mode==0 || $mode==3){
			$r = $this->mail_smtp($to,$title,$k_message,$emps_smtp_data,$emps_smtp_params);
		}elseif($mode==1 || $mode==2){
			$SET = array();
			$SET['id'] = "";
			$SET['status'] = 0;
			$SET['to'] = $to;
			$SET['title'] = $title;
			$SET['message'] = $k_message;
			$SET['params'] = serialize($emps_smtp_params);
			$data = $emps_smtp_data;
			$data['attachments'] = $this->attachments;
			$data['inline'] = $this->inline;
			$SET['smtpdata'] = serialize($data);		
			$SET['dt'] = time();
			$SET['sdt'] = 0;
			$emps->db->sql_insert("e_msgcache");
			if($emps->db->last_insert()){
				$r = true;
			}else{
				$r = false;
			}
		}
	
		if($r == true){
			return 0;
		}else{
			return -3;
		}
	
	}	
	
	public function encode_string($string,$encoding){
		$string = htmlspecialchars_decode($string);
		$txt=base64_encode($string);
		$res="=?".$encoding."?B?".$txt."?=";
		return $res;
	}
	
	public function send_message($user_id,$template,$title){
		return $this->send_message_ex($user_id,$template,$title,0);
	}
	
	public function queue_message($user_id,$template,$title){
		return $this->send_message_ex($user_id,$template,$title,1);
	}
	
	public function queue_direct_email($email,$template,$title){
		return $this->send_message_ex($email,$template,$title,2);
	}
	
	public function direct_email($email,$template,$title){
		return $this->send_message_ex($email,$template,$title,3);
	}
	
	public function parse_headers($raw_headers){
		$raw_headers = preg_replace("/\r\n[ \t]+/", ' ', $raw_headers); // Unfold headers
		$raw_headers = explode("\r\n", $raw_headers);
		foreach ($raw_headers as $value) {
			$name  = substr($value, 0, $pos = strpos($value, ':'));
			$value = ltrim(substr($value, $pos + 1));
			if (isset($headers[$name]) AND is_array($headers[$name])) {
				$headers[$name][] = $value;
			} elseif (isset($headers[$name])) {
				$headers[$name] = array($headers[$name], $value);
			} else {
				$headers[$name] = $value;
			}
		}
	
		return $headers;
	}
	
	public function apply_headers($hds){
		global $emps_smtp_params;
		
		reset($hds);
		while(list($n,$v)=each($hds)){
			reset($emps_smtp_params);
			$use_name=$n;
			while(list($nn,$vv)=each($emps_smtp_params)){
				if(strtolower($nn)==strtolower($n)){
					$use_name=$nn;
				}
			}
			$emps_smtp_params[$use_name]=$v;		
		}
	}
	
	public function queued_email($email,$title,$message,$headers){
		global $smarty,$emps_smtp_params;
		$smarty->assign("plain",$message);
		
		if(!is_array($headers)){
			$hds=$this->parse_headers($headers);
			$this->apply_headers($hds);
		}else{
			$emps_smtp_params=$headers;
		}
		
		return $this->queue_direct_email($email,"db:msg/plain",$title);
	}	
}
?>