<?php

namespace dellosleones\UIShop\ui;

use dellosleones\UIShop\{
	ShopDB, UIShop
};
use pocketmine\Player;

abstract class UserInterface{
	public $plugin;
	public $db;

	public function __construct(UIShop $plugin, ShopDB $db){
		$this->plugin = $plugin;
		$this->db = $db;
	}

	abstract public function sendTo(Player $p);

	abstract public function handle(Player $p, $response);
}

?>