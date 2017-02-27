<?php

$_TEMPLATE["fortinet.fgt"] = array(
	"cmd" => "scp -q -oStrictHostKeyChecking=no $auth[1]@$address:fgt-config $outfile"
	, "cases" => array(	
		array(
			array("password:", "password", EXP_GLOB),
			array(".*\n", "skip", EXP_REGEXP)
		)
	)
	, "answers" => array(
		array(
			array("password", "$auth[2]\n", 1)
		)
	)
);

