<?php

/*
 *    libnetdump.php 
 *    Copyright (C) 2017  Olaf Reitmaier Veracierta
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *		along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

define("NETDUMP_EOF", 0);         		// End of expect stream
define("NETDUMP_FULLBUFFER", 10); 		// Buffer stream is full 
define("NETDUMP_TIMEOUT", 20);    		// Input timeout error
define("NETDUMP_FINISHED", 30);  			// Completed and finished
define("NETDUMP_UNKNOWN_CASE", 30);   // Unknown case for match

function readlines($file, $skip = "#"){
	$lines = array();
	$handle = fopen($file, "r");
	while (($line = fgets($handle)) !== false){
		if ($line[0]!="#") $lines[] = $line;
	}
	fclose($handle);
	return $lines;
}

function splitlines($lines, $delimiter){
	$array = array(); 
	foreach($lines as $line){
  	$array[] = explode($delimiter, trim($line));
	}
	return $array;
}

function tabulate($array, $headers = NULL){
	$output_table = new Console_Table();
	if (!is_null($headers)) $output_table->setHeaders($headers);
	foreach($array as $row) $output_table->addRow($row);
	return $output_table->getTable();
}

function tabget($array, $index, $value){
	$found = NULL;
	foreach($array as $row){
		if ($row[$index] == $value){
			$found = $row;
			break;
		}
	}
	return $found;
}
				
function help(){
	global $_TARGETS_FILE;
	global $_AUTHS_FILE;
	global $_OUTFILE_ROOTDIR;
	global $_GITFILE_ROOTDIR;
	global $_LOGFILE_ROOTDIR;

			echo 
"
COMMANDS

php netdump.php [help]
  Shows this commands help

php netdump.php show target[s]
  List targets from file '$_TARGETS_FILE'

php netdump.php show auth[s]
  List crendentials file '$_AUTHS_FILE'

php netdump.php show dump[s] target [+/-days]
  List dumps for 'target' (case sensitive) created 'days' 
  before/after (+/-) somedays until today, using system 
  comands like: find, sort, etc.

php netdump.php show commit[s] target
  List commits made to git control version repository
  in '$_GITFILE_ROOTDIR' for 'target' (case sensitive)

php netdump.php show diff[s] target
  List changed (lines) between commits made to git control
  version repository for 'target' (case sensitive)

php netdump.php run [tag]
	Remotly dump configuration for target with 'tag	

php netdump.php debug [tag]
	Same as run with debugging

LOGGING

- Dumps are saved in: '$_OUTFILE_ROOTDIR'
- Logs are saved in: '$_LOGFILE_ROOTDIR'

\n";
	
}

function colorDebug($msg, $newline = "\n"){
	global $_COLORS;
	global $_DEBUG;
	return !$_DEBUG ? "" : $_COLORS->getColoredString($msg, "white", "magenta") . $newline;
}

function colorInfo($msg, $newline = "\n"){
	global $_COLORS;
	return $_COLORS->getColoredString($msg, "white", "blue") . $newline;
}

function colorOk($msg, $newline = "\n"){
	global $_COLORS;
	return $_COLORS->getColoredString($msg, "white", "green"). $newline;
}

function colorWarn($msg, $newline = "\n"){
	global $_COLORS;
	return $_COLORS->getColoredString($msg, "black", "yellow") . $newline;
}

function colorError($msg, $newline = "\n"){
	global $_COLORS;
	return $_COLORS->getColoredString($msg, "white", "red") . $newline;
}

function strvarsub($str, $vars){
	foreach($vars as $index => $value){
		$str = str_replace($index, $value, $str);
	}
	return $str;
}

function strclean($str){
	return trim(preg_replace('/[[:^print:]]/', '', $str)); 
}

function logError($msg, $target, $logfile){
	global $_ERRORS;	
	list($template, $target_tag, $address, $auth_tag) = $target;
	$_ERRORS[] = array($target_tag, $address, substr($msg,0,20), basename($logfile));
	return colorError($msg);
}


