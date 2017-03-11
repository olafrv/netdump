<?php

/*
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

namespace Netdump;

define("AUTOMATA_EOF", 0);							// End of expect stream
define("AUTOMATA_FULLBUFFER", 10);			// Buffer stream is full 
define("AUTOMATA_TIMEOUT", 20);				// Input timeout error
define("AUTOMATA_FINISHED", 30);				// Completed and finished
define("AUTOMATA_UNKNOWN_CASE", 30);		// Unknown case for match

class Automata {

	function strgetchr($str, $to_string = true){
		$chars = array();
		foreach(str_split($str) as $char){
			if (ctype_print($char)){
			 $chars[] = $char;
			}else{
			 $chars[] = ' chr(' . ord($char) . ') ';
			}
		}
		if ($to_string){
			return implode("", $chars);
		}else{
			return $chars;
		}
	}

	function expect($cmd, $cases_groups, $answers_groups, $outfile, &$debug){
		$outstream = NULL; // Not always it is needed to save expect selected output
		if (!is_null($outfile)) $outstream = fopen($outfile, "w+"); // Here is saved selected output
		$stream = expect_popen($cmd); // Command input/ouput stream

		$finished = false;

		for($iteration = 0; $iteration < count($cases_groups); $iteration++)
		{
			$cases = $cases_groups[$iteration];  // expr | case name | expr type | jump next group?
			$answers = $answers_groups[$iteration]; // case name | answer | times -1, 0, 1, ...?, 
			$debug[] = ["iteration:", $iteration . "\n"];
			while (true) 
			{
				$debug[] = ["expect"];
				$matchs = array(); // stream input matches 0, 1, 2, ... 8
				$casename = expect_expectl($stream, $cases, $matchs); // group of cases
				$matched = isset($matchs[0]) ? $matchs[0] : ""; // whole input which matched the case
				$case = tabget($cases, 1, $casename);

				$answered = false;

				// First reserved answers
				if ($casename == "chr")
				{ 
					// Print characters ASCII codes & skip
					$debug[] = ["chr ->' ", $this->strgetchr($matched) . "'\n"];
					$answered = true;
				}
				else if ($casename == "skip")
				{ 
					$debug[] = ["skip -> '", $matched . "'\n"];	
					$answered = true;
				}
				else if ($casename == "save")
				{ 
					// Save input to file
					$written = 0;
					if (!is_null($outstream))
					{
						if (strlen($matched)>0) $written = fwrite($outstream, $matched); // Save match (buffer)
						$debug[] = ["save [$written] -> ", $matched . "\n"];
					}
					else
					{
						$debug[] = ["save [$written] -> ", "skipped because no output file defined!\n"];
					}
					$answered = true;
				}
		
				if ($answered)
				{
					// Case has a jump or finish action?
					if (!(isset($case[3]) && in_array(strtolower($case[3]), array("jump", "finish"))))
					{
						continue; // If not just continue because the answer was: chr, skip, save
					}
				}

				// Second, custom answers (if not previously answered!)
				$custom_answer = null;
				for($i=0; !$answered && ($i<count($answers)); $i++)
				{
					if ($answers[$i][0] == $casename)
					{
						$custom_answer = $answers[$i]; // Needed later for answer action!

						// When -1 means this answer can be used always
						if ($answers[$i][2] == 0) continue; // This answer can't be used, look for another...
						if ($answers[$i][2] > 0) --$answers[$i][2]; // Use this answer again (n-times)
						$debug[] = ["answer (match:{$casename}) -> ", $matched . "\n"];	
						if (empty($answers[$i][1]))
						{ 
							// Skip once, n-times or always (do nothing)
							$debug[] = ["answer (skip)", $matched . "\n"];	
						}else{
							// Answer
							fwrite($stream, $answers[$i][1]); // Answers ...
							$debug[] = ["answer <- ", $answers[$i][1] . "\n"];
						}
						$answered = true;
					}
				}

				if ($answered)
				{	
					// Answer action?
					if (!is_null($custom_answer) && isset($custom_answer[3]))
					{
						if (in_array(strtolower($custom_answer[3]), array("jump", "finish")))
						{
							$debug[] = [ "last answer {$custom_answer[1]} -> action:"
														. strtolower($custom_answer[3]) . "\n" ];
							if (strtolower($custom_answer[3]) == "jump"){
								break 1; // Continue with the next group of cases (break while)
							}else{
								$finished = true;
								break 2; // Do not process more group of cases (break for)
							}
						}
						else
						{	
							$casename = "";
							$debug[] = ["unknown 'answer' action '$custom_answer[3]'", ""];				
							break 2; // Abort to show an error!
						}
					}

					// Case action?
					if (isset($case[3]))
					{ 
						if (in_array(strtolower($case[3]), array("jump", "finish")))
						{
							$debug[] = [ "last case {$casename} -> action:"
														. strtolower($case[3])."\n" ];
							if (strtolower($case[3]) == "jump"){
								break 1; // Continue with the next group of cases (break while)
							}else{
								$finished = true;
								break 2; // Do not process more group of cases (break for)
							}
						}
						else
						{
							$casename = "";
							$debug[] = ["unknown 'case' action '$case[3]'", ""];				
							break 2; // Abort to show an error!
						}
					}
				}
				else
				{ 
					// EOF, Timeout, Full buffer, Unknown...
					switch($casename)
					{
						case EXP_EOF:
							$debug[] = ["eof"];
							break;
						case EXP_TIMEOUT:
							$debug[] = ["timeout"];
							break;
						case EXP_FULLBUFFER:
							$debug[] = ["fullbuffer"];
							break;
						default:
							$debug[] = ["unknown error on case '$casename' -> ", $this->strgetchr($matched)];				
							break;
					}
					break 2; // Do not process more group of cases (break for)
				}
			}
		}
		fclose($stream);
		if (!is_null($outstream)) fclose($outstream);
		switch($casename){
			case EXP_EOF:
				$result = AUTOMATA_EOF;
				break;
			case EXP_TIMEOUT:
				$result = AUTOMATA_TIMEOUT;
				break;
			case EXP_FULLBUFFER:
				$result = AUTOMATA_FULLBUFFER;
				break;
			case "finish":
				$result = AUTOMATA_FINISHED;
				break;
			default:
				$result = AUTOMATA_UNKNOWN_CASE;
				break;
		}
		return $result;
	}

}
