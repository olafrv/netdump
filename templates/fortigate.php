<?php

$_TEMPLATES["fortigate"] = array(
	"cmd" => "scp -q -oStrictHostKeyChecking=no $user@$address:fpt-config $outfile"
	, "cases" => array(	
		array(
			array("password:", "password", EXP_GLOB),
		)
	)
	, "answers" => array(
		array(
			array("password", "$auth[2]\n", 1)
		)
	)
);

$_TEMPLATES["fortigate.old"] = array(
	"cmd" => "scp -q -oStrictHostKeyChecking=no $user@$address:sys_config %outfile%"
	, "cases" => $_TEMPLATES["fortigate"]["cases"]
	, "answers" => $_TEMPLATES["fortigate"][["answers"]
);
