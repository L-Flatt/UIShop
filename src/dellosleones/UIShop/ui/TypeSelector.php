<?php
namespace dellosleones\UIShop\ui;

use pocketmine\Player;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;

class TypeSelector extends UserInterface {
	const FORM_ID = 24648990;
	public function handle(Player $p, $response){
		$types = $this->db->getTypes();
		if($response === null) return;
		$responseKey = json_decode($response, true);
		if($responseKey === null) return;
		$type = $types[$responseKey];
		$this->plugin->getItemSelector($type)->sendTo($p);
	}
	public function sendTo(Player $p){
		$types = $this->db->getTypes();
		$ui = [
		"title"=>"아이템 분류 선택",
		"content"=>"구매/판매할 아이템 분류를 선택해주세요. 방어구는 구매만 가능합니다.",
		"type"=>"form",
		"buttons"=>[ ]
		];
		foreach($types as $type){
			$ui["buttons"][] = ["text"=>$type];
		}
		$pk = new ModalFormRequestPacket();
		$pk->formId = self::FORM_ID;
		$pk->formData = json_encode($ui);
		$p->dataPacket($pk);
	}
}
?>