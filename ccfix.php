<?php
header('Content-Type: application/rss+xml; charset=utf-8');

$url = "http://www.commissionedcomic.com/?feed=rss2";

if ($_GET['hwc'] == 1)
 $url = "http://www.dernwerks.com/HWC/?feed=rss2";

$input = file_get_contents($url);

$match = '</description>
	<p>';
$replace = "</description>\n\t\t<content:encoded><![CDATA[<p>";

$input = str_replace($match, $replace, $input);

$match = '</p>			<content:encoded><![CDATA[';
$replace = '</p>';

echo str_replace($match, $replace, $input);
