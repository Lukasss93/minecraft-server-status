<?php

namespace MCServerStatus;

use Exception;
use MCServerStatus\Exceptions\MCQueryException;
use MCServerStatus\Responses\MCQueryResponse;

/**
 * PHP class to check the status of a Minecraft server by Query
 * Class MCQuery
 * @package MCServerStatus
 */
class MCQuery {
	private static $statistic = 0x00;
	private static $handshake = 0x09;
	private static $socket;
	
	private function __construct() {
	}
	
	/**
	 * Check and get the server status
	 * @param string $host    Server hostname
	 * @param int    $port    Server query port
	 * @param int    $timeout Timeout in seconds
	 * @param bool   $resolveSRV
	 * @return MCQueryResponse
	 */
	public static function check($host = '127.0.0.1', $port = 25565, $timeout = 2, $resolveSRV = true) {
		
		//initialize response
		$response = new MCQueryResponse();
		
		try {
			
			//save hostname to response
			$response->hostname = $host;
			
			//check port
			if(!is_int($port) || $port < 1024 || $port > 65535) {
				throw new MCQueryException('Invalid port');
			}
			
			//check timeout
			if(!is_int($timeout) || $timeout < 0) {
				throw new MCQueryException('Invalid timeout');
			}
			
			//check resolveSRV
			if(!is_bool($resolveSRV)) {
				throw new MCQueryException('Invalid resolveSRV');
			}
			
			//resolve SRV record
			if($resolveSRV) {
				self::resolveSRV($host, $port);
			}
			
			//open the socket
			self::$socket = @fsockopen("udp://$host", $port, $errno, $errstr, $timeout);
			
			//check socket errors
			if($errno || self::$socket === false) {
				throw new MCQueryException("Socket error: $errstr");
			}
			
			//socket options
			@stream_set_timeout(self::$socket, $timeout);
			@stream_set_blocking(self::$socket, true);
			
			//challenge
			$data = self::writedata(self::$handshake);
			if($data === false) {
				throw new MCQueryException('Failed to receive challenge');
			}
			$challenge = pack('N', $data);
			
			//statistics
			$data = self::writedata(self::$statistic, $challenge . pack('c*', 0x00, 0x00, 0x00, 0x00));
			if(!$data) {
				throw new MCQueryException('Failed to receive status');
			}
			
			$last = '';
			$info = [];
			
			$data = substr($data, 11);
			$data = explode("\x00\x00\x01player_\x00\x00", $data);
			
			if(count($data) !== 2) {
				throw new MCQueryException('Failed to parse server\'s response');
			}
			
			$players = @substr($data[1], 0, -2);
			$data = explode("\x00", $data[0]);
			
			//array with known keys in order to validate the result
			//it can happen that server sends custom strings containing bad things (who can know!)
			$keys = [
				'hostname'   => 'HostName',
				'gametype'   => 'GameType',
				'version'    => 'Version',
				'plugins'    => 'Plugins',
				'map'        => 'Map',
				'numplayers' => 'Players',
				'maxplayers' => 'MaxPlayers',
				'hostport'   => 'HostPort',
				'hostip'     => 'HostIp',
				'game_id'    => 'GameName'
			];
			
			foreach($data as $key => $value) {
				if(~$key & 1) {
					if(!array_key_exists($value, $keys)) {
						$last = false;
						continue;
					}
					
					$last = $keys[$value];
					$info[$last] = '';
				}
				else if($last != false) {
					$info[$last] = $value;
				}
			}
			
			//integer results
			$response->players = intval($info['Players']);
			$response->max_players = intval($info['MaxPlayers']);
			$response->port = intval($info['HostPort']);
			
			//parse "plugins", if any
			if(@$info['Plugins']) {
				$data = explode(": ", $info['Plugins'], 2);
				
				$info['RawPlugins'] = $info['Plugins'];
				$info['Software'] = $data[0];
				
				if(count($data) == 2) {
					$info['Plugins'] = explode("; ", $data[1]);
				}
			}
			else {
				$info['Software'] = 'Vanilla';
			}
			
			if(!is_array($info['Plugins'])) {
				$info['Plugins'] = [];
			}
			
			//get other infos
			$response->address = isset($info['HostIp']) ? $info['HostIp'] : null;
			$response->version = isset($info['Version']) ? $info['Version'] : null;
			$response->software = isset($info['Software']) ? $info['Software'] : null;
			$response->game_type = isset($info['GameType']) ? $info['GameType'] : null;
			$response->game_name = isset($info['GameName']) ? $info['GameName'] : null;
			$response->motd = isset($info['HostName']) ? $info['HostName'] : null;
			$response->map = isset($info['Map']) ? $info['Map'] : null;
			$response->plugins = $info['Plugins'];
			
			//get player list
			if($players) {
				$response->player_list = explode("\x00", $players);
			}
			
			//get the ip address if the address is 0.0.0.0
			if($response->address === '0.0.0.0') {
				$response->address = gethostbyname($response->hostname);
			}
			
			//server status (if you are here the server is online obv)
			$response->online = true;
		}
		catch(MCQueryException $e) {
			$response->error = $e->getMessage();
		}
		catch(Exception $e) {
			$response->error = $e->getMessage();
		}
		finally {
			//close the socket
			@fclose(self::$socket);
		}
		
		//return the response
		return $response;
	}
	
	/**
	 * Resolve SRV record
	 * @param $host
	 * @param $port
	 */
	private static function resolveSRV(&$host, &$port) {
		if(ip2long($host) !== false) {
			return;
		}
		
		$record = dns_get_record('_minecraft._tcp.' . $host, DNS_SRV);
		
		if(empty($record)) {
			return;
		}
		
		if(isset($record[0]['target'])) {
			$host = $record[0]['target'];
			$port = $record[0]['port'];
		}
	}
	
	/**
	 * Write data to socket
	 * @param string $command
	 * @param string $append
	 * @return mixed
	 * @throws MCQueryException
	 */
	private static function writedata($command, $append = '') {
		$command = pack('c*', 0xFE, 0xFD, $command, 0x01, 0x02, 0x03, 0x04) . $append;
		$length = strlen($command);
		
		if($length !== @fwrite(self::$socket, $command, $length)) {
			throw new MCQueryException('Failed to write on socket');
		}
		
		$data = @fread(self::$socket, 4096);
		
		if($data === false) {
			throw new MCQueryException('Failed to read from socket');
		}
		
		if(strlen($data) < 5 || $data[0] != $command[2]) {
			return false;
		}
		
		return substr($data, 5);
	}
}