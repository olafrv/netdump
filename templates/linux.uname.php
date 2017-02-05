<?php

$_TEMPLATE["linux.uname"] = array(
	"cmd" => "ssh -q -oStrictHostKeyChecking=no $auth[1]@$address"
	, "cases" => array(	
		array(
			array("password:", "password", EXP_GLOB),
			array("^.*[-_\.@\~\:0-9A-Za-z]+[#\$]+", "prompt", EXP_REGEXP, "jump")
		)
		, array(
			array("*\n", "save", EXP_GLOB),
			array("^.*[-_\.@\~\:0-9A-Za-z]+[#\$]+", "exit", EXP_REGEXP, "finish")
		)
	)
	, "answers" => array(
		array(
			array("password", "$auth[2]\n", 1)
			, array("prompt", "uname -a\n", 1)
		)
		, array(
			array("exit", "exit\n", 1)
		)
	)
);

