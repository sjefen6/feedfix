<?php
if (empty($_GET['username'])){
	header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Improved My Subscriptions feed for YouTube</title>
	</head>
	<body>
		<p>This adds a YouTube Player to the My Subscriptions feed. This is awesome for us using feedreaders.</p>
		<form name="input" method="get">
		YouTube username: <input type="text" name="username">
		<input type="submit" value="Submit">
		</form>
	</body>
</html>
<?php
} else {
	header('Content-Type: application/rss+xml; charset=utf-8');
	
	$url = "http://gdata.youtube.com/feeds/base/users/" . $_GET['username'] . "/newsubscriptionvideos?client=ytapi-youtube-user&v=2";
	
	$feed = simplexml_load_file($url);
	
	
	for ($i = 0; $i < sizeof($feed->entry); $i++) {
		$link = $feed->entry[$i]->link["href"];
		$linkQuery = parse_url($link)["query"];
		parse_str($linkQuery, $linkQueryParams);
		$videoId = $linkQueryParams["v"];
		
		$feed->entry[$i]->content = str_replace('font-size: 12px; margin: 3px 0px;','font-size: 12px; margin: 3px 0px; white-space: pre-wrap;',$feed->entry[$i]->content);
		
		$feed->entry[$i]->content = "<iframe id=\"ytplayer\" type=\"text/html\" width=\"640\" height=\"390\" " .
			"src=\"http://www.youtube.com/embed/" . $videoId . "?autoplay=0&amp;origin=http://feedfix.gbt.cc\" " .
			"frameborder=\"0\" allowfullscreen></iframe>\n<br>" . $feed->entry[$i]->content;
	}
	
	echo $feed->asXML();
}
