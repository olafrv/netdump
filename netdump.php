#!/usr/bin/php
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

set_time_limit(1800); // 30 Minutes

$targets = splitlines(readlines($_TARGETS_FILE), ":");
$auths = splitlines(readlines($_AUTHS_FILE), " ");

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
							help(); exit(-1);	// Show help (no argument)
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
							help(); exit(-1);	// Show help (no argument)
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
							help(); exit(-1); // Show help (no argument)	
						}
						break;

					default:
						help(); exit(-1);	// Show help (bad argument)
						break;
				}
			}
			else
			{
				help(); exit(-1);	// Show help (no argument)
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
				help(); exit(-1);	// Show help (no argument)
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
			help(); exit(-1);	// Show help (bad argument)
			break;
	}
}
else
{
	help(); exit(-1);	// Show help (no argument)
}

// From here is only for run or debug
$targets_count = count($targets);
if (($_RUN || $_DEBUG) && isset($argv[2]))
{
	$target_found = tabget($targets, 1, $argv[2]);
	if (!is_null($target_found))
	{
		$targets_counts = 1;
		$targets = array($target_found);	
	}
	else
	{
		logError("Target '" . $argv[2] . "' does not exists"); 
		help(); exit(); // Show help (wrong argument)
	}
}

$targets_processed = 0;
foreach($targets as $target)
{
	$targets_processed++; // Count target processed!

	if (count($target) < 4)
	{
		logError("Wrong target specification '".implode(" ", $target) . "'", $target, NULL);
		continue; // Skip wrong target specification from 'targets.conf'
	}

	list($template, $target_tag, $address, $auth_tag) = $target; // Tokenize target specification

	$auth = tabget($auths, 0, $auth_tag); // Find the authentication credentials for the target
	if (is_null($auth))
	{
		logError("Authentication credential '$auth_tag' does not exists!", $target, NULL);
		continue;
	}

	logEcho("*** TARGET: $template, $target_tag, $address", true);

	// *** DIRECTORY AND FILE PATHS CREATION - BEGIN
	$outfile_dir = $_OUTFILE_ROOTDIR . "/" . $target_tag . "/" . $outfile_datedir;
	$gitfile_dir = $_GITFILE_ROOTDIR . "/" . $target_tag;
	$logfile_dir = $_LOGFILE_ROOTDIR . "/" . $target_tag . "/" . $outfile_datedir;

	if (!is_dir($outfile_dir)) mkdir($outfile_dir, 0777, true);
	if (!is_dir($gitfile_dir)) mkdir($gitfile_dir, 0777, true);
	if (!is_dir($logfile_dir)) mkdir($logfile_dir, 0777, true);

	$outfile = $outfile_dir . "/" .  $outfile_datepfx . "_" . $target_tag . ".conf";
	$gitfile = $gitfile_dir . "/" .  $target_tag . ".conf";
	$logfile = $logfile_dir . "/" .  $outfile_datepfx . "_" . $target_tag . ".log";
	// *** DIRECTORY AND FILE PATHS CREATION - END

	// *** EXPECT GLOBAL SETTING - BEGIN
	ini_set("expect.timeout", 30);			// Expect input timeout?
	ini_set("expect.loguser", $_DEBUG);		// Expect input printed to stdout?
	ini_set("expect.match_max", 8192);	// Expect input buffer size?
	ini_set("expect.logfile", $logfile);// Expect session (input/output) log?
	// *** EXPECT GLOBAL SETTING - END

	// *** TEMPLATE LOADING - BEGIN
  //
	// Variables allowed to use (pre-defined) in templates:
  // * $target, $target_tag, $address
  // * $auth, $auth_tag
  // * $outfile, $outfile_dir
  // * $gitfile, $gitfile_dir
  //
	$template_file = $_TEMPLATE_ROOTDIR . "/" . $template . ".php";
	if (is_file($template_file))
	{
		if ($_DEBUG) logEcho("*** TEMPLATE: $template_file");
		require($template_file);
		if (isset($_TEMPLATE[$template]))
		{
			if ($_DEBUG) logEcho(print_r($_TEMPLATE[$template], true));
		}
		else
		{
			logError("Undefined \$_TEMPLATE[\"". $template . "\"] in '$template_file'", $target, $logfile);
			continue;
		}
	}
	else
	{
		logError("Template file not found '$template_file'", $target, $logfile);
		continue;
	}
	// *** TEMPLATE LOADING - END

	// *** PRE-EXEC - BEGIN
	$pre_exec = true;
	if (isset($_TEMPLATE[$template]["pre-exec"]))
	{
		$cmds = $_TEMPLATE[$template]["pre-exec"];
		foreach($cmds as $cmd)
		{
			if ($_DEBUG) logEcho("*** PRE-EXEC: " . $cmd, true);
			exec($cmd, $cmd_output, $cmd_status);
			if ($_DEBUG) logEcho(implode("\n", $cmd_output));
			$pre_exec = $pre_exec &&  ($cmd_status==0);
			if ($cmd_status!=0) logError("Error on pre-exec '$cmd'", $target, $logfile);
		}
		if (!$pre_exec) continue; // Skip this target if pre-exec was not sucessfull
	}
	// *** PRE-EXEC - END

	// *** AUTOMATA - BEGIN
	$cmd = $_TEMPLATE[$template]["cmd"]; 
	$cases_groups = $_TEMPLATE[$template]["cases"]; 
	$answers_groups = $_TEMPLATE[$template]["answers"]; 
	$output_file_sync = isset($_TEMPLATE[$template]["output"]) ? $_TEMPLATE[$template]["output"] : "sync";
	$automata = new \Netdump\Automata(); // Expect automata
	$result; // Result code from expect execution
	$retries = 0; // Retries counter
	$retries_max = 3;
	$retries_sleep = 5;

	while(++$retries <= $retries_max)
	{
		$debug = array();

		if ($retries>1)
		{
			if ($_DEBUG) logEcho("*** SLEEP($retries_sleep): Before retry...", true);
			sleep($retries_sleep); 
		}

		if ($_DEBUG) logEcho("*** CMD($retries/$retries_max): " . $cmd, true);

		if ($output_file_sync=="async")
		{
			if ($_DEBUG) logEcho("*** DUMP: Asynchronous should be managed by 'post-exec'", true);
			$result = $automata->expect($cmd, $cases_groups, $answers_groups, NULL, $debug);
		}
		else
		{
			if ($_DEBUG) logEcho("*** DUMP: Created by expect (Synchronous)", true);
			$result = $automata->expect($cmd, $cases_groups, $answers_groups, $outfile, $debug);
		}

		if ($_DEBUG) foreach($debug as $msg) if (!empty($msg[0])) logEcho($msg[0] . (isset($msg[1]) ? $msg[1] : "")); // Expect debug messages

		if ($retries < $retries_max)
		{ 
			if ($result == AUTOMATA_TIMEOUT)
			{
				logEcho("*** Timeout!, retrying...", true);
				continue; // Retry when timeout (connection or response)
			}
			else if ($output_file_sync=="sync")
			{
				clearstatcache(); 
				if (!is_file($outfile) || filesize($outfile)==0){
					logEcho("*** Empty dump!, retrying...", true);
					continue; // Retry when sync empty output file
				}
			}
		}

		break; // Everything seems OK (Stop retrying)!
	}

	switch($result)
	{
		case AUTOMATA_EOF:
			if ($_DEBUG) logEcho("*** EOF"); // End of file (stream)
			break;
		case AUTOMATA_TIMEOUT:
			$msg = "Error timeout!"; // Connection or expect timeout
			logError($msg, $target, $logfile);
			break;
		case AUTOMATA_FULLBUFFER:
			$msg = "Error buffer full!"; // Buffer full? => Raise expect buffer
			logError($msg, $target, $logfile);
			break;
		case AUTOMATA_FINISHED:
			if ($_DEBUG) logEcho("*** FINISHED"); // As programmed in cases so it's ok
			break;
		default:
			$msg = "Unknown case error result. Please debug!"; // Unknown error!
			logError($msg, $target, $logfile);
			break;
	}
	if ($_DEBUG && !is_null(tabget($_ERRORS, 1, $target_tag))) logEcho("*** EXPECT LOG: $logfile");
	// *** AUTOMATA - END

	// *** POST-EXEC - BEGIN
	$post_exec = true;
	if (isset($_TEMPLATE[$template]["post-exec"]))
	{
		$cmds = $_TEMPLATE[$template]["post-exec"];
		foreach($cmds as $cmd)
		{
			if ($_DEBUG) logEcho("*** POST-EXEC: " . $cmd, true);
			exec($cmd, $cmd_output, $cmd_status);
			if ($_DEBUG) logEcho(implode("\n", $cmd_output));
			$post_exec = $post_exec & ($cmd_status==0);
			if ($cmd_status!=0) logError("Error on post-exec '$cmd'", $target, $logfile);
		}
		if (!$post_exec) continue; // Skip this target if pre-exec was not sucessfull
	}
	// *** POST-EXEC - END

	// *** EMPTY OUTPUT FILE - BEGIN
	if ($output_file_sync=="sync")
	{
		clearstatcache(); $outfile_size = is_file($outfile) ? filesize($outfile) : 0;
		if ($outfile_size>0)
		{
			if ($_DEBUG) logEcho("*** DUMP [" . $outfile_size  . "]: " . $outfile, true);
		}
		else
		{
			logError("Empty file '$outfile'!", $target, $logfile);
		}
	}
	// *** EMPTY OUTPUT FILE - END

	// *** VERSION CONTROL - BEGIN
	if (empty($_ERRORS))
	{
		// Git repo create, backup configuration add and commit actions
		if ($output_file_sync=="sync")
		{
			$cmd = "/bin/bash $_ROOTDIR/git/git-sync.sh" 
			. " " . escapeshellarg($gitfile_dir)
			. " " . escapeshellarg($outfile)
			. " " . escapeshellarg($gitfile)
			. " " . escapeshellarg("$target_tag $outfile_size $outfile_datepfx")
			. ($_DEBUG ? " 1" : "");
		}
		else
		{
			$cmd = "/bin/bash $_ROOTDIR/git/git-async.sh" 
			. " " . escapeshellarg($gitfile_dir)
			. " " . escapeshellarg("$target_tag $outfile_datepfx")
			. ($_DEBUG ? " 1" : "");
		}
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
	// *** VERSION CONTROL - END

}

// *** FINAL REPORT - BEGIN
if (!empty($_ERRORS))
{
	$errorList = tabulate($_ERRORS, array("Tag", "Addr", "Error", "Log"));
	logEcho("\n" . $errorList);
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
// *** FINAL REPORT - END


// *** EMAIL NOTIFICATION - BEGIN
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
// *** EMAIL NOTIFICATION - END

exit($_EXITCODE);

