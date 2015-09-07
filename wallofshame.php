<?php // 6779 hits at take down
function forceExec($in) {
	$out = '';
	if (function_exists('exec')) {
		@exec($in,$out);
		$out = @join("\n",$out);
	} elseif (function_exists('passthru')) {
		ob_start();
		@passthru($in);
		$out = ob_get_clean();
	} elseif (function_exists('system')) {
		ob_start();
		@system($in);
		$out = ob_get_clean();
	} elseif (function_exists('shell_exec')) {
		$out = shell_exec($in);
	} elseif (is_resource($f = @popen($in,"r"))) {
		$out = "";
		while(!@feof($f))
			$out .= fread($f,1024);
		pclose($f);
	}
	return trim($out);
}

function updateHit() {
	$x = file_get_contents('hits.txt') + 1;
	file_put_contents('hits.txt', $x);
	return $x;
}

echo "Hits: ".updateHit()."<br /><br />";
echo "<b>USAGE is accurate and calculated by taking the load from ps and dividing it by available cores - Only showing > 0.0500%</b><br /><b>Don't abuse these people this is simply here to name and shame... It's entirely possible that this may be a false positive as well.</b><hr />";

//Fetch commands
$outputs = forceExec('ps -eo pcpu,etime,user,args | sort -k 1 -r | head -25 | grep -v %CPU');
$cores = forceExec('nproc') + 0;
$totalghz = (forceExec('lscpu | grep "CPU MHz" | cut -d : -f2') + 0) * $cores;

//For each command
foreach (explode("\n", $outputs) as $output) {
	//Empty?
	$output = preg_replace('/\s+/', ' ', $output);
	$output = trim($output);
	if(empty($output)) {
		continue;
	}

	//Grab elements
	$elements = explode(' ', $output);
	if (count($elements) < 4) {
		continue;
	} else if ($elements[2] == "root" || $elements[2] == "apache" || empty($elements[2])) {
		continue;
	}

	//Workout REAL load
	$elements[0] = ($elements[0] + 0) / $cores;
	if ($elements[0] < 0.0500) {
		continue;
	}

	//Fetch fullane
	$fullname = forceExec('finger '.$elements[2].' | cut -d : -f3 | head -1');
	if(empty($fullname)) {
		continue;
	}

	$ghz = $totalghz * ($elements[0]/100);

	//Print
	echo "USAGE: ".$elements[0]."% or ".$ghz."MHz<br />";
	echo "RUNTIME: ".$elements[1]."<br />";
	echo "COMMAND: ".implode(' ', array_slice($elements, 3))."<br />";
	echo "USERNAME: ".$elements[2]."<br />";
	echo "FULL NAME: ".$fullname."<hr />";
}
?>

