<?php

/*
 *    netdump.php 
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

require_once 'Console/Table.php';
require_once 'lib/Colors.php';

function readlines($file, $skip = "#"){
	$lines = array();
	$handle = fopen($file, "r");
	while (($line = fgets($handle)) !== false){
		if ($line[0]!="#") $lines[] = $line;
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

function automata_expect($cmd, $cases, $answers, $capturefile){
	global $_DEBUG;
	global $_COLORS;
	$cstream = fopen($capturefile, "a+");
	$stream = expect_popen($cmd);
	while (true) {
		$match = array();		
		$case = expect_expectl($stream, $cases, $match);
		$str = isset($match[0]) ? $match[0] : "";
		if ($_DEBUG){
			echo $_COLORS->getColoredString($case, "black", "yellow") . " -> '" . $str . "\n";
		}
		if ($case == "chr"){
			if ($_DEBUG){
				echo $_COLORS->getColoredString($case, "black", "yellow") . " -> '";
				$chars = array();
				foreach(str_split($match[0]) as $char) $chars[] = ord($char);
				echo implode(",", $chars);
				echo "'\n";
			}
			continue;
		}
		if ($case == "skip"){
			if ($_DEBUG){
				echo $_COLORS->getColoredString($case, "black", "yellow") . " -> '";
				echo $match[0];
				echo "'\n";	
			}
			continue;
		}
		if ($case == "save"){
			if (strlen($str)>0){
				$written = fwrite($cstream, $str); // Save match (buffer)
			}
			continue;	
		}
		$answered = false;
		for($i=0; $i<count($answers); $i++){
			if ($answers[$i][0] == $case){
				if ($answers[$i][2] == 0) continue; // Answers can't be used anymore
				if ($answers[$i][2] > 0) --$answers[$i][2]; // Use this answer once more
				fwrite($stream, $answers[$i][1]); // Answers ...
				if ($_DEBUG){
					echo $_COLORS->getColoredString("answer", "black", "yellow") . " <- '" . $answers[$i][1] . "'\n";
				}
				$answered = true;
				break;
			}
		}	
		if (!$answered){
			if ($case != EXP_EOF) echo $case . "-> '" . $str . "'\n";
			break; // EOF, Timeout, Full buffer, Unknown...
		}
	}
	fclose($stream);
	fclose($cstream);
	return $case;
}

function automata_fortigate($type, $address, $user, $password, $outfile) {
	global $_DEBUG;
	if ($type == "fortigate") $infile = "fgt-config";
	if ($type == "fortigate-sfg") $infile = "sys_config";
	$cmd = "scp -q -oStrictHostKeyChecking=no $user@$address:$infile $outfile";
	if ($_DEBUG) echo "CMD: " . $cmd . "\n";
	$cases = array(
		array("password:", "password", EXP_GLOB)
	);
	$answers = array(
		array("password", "$password\n", 1)
	);
	return automata_expect($cmd, $cases, $answers, $outfile);
}

function automata_cisco($type, $address, $user, $password, $passwordEnable, $outfile) {
	global $_DEBUG;
	if ($type == "cisco-telnet"){
		$cmd = "telnet $address";
	}else{
		$cmd = "ssh -q -oStrictHostKeyChecking=no $user@$address";
	}
	if ($_DEBUG) echo "CMD: " . $cmd . "\n";
	$cases = array(
		array(".*@.*'s [Pp]assword:", "sshpassword", EXP_REGEXP),
		array("^[Uu]sername:", "user", EXP_REGEXP),
		array("^[Pp]assword:", "password", EXP_REGEXP),
		array("* >", "enable", EXP_GLOB),
		array("^[-_\.0-9A-Za-z]+#$", "prompt", EXP_REGEXP),
		array("Building configuration...", "skip", EXP_GLOB),
		array("^[\010]+[\x20h]+[\010]+", "chr", EXP_REGEXP), // Backspace Space Backspace
		array("*\n", "save", EXP_GLOB),
		array("*--More--*", "more", EXP_GLOB)
	);
	$answers = array(
		array("user", "$user\n", 1),
		array("sshpassword", "$password\n", 3),
		array("password", "$password\n", 1),
		array("enable", "enable\n", 1),
		array("password", "$passwordEnable\n", 1),
		array("prompt", "show run\n", 1),
		array("more", " ", -1),
		array("prompt", "exit\n", 1)
	);
	return automata_expect($cmd, $cases, $answers, $outfile);
}

function help(){
	global $_TARGETS_FILE;
	global $_AUTHS_FILE;
	global $_OUTFILE_ROOTDIR;
	global $_LOGFILE_ROOTDIR;

			echo 
"
COMMANDS

php netdump.php [help]
	Shows this help
php netdump.php show target
	List targets from file '$_TARGETS_FILE'
php netdump.php show auth
	List crendentials file '$_AUTHS_FILE'
php netdump.php run [tag]
php netdump.php debug [tag]

LOGGING

- Dumps are saved in: '$_OUTFILE_ROOTDIR'
- Logs are saved in: '$_LOGFILE_ROOTDIR'

\n";
	
}

$_COLORS = new Colors();
$_DEBUG = false;
$_TARGETS_FILE = "/etc/netdump/targets.conf";
$_AUTHS_FILE = "/etc/netdump/auths.conf";
$_OUTFILE_ROOTDIR = "/var/lib/netdump/dumps";
$_LOGFILE_ROOTDIR = "/var/lib/netdump/logs";

$targets = splitlines(readlines($_TARGETS_FILE), ":");
$auths = splitlines(readlines($_AUTHS_FILE), ":");

$outfile_datedir = date("Y/m/d");
$outfile_datepfx = date("Ymd_his");

// Parse arguments
if (isset($argv[1])){
	switch($argv[1]){
		case "show":
			if (isset($argv[2])){
				switch($argv[2]){
					case "targets":
						// Show targets
						echo tabulate($targets, array("Model", "Address", "Tag", "Auth"));
						exit(0);
						break;
					case "auths";
						// Show authentication credential list
						echo tabulate($auths, array("Auth", "Param1", "Param2", "Parm3"));
						exit(0);
						break;
				}
			}
		case "run":
			// Just run netdump
			break;
		case "debug":
			// Run and show debug messages
			$_DEBUG = true;
			break;
		default:
			// Show help (bad argument)
			help(); exit(-1);	
			break;
	}
}else{
	// Show help (no argument)
	help(); exit(-1);	
}

$errors = array();
foreach($targets as $target){
	list($type, $address, $tag, $authtag) = $target;
	if (isset($argv[2]) and $tag != $argv[2]) continue; // Process specific target (tag)
	$auth = tabget($auths, 0, $authtag); // Find the credential for the target
	$outfile_dir = $_OUTFILE_ROOTDIR . "/" . $tag . "/" . $outfile_datedir;
	$logfile_dir = $_LOGFILE_ROOTDIR . "/" . $tag . "/" . $outfile_datedir;
	if (!is_dir($outfile_dir)) mkdir($outfile_dir, 0777, true);
	if (!is_dir($logfile_dir)) mkdir($logfile_dir, 0777, true);
	$outfile = $outfile_dir . "/" .  $outfile_datepfx . "_" . $tag . ".conf";
	$logfile = $logfile_dir . "/" .  $outfile_datepfx . "_" . $tag . ".log";
	ini_set("expect.timeout", 10);
	ini_set("expect.loguser", false);
	ini_set("expect.match_max", 8192);
	ini_set("expect.logfile", $logfile);
	echo "---\n";
	$result;
	switch($type){
		case "fortigate";
		case "fortigate-sfg";
			list($auth, $user, $password) = $auth;
			$msg = "TARGET: Type: $type, Tag: $tag, Address: $address, User: $user";
			echo $_COLORS->getColoredString($msg, "white", "blue") . "\n";
			$result = automata_fortigate($type, $address, $user, $password, $outfile);
			break;
		case "cisco":
		case "cisco-telnet":
		case "cisco-enable":
			$passwordEnable = "";
			if ($type=="cisco-enable"){
				list($auth, $user, $password, $passwordEnable) = $auth;
			}else{
				list($auth, $user, $password) = $auth;
			}
			$msg = "TARGET: Type: $type, Tag: $tag, Address: $address, User: $user";
			echo $_COLORS->getColoredString($msg, "white", "blue") . "\n";
			$result = automata_cisco($type, $address, $user, $password, $passwordEnable, $outfile);
			break;
	}
	$msg = "";
	switch($result){
		case EXP_EOF:
			if ($_DEBUG) $msg = "End of stream (EOF)";
			break;
		case EXP_TIMEOUT:
			$msg = "Error timeout!"; // Connection or expect timeout
			break;
		case EXP_FULLBUFFER:
			$msg = "Error buffer full!"; // Buffer full? => Raise expect buffer
			break;
		default:
			$msg = "Unknown ('$result') has occurred. Please debug!"; // Unknown error!
			break;
	}
	$new_errors = false;
	if (!empty($msg) && $result == EXP_EOF){
		echo $_COLORS->getColoredString("-> " . $msg, "black", "yellow") . "\n";
	}else if (!empty($msg)){
		echo $_COLORS->getColoredString("-> " . $msg, "white", "red") . "\n";
		$errors[] = array($tag, $address, substr($msg,0,20), basename($logfile));
		$new_errors = true;
	}
	if (is_file($outfile) && filesize($outfile)>0){
		$msg = "SAVED: [" . filesize($outfile)  . "B] '$outfile'";
		echo $_COLORS->getColoredString($msg, "black", "green") . "\n";
	}else{
		$msg = "SAVED: Empty! '$outfile'";
		echo $_COLORS->getColoredString($msg, "white", "red") . "\n";
		$errors[] = array($tag, $address, substr($msg,0,20), basename($logfile));
		$new_errors = true;
	}
	if ($_DEBUG || $new_errors){
		$msg = "LOG: $logfile";
		echo $_COLORS->getColoredString($msg, "black", "yellow") . "\n";
	}
	echo "---\n";
}

if (!empty($errors)){
	$msg = "Final report of errors:";
	echo $_COLORS->getColoredString($msg, "white", "red") . "\n";
	echo tabulate($errors, array("Tag", "Addr", "Error", "Log" ));
	$msg = "Log files saved in: $_LOGFILE_ROOTDIR";
	echo $_COLORS->getColoredString($msg, "white", "red") . "\n";
}else{
	$msg = "Sucessful.";
	echo $_COLORS->getColoredString($msg, "white", "green") . "\n";
}

 
