<?php

$_TEMPLATE["fortinet.fortios"] = array(
	"cmd" => "ssh -q -oStrictHostKeyChecking=no " . $auth[1] . "@" . $address
	, "output" => "async"
	, "cases" => array(
		array(
			  array("^.*[Pp]assword:", "sshpassword", EXP_REGEXP)
			, array("^.*[-_\.0-9A-Za-z]+ #", "prompt", EXP_REGEXP)
			,	array("^.*[-_\.0-9A-Za-z]+ \(global\) #", "prompt global", EXP_REGEXP)
		)
	)
  , "answers" => array(
		array(
			  array("sshpassword", "$auth[2]\n", 1)
			, array("prompt", "config global\n", 1)
			,	array("prompt global", "execute backup full-config ftp ftp/" . $target_tag . ".conf $auth[5] $auth[3] $auth[4]\n", 1)
			, array("prompt global", "end\n", 1)
			, array("prompt", "exit\n", 1, "finish")
		)
	)
	, "pre-exec" => array(
		"rm -f '/opt/netdump/ftp/" . $target_tag . ".conf'" // Delete any previous backup
	)
	, "post-exec" => array(
		"sleep 3" // Fortinet FTP job preventive sleep
		, "test -s '/opt/netdump/ftp/" . $target_tag . ".conf'"
		, "cp '/opt/netdump/ftp/" . $target_tag . ".conf' " . escapeshellarg($outfile)
		, "mv '/opt/netdump/ftp/" . $target_tag . ".conf' " . escapeshellarg($gitfile)
	)
);

