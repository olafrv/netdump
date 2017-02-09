#!/usr/bin/php
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
 *    along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

require 'Console/Table.php';
require 'PHPMailer/PHPMailerAutoload.php';
require 'lib/common.php';
require 'lib/Automata.php';
require '/etc/netdump/mail.php';

$_RUN = false;
$_DEBUG = false;
$_MAIL_ACTIVE = false;
$_ROOTDIR = "/opt/netdump/netdump";
$_TARGETS_FILE = "/etc/netdump/targets.conf";
$_AUTHS_FILE = "/etc/netdump/auths.conf";
$_OUTFILE_ROOTDIR = "/var/lib/netdump/dumps";
$_GITFILE_ROOTDIR = "/var/lib/netdump/git";
$_LOGFILE_ROOTDIR = "/var/lib/netdump/logs";
$_TEMPLATE_ROOTDIR = $_ROOTDIR . "/templates";
$_ERRORS = array();
$_EXITCODE = 0;

$targets = splitlines(readlines($_TARGETS_FILE), ":");
$auths = splitlines(readlines($_AUTHS_FILE), ":");

$outfile_datedir = date("Y/m/d");
$outfile_datepfx = date("Ymd_his");

if (posix_geteuid()==0)
{
	logError("Must not be run as root");
	exit(2);
}

if (!is_file("/bin/bash")){
	logError("Bash shell is unavailable (/bin/bash)");
	exit(2);
}

// Parse arguments
if (isset($argv[1]))
{
	switch($argv[1])
	{	
		case "show":
			if (isset($argv[2]))
			{
				switch($argv[2])
				{
					case "target":
						// Show targets
						echo tabulate($targets, array("Template", "Address", "Tag", "Auth"));
						exit(0);
						break;

					case "auth";
						// Show authentication credential list
						echo tabulate($auths, array("Auth", "Param1", "Param2", "Parm3"));
						exit(0);
						break;

					case "dump":
						if (isset($argv[3]))
						{
							$backtime = -7;
							if (isset($argv[4])) $backtime = escapeshellarg($argv[4]);
							$cmd = 
								"find '$_OUTFILE_ROOTDIR' -type f -name " 
								. escapeshellarg('*' . $argv[3]. '*.conf') . " -mtime $backtime" 
								. " -printf \"%TY-%Tm-%Td %TH:%TM \t%k KB\t%p\n\" | sort -r\n";
							exec($cmd, $cmd_output, $cmd_status); // Git actions
							logEcho(implode("\n", $cmd_output));
							exit($cmd_status);
						}
						else
						{	
							help(); exit(-1);	
						}
						break;

					case "commit":
						if (isset($argv[3]))
						{
							$gitfile_dir = $_GITFILE_ROOTDIR . "/" . $argv[3];
							$cmd = "/bin/bash $_ROOTDIR/git/git-log.sh" . " " . escapeshellarg($gitfile_dir);
							if ($_DEBUG) logEcho("*** EXEC: " . $cmd, true);
							exec($cmd, $cmd_output, $cmd_status); // Git actions
							logEcho(implode("\n", $cmd_output));
							exit($cmd_status);
						}
						else
						{	
							help(); exit(-1);	
						}
						break;

					case "diff":
						if (isset($argv[3]))
						{
							$gitfile_dir = $_GITFILE_ROOTDIR . "/" . $argv[3];
							$cmd = "/bin/bash $_ROOTDIR/git/git-diff.sh " . escapeshellarg($gitfile_dir);
							if (isset($argv[4]) && isset($argv[5]))
							{
								$cmd .= " " . escapeshellarg($argv[4] . ".." . $argv[5]);
							}
							if ($_DEBUG) logEcho("EXEC: " . $cmd, true);
							exec($cmd, $cmd_output, $cmd_status); // Git actions
							logEcho(implode("\n", $cmd_output));
							exit($cmd_status);
						}
						else
						{	
							help(); exit(-1);	
						}
						break;

					default:
						// Show help (bad argument)
						help(); exit(-1);	
						break;
				}
			}
			else
			{
				help(); exit(-1);	
			}
			break;

		case "clone":
			if (isset($argv[2]) && isset($argv[3]))
			{
				$gitfile_dir = $_GITFILE_ROOTDIR . "/" . $argv[2];
				$cmd = "/bin/bash $_ROOTDIR/git/git-clone.sh " . escapeshellarg($gitfile_dir);
				$cmd .= " " . escapeshellarg($argv[3] . "/" . $argv[2]);
				$cmd .= " " . (isset($argv[4]) ? escapeshellarg($argv[4]) : "HEAD");
				if ($_DEBUG) logEcho("EXEC: " . $cmd, true);
				exec($cmd, $cmd_output, $cmd_status); // Git actions
				if ($_DEBUG) logEcho(implode("\n", $cmd_output));
				exit($cmd_status);
			}
			else
			{	
				help(); exit(-1);	
			}
			break;


		case "runmail":
			$_MAIL_ACTIVE = true;
		case "run":
			$_RUN = true;
			break;

		case "debugmail":
			$_MAIL_ACTIVE = true;
		case "debug":
			$_DEBUG = true;
			break;

		default:
			// Show help (bad argument)
			help(); exit(-1);	
			break;
	}
}
else
{
	// Show help (no argument)
	help(); exit(-1);	
}

