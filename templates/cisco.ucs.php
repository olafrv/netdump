<?php

// Cisco UCS Manager CLI Configuration Guide, Release 2.1 
//
// Create and run backups in Cisco UCS CLI:
//
// http://www.cisco.com/c/en/us/td/docs/unified_computing/ucs/sw/cli/config/guide/2-1/b_UCSM_CLI_Configuration_Guide_2_1/b_UCSM_CLI_Configuration_Guide_2_1_chapter_0100111.html
//
// This templates works if you create a backup in the following way:
//
// scope system
// create backup {protocol}://{server}/{target}.bin disabled
// commit-buffer
// exit
//
// Where:
// {protocol} = tftp, ftp, sftp, scp 
// {server}   = tftp, ftp, sftp, scp (SSH) IP server address
// {target}   = Target tag as specified in the target.conf

$_TEMPLATE["cisco.ucs"] = array(
	"cmd" => "ssh -q -oStrictHostKeyChecking=no " . $auth[1] . "@" . $address
	, "cases" => array(
		array(
			  array("^.*[Pp]assword:", "sshpassword", EXP_REGEXP)
			, array("^.*[-_\.0-9A-Za-z]+#", "system", EXP_REGEXP)
			, array("^.*[-_\.0-9A-Za-z]+ \/system #", "backup", EXP_REGEXP, "jump")
		)
		, array(
			  array("^.*[-_\.0-9A-Za-z]+ \/system/backup\** #", "set-type", EXP_REGEXP, "jump")
		)
		, array(
			  array("^.*[-_\.0-9A-Za-z]+ \/system/backup\** #", "set-remote-file", EXP_REGEXP, "jump")
		)
		, array(
			  array("^.*[-_\.0-9A-Za-z]+ \/system/backup\** #", "enable", EXP_REGEXP, "jump")
		)
		// Uncomment for FTP, SFTP and SCP
		//, array(
		//	array("^.*[Pp]assword:", "password", EXP_REGEXP, "jump")
		//)
		, array(
			  array("^.*[-_\.0-9A-Za-z]+ \/system/backup\** #", "commit-buffer", EXP_REGEXP, "jump")
		)
		, array(
			  array("^.*[-_\.0-9A-Za-z]+ \/system/backup #", "exit", EXP_REGEXP)
			,	array("^.*[-_\.0-9A-Za-z]+ \/system #", "exit", EXP_REGEXP)
			, array("^.*[-_\.0-9A-Za-z]+#", "exit", EXP_REGEXP, "finish")
		)
	)
  , "answers" => array(
		array(
			  array("sshpassword", "$auth[2]\n", 1)
			, array("system", "scope system\n", 1)
			, array("backup", "scope backup $auth[4]\n", 1)
		)
    , array(
			  array("set-type", "set type $auth[3]\n", 1)
		)
    , array(
			  array("set-remote-file", "set remote-file " . $target_tag . ".bin\n", 1)
		)
    , array(
			  array("enable", "enable\n", 1)
		)
		// Uncomment for FTP, SFTP and SCP
		//, array(
		//	array("password", "$auth[4]\n", 1)
		//)
    , array(
			  array("commit-buffer", "commit-buffer\n", 1)
		)
    , array(
			  array("exit", "exit\n", 3)
		)
	)
	, "pre-exec" => array(
		"rm -f '/var/lib/tftpboot/" . $target_tag . ".bin'" // Delete any previous backup
	)
	, "post-exec" => array(
		"sleep 120" // UCS asynchronous job triggering
		, "mv '/var/lib/tftpboot/" . $target_tag . ".bin' " . escapeshellarg($outfile)
	)
);

