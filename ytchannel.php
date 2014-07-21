<?php
require("ytapikey.php"); //define("API_KEY","YOUR API KEY");

if(!empty($_GET['channel'])){
	$channel = $_GET['channel'];
	$url = "https://www.googleapis.com/youtube/v3/channels?part=snippet%2C+contentDetails&id=" . $channel . "&key=" . API_KEY;
	$json = file_get_contents($url);
	$json = json_decode($json, true);

	if(count($json["items"]) == 0){
		header("HTTP/1.0 404 Channel not found");
		echo "Channel not found";
		exit;
	}

	$feedtitle = $json["items"][0]["snippet"]["title"];
	$feeddesc = $json["items"][0]["snippet"]["description"];
	$uploadsid = $json["items"][0]["contentDetails"]["relatedPlaylists"]["uploads"];

	$url = "https://www.googleapis.com/youtube/v3/playlistItems?part=+id%2C+snippet%2C+contentDetails%2C+status&maxResults=20&playlistId=" . $uploadsid . "&key=" . API_KEY; 
        $json = file_get_contents($url);
        $json = json_decode($json, true);
	$items = $json["items"];

header('Content-Type: application/rss+xml; charset=utf-8');
echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
?>
<rss version="2.0"
	xmlns:atom="http://www.w3.org/2005/Atom"
>
<channel>
	<title><?= $feedtitle ?></title>
	<link>https://www.youtube.com/playlist?list=<?= $uploadsid ?></link> 
	<atom:link href="http://feedfix.gbt.cc/ytchannel.php?channel=<?= $channel ?>" rel="self" type="application/rss+xml" />
	<description><?= $feeddesc ?></description>
<?php
	foreach($items as $item){
		$title = $item["snippet"]["title"];
		$description = $item["snippet"]["description"];
		$videoid = $item["contentDetails"]["videoId"];
		$time = new DateTime($item["snippet"]["publishedAt"]);
		$pubdate = $time->format(DateTime::RFC822);
?>
	<item>
		<title><?= $title ?></title>
		<guid>https://www.youtube.com/watch?v=<?= $videoid ?></guid>
		<link>https://www.youtube.com/watch?v=<?= $videoid ?></link>
		<description><![CDATA[
			<iframe id="ytplayer" type="text/html" width="640" height="390"
				src="http://www.youtube.com/embed/<?= $videoid ?>?autoplay=0&amp;origin=http://feedfix.gbt.cc"
				frameborder="0" allowfullscreen>
			</iframe><br>
			<?= $description ?>
		]]></description>
		<pubDate><?= $pubdate ?></pubDate>
	</item>
<?php
	}
?>
</channel>
</rss>
<?php
} else {
	header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
	<head>
		<title>YouTube channel rssfeed</title>
	</head>
	<body>
		<p>This generates an rssfeed for channels not covered by the youtube v2 api</p>
		<p><strong>Example channelid:</strong> https://www.youtube.com/channel/<strong>UC-vIANCum1yBw_4DeJImc0Q</strong></p>
		<form name="input" method="get">
		YouTube channelid: <input type="text" name="channel">
		<input type="submit" value="Submit">
		</form>
	</body>
</html>
<?php
}
?>
