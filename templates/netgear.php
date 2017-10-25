<?php

// Product: Netgear GS752TP ProSafe 48 Port Gigabit Smart Switch
// Requirements: Web GUI -> Maintenance -> Troubleshooting -> Remote Diagnostic -> Enable

$_TEMPLATE["netgear"] = array(
	"cmd" => "telnet $address",
	"cases" => array(
		array(
			array("*User Name:", "user", EXP_GLOB),
			array("*Password:", "password", EXP_GLOB),
			array("^.*[-_\.0-9A-Za-z]+#", "prompt", EXP_REGEXP, "jump")
		),
		array(
			array("*\n", "save", EXP_GLOB),
			array("More: <space>,*", "more", EXP_GLOB),
			array("^[-_\.0-9A-Za-z]+#$", "exit", EXP_REGEXP, "finish")
		)
	),
	"answers" => array(
		array(
			array("user", "$auth[1]\n", 1),
			array("password", "$auth[2]\n", 1),
			array("prompt", "show running-config\n", 1)
		),
		array(
			array("more", " ", -1),
			array("exit", "exit\n", 1)
		)
	)
);

