<?php

$_TEMPLATE["fortigate"] = array(
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

$_TEMPLATE["fortigate.old"] = array(
	"cmd" => "scp -q -oStrictHostKeyChecking=no $auth[1]@$address:sys_config %outfile%"
	, "cases" => $_TEMPLATE["fortigate"]["cases"]
	, "answers" => $_TEMPLATE["fortigate"]["answers"]
);
