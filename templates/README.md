# Templates

A templates is a single PHP file defining the $_TEMPLATE array.

The following index values can be used in $_TEMPLATE definition:

 * *$target*: array of:
  * 0 := Template id
  * 1 := Target tag
  * 2 := Target hostname or IP address 
  * 3 := Authentication tag id
 * *$target_tag*, an alias for $target[1]
 * *$address*, an alias for $target[2]
 * *$auth*: array of:
  * 0 := Authentication tag id
  * 1 := Username
  * 2 := Password
  * 3 ... N := Depends on references inside this templates
 * *$auth_tag*, an alias for $target[1]
 * *$outfile_dir*, directory where $outfile is located.
 * *$outfile*, depends on the value of $output variable:
  * *sync*: then $outfile is created by expect automata for case 'save', and trigger an error if empty.
  * *async*: then $outfile is optional, could be empty. Could be created by user defined post-exec commands.
 * *$gitfile_dir*, directory where $gitfile is located.
 * *$gitfile*, depends on the value of $output variable:
  * *sync:* then $gitfile is created by expect automata from a copy of $outfile.
  * *async:* then $gitfile should be created by user defined post-exec commands.

Template should be defined as follows:
``` [php]
$_TEMPLATE["provider.device.os.version.anything.else"] = array(
  "cmd" => "dervice connection command with address and user and others"
  , "output" => "output processing type"
	, "cases" => array(
		array(
			  "pattern", "answer-id1", "pattern-type"
			, "pattern", "answer-id2", "pattern-type", "action"
		)
		, array(
			  "pattern", "answer-id1", "pattern-type"
			, "pattern", "answer-id2", "pattern-type", "action"
		)
	)
	, "answers" => array(
		array(
			  "answer-id1", "answer", repeats, 
			, "answer-id2", "answer", repeats, "action"
		)
		, array(
			  "answer-id1", "answer", repeats, 
			, "answer-id2", "answer", repeats, "action"
		)		
  )
	, "pre-exec" => array(
		  "remove previous backup files (async)"
		,	"other shell command #1"
		, "other shell command #N"	
	)
	, "post-exec" => array(
		  "test for file existence (async)"
		, "copy file to /var/lib/netdump/dumps/... (async)"
		, "move file to repositorio /var/lib/netdump/git/... (async)"
		, "other shell command #1"
		, "other shell command #N"	
	)
)
```

![Netdump Workflow](https://raw.githubusercontent.com/olafrv/netdump/master/doc/netdump.png "Netdump Workflow")

