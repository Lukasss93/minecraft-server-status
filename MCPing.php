<?php

class MCPing
{
	private $socket;
	private $timeout;

	private $error;
	private $host;
	private $address;
	private $port;
	private $ping;
	private $version;
	private $protocol;
	private $players;
	private $max_players;
	private $sample_player_list;
	private $motd;
	private $favicon;
	private $mods;

	//public methods

	public function __construct()
	{
	}

	public function __destruct()
	{
		$this->Close();
	}

	public function GetStatus($Hostname = '127.0.0.1', $Port = 25565, $IsOld17 = false, $Timeout = 2)
	{
		$this->Clear();

		$this->host = $Hostname;
		$this->port = $Port;
		$this->timeout = $Timeout;

		//validate host
		if (filter_var($this->host, FILTER_VALIDATE_IP))
		{
			//host is ip => address is host
			$this->address = $this->host;
		}
		else
		{
			//find domain ip
			$resolvedIp = gethostbyname($this->host);

			if (filter_var($resolvedIp, FILTER_VALIDATE_IP))
			{
				//resolvedIp is a valid IP => address is resolvedIp
				$this->address = $resolvedIp;
			}
			else
			{
				//resolvedIp is not a valid IP then find SRV record
				$dns = @dns_get_record('_minecraft._tcp.' . $this->host, DNS_SRV);
				/*
				 * added @ because in a few server, php return this error:
				 * 'dns_get_record(): A temporary server error occurred'
				 * the resolution time is almost always 15-25sec.
				 * I don't know how fix the time.
				 */

				if (!$dns)
				{
					$this->error = 'dns_get_record(): A temporary server error occurred';
					return $this;
				}

				if (is_array($dns) and count($dns) > 0)
				{
					$this->address = gethostbyname($dns[0]['target']);
					$this->port = $dns[0]['port'];
				}
			}
		}

		//validate port
		if (!is_int($this->port) || $this->port < 1024 || $this->port > 65535)
		{
			$this->error = "Invalid port";
			return $this;
		}

		//validate isold17 parameter
		if (!is_bool($IsOld17))
		{
			$this->error = 'Invalid parameter in $isold17';
			return $this;
		}

		//validate timeout
		if (!is_int($this->timeout) || $this->timeout < 0)
		{
			$this->error = "Invalid timeout";
			return $this;
		}

		//opening socket
		$this->Connect();

		if ($this->error == null)
		{
			if (!$IsOld17)
			{
				$this->Ping();
			}
			else
			{
				$this->PingOld();
			}

			//closing socket
			$this->Close();
		}

		return $this;
	}

	public function Response()
	{
		return array(
			'online' => $this->error == null ? true : false,
			'error' => $this->error,
			'hostname' => $this->host,
			'address' => $this->address,
			'port' => $this->port,
			'ping'=>$this->ping,
			'version' => $this->version,
			'protocol' => $this->protocol,
			'players' => $this->players,
			'max_players' => $this->max_players,
			'sample_player_list' => $this->sample_player_list,
			'motd' => $this->motd,
			'favicon' => $this->favicon,
			'mods' => $this->mods
		);
	}

	public static function ClearMotd($string)
	{
		$chars = array('§0', '§1', '§2', '§3', '§4', '§5', '§6', '§7', '§8', '§9', '§a', '§b', '§c', '§d', '§e', '§f', '§k', '§l', '§m', '§n', '§o', '§r');
		$output = str_replace($chars, '', $string);
		$output = str_replace('\n', '<br>', $output);
		return $output;
	}

