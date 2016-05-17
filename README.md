# PHP Minecraft Server Status

This library can be used to check Minecraft Servers Status for some basic information.

**Please do not create issues when you are unable to retrieve information from a server, unless you can prove that there is a bug within the library.**

## Differences between Ping and Query
There are two methods of retrieving information about a Minecraft server.

* **Ping**

    Ping protocol was added in Minecraft 1.7 and is used to query the server for minimal amount of information (hostname, motd, icon, and a sample of players). 
    This is easier to use and doesn't require extra setup on server side. 
    It uses TCP protocol on the same port as you would connect to your server an optional parameter `IsOld17` which can be used to query servers on version 1.6 or older.
    *N.B.: this method doesn't work with Minecraft: Pocket Edition*

* **Query**

    This method uses GameSpy4 protocol, and requires enabling `query` listener in your `server.properties` like this:

    > enable-query=true
    query.port=25565

    Query allows to request a full list of servers' plugins and players, however this method is more prone to breaking, so if you don't need all this information, stick to the ping method as it's more reliable.

## MCPing
#### Using
```php
<?php
	require 'MCPing.php';	
	
	$status = new MCPing();
	print_r( $status->GetStatus( 'localhost', 25565 )->Response() );	
?>
```

If you want to get `ping` info from a server that uses a version older than Minecraft 1.7,
then add true parameter after `host` and `port`.

Please note that this library does resolve **SRV** records too.

#### Input
The `GetStatus()` method has 4 optional parameters:

\# | Parameter | Type | Default |Description
---|-----------|------|---------|-----------
1 | Host | string | 127.0.0.1 |Server Hostname or IP address
2 | Port | int| 255656 | Server port
3 | IsOld17 | bool | false | Boolean value to find informations on servers that uses a version older than Minecraft 1.7
4 | Timeout | int | 2 | Timeout (in seconds)

#### Output
The `Response()` method return an array with the following keys:

Key|Type|Description
---|----|------------
online|bool|Returns `true` if the server is online else `false`
error|string|Returns any error message
hostname|string|Returns the server hostname or IP address 
address|string|Returns server IP address
port|int|Returns the server port
version|string|Returns the server version
protocol|int|Returns the server protocol
players|int|Returns the number of online players
max_players|int|Returns the maximum number of players that can enter the server
sample_player_list|array|Returns a partial list of online players
motd|string|Returns server description
favicon|string|Returns an image in Base64 string
mods|array|Returns a list of installed mods on the server

----

## MCQuery
Coming soon




