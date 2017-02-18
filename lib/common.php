<?php

/*
 *    Copyright (C) 2017  Olaf Reitmaier Veracierta
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *		along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

define("NETDUMP_EOF", 0);         		// End of expect stream
define("NETDUMP_FULLBUFFER", 10); 		// Buffer stream is full 
define("NETDUMP_TIMEOUT", 20);    		// Input timeout error
define("NETDUMP_FINISHED", 30);  			// Completed and finished
define("NETDUMP_UNKNOWN_CASE", 30);   // Unknown case for match

function readlines($file, $skip = "#"){
	$lines = array();
	$handle = fopen($file, "r");
	while (($line = fgets($handle)) !== false){
		if ($line[0]!="#" && strlen(trim($line))>0) $lines[] = $line;
	}
	fclose($handle);
	return $lines;
}

function splitlines($lines, $delimiter){
	$array = array(); 
	foreach($lines as $line){
  	$array[] = explode($delimiter, trim($line));
	}
	return $array;
}

function tabulate($array, $headers = NULL){
	$output_table = new Console_Table();
	if (!is_null($headers)) $output_table->setHeaders($headers);
	foreach($array as $row) $output_table->addRow($row);
	return $output_table->getTable();
}

function tabget($array, $index, $value){
	$found = NULL;
	foreach($array as $row){
		if ($row[$index] == $value){
			$found = $row;
			break;
		}
	}
	return $found;
}
				
function help(){
	global $_ROOTDIR;
	echo "Please read $_ROOTDIR/README.md\n";
}

function strvarsub($str, $vars){
	foreach($vars as $index => $value){
		$str = str_replace($index, $value, $str);
	}
	return $str;
}

function strclean($str){
	return trim(preg_replace('/[[:^print:]]/', '', $str)); 
}

function logEcho($msg, $syslog = false)
{
	global $_REPORT;
	$_REPORT[] = $msg;
	echo "*** " .  getTimeString() . " " . $msg . "\n";
	if ($syslog) logToSyslog(str_replace("\n", " ", $msg));
}

function logError($msg, $target = NULL, $logfile = NULL){
	global $_ERRORS;
	$msg = "*** ERROR: *** " . $msg;
	if (!is_null($target)){
		list($template, $target_tag, $address, $auth_tag) = $target;
		$_ERRORS[] = array($target_tag, $address, substr($msg,0,20), basename($logfile));
	}else{
		$_ERRORS[] = array("*netdump*", "localhost", $msg, "syslog");
	}
	echo "*** " . getTimeString() . " " . $msg . "\n";
	logToSyslog(str_replace("\n", " ", $msg));
}

function getTimeString()
{
	$mt = microtime(true);
	$now = DateTime::createFromFormat("U.u", number_format($mt, 6, '.', ''));
	$nowf = $now->format("Y-m-d H:i:s.u");
	return $nowf;
}

function logToSyslog($message, $level = LOG_INFO){
	$nowf = getTimeString();
	openlog("netdump", LOG_PID | LOG_CONS, LOG_SYSLOG);
	syslog($level, "$nowf $message");
	closelog();
}

function sendmail(
	$from, $tos, $subject, $body, $servers, 
	$port = 25, $secure = NULL, $user = NULL, $password = NULL
)
{
	global $_DEBUG;

	$mail = new PHPMailer;

	if ($_DEBUG) $mail->SMTPDebug = 3; // Enable verbose debug output

	$mail->Host = $servers; // Semicolon ; separated
	$mail->isSMTP();                                    
	if (!empty($user)) $mail->Username = $user;
	if (!empty($password)) $mail->Password = $password;
	if (!empty($user) && !empty($password)) $mail->SMTPAuth = true;                            
	if (!empty($secure)) $mail->SMTPSecure = $secure; // tls or ssl
	$mail->SMTPOptions['ssl'] = array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
	);
	$mail->Port = $port; 

	$mail->setFrom($from); // Array of email address and full name 
	foreach(explode(";",$tos) as $to){
		$mail->addAddress($to); // Array of email address and full name
	}

	$mail->Subject = $subject;

	$mail->IsHTML(false);

	$mail->Body    = $body;
	// $mail->AltBody = $body;

	$status = $mail->send();
	return array(
		"status" => $status, 
		"error" => $mail->ErrorInfo
	);
}
