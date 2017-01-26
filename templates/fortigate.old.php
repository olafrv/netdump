<?php

$_TEMPLATE["fortigate.old"] = array(
	"cmd" => "scp -q -oStrictHostKeyChecking=no $auth[1]@$address:sys_config %outfile%"
	, "cases" => $_TEMPLATE["fortigate"]["cases"]
	, "answers" => $_TEMPLATE["fortigate"]["answers"]
);
