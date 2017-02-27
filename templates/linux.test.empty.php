<?php

// Trigger empty file error for "sync" or "async"

$_TEMPLATE["linux.test.empty"] = array(
	"cmd" => "ssh -q -oStrictHostKeyChecking=no $auth[1]@$address"
	, "output" => "async" // To test use: "sync" or "async" 
	, "cases" => array(	
		array(
			array("password:", "password", EXP_GLOB),
			array("^.*[-_\.@\~\:0-9A-Za-z]+[#\$]+", "prompt", EXP_REGEXP, "jump")
		)
		, array(
			array("*\n", "save", EXP_GLOB), // To test use: "save" or "skip"
			array("^.*[-_\.@\~\:0-9A-Za-z]+[#\$]+", "exit", EXP_REGEXP, "finish")
		)
	)
	, "answers" => array(
		array(
			array("password", "$auth[2]\n", 1)
			, array("prompt", "uname -nr; date;\n", 1)
		)
		, array(
			array("exit", "exit\n", 1)
		)
	)
);

