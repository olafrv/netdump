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

require_once 'Console/Table.php';
require_once 'lib/Colors.php';
require_once 'lib/netdump.php';

$_COLORS = new Colors();
$_RUN = false;
$_DEBUG = false;
$_ROOTDIR = "/opt/netdump/netdump";
$_TARGETS_FILE = "/etc/netdump/targets.conf";
$_AUTHS_FILE = "/etc/netdump/auths.conf";
$_OUTFILE_ROOTDIR = "/var/lib/netdump/dumps";
$_GITFILE_ROOTDIR = "/var/lib/netdump/git";
$_LOGFILE_ROOTDIR = "/var/lib/netdump/logs";
$_TEMPLATE_ROOTDIR = "./templates";
$_ERRORS = array();

$targets = splitlines(readlines($_TARGETS_FILE), ":");
$auths = splitlines(readlines($_AUTHS_FILE), ":");

$outfile_datedir = date("Y/m/d");
$outfile_datepfx = date("Ymd_his");

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
						if (isset($argv[3]))
						{
							$backtime = -7;
							if (isset($argv[4])) $backtime = escapeshellarg($argv[4]);
							$system_cmd = 
								"find '$_OUTFILE_ROOTDIR' -type f -name " 
								. escapeshellarg('*' . $argv[3]. '*.conf') . " -mtime $backtime" 
								. " -printf \"%TY-%Tm-%Td %TH:%TM \t%k KB\t%p\n\" | sort\n";
							// echo $system_cmd;
							system($system_cmd);
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
}
else
{
	// Show help (no argument)
	help(); exit(-1);	
}

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

	$template_file = $_TEMPLATE_ROOTDIR . "/" . $template . ".php";

	// Define expect library global settings
	ini_set("expect.timeout", 10);			// Expect input timeout?
	ini_set("expect.loguser", false);		// Expect input printed to stdout?
	ini_set("expect.match_max", 8192);	// Expect input buffer size?
	ini_set("expect.logfile", $logfile);// Expect session (input/output) log?

	$result; // Result code from expect execution

	if (is_file($template_file))
	{
		require $template_file;
		if (isset($_TEMPLATE[$template]))
		{
			if ($_DEBUG) echo colorDebug("template: $template_file");
			if ($_DEBUG) print_r($_TEMPLATE[$template]);
			$cmd = $_TEMPLATE[$template]["cmd"]; 
			$cases_groups = $_TEMPLATE[$template]["cases"]; 
			$answers_groups = $_TEMPLATE[$template]["answers"]; 
			if ($_DEBUG) echo colorDebug($cmd) . "\n";
			$result = automata_netdump($cmd, $cases_groups, $answers_groups, $outfile);
		}
		else
		{
			echo logError("Undefined template '$template' in file '$template_file'!", $target, $logfile);
			continue;
		}
	}
	else
	{
		echo logError("Template file not found ($template_file)!", $target, $logfile);
		continue;
	}
	
	// Result code is an error?
	$msg = "";
	switch($result)
	{
		case NETDUMP_EOF:
			// End of file (stream)
			if ($_DEBUG) colorWarn("EOF");
			break;
		case NETDUMP_TIMEOUT:
			$msg = "Error timeout!"; // Connection or expect timeout
			break;
		case NETDUMP_FULLBUFFER:
			$msg = "Error buffer full!"; // Buffer full? => Raise expect buffer
			break;
		case NETDUMP_FINISHED:
			// Finished (OK)
			if ($_DEBUG) colorWarn("FINISHED");
			break;
		default:
			$msg = "Unknown case error result. Please debug!"; // Unknown error!
			break;
	}
	
	// Check for other common errors?
	if (!empty($msg)) echo logError($msg, $target, $logfile);

	// Dump was really saved?
	if (is_file($outfile) && filesize($outfile)>0)
	{
		echo colorOk("SAVED: [" . filesize($outfile)  . "B] '$outfile'");
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
			$cmd = "/bin/bash $_ROOTDIR/git.sh" 
			. " " . escapeshellarg($gitfile_dir)
			. " " . escapeshellarg($outfile)
			. " " . escapeshellarg($gitfile)
			. " " . escapeshellarg(
				$target_tag . " configuration dumped at " . $outfile_datedir . " " . $outfile_datepfx
			);
			exec($cmd, $cmd_output, $cmd_status); // Git actions
			if ($cmd_status == 0)
			{
				file_put_contents($gitfile_dir . "/.git/description", "$target_tag"); // Update git repo name
			}
			else
			{
				if ($_DEBUG){
					echo "$cmd" . "\n";
					echo colorDebug(implode("\n", $cmd_output));
				}
				echo logError("Error ($cmd_status) executing command '$cmd'", $target, $logfile);
			}
		}
		else
		{
			echo logError("Bash interpreter is not present '/bin/bash'", $target, $logfile);
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
	echo colorError("Final report of errors:");
	echo tabulate($_ERRORS, array("Tag", "Addr", "Error", "Log"));
	echo colorWarn("Log files saved in: $_LOGFILE_ROOTDIR");
	exit(-2);
}
else
{
	if ($_DEBUG) echo colorOk("Sucessful.");
	exit(0);
}

