<?php

$_TEMPLATE["cisco.telnet"] = array(
	"cmd" => "telnet $address",
	"cases" => array(
		array(
			array("^[Uu]sername:", "user", EXP_REGEXP),
			array("^[Pp]assword:", "password", EXP_REGEXP),
			array("^.*[-_\.0-9A-Za-z]+#$", "prompt", EXP_REGEXP, "jump"),
			array("*\n", "skip", EXP_GLOB) // Garbage output
		),
		array(
			array("show run", "show run", EXP_GLOB), // Mirror output from answers
			array("Building configuration...", "skip", EXP_GLOB), // Mirror output from answers
			array("^[\010]+[\x20h]+[\010]+", "chr", EXP_REGEXP), // Backspace-Space-Backspace
			array("*\n", "save", EXP_GLOB),
			array("*--More--*", "more", EXP_GLOB),
			array("^[-_\.0-9A-Za-z]+#$", "exit", EXP_REGEXP, "finish")
		)
	),
	"answers" => array(
		array(
			array("user", "$auth[1]\n", 1),
			array("password", "$auth[2]\n", 1),
			array("prompt", "show run\n", 1)
		),
		array(
			array("show run", "", 1),
			array("more", " ", -1),
			array("exit", "exit\n", 1)
		)
	)
);

