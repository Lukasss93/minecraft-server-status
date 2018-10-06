<?php

namespace MCServerStatus;

use Exception;
use MCServerStatus\Exceptions\MCPingException;
use MCServerStatus\Responses\MCPingResponse;

/**
 * PHP class to check the status of a Minecraft server by Ping
 * Class MCPing
 * @package MCServerStatus
 */
class MCPing {
	
	private static $socket;
	private static $timeout;
	private static $response;
	
	private function __construct() {
	}
	
	/**
	 * Check and get the server status
	 * @param string $hostname
	 * @param int    $port
	 * @param int    $timeout
	 * @param bool   $isOld17
	 * @return MCPingResponse
	 */
	public static function check($hostname = '127.0.0.1', $port = 25565, $timeout = 2, $isOld17 = false) {
		
		//initialize response
		self::$response = new MCPingResponse();
		
		try {
			
			//save hostname
			self::$response->hostname = $hostname;
			
			//check port
			if(!is_int($port) || $port < 1024 || $port > 65535) {
				throw new MCPingException('Invalid port');
			}
			self::$response->port = $port;
			
			//check timeout
			if(!is_int($timeout) || $timeout < 0) {
				throw new MCPingException('Invalid timeout');
			}
			self::$timeout = $timeout;
			
			//validate isold17 parameter
			if(!is_bool($isOld17)) {
				throw new MCPingException('Invalid parameter in $isold17');
			}
			
			//validate host
			if(filter_var(self::$response->hostname, FILTER_VALIDATE_IP)) {
				//host is ip => address is host
				self::$response->address = self::$response->hostname;
			}
			else {
				//find domain ip
				$resolvedIP = gethostbyname(self::$response->hostname);
				
				if(filter_var($resolvedIP, FILTER_VALIDATE_IP)) {
					//resolvedIP is a valid IP => address is resolvedIP
					self::$response->address = $resolvedIP;
				}
				else {
					//resolvedIP is not a valid IP then find SRV record
					$dns = @dns_get_record('_minecraft._tcp.' . self::$response->hostname, DNS_SRV);
					/*
					 * added @ because in a few server, php return this error:
					 * 'dns_get_record(): A temporary server error occurred'
					 * the resolution time is almost always 15-25sec.
					 * I don't know how fix the time.
					 */
					
					if(!$dns) {
						throw new MCPingException('dns_get_record(): A temporary server error occurred');
					}
					
					if(is_array($dns) and count($dns) > 0) {
						self::$response->address = gethostbyname($dns[0]['target']);
						self::$response->port = $dns[0]['port'];
					}
				}
			}
			
			//open the socket
			self::$socket = @fsockopen(self::$response->address, self::$response->port, $errno, $errstr, self::$timeout);
			if(!self::$socket) {
				throw new MCPingException("Failed to connect or create a socket: $errstr");
			}
			stream_set_timeout(self::$socket, self::$timeout);
			
			//get server infos
			if(!$isOld17) {
				self::ping();
			}
			else {
				self::pingOld();
			}
			
			//server status (if you are here the server is online obv)
			self::$response->online = true;
		}
		catch(MCPingException $e) {
			self::$response->error = $e->getMessage();
		}
		catch(Exception $e) {
			self::$response->error = $e->getMessage();
		}
		finally {
			//close the socket
			@fclose(self::$socket);
		}
		
		//return the response
		return self::$response;
	}
	
	/**
	 * Ping the server with version >= 1.7
	 * @throws MCPingException
	 */
	private static function ping() {
		
		// for read timeout purposes
		$timestart = microtime(true);
		
		//data to send with socket, see http://wiki.vg/Protocol (Status Ping)
		
		//packet ID = 0 (varint)
		$data = "\x00";
		
		//protocol version (varint)
		$data .= "\x04";
		
		//server (varint len + UTF-8 addr)
		$data .= pack('c', strlen(self::$response->address)) . self::$response->address;
		
		//server port (unsigned short)
		$data .= pack('n', self::$response->port);
		
		//next state: status (varint)
		$data .= "\x01";
		
		//prepend length of packet ID + data
		$data = pack('c', strlen($data)) . $data;
		
		//handshake
		fwrite(self::$socket, $data);
		
		//start ping time
		$startPing = microtime(true);
		
		// status ping
		fwrite(self::$socket, "\x01\x00");
		
		//full packet length
		$length = self::readVarInt();
		if($length < 10) {
			throw new MCPingException('Response length not valid');
		}
		
		//packet type, in server ping it's 0
		fgetc(self::$socket);
		
		//string length
		$length = self::readVarInt();
		$data = "";
		do {
			if(microtime(true) - $timestart > self::$timeout) {
				throw new MCPingException('Server read timed out');
			}
			$remainder = $length - strlen($data);
			
			//get the json string
			$block = fread(self::$socket, $remainder);
			
			//abort if there is no progress
			if(!$block) {
				throw new MCPingException('Server returned too few data');
			}
			$data .= $block;
		}
		while(strlen($data) < $length);
		
		//calculate the ping
		self::$response->ping = round((microtime(true) - $startPing) * 1000);
		
		//no data
		if($data === false) {
			throw new MCPingException('Server didn\'t return any data');
		}
		
		//decode json data
		$data = json_decode($data, true);
		
		//optional json error
		if(json_last_error() !== JSON_ERROR_NONE) {
			throw new MCPingException(json_last_error_msg());
		}
		
		self::$response->version = $data['version']['name'];
		self::$response->protocol = $data['version']['protocol'];
		self::$response->players = $data['players']['online'];
		self::$response->max_players = $data['players']['max'];
		self::$response->sample_player_list = self::createSamplePlayerList(@$data['players']['sample']);
		self::$response->motd = self::createMotd($data['description']);
		self::$response->favicon = isset($data['favicon']) ? $data['favicon'] : null;
		self::$response->mods = isset($data['modinfo']) ? $data['modinfo'] : null;
	}
	
