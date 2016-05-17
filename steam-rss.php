<?php

define('FEEDBIN_USERNAME', 'your username here');
define('FEEDBIN_PASSWORD', 'your password here');
define('STEAM_ID', 'your steam id here i.e. bendodson');

require 'vendor/autoload.php';
use Goutte\Client;

$html = file_get_contents('http://steamcommunity.com/id/'.STEAM_ID.'/games/?tab=all');

$match = "rgGames = ";
$start = strpos($html, $match) + strlen($match);
$json = substr(trim(strtok(substr($html, $start), "\n")), 0, -1);
$array = json_decode($json);

$games = explode("\n", file_get_contents('games.txt'));
if ($games[0] == "") {
	$games = [];
}
foreach ($array as $game) {
	$unsubscribed = $game->appid.'-0';
	$subscribed = $game->appid.'-1';
	$failed = $game->appid.'-x';
	if (!in_array($unsubscribed, $games) && !in_array($subscribed, $games) && !in_array($failed, $games)) {
		$games[] = $unsubscribed;
	}
}
file_put_contents('games.txt', implode("\n", $games));

$games = explode("\n", file_get_contents('games.txt'));
$index = 0;
foreach ($games as $game) {
	list($id, $subscribed) = explode("-", $game);
	if ($subscribed == '0') {
		subscribeWithSteamIDAtIndex($id, $index);
	}
	$index += 1;
}

echo 'Done';


function subscribeWithSteamIDAtIndex($id, $index, $url='') {

	$key = $url == '' ? $id : $url;
	$feedURL = $url == '' ? 'http://steamcommunity.com/games/'.$id.'/rss/' : $url;
	echo 'Subscribing to '.$feedURL.'<br>';
	$post = json_encode(["feed_url" => $feedURL]);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
	curl_setopt($ch, CURLOPT_URL, 'https://api.feedbin.com/v2/subscriptions.json');    	
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15); 
	curl_setopt($ch, CURLOPT_USERPWD, FEEDBIN_USERNAME . ":" . FEEDBIN_PASSWORD);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$output = curl_exec($ch);
	$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);

	if ($statusCode == 404 && $url == '') {

		$success = false;

		$client = new Client();
		$crawler = $client->request('GET', 'http://steamcommunity.com/app/'.$id.'/allnews/');
		$elements = $crawler->filter('#apphub_InitialContent .Announcement_Card:first-child');
		if ($elements->count() > 0) {
			$url = $elements->attr('data-modal-content-url');
			if ($url) {
				$client = new Client();
				$crawler = $client->request('GET', $url);
				$url = $crawler->selectLink('Subscribe to RSS Feed')->attr('href');;
				if ($url) {
					$success = subscribeWithSteamIDAtIndex($id, $index, $url);
				}
			}
		}

		if (!$success) {
			$games = explode("\n", file_get_contents('games.txt'));
			$games[$index] = $id.'-x';
			file_put_contents('games.txt', implode("\n", $games));			
			echo 'Failed<br><br>';
		}


	} else if ($statusCode == 201) {

		$subscription = json_decode($output);
		$post = json_encode(["feed_id" => $subscription->feed_id, "name" => "Steam Games"]);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json; charset=utf-8']);
		curl_setopt($ch, CURLOPT_URL, 'https://api.feedbin.com/v2/taggings.json');    	
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 15); 
		curl_setopt($ch, CURLOPT_USERPWD, FEEDBIN_USERNAME . ":" . FEEDBIN_PASSWORD);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		curl_exec($ch);
		curl_close($ch);

	}

	if ($statusCode == 201 || $statusCode == 302) {
		$games = explode("\n", file_get_contents('games.txt'));
		$games[$index] = $id.'-1';
		file_put_contents('games.txt', implode("\n", $games));
		echo 'Subscribed!<br><br>';
		return true;
	}

	return false;
}