	public static function MotdToHtml($minetext)
	{
		preg_match_all("/[^§&]*[^§&]|[§&][0-9a-z][^§&]*/", $minetext, $brokenupstrings);
		$returnstring = "";
		foreach ($brokenupstrings as $results)
		{
			$ending = '';
			foreach ($results as $individual)
			{
				$code = preg_split("/[&§][0-9a-z]/", $individual);
				preg_match("/[&§][0-9a-z]/", $individual, $prefix);
				if (isset($prefix[0]))
				{
					$actualcode = substr($prefix[0], 1);
					switch ($actualcode)
					{
						case "1":
							$returnstring = $returnstring . '<span style="color:#0000aa">';
							$ending = $ending . "</span>";
							break;
						case "2":
							$returnstring = $returnstring . '<span style="color:#00aa00">';
							$ending = $ending . "</span>";
							break;
						case "3":
							$returnstring = $returnstring . '<span style="color:#00aaaa">';
							$ending = $ending . "</span>";
							break;
						case "4":
							$returnstring = $returnstring . '<span style="color:#aa0000">';
							$ending = $ending . "</span>";
							break;
						case "5":
							$returnstring = $returnstring . '<span style="color:#aa00aa">';
							$ending = $ending . "</span>";
							break;
						case "6":
							$returnstring = $returnstring . '<span style="color:#ffaa00">';
							$ending = $ending . "</span>";
							break;
						case "7":
							$returnstring = $returnstring . '<span style="color:#aaaaaa">';
							$ending = $ending . "</span>";
							break;
						case "8":
							$returnstring = $returnstring . '<span style="color:#555555">';
							$ending = $ending . "</span>";
							break;
						case "9":
							$returnstring = $returnstring . '<span style="color:#5555ff">';
							$ending = $ending . "</span>";
							break;
						case "a":
							$returnstring = $returnstring . '<span style="color:#55ff55">';
							$ending = $ending . "</span>";
							break;
						case "b":
							$returnstring = $returnstring . '<span style="color:#55ffff">';
							$ending = $ending . "</span>";
							break;
						case "c":
							$returnstring = $returnstring . '<span style="color:#ff5555">';
							$ending = $ending . "</span>";
							break;
						case "d":
							$returnstring = $returnstring . '<span style="color:#ff55ff">';
							$ending = $ending . "</span>";
							break;
						case "e":
							$returnstring = $returnstring . '<span style="color:rgb(221, 195, 0)">';
							$ending = $ending . "</span>";
							break;
						case "f":
							$returnstring = $returnstring . '<span style="color:#ffffff">';
							$ending = $ending . "</span>";
							break;
						case "l":
							if (strlen($individual) > 2)
							{
								$returnstring = $returnstring . '<span style="font-weight:bold;">';
								$ending = "</span>" . $ending;
								break;
							}
						case "m":
							if (strlen($individual) > 2)
							{
								$returnstring = $returnstring . '<strike>';
								$ending = "</strike>" . $ending;
								break;
							}
						case "n":
							if (strlen($individual) > 2)
							{
								$returnstring = $returnstring . '<span style="text-decoration: underline;">';
								$ending = "</span>" . $ending;
								break;
							}
						case "o":
							if (strlen($individual) > 2)
							{
								$returnstring = $returnstring . '<i>';
								$ending = "</i>" . $ending;
								break;
							}
						case "r":
							$returnstring = $returnstring . $ending;
							$ending = '';
							break;
					}
					if (isset($code[1]))
					{
						$returnstring = $returnstring . $code[1];
						if (isset($ending) && strlen($individual) > 2)
						{
							$returnstring = $returnstring . $ending;
							$ending = '';
						}
					}
				}
				else
				{
					$returnstring = $returnstring . $individual;
				}
			}
		}
		return $returnstring;
	}


	//private methods

	private function Clear()
	{
		$this->socket = null;
		$this->timeout = null;
		$this->error = null;
		$this->host = null;
		$this->address = null;
		$this->port = null;
		$this->ping=null;
		$this->version = null;
		$this->protocol = null;
		$this->players = null;
		$this->max_players = null;
		$this->sample_player_list = null;
		$this->motd = null;
		$this->favicon = null;
		$this->mods = null;
	}

	private function Connect()
	{
		$connectTimeout = $this->timeout;
		$this->socket = @fsockopen($this->address, $this->port, $errno, $errstr, $connectTimeout);

		if (!$this->socket)
		{
			$this->error = "Failed to connect or create a socket: $errno ($errstr)";
			return $this;
		}

		if ($this->error == null)
		{
			stream_set_timeout($this->socket, $this->timeout);
		}
	}

	private function Close()
	{
		if ($this->error == null and $this->socket !== null)
		{
			fclose($this->socket);

			$this->socket = null;
		}
	}

	private function ReadVarInt()
	{
		$i = 0;
		$j = 0;

		while (true)
		{
			$k = @fgetc($this->socket);
			if ($k === FALSE)
			{
				return 0;
			}
			$k = ord($k);
			$i |= ($k & 0x7F) << $j++ * 7;
			if ($j > 5)
			{
				$this->error = 'VarInt too big';
			}
			if (($k & 0x80) != 128)
			{
				break;
			}
		}

		return $i;
	}