	/**
	 * Ping the server with version < 1.7
	 * @throws MCPingException
	 */
	private static function pingOld() {
		fwrite(self::$socket, "\xFE\x01");
		
		//start ping time
		$startPing = microtime(true);
		
		$data = fread(self::$socket, 512);
		self::$response->ping = round((microtime(true) - $startPing) * 1000);
		
		$length = strlen($data);
		if($length < 4 || $data[0] !== "\xFF") {
			throw new MCPingException('$length < 4 || $data[ 0 ] !== "\xFF"');
		}
		
		//strip packet header (kick message packet and short length)
		$data = substr($data, 3);
		$data = iconv('UTF-16BE', 'UTF-8', $data);
		
		//are we dealing with Minecraft 1.4+ server?
		if($data[1] === "\xA7" && $data[2] === "\x31") {
			$data = explode("\x00", $data);
			self::$response->motd = $data[3];
			self::$response->players = intval($data[4]);
			self::$response->max_players = intval($data[5]);
			self::$response->protocol = intval($data[1]);
			self::$response->version = $data[2];
		}
		else {
			$data = explode("\xA7", $data);
			self::$response->motd = substr($data[0], 0, -1);
			self::$response->players = isset($data[1]) ? intval($data[1]) : 0;
			self::$response->max_players = isset($data[2]) ? intval($data[2]) : 0;
			self::$response->protocol = 0;
			self::$response->version = '1.3';
		}
		
	}
	
	/**
	 * Read int var from socket
	 * @return int
	 * @throws MCPingException
	 */
	private static function readVarInt() {
		$i = 0;
		$j = 0;
		
		while(true) {
			$k = @fgetc(self::$socket);
			if($k === false) {
				return 0;
			}
			$k = ord($k);
			$i |= ($k & 0x7F) << $j++ * 7;
			if($j > 5) {
				throw new MCPingException('VarInt too big');
			}
			if(($k & 0x80) != 128) {
				break;
			}
		}
		
		return $i;
	}
	
	/**
	 * Return sample player list
	 * @param $obj
	 * @return array|null
	 */
	private static function createSamplePlayerList($obj) {
		return (isset($obj) && is_array($obj) && count($obj) > 0) ? $obj : null;
	}
	
	/**
	 * Build the motd
	 * @param $string
	 * @return mixed|string
	 */
	private static function createMotd($string) {
		if(!is_array($string)) {
			return $string;
		}
		else if(isset($string['extra'])) {
			$output = '';
			
			foreach($string['extra'] as $item) {
				if(isset($item['color'])) {
					switch($item['color']) {
						case 'black':
							$output .= '§0';
							break;
						case 'dark_blue':
							$output .= '§1';
							break;
						case 'dark_green':
							$output .= '§2';
							break;
						case 'dark_aqua':
							$output .= '§3';
							break;
						case 'dark_red':
							$output .= '§4';
							break;
						case 'dark_purple':
							$output .= '§5';
							break;
						case 'gold':
							$output .= '§6';
							break;
						case 'gray':
							$output .= '§7';
							break;
						case 'dark_gray':
							$output .= '§8';
							break;
						case 'blue':
							$output .= '§9';
							break;
						case 'green':
							$output .= '§a';
							break;
						case 'aqua':
							$output .= '§b';
							break;
						case 'red':
							$output .= '§c';
							break;
						case 'light_purple':
							$output .= '§d';
							break;
						case 'yellow':
							$output .= '§e';
							break;
						case 'white':
							$output .= '§f';
							break;
					}
				}
				
				if(isset($item['obfuscated'])) {
					$output .= '§k';
				}
				
				if(isset($item['bold'])) {
					$output .= '§l';
				}
				
				if(isset($item['strikethrough'])) {
					$output .= '§m';
				}
				
				if(isset($item['underline'])) {
					$output .= '§n';
				}
				
				if(isset($item['italic'])) {
					$output .= '§o';
				}
				
				if(isset($item['reset'])) {
					$output .= '§r';
				}
				
				$output .= $item['text'];
			}
			
			if(isset($string['text'])) {
				$output .= $string['text'];
			}
			
			return $output;
		}
		else if(isset($string['text'])) {
			return $string['text'];
		}
		else {
			return $string;
		}
	}
}