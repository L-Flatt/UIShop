<?php

namespace dellosleones\UIShop\ui;

use dellosleones\UIShop\{
	Shop, ShopDB, UIShop
};
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Player;

class ItemSelector extends UserInterface{
	const FORM_ID = 24648991;
	protected $type;
	private $sendQueue = [];

	public function __construct(UIShop $plugin, ShopDB $db, $type){
		$this->type = $type;
		parent::__construct($plugin, $db);
	}

	public function sendTo(Player $p){
		$ui = [
			"type" => "form",
			"title" => "아이템 선택",
			"content" => "구매/판매하실 아이템을 선택해주세요.",
			"buttons" => []
		];
		foreach($this->db->getShops()[$this->type] as $id => $shop){
			$ui["buttons"][] = $shop->getButtonArray();
		}
		$pk = new ModalFormRequestPacket();
		$pk->formId = self::FORM_ID;
		$pk->formData = json_encode($ui);
		$p->dataPacket($pk);
		$this->sendQueue[$p->getName()] = true;
	}

	public function handle(Player $p, $response){
		if(!isset($this->sendQueue[$p->getName()])){
			return;
		}
		unset($this->sendQueue[$p->getName()]);
		if($response === null){
			return;
		}
		$responseKey = json_decode($response, true);
		if($responseKey === null){
			return;
		}
		$shops = $this->db->getShops();
		$shop = $shops[$this->type][$responseKey];
		if($shop instanceof Shop){
			$this->plugin->getTradeSelector($shop)->sendTo($p);
		}
	}
}

?>