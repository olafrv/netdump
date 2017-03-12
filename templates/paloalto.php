<?php

//
// How to Save an Entire Configuration for Import into Another Palo Alto Networks Device
// https://live.paloaltonetworks.com/t5/Management-Articles/How-to-Save-an-Entire-Configuration-for-Import-into-Another-Palo/ta-p/61476
//
// > configure
// # save config to PAN_CurrentConfig.xml
// # scp export configuration from PAN_CurrentConfig.xml to netdump@10.0.1.242/ftp/
// # exit
// > exit
//

$_TEMPLATE["paloalto"] = array(
	"cmd" => "ssh -q -oStrictHostKeyChecking=no " . $auth[1] . "@" . $address
	, "output" => "async"
	, "cases" => array(
		array(
				array("^.*[Pp]assword:", "password", EXP_REGEXP)
			, array("^.*".$auth[1]."@[-_\.0-9A-Za-z]+\(.+\)>", "prompt", EXP_REGEXP)
			,	array("^.*".$auth[1]."@[-_\.0-9A-Za-z]+\(.+\)#", "configure", EXP_REGEXP)
		)
		, array(
			 	array("\033(\[.*\033*)+", "skip", EXP_REGEXP) // chr(27) [ chr(27)
			, array("^.*[Pp]assword:", "password", EXP_REGEXP)
			, array("Are you sure you want to continue connecting (yes/no)?", "ssh-key-yes", EXP_GLOB)
			, array("^.*".$auth[1]."@[-_\.0-9A-Za-z]+\(.+\)>", "prompt", EXP_REGEXP)
		)
		, array(
			array("^.*".$auth[1]."@[-_\.0-9A-Za-z]+\(.+\)>", "prompt", EXP_REGEXP)
		)
	)
  , "answers" => array(
		array(
			  array("password", "$auth[2]\n", 1)
			, array("prompt", "configure\n", 1)
			, array("configure", "save config to ".$target_tag.".xml\n", 1)
			, array("configure", "exit\n", 1, "jump")
		)
		, array(
				array("prompt", 
					"scp export configuration from ".$target_tag.".xml to $auth[3]@$auth[5]:$auth[6]\n", 1
				)
			, array("ssh-key-yes", "yes\n", 1)
			, array("password", "$auth[4]\n", 1, "jump")
		)
		, array(
			array("prompt", "exit\n", 1, "finish")
		)
	)
	, "pre-exec" => array(
		"rm -f '/opt/netdump/ftp/" . $target_tag . ".xml'" // Delete any previous backup
	)
	, "post-exec" => array(
		  "sleep 3"
		, "test -s '/opt/netdump/ftp/" . $target_tag . ".xml'"
		, "cp '/opt/netdump/ftp/" . $target_tag . ".xml' " . escapeshellarg($outfile)
		, "mv '/opt/netdump/ftp/" . $target_tag . ".xml' " . escapeshellarg($gitfile)
	)
);

