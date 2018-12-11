<?php

$_TEMPLATE["netdump.dir"] = array(
	"cmd" => "echo dummy" // Fake output
	, "output" => "async"
	, "cases" => array(
		array(
			array(
				"dummy", "dummy", EXP_GLOB, "finish" // Expect fake output and finish
			)
		)
	)
  , "answers" => array(
		array(
			array(
				"dummy", "", 1 // Write nothing
			)
		)
	)
	, "pre-exec" => array(
	)
	, "post-exec" => array(
		  "test -d '/opt/netdump/ftp/" . $target_tag . "'" // Check for directory
		, "cp -ax '/opt/netdump/ftp/" . $target_tag . "' " . escapeshellarg($gitfile_dir) // Versioning of directory
	)
);

