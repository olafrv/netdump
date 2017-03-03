<?php

// Bluecoat PacketShaper 
// Version: PacketShaper/ISP v7.1.2g1 2005-06-07
// Product: PacketShaper 8500/ISP

$_TEMPLATE["bluecoat.7.1"] = array(
	"cmd" => "telnet " . $address
	, "output" => "async"
	, "cases" => array(
		array(
			  array("^.*[Pp]assword:", "password", EXP_REGEXP, "jump")
//			, array("^.*[-_\.0-9A-Za-z]+#", "setup", EXP_REGEXP, "jump")
		)
		, array(
			array("^.*[-_\.0-9A-Za-z]+#", "ftp-backup", EXP_REGEXP, "jump")
		)
		, array(
			array("^.*[-_\.0-9A-Za-z]+#", "ftp-config", EXP_REGEXP, "jump")
		)
		, array(
			array("^.*[-_\.0-9A-Za-z]+#", "ftp-settings", EXP_REGEXP, "jump")
		)
		, array(
			array("^.*[-_\.0-9A-Za-z]+#", "ftp-basic", EXP_REGEXP, "jump")
		)
		, array(
			array("^.*[-_\.0-9A-Za-z]+#", "exit", EXP_REGEXP, "finish")
		)
	)
  , "answers" => array(
		array(
			  array("password", "$auth[2]\n", 1)
//			, array("setup", "setup capture complete backup.cmd\n", 1)
		)
    , array(
			array("ftp-backup", 
				"ftpput $auth[3]:$auth[4]@$auth[5] 9.256/cmd/backup.cmd ftp/".$target_tag."-backup.cmd\n"	, 1)
		)
    , array(
			array("ftp-config", 
				"ftpput $auth[3]:$auth[4]@$auth[5] 9.256/cfg/config.ldi ftp/".$target_tag."-config.ldi\n"	, 1)
		)
    , array(
			array("ftp-settings", 
				"ftpput $auth[3]:$auth[4]@$auth[5] 9.256/cfg/settings.cfg ftp/".$target_tag."-settings.cfg\n", 1)
		)
    , array(
			array("ftp-basic", 
				"ftpput $auth[3]:$auth[4]@$auth[5] 9.256/cfg/basic.cfg ftp/".$target_tag."-basic.cfg\n", 1)
		)
    , array(
			array("exit", "exit\n", 3)
		)
	)
	, "pre-exec" => array(
		  "rm -f '/opt/netdump/ftp/".$target_tag."-backup.cmd'"
		, "rm -f '/opt/netdump/ftp/".$target_tag."-config.ldi'"
		, "rm -f '/opt/netdump/ftp/".$target_tag."-settings.cfg'"
		, "rm -f '/opt/netdump/ftp/".$target_tag."-basic.cfg'"
	)
	, "post-exec" => array(
		  "test -s '/opt/netdump/ftp/".$target_tag."-backup.cmd'"
		, "mv '/opt/netdump/ftp/".$target_tag."-backup.cmd' "   . escapeshellarg($gitfile_dir."/")
		, "test -s '/opt/netdump/ftp/".$target_tag."-config.ldi'"
		, "mv '/opt/netdump/ftp/".$target_tag."-config.ldi' "   . escapeshellarg($gitfile_dir."/")
		, "test -s '/opt/netdump/ftp/".$target_tag."-settings.cfg'"
		, "mv '/opt/netdump/ftp/".$target_tag."-settings.cfg' " . escapeshellarg($gitfile_dir."/")
		, "test -s '/opt/netdump/ftp/".$target_tag."-basic.cfg'"
		, "mv '/opt/netdump/ftp/".$target_tag."-basic.cfg' "    . escapeshellarg($gitfile_dir."/")
	)
);

