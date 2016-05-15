# PHP Minecraft Server Status

This library can be used to check Minecraft Servers Status for some basic information.

**Please do not create issues when you are unable to retrieve information from a server, unless you can prove that there is a bug within the library.**

## Differences between Ping and Query
There are two methods of retrieving information about a Minecraft server.

### Ping
Ping protocol was added in Minecraft 1.7 and is used to query the server for minimal amount of information (hostname, motd, icon, and a sample of players). This is easier to use and doesn't require extra setup on server side. It uses TCP protocol on the same port as you would connect to your server.

`MCPing` main method contains a optional parameter `IsOld17` which can be used to query servers on version 1.6 or older.

### Query
This method uses GameSpy4 protocol, and requires enabling `query` listener in your `server.properties` like this:

> *enable-query=true*<br>
> *query.port=25565*

Query allows to request a full list of servers' plugins and players, however this method is more prone to breaking, so if you don't need all this information, stick to the ping method as it's more reliable.

## Example MCPing
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

----

## Example MCQuery

Coming soon




