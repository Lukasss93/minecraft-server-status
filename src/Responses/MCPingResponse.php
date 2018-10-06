<?php

namespace MCServerStatus\Responses;

/**
 * Class MCPingResponse
 * @package MCServerStatus
 */
class MCPingResponse extends MCBaseResponse {
	
	/** @var int $ping Get the server ping */
	public $ping = null;
	
	/** @var int $protocol Get the protocol number */
	public $protocol = null;
	
	/** @var array $sample_player_list Get a sample array of players */
	public $sample_player_list = [];
	
	/** @var string $favicon Get the server icon as base64 string */
	public $favicon = null;
	
	/** @var array $mods Get an array of installed mods */
	public $mods = [];
	
}