// From here is only for run or debug
if (($_RUN || $_DEBUG) && isset($argv[2]) && is_null(tabget($targets, 1, $argv[2]))){
	logError("Target '" . $argv[2] . "' does not exists"); 
}

$targets_count = count($targets);
$targets_processed = 0;
foreach($targets as $target)
{

	if (count($target)<4) continue; // Skip empty lines from targets.conf
	$target = array_map("strclean", $target); // Trim spaces and non printable from targets.conf

	list($template, $target_tag, $address, $auth_tag) = $target; // Tokenize target array
	if (isset($argv[2]) and $target_tag != $argv[2]) continue; // Filter for specific target (tag)

	$auth = tabget($auths, 0, $auth_tag); // Find the authentication credentials for the target

	$targets_processed++; // Process the target!

	logEcho("*** TARGET: $template, $target_tag, $address, $auth[1]", true);

	// Define and create directory and file path
	$outfile_dir = $_OUTFILE_ROOTDIR . "/" . $target_tag . "/" . $outfile_datedir;
	$gitfile_dir = $_GITFILE_ROOTDIR . "/" . $target_tag;
	$logfile_dir = $_LOGFILE_ROOTDIR . "/" . $target_tag . "/" . $outfile_datedir;

	if (!is_dir($outfile_dir)) mkdir($outfile_dir, 0777, true);
	if (!is_dir($gitfile_dir)) mkdir($gitfile_dir, 0777, true);
	if (!is_dir($logfile_dir)) mkdir($logfile_dir, 0777, true);

	$outfile = $outfile_dir . "/" .  $outfile_datepfx . "_" . $target_tag . ".conf";
	$gitfile = $gitfile_dir . "/" .  $target_tag . ".conf";
	$logfile = $logfile_dir . "/" .  $outfile_datepfx . "_" . $target_tag . ".log";

	// Define expect library global settings
	ini_set("expect.timeout", 30);			// Expect input timeout?
	ini_set("expect.loguser", false);		// Expect input printed to stdout?
	ini_set("expect.match_max", 8192);	// Expect input buffer size?
	ini_set("expect.logfile", $logfile);// Expect session (input/output) log?

	$result; // Result code from expect execution

	$dependencies = explode(".", $template);
	$depends = array();
	foreach($dependencies as $dependency)
	{
		$depends[] = $dependency;
		$template_file = $_TEMPLATE_ROOTDIR . "/" . implode(".", $depends) . ".php";
		if (is_file($template_file)){
			if ($_DEBUG) logEcho("*** TEMPLATE: $template_file");
			require_once $template_file;
		}
		else
		{
			logError("Template file not found ($template_file)", $target, $logfile);
			continue;
		}
	}

	if (isset($_TEMPLATE[$template]))
	{
			if ($_DEBUG) logEcho(print_r($_TEMPLATE[$template], true));
			$cmd = $_TEMPLATE[$template]["cmd"]; 
			$cases_groups = $_TEMPLATE[$template]["cases"]; 
			$answers_groups = $_TEMPLATE[$template]["answers"]; 
			if ($_DEBUG) logEcho("*** CMD: " . $cmd, true);
			$automata = new \Netdump\Automata();
			$debug = array();
			$result = $automata->expect($cmd, $cases_groups, $answers_groups, $outfile, $debug);
			if ($_DEBUG) foreach($debug as $msg) logEcho($msg[0] . (isset($msg[1]) ? $msg[1] : ""));
	}
	else
	{
		logError("Undefined template '$template' in file '$template_file'", $target, $logfile);
		continue;
	}

	// Result code is an error?
	$msg = "";
	switch($result)
	{
		case AUTOMATA_EOF:
			// End of file (stream)
			if ($_DEBUG) logEcho("*** EOF");
			break;
		case AUTOMATA_TIMEOUT:
			$msg = "Error timeout!"; // Connection or expect timeout
			break;
		case AUTOMATA_FULLBUFFER:
			$msg = "Error buffer full!"; // Buffer full? => Raise expect buffer
			break;
		case AUTOMATA_FINISHED:
			// Finished (OK)
			if ($_DEBUG) logEcho("*** FINISHED");
			break;
		default:
			$msg = "Unknown case error result. Please debug!"; // Unknown error!
			break;
	}
	
	// Check for other common errors?
	if (!empty($msg)) logError($msg, $target, $logfile);

	// Dump was really saved?
	clearstatcache(); // clear stat cache! 
	$outfile_size = filesize($outfile);
	if (is_file($outfile) && $outfile_size>0)
	{
		if ($_DEBUG) logEcho("*** DUMP [" . $outfile_size  . "]: " . $outfile, true);
	}
	else
	{
		logError("Empty file '$outfile'!", $target, $logfile);
	}

	// Git repo create, backup configuration add and commit actions
	if (empty($_ERRORS))
	{
		$cmd = "/bin/bash $_ROOTDIR/git/git.sh" 
		. " " . escapeshellarg($gitfile_dir)
		. " " . escapeshellarg($outfile)
		. " " . escapeshellarg($gitfile)
		. " " . escapeshellarg("$target_tag $outfile_size $outfile_datepfx")
		. ($_DEBUG ? " 1" : "");
		if ($_DEBUG) logEcho("*** EXEC: " . $cmd);
		exec($cmd, $cmd_output, $cmd_status); // Git actions
		if ($cmd_status == 0)
		{
			file_put_contents($gitfile_dir . "/.git/description", "$target_tag"); // Update git repo name
			if ($_DEBUG) logEcho(implode("\n", $cmd_output));
		}
		else
		{
			logError("Error ($cmd_status) exec '$cmd' trace: " . implode("\n", $cmd_output), $target, $logfile);
		}
	}

	// Show log file path if there were target errors
	if ($_DEBUG && !is_null(tabget($_ERRORS, 1, $target_tag))) logEcho("LOG: $logfile");

}

