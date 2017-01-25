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
require_once 'lib/netdump.php';

$_COLORS = new Colors();
$_RUN = false;
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
					case "dump":
						if (isset($argv[3])){
							$backtime = -7;
							if (isset($argv[4])) $backtime = escapeshellarg($argv[4]);
							system(
									"find '$_OUTFILE_ROOTDIR' -type f -name '*" 
									. escapeshellarg($argv[3]) . "*.conf' -mtime $backtime" 
									. " -printf \"%TY-%Tm-%Td %TH:%TM:%TS% Tz\t%k KB\t%p\n\" | sort\n"
							);
						}else{	
							help(); exit(-1);	
						}
						break;
				}
			}
			exit(0);
			break;
		case "run":
			// Just run netdump
			$_RUN = true;
			break;
		case "debug":
			// Run and show debug messages
			$_RUN = true;
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
	// Result is an error?
	$msg = "";
	switch($result){
		case NETDUMP_EOF:
			// End of file (stream)
			break;
		case NETDUMP_TIMEOUT:
			$msg = "Error timeout!"; // Connection or expect timeout
			break;
		case NETDUMP_FULLBUFFER:
			$msg = "Error buffer full!"; // Buffer full? => Raise expect buffer
			break;
		case NETDUMP_FINISHED:
			// Finished (OK)
			break;
		default:
			$msg = "Unknown case error result. Please debug!"; // Unknown error!
			break;
	}
	$new_errors = false;
	if (!empty($msg) && $result == NETDUMP_EOF){
		echo colorWarn("-> " . $msg);
	}else if (!empty($msg)){
		echo colorError("-> " . $msg);
		$errors[] = array($tag, $address, substr($msg,0,20), basename($logfile));
		$new_errors = true;
	}
	if (is_file($outfile) && filesize($outfile)>0){
		echo colorOk("SAVED: [" . filesize($outfile)  . "B] '$outfile'");
	}else{
		echo colorError("SAVED: Empty! '$outfile'");
		$errors[] = array($tag, $address, substr($msg,0,20), basename($logfile));
		$new_errors = true;
	}
	if ($_DEBUG || $new_errors){
		colorWarn("LOG: $logfile");
	}
	echo "\n";
}
// Final message (report)
if (!empty($errors)){
	echo colorError("Final report of errors:");
	echo tabulate($errors, array("Tag", "Addr", "Error", "Log"));
	echo colorWarn("Log files saved in: $_LOGFILE_ROOTDIR");
	exit(-2);
}else{
	echo colorOk("Sucessful.");
	exit(0);
}

