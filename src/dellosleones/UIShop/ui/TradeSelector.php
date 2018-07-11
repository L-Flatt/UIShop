<?php
namespace dellosleones\UIShop\ui;

use pocketmine\Player;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use dellosleones\UIShop\{UIShop, ShopDB, Shop};
use pocketmine\utils\TextFormat;

class TradeSelector extends UserInterface {
	protected $shop;
	private $sendQueue = [ ];
	const FORM_ID = 24648992;
	public function __construct(UIShop $plugin, ShopDB $db, Shop $shop){
		$this->shop = $shop;
		parent::__construct($plugin, $db);
	}
	public function sendTo(Player $p){
		$ui = [
		"type"=>"modal",
		"content"=>$this->shop->getName() . "(을)를 구매하시려면 §b구매 §r버튼을, 판매하시려면 §b판매 §r버튼을 눌러주세요. 취소하려면 구매/판매 중 아무 버튼이나 누르신 후 채팅창에 §b취소§r를 입력하세요.",
		"button1"=>"구매",
		"button2"=>"판매",
		"title"=>"거래 선택"
		];
		$pk = new ModalFormRequestPacket();
		$pk->formId = self::FORM_ID;
		$pk->formData = json_encode($ui);
		$p->dataPacket($pk);
		$this->sendQueue[$p->getName()] = true;
	}
	public function handle(Player $p, $response){
		if(! isset($this->sendQueue[$p->getName()]))
			return;
		unset($this->sendQueue[$p->getName()]);
		if($response === null) return;
		$response = json_decode($response);
		if($response === null) return;
		$price = $this->shop->meta->getBuyPrice();
		$mode = "구매";
		if($response){
			$this->plugin->buyQueue[$p->getName()] = $this->shop;
		} else {
			$this->plugin->sellQueue[$p->getName()] = $this->shop;
			$price = $this->shop->meta->getSellPrice();
			$mode = "판매";
		}
		$p->sendMessage(TextFormat::AQUA . $this->shop->getName() . "(을)를 ". $mode . "합니다. 채팅창에 " . $mode . "할 아이템 개수를 입력하세요. (가격 : " . $price . ") 진행중인 작업을 취소하시려면 채팅창에 §b취소§r를 입력하세요.");
	}
}
?>