// Final message (report)
if (!empty($_ERRORS))
{
	$errorList = tabulate($_ERRORS, array("Tag", "Addr", "Error", "Log"));
	logEcho($errorList);
	$_REPORT = array_merge(
		array(
			"Targets processed " . $targets_processed . "/" . $targets_count . "."
			, "Sorry, there were ".count($_ERRORS)." errors:"
			, $errorList
			, "Execution output:"
		)
		, $_REPORT
	);
	$_EXITCODE = -2;
}else{
	$_REPORT = array_merge(
		array(
			"Targets processed " . $targets_processed . "/" . $targets_count . "."
			, "No errors where reported, yeah!."
			, "Execution output:"
		)
		, $_REPORT
	);
}

$body = implode("\n", $_REPORT);
$subject = "Netdump [OK:" . $targets_processed . "/" . $targets_count . "]";
if ($_EXITCODE != 0) $subject = "Netdump [" . count($_ERRORS) . " errors]";
$subject .= " - $outfile_datepfx";

if ($_MAIL_ACTIVE){
	logEcho("*** EMAIL", true);
	$sent = sendmail(
		$_MAIL["from"],
		$_MAIL["to"],
		$subject,
		$body,
		$_MAIL["server"],
		$_MAIL["port"],
		$_MAIL["secure"],
		$_MAIL["user"],
		$_MAIL["password"]
	);
	if (!$sent["status"]){
		logError($sent["error"]);
	}
}

exit($_EXITCODE);