	private function Ping()
	{

		$TimeStart = microtime(true); // for read timeout purposes

		// See http://wiki.vg/Protocol (Status Ping)
		$Data = "\x00"; // packet ID = 0 (varint)
		$Data .= "\x04"; // Protocol version (varint)
		$Data .= pack('c', strlen($this->address)) . $this->address; // Server (varint len + UTF-8 addr)
		$Data .= pack('n', $this->port); // Server port (unsigned short)
		$Data .= "\x01"; // Next state: status (varint)
		$Data = pack('c', strlen($Data)) . $Data; // prepend length of packet ID + data
		fwrite($this->socket, $Data); // handshake

		$startPing = microtime(true);
		fwrite($this->socket, "\x01\x00"); // status ping


		$Length = $this->ReadVarInt(); // full packet length
		if ($Length < 10)
		{
			$this->error = '$Length < 10';
			return $this;
		}
		fgetc($this->socket); // packet type, in server ping it's 0
		$Length = $this->ReadVarInt(); // string length
		$Data = "";
		do
		{
			if (microtime(true) - $TimeStart > $this->timeout)
			{
				$this->error = 'Server read timed out';
				return $this;
			}
			$Remainder = $Length - strlen($Data);
			$block = fread($this->socket, $Remainder); // and finally the json string
			// abort if there is no progress
			if (!$block)
			{
				$this->error = 'Server returned too few data';
				return $this;
			}
			$Data .= $block;
		} while (strlen($Data) < $Length);
		$this->ping=round((microtime(true) - $startPing) * 1000);

		if ($Data === FALSE)
		{
			$this->error = 'Server didn\'t return any data';
			return $this;
		}
		$Data = json_decode($Data, true);
		if (json_last_error() !== JSON_ERROR_NONE)
		{
			if (function_exists('json_last_error_msg'))
			{
				$this->error = json_last_error_msg();
				return $this;
			}
			else
			{
				$this->error = 'JSON parsing failed';
				return $this;
			}
			$this->error = 'json_last_error( ) !== JSON_ERROR_NONE';
			return $this;
		}
		$this->version = $Data['version']['name'];
		$this->protocol = $Data['version']['protocol'];
		$this->players = $Data['players']['online'];
		$this->max_players = $Data['players']['max'];
		$this->sample_player_list = $this->CreateSamplePlayerList(@$Data['players']['sample']);
		$this->motd = $this->CreateDescription($Data['description']);
		$this->favicon = isset($Data['favicon']) ? $Data['favicon'] : null;
		$this->mods = isset($Data['modinfo']) ? $Data['modinfo'] : null;

	}

	private function PingOld()
	{
		fwrite($this->socket, "\xFE\x01");

		$startPing = microtime(true);
		$Data = fread($this->socket, 512);
		$this->ping=round((microtime(true) - $startPing) * 1000);
		
		$Len = strlen($Data);
		
		if ($Len < 4 || $Data[0] !== "\xFF")
		{
			$this->error = '$Len < 4 || $Data[ 0 ] !== "\xFF"';
			return $this;
		}
		
		$Data = substr($Data, 3); // Strip packet header (kick message packet and short length)
		$Data = iconv('UTF-16BE', 'UTF-8', $Data);
		
		// Are we dealing with Minecraft 1.4+ server?
		if ($Data[1] === "\xA7" && $Data[2] === "\x31")
		{
			$Data = explode("\x00", $Data);
			
			
			$this->motd = $Data[3];
			$this->players = intval($Data[4]);
			$this->max_players = intval($Data[5]);
			$this->protocol = intval($Data[1]);
			$this->version = $Data[2];
		}
		else
		{
			
			$Data = explode("\xA7", $Data);
			
			$this->motd = substr($Data[0], 0, -1);
			$this->players = isset($Data[1]) ? intval($Data[1]) : 0;
			$this->max_players = isset($Data[2]) ? intval($Data[2]) : 0;
			$this->protocol = 0;
			$this->version = '1.3';
		}
		
	}
	
	private function CreateDescription($string)
	{
		if (!is_array($string))
		{
			return $string;
		}
		else if (isset($string['extra']))
		{
			$output = '';
			
			foreach ($string['extra'] as $item)
			{
				if (isset($item['color']))
				{
					switch ($item['color'])
					{
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
				
				if (isset($item['obfuscated']))
				{
					$output .= '§k';
				}
				
				if (isset($item['bold']))
				{
					$output .= '§l';
				}
				
				if (isset($item['strikethrough']))
				{
					$output .= '§m';
				}
				
				if (isset($item['underline']))
				{
					$output .= '§n';
				}
				
				if (isset($item['italic']))
				{
					$output .= '§o';
				}
				
				if (isset($item['reset']))
				{
					$output .= '§r';
				}
				
				$output .= $item['text'];
			}
			
			if (isset($string['text']))
			{
				$output .= $string['text'];
			}
			
			return $output;
		}
		else if (isset($string['text']))
		{
			return $string['text'];
		}
		else
		{
			return $string;
		}
	}
	
	private function CreateSamplePlayerList($obj)
	{
		if (isset($obj) and is_array($obj) and count($obj) > 0)
		{
			return $obj;
		}
		else
		{
			return null;
		}
	}
}