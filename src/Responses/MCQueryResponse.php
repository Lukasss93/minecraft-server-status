<?php

namespace MCServerStatus\Responses;

/**
 * Class MCQueryResponse
 * @package MCServerStatus
 */
class MCQueryResponse extends MCBaseResponse {
	
	/** @var string $software Get the server software name */
	public $software = null;
	
	/** @var string $game_type Get the game type */
	public $game_type = null;
	
	/** @var string $game_name Get the game name */
	public $game_name = null;
	
	/** @var array $player_list Get an array of players */
	public $player_list = [];
	
	/** @var string $map Get the map name */
	public $map = null;
	
	/** @var array $plugins Get an array of installed plugins */
	public $plugins = [];
	
}