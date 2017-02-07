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
require 'lib/Colors.php';
require 'PHPMailer/PHPMailerAutoload.php';
require 'lib/common.php';
require 'lib/Automata.php';
require '/etc/netdump/mail.php';

$_COLORS = new Colors();
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
	echo "Must not be run as root\n";
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
							$system_cmd = 
								"find '$_OUTFILE_ROOTDIR' -type f -name " 
								. escapeshellarg('*' . $argv[3]. '*.conf') . " -mtime $backtime" 
								. " -printf \"%TY-%Tm-%Td %TH:%TM \t%k KB\t%p\n\" | sort -r\n";
							// echo $system_cmd;
							system($system_cmd);
						}
						else
						{	
							help(); exit(-1);	
						}
						break;
					case "commit":
						if (isset($argv[3]))
						{
							if (is_file("/bin/bash"))
							{
								$gitfile_dir = $_GITFILE_ROOTDIR . "/" . $argv[3];
								$cmd = "/bin/bash $_ROOTDIR/git/git-log.sh" . " " . escapeshellarg($gitfile_dir);
								if ($_DEBUG) echo colorDebug("exec: ") . $cmd . "\n";
								exec($cmd, $cmd_output, $cmd_status); // Git actions
								echo implode("\n", $cmd_output) . "\n";
							}
							else
							{
								echo logError("Bash unavailable '/bin/bash'", $target, $logfile);
							}
						}
						else
						{	
							help(); exit(-1);	
						}
						break;
					case "diff":
						if (isset($argv[3]))
						{
							if (is_file("/bin/bash"))
							{
								$gitfile_dir = $_GITFILE_ROOTDIR . "/" . $argv[3];
								$cmd = "/bin/bash $_ROOTDIR/git/git-diff.sh" . " " . escapeshellarg($gitfile_dir);
								if (isset($argv[4]) && isset($argv[5]))
								{
									$cmd .= " " . escapeshellarg($argv[4] . ".." . $argv[5]);
								}
								if ($_DEBUG) echo colorDebug("exec: ") . $cmd . "\n";
								exec($cmd, $cmd_output, $cmd_status); // Git actions
								echo implode("\n", $cmd_output) . "\n";
							}
							else
							{
								echo logError("Bash unavailable '/bin/bash'", $target, $logfile);
							}
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
		case "run":
		case "runmail":
			// Just run netdump
			break;
		case "debug":
		case "debugmail":
			// Run and show debug messages
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

if (isset($argv[1]) && ($argv[1]=="runmail" || $argv[1] = "debugmail")) $_MAIL_ACTIVE = true;
	
foreach($targets as $target)
{

	if (count($target)<4) continue; // Skip empty lines from targets.conf
	$target = array_map("strclean", $target); // Trim spaces and non printable from targets.conf
	list($template, $target_tag, $address, $auth_tag) = $target;

	if (isset($argv[2]) and $target_tag != $argv[2]) continue; // Filter for specific target (tag)

	$auth = tabget($auths, 0, $auth_tag); // Find the authentication credentials for the target

	echo colorInfo("TARGET: Template: $template, Tag: $target_tag, Address: $address, User: $auth[1]");

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
			echo colorDebug("template: $template_file");
			require_once $template_file;
		}
		else
		{
			echo logError("Template file not found ($template_file)!", $target, $logfile);
			continue;
		}
	}

	if (isset($_TEMPLATE[$template]))
	{
			if ($_DEBUG) echo print_r($_TEMPLATE[$template], true);
			$cmd = $_TEMPLATE[$template]["cmd"]; 
			$cases_groups = $_TEMPLATE[$template]["cases"]; 
			$answers_groups = $_TEMPLATE[$template]["answers"]; 
			echo colorDebug($cmd) . "\n";
			$automata = new \Netdump\Automata();
			$debug = array();
			$result = $automata->expect($cmd, $cases_groups, $answers_groups, $outfile, $debug);
			if ($_DEBUG) foreach($debug as $msg) echo colorDebug($msg[0]) . (isset($msg[1]) ? $msg[1] : "");
	}
	else
	{
		echo logError("Undefined template '$template' in file '$template_file'!", $target, $logfile);
		continue;
	}

	// Result code is an error?
	$msg = "";
	switch($result)
	{
		case AUTOMATA_EOF:
			// End of file (stream)
			echo colorDebug("EOF");
			break;
		case AUTOMATA_TIMEOUT:
			$msg = "Error timeout!"; // Connection or expect timeout
			break;
		case AUTOMATA_FULLBUFFER:
			$msg = "Error buffer full!"; // Buffer full? => Raise expect buffer
			break;
		case AUTOMATA_FINISHED:
			// Finished (OK)
			echo colorDebug("finished");
			break;
		default:
			$msg = "Unknown case error result. Please debug!"; // Unknown error!
			break;
	}
	
	// Check for other common errors?
	if (!empty($msg)) echo logError($msg, $target, $logfile);

	// Dump was really saved?
	clearstatcache(); // clear stat cache! 
	$outfile_size = filesize($outfile);
	if (is_file($outfile) && $outfile_size>0)
	{
		echo colorDebug("dump [" . $outfile_size  . "] ->") . $outfile . "\n";
	}
	else
	{
		echo logError("Error empty file '$outfile'!", $target, $logfile);
	}

	// Git repo create, backup configuration add and commit actions
	if (empty($_ERRORS))
	{
		if (is_file("/bin/bash"))
		{
			$cmd = "/bin/bash $_ROOTDIR/git/git.sh" 
			. " " . escapeshellarg($gitfile_dir)
			. " " . escapeshellarg($outfile)
			. " " . escapeshellarg($gitfile)
			. " " . escapeshellarg("$target_tag $outfile_size $outfile_datepfx")
			. ($_DEBUG ? " 1" : "");
			if ($_DEBUG) echo colorDebug("exec: ") . $cmd . "\n";
			exec($cmd, $cmd_output, $cmd_status); // Git actions
			if ($cmd_status == 0)
			{
				file_put_contents($gitfile_dir . "/.git/description", "$target_tag"); // Update git repo name
				if ($_DEBUG) echo implode("\n", $cmd_output) . "\n";
			}
			else
			{
				echo logError("Error ($cmd_status) exec '$cmd' trace: " . implode("\n", $cmd_output), $target, $logfile);
			}
		}
		else
		{
			echo logError("Bash unavailable '/bin/bash'", $target, $logfile);
		}
	}

	// Show log file path if there were target errors
	if ($_DEBUG && !is_null(tabget($_ERRORS, 1, $target_tag))) echo colorWarn("LOG: $logfile");

	// Separator (Output)
	echo "\n";
}

// Final message (report)

if (!empty($_ERRORS))
{
	$report .= colorError("Final report of errors:");
	$report .= tabulate($_ERRORS, array("Tag", "Addr", "Error", "Log"));
	$rerpot .= colorWarn("Log files saved in: $_LOGFILE_ROOTDIR");
	$_EXITCODE = -2;
}else{
	$report = "No errors where reported.";
}

$body = $report;
$subject = "netdump [OK]";
if ($_EXITCODE != 0) $subject = "netdump [ERROR " . count($_ERRORS) . "]";
$subject .= " - $outfile_datepfx";

if ($_MAIL_ACTIVE){
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
		echo colorError($sent["error"]) . "\n";
		logToSyslog($sent["error"], LOG_ERR);
	}
}

exit($_EXITCODE);
