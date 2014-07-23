<?php
exit; //debug

require("ytapikey.php"); //define("API_KEY","YOUR API KEY");

$channelid = isset($_GET['channel']) ? $_GET['channel'] : null;
$playlistid = isset($_GET['playlist']) ? $_GET['playlist'] : null;
$playlists = array();

function getreq($url){
	$req = file_get_contents($url . "&key=" . API_KEY);
	return json_decode($req, true);
}

function pagedreq($url){
	$items = array();

        do{
                $page = getreq($url .
                        (
                                isset($page["nextPageToken"])? "&pageToken=" . $page["nextPageToken"] : ""
                        )
                );

		$items = array_merge($items, $page["items"]);

        } while(isset($page["nextPageToken"]));
	return $items;
}

function chunkedreq($url, $param, $array, $chunksize = 50, $pagedreq = true ){
	$items = array();

	foreach(array_chunk($array, $chunksize) as $chunk){
		if($pagedreq){
			$page = pagedreq($url .
				"&" . $param . "=" .implode(",", $chunk)
			);
		} else {
			$page = getreq($url .
				"&" . $param . "=" .implode(",", $chunk)
			)["items"];
		}
		$items = array_merge($items, $page);
	}
	return $items;
}

function error($message){
	header("HTTP/1.0 404 " . $message);
	echo $message;
	exit;
}

function htmlplz($str){
	$str = str_replace("\r\n", "\n", $str);
	$str = str_replace("\r", "\n", $str);
	$str = preg_replace("/\n{2,}/", "\n\n", $str);
	$str = preg_replace('/\n(\s*\n)+/', '</p><p>', $str);
	$str = preg_replace('/\n/', '<br>', $str);
	return '<p>'.$str.'</p>';
}

$channelid = "UChJRyNlaSpSBUhPLgqdSCzQ";

if($channelid != null){
	if($_GET['chtype'] == "user"){
		$channelinfo = getreq("https://www.googleapis.com/youtube/v3/channels?part=id%2C+contentDetails&forUsername=" . $channelid);
	} else {
		$channelinfo = getreq("https://www.googleapis.com/youtube/v3/channels?part=id%2C+contentDetails&id=" . $channelid);
	}

	if(count($channelinfo["items"]) == 0){
		error("Channel not found");
	}

	$channelid = $channelinfo["items"][0]["id"];

        $subschannels = pagedreq("https://www.googleapis.com/youtube/v3/subscriptions?part=snippet&maxResults=50&channelId=" . $channelid );

	foreach($subschannels as $subschannel){
		$subschannelids[] = $subschannel["snippet"]["resourceId"]["channelId"];
	}

	$subschinfo = chunkedreq("https://www.googleapis.com/youtube/v3/channels?part=id%2C+contentDetails", "id", $subschannelids);

	foreach ($subschinfo as $subsch){
		foreach($subsch["contentDetails"]["relatedPlaylists"] as $key => $value){
			if(in_array($key, array("uploads"))){
				$playlists[] = $value;
			}
		}
	}

	$items = chunkedreq("https://www.googleapis.com/youtube/v3/playlistItems?part=+id%2C+snippet%2C+contentDetails%2C+status&maxResults=10", "playlistId", $playlists, 1, false);

	var_dump($items);
	exit;
}

if($playlistid != null){
	$playlist = getreq("https://www.googleapis.com/youtube/v3/playlistItems?part=+id%2C+snippet%2C+contentDetails%2C+status&maxResults=10&playlistId=" . $playlistid . "&key=" . API_KEY);
	$playlistdetalis = getreq("https://www.googleapis.com/youtube/v3/playlists?part=snippet&id=" . $playlistid . "&key=" . API_KEY);
	$items = $playlist["items"];

	$feedtitle = $playlist["items"][0]["snippet"]["channelTitle"];


header('Content-Type: application/rss+xml; charset=utf-8');
echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";
?>
<rss version="2.0"
	xmlns:atom="http://www.w3.org/2005/Atom"
>
<channel>
	<title><?= $playlistdetalis["items"][0]["snippet"]["title"] ?></title>
	<description><?= $playlistdetalis["items"][0]["snippet"]["description"] ?></description>
	<link>https://www.youtube.com/playlist?list=<?= $playlistid ?></link> 
	<atom:link href="http://feedfix.gbt.cc/ytchannel.php?playlist=<?= $playlistid ?>" rel="self" type="application/rss+xml" />
<?php
	foreach($items as $item){
		$title = $item["snippet"]["title"];
		$description = htmlplz($item["snippet"]["description"]);
		$videoid = $item["contentDetails"]["videoId"];
		$time = new DateTime($item["snippet"]["publishedAt"]);
		$pubdate = $time->format(DateTime::RSS);
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
<?php	} ?>
</channel>
</rss>
<?php
} else {
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
	<head>
		<title>YouTube channel and playlist feed generator</title>
	</head>
	<body>
<?php		if(empty($_GET['channel'])){ ?>
		<h1>YouTube channel and playlist feed generator</h1>
		<p>This generates an rssfeed for YouTube Channels/Playlists via the YouTube API v3. This include channels that are not accessible via the rssfeeds from API v2.</p>
		<p><strong>Example user:</strong> https://www.youtube.com/user/<strong>acedtect</strong><br>
		<strong>Example channel:</strong> https://www.youtube.com/channel/<strong>UC-vIANCum1yBw_4DeJImc0Q</strong></p>
		<form name="input" method="get">
			<input type="radio" name="chtype" value="user" required>User</input>
			<input type="radio" name="chtype" value="channel">Channel</input><br>
		YouTube channel: <input type="text" name="channel">
		<input type="submit" value="Show playlists">
		</form>
<?php		} else { ?>
		<form name="input" method="get">
		Select playlist to subscribe to:<br>
<?php			foreach($playlists as $playlist){ ?>
			<input type="radio" name="playlist" value="<?= $playlist["id"] ?>" required><?= $playlist["name"] ?></input>
<?php			} ?><br>
		<input type="submit" value="Get feed">
		</form>
<?php		} ?>
	</body>
</html>
<?php	} ?>
