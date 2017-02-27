<?php

// Foundry ServerIron

$_TEMPLATE["foundry"] = array(
	"cmd" => "telnet $address",
	"cases" => array(
		array(
			array("Password:", "password", EXP_GLOB),
			array("*>", "enable", EXP_GLOB),
			array("*#", "prompt", EXP_GLOB, "jump"),
		),
		array(
			array("[\010]+[\x20h]+[\010]", "chr", EXP_REGEXP), // Backspace-Space-Backspace
			array("*\n", "save", EXP_GLOB),
			array("*--More--*", "more", EXP_GLOB),
			array("*#", "exit", EXP_GLOB, "finish"),
		)
	),
	"answers" => array(
		array(
			array("password", "$auth[1]\r\n", 1), // Telnet password
			array("enable", "enable\r\n", 1),
			array("password", "$auth[2]\r\n", 1), // Enabled password
			array("prompt", "show configuration\r\n", 1)
		),
		array(
			array("more", " ", -1),
			array("exit", "exit\n", 1)
		)
	)
);

