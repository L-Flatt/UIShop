<?php
namespace dellosleones\UIShop;

use onebone\economyapi\EconomyAPI;
use pocketmine\Player;
use pocketmine\item\Armor;
use pocketmine\utils\TextFormat;

class Shop {
	const CROPS = "농작물";
	const FOOD = "음식";
	const WEAPON = "무기";
	const ARMOR = "방어구";
	const ETC = "기타";
	const BLOCK = "블럭";
	const MINERAL = "광물";
	const TOOL = "도구";
	public $meta;
	public function __construct(UIShop $plugin, ShopMetadata $meta){
		$this->meta = $meta;
		$this->plugin = $plugin;
	}
	public function getName(){
		return $this->meta->getName();
	}
	public function buy(Player $p, $count){
		if(0 >= $count){
			$p->sendMessage(TextFormat::RED. "0 이상의 정수를 입력해주세요.");
			return true;
		}
		$count = (int) $count;
		$m = EconomyAPI::getInstance()->myMoney($p);
		$buyPrice = $this->meta->getBuyPrice();
		$price = $buyPrice * $count;
		if($price > $m) {
			$p->sendMessage(TextFormat::RED . "돈이 부족합니다.");
			return false;
		}
		if(! $p->getInventory()->canAddItem($this->meta->getItem()->setCount($count))){
			$p->sendMessage(TextFormat::RED . "인벤토리 공간이 부족합니다. 인벤토리 공간 확보 후 다시 시도해주세요.");
			return false;
		}
		EconomyAPI::getInstance()->reduceMoney($p, $price);
		$p->getInventory()->addItem($this->meta->getItem()->setCount($count));
		$p->sendMessage(TextFormat::AQUA . $this->getName() . " " . $count . "개를 " . $price . "원에 구매하셨습니다.");
		return true;
	}
	public function sell(Player $p, $count){
		if(0 >= $count){
			$p->sendMessage(TextFormat::RED. "0 이상의 정수를 입력해주세요.");
			return true;
		}
		$count = (int) $count;
		$item = $this->meta->getItem()->setCount($count);
		$price = $this->meta->getSellPrice() * $count;
		if($item instanceof Armor){
			return false;
		}
		if(! $p->getInventory()->contains($item)){
			$p->sendMessage(TextFormat::RED . "아이템이 부족합니다.");
			return false;
		}
		$p->getInventory()->removeItem($item);
		EconomyAPI::getInstance()->addMoney($p, $price);
		$p->sendMessage(TextFormat::AQUA . $this->getName() . "(을)를 " . $count . "개 판매하셨습니다. ($price)");
		return true;
	}
	public function getButtonArray(){
		return ["text"=>$this->getName() . " (구매: " . $this->meta->getBuyPrice() . ", 판매: " . $this->meta->getSellPrice() . ")"];
	}
}

?>