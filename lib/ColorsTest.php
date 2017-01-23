<?php

// PHP CLI Colors – PHP Class Command Line Colors (bash)
/*
https://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-cli-scripts-output-php-output-colorizing-using-bash-shell-colors/
PHP Command Line Interface (CLI) has not built-in coloring for script output, like example Perl language has (perldoc.perl.org/Term/ANSIColor.html). So I decided to make own class for adding colors on PHP CLI output. This class works only Bash shells. This class is easy to use. Just create new instance of class and call getColoredString function with string and foreground color and/or background color.
*/

		require("Colors.php");

		// Create new Colors class
		$colors = new Colors();

		// Get Foreground Colors
		$fgs = $colors->getForegroundColors();
		// Get Background Colors
		$bgs = $colors->getBackgroundColors();

		// Loop through all foreground and background colors
		$count = count($fgs);
		for ($i = 0; $i < $count; $i++) {
			echo $colors->getColoredString("Test Foreground colors", $fgs[$i]) . "\t";
			if (isset($bgs[$i])) {
				echo $colors->getColoredString("Test Background colors", null, $bgs[$i]);
			}
			echo "\n";
		}
		echo "\n";

		// Loop through all foreground and background colors
		foreach ($fgs as $fg) {
			foreach ($bgs as $bg) {
				echo $colors->getColoredString("Test Colors", $fg, $bg) . "\t";
			}
			echo "\n";
		}

