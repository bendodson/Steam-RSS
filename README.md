# Steam-RSS
This is a basic PHP script that will fetch all of your owned games from Steam, find the update RSS feed for each, and then subscribe to them with Feedbin. More information is available [on my blog](https://bendodson.com/weblog/2016/05/17/fetching-rss-feeds-for-steam-game-updates/).

## Installation
You'll need to use [Composer](https://getcomposer.org) in order to install the [Goutte](https://github.com/FriendsOfPHP/Goutte) dependency. A `composer.json` file is included.

Next, you'll want to update the three constants at the top of the `steam-rss.php` script to reference your Feedbin username, Feedbin password, and your Steam ID (which can be found by going to your Steam profile page and looking at the URL; mine is '[bendodson](https://steamcommunity.com/id/bendodson)').

Finally, make sure the permissions of the directory are set to 0777 or create an empty file named `games.txt` and set its permissions to 0777. Setup the script to run on a CRON (I run it hourly) and marvel as you are automatically subscribed to the RSS feeds of all of the Steam games you own. Enjoy!
'
