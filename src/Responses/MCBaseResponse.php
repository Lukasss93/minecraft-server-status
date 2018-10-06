<?php

namespace MCServerStatus\Responses;

abstract class MCBaseResponse {
	
	/** @var bool $online */
	public $online = false;
	
	/** @var string $error */
	public $error = null;
	
	/** @var string $hostname */
	public $hostname = null;
	
	/** @var string $address */
	public $address = null;
	
	/** @var int $port */
	public $port = null;
	
	/** @var string $version */
	public $version = null;
	
	/** @var int $players */
	public $players = null;
	
	/** @var int $max_players */
	public $max_players = null;
	
	/** @var string $motd */
	public $motd = null;
	
	/**
	 * Remove the format codes from motd
	 * @param $motd
	 * @return mixed
	 */
	public function getMotdToText() {
		$chars = ['§0', '§1', '§2', '§3', '§4', '§5', '§6', '§7', '§8', '§9', '§a', '§b', '§c', '§d', '§e', '§f', '§k', '§l', '§m', '§n', '§o', '§r'];
		$output = str_replace($chars, '', $this->motd);
		$output = str_replace('\n', '<br>', $output);
		return $output;
	}
	
	/**
	 * Convert the motd to html
	 * @param $motd
	 * @return string
	 */
	public function getMotdToHtml() {
		preg_match_all("/[^§&]*[^§&]|[§&][0-9a-z][^§&]*/", $this->motd, $brokenupstrings);
		$returnstring = "";
		foreach($brokenupstrings as $results) {
			$ending = '';
			foreach($results as $individual) {
				$code = preg_split("/[&§][0-9a-z]/", $individual);
				preg_match("/[&§][0-9a-z]/", $individual, $prefix);
				if(isset($prefix[0])) {
					$actualcode = substr($prefix[0], 1);
					switch($actualcode) {
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
							if(strlen($individual) > 2) {
								$returnstring = $returnstring . '<span style="font-weight:bold;">';
								$ending = "</span>" . $ending;
								break;
							}
						case "m":
							if(strlen($individual) > 2) {
								$returnstring = $returnstring . '<del>';
								$ending = "</del>" . $ending;
								break;
							}
						case "n":
							if(strlen($individual) > 2) {
								$returnstring = $returnstring . '<span style="text-decoration: underline;">';
								$ending = "</span>" . $ending;
								break;
							}
						case "o":
							if(strlen($individual) > 2) {
								$returnstring = $returnstring . '<i>';
								$ending = "</i>" . $ending;
								break;
							}
						case "r":
							$returnstring = $returnstring . $ending;
							$ending = '';
							break;
					}
					if(isset($code[1])) {
						$returnstring = $returnstring . $code[1];
						if(isset($ending) && strlen($individual) > 2) {
							$returnstring = $returnstring . $ending;
							$ending = '';
						}
					}
				}
				else {
					$returnstring = $returnstring . $individual;
				}
			}
		}
		return $returnstring;
	}
	
	/**
	 * Returns all class properties as an array.
	 * @return array
	 */
	public function toArray() {
		return get_object_vars($this);
	}
}