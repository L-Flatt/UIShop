<?php
namespace dellosleones\UIShop;

use pocketmine\item\Item;
use pocketmine\utils\Config;

class ShopMetadata {
	private $plugin;
	private $id, $damage, $buyPrice, $sellPrice;
	private $priceConf;
	private $item;
	private $itemName;
	private $type;
	
	const CROPS = "농작물";
	const FOOD = "음식";
	const WEAPON = "무기";
	const ARMOR = "방어구";
	const ETC = "기타";
	const BLOCK = "블럭";
	const MINERAL = "광물";
	const TOOL = "도구";
	
	public function __construct(UIShop $plugin, $id, $damage, $type, Config $price){
		$this->plugin = $plugin;
		@mkdir($plugin->getDataFolder());
		$key = $damage === 0 ? (string) $id :  $id . ":" . $damage;
		$this->priceConf = $price;
		$this->buyPrice = $this->priceConf->get($key, [0])[0];
		$this->sellPrice = $this->priceConf->get($key, [0, 0])[1];
		$this->item = Item::get($id, $damage);
		$this->itemName = $this->item->getName();
		$this->type = $type;
	}
	public function getType(){
		return $this->type;
	}
	public function getBuyPrice(){
		return $this->buyPrice;
	}
	public function getSellPrice(){
		return $this->sellPrice;
	}
	public function getItem(){
		return clone $this->item;
	}
	public function getName(){
		return $this->itemName;
	}
}
?>