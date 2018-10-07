# Minecraft Server Status

> This library can be used to check Minecraft Servers Status for some basic information.

**⚠ Please do not create issues when you are unable to retrieve information from a server, unless you can prove that there is a bug within the library.**

## Differences between Ping and Query
There are two methods of retrieving information about a Minecraft server.

* **Ping**

    Ping protocol was added in Minecraft 1.7 and is used to query the server for minimal amount of information (hostname, motd, icon, and a sample of players). 
    This is easier to use and doesn't require extra setup on server side. 
    It uses TCP protocol on the same port as you would connect to your server an optional parameter `IsOld17` which can be used to query servers on version 1.6 or older.
    *N.B.: this method doesn't work with Minecraft: Bedrock Edition*

* **Query**

    This method uses GameSpy4 protocol, and requires enabling `query` listener in your `server.properties` like this:

    >*enable-query=true*
    
    >*query.port=25565*

    Query allows to request a full list of servers' plugins and players, however this method is more prone to breaking, so if you don't need all this information, stick to the ping method as it's more reliable.

Requirements
---------
* PHP >= 5.6
* Json Extension
* Iconv Extension

Installation
---------
You can install this library with composer:

`composer require lukasss93/minecraft-server-status`


Using
---------
### MCPing
#### Using
```php
<?php
	use MCServerStatus\MCPing;
	print_r( MCPing::check('hostname or IP') );
?>
```

If you want to get `ping` info from a server that uses a version older than Minecraft 1.7,
then add true parameter after `$timeout`.

Please note that this library does resolve **SRV** records too.

#### Input
The `check()` method has 4 optional parameters:

\# | Parameter | Type | Default |Description
---|-----------|------|---------|-----------
1 | host | string | 127.0.0.1 |Server Hostname or IP address
2 | port | int| 25565 | Server port
3 | timeout | int | 2 | Timeout (in seconds)
4 | isOld17 | bool | false | Boolean value to find informations on servers that uses a version older than Minecraft 1.7

#### Output
The `check()` method return an object with the following properties:

Key|Type|Description
---|----|------------
online|bool|Returns `true` if the server is online else `false`
error|string|Returns any error message
hostname|string|Returns the server hostname or IP address 
address|string|Returns server IP address
port|int|Returns the server port
ping|int|Returns server ping
version|string|Returns the server version
protocol|int|Returns the server protocol
players|int|Returns the number of online players
max_players|int|Returns the maximum number of players that can enter the server
sample_player_list|array|Returns a partial list of online players
motd|string|Returns server description
favicon|string|Returns an image in Base64 string
mods|array|Returns a list of installed mods on the server

_You can use the following methods after_ `check()` _method:_

Method|Description
------|-----------
toArray()|Return the object properties as an array
getMotdToText()|Get the motd without the format codes
getMotdToHtml()|Get the motd as HTML


----

### MCQuery
#### Using
```php
<?php
	use MCServerStatus\MCQuery;
	print_r( MCQuery::check('hostname or IP') );
?>
```

#### Input
The `check()` method has 4 optional parameters:

\# | Parameter | Type | Default |Description
---|-----------|------|---------|-----------
1 | host | string | 127.0.0.1 | Server Hostname or IP address
2 | port | int| 25565 | Server query port
4 | timeout | int | 2 | Timeout (in seconds)
5 | resolveSRV | bool | true | Resolve SRV record

#### Output
The `check()` method return an array with the following properties:

Key|Type|Description
---|----|------------
online|bool|Returns `true` if the server is online else `false`
error|string|Returns any error message
hostname|string|Returns the server hostname or IP address
address|string|Returns server IP address
port|int|Returns the server port
version|string|Returns the server version
software|string|Returns the server software
game_type|string|Returns the server software type
game_name|string|Return the server software name
players|int|Returns the number of online players
max_players|int|Returns the maximum number of players that can enter the server
player_list|array|Returns a list of online players
motd|string|Returns server description
map|string|Returns the server map name
plugins|array|Returns a list of installed plugins on the server

_You can use the following methods after_ `check()` _method:_

Method|Description
------|-----------
toArray()|Return the object properties as an array
getMotdToText()|Get the motd without the format codes
getMotdToHtml()|Get the motd as HTML

Changelog
---------
All notable changes to this project will be documented [here](https://github.com/Lukasss93/minecraft-server-status/blob/master/CHANGELOG.md).

### Recent changes
## [1.0]
- First release
