<?php
ini_set("user_agent","Feedfix; +http://feedfix.gbt.cc/titlefilter.php (PHP " . phpversion() . ")");

function superexplode($csv, $delimiter = ","){
	// explode and clear " " and ""
	$csv = explode($delimiter, $csv);
	$csv = array_map('trim', $csv);
	$csv = array_diff($csv, array(''));
	return $csv;
}

function searchfor($haystack, $needlearray) {
  foreach($needlearray as $needle)
    if(strpos($haystack, $needle) !== FALSE) return TRUE;
  return FALSE;
}

if (empty($_GET['feed']) || empty($_GET['filter'])){
	header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Simple feed filter</title>
	</head>
	<body>
		<p>This filters a feed to only include items with a title containing a string from a text file with line separated strings.
			Good places to store the textfile would be publicly on google drive, dropbox or webserver.</p>
		<p><b>Example filter:</b><br>
		<pre>Foo
bar</pre>
		This would let trough items with a title of e.g. "cowfoo", "snackbar"</p>
		<form name="input" method="get">
		Feed: <input type="text" name="feed">
		Filter: <input type="text" name="filter">
		<input type="submit" value="Submit">
		</form>
	</body>
</html>
<?php
} else {
	header('Content-Type: application/rss+xml; charset=utf-8');

	$feed = $_GET["feed"];
	$filter = $_GET["filter"];

	$feed = simplexml_load_file($feed);
	$filter = file_get_contents($filter);

	$filter = superexplode($filter, "\n");
	$filter = array_map('strtolower', $filter);

	$i = 0;
	while ($i < sizeof($feed->channel->item)) {
		$title = $feed->channel->item[$i]->title;

		if (!searchfor(strtolower($title),$filter)){
			unset($feed->channel->item[$i]);
		} else {
			$i++;
		}
	}

	echo $feed->asXML();
}
