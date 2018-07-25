<?php

namespace dellosleones\UIShop;

use pocketmine\Player;
use pocketmine\utils\Config;

class ShopDB{
	private $conf;
	private $shops = [];
	private $price;

	public function setBuyPrice($id, $damage, $price){
		$str = $damage === 0 ? (string) $id : $id . ":" . $damage;
		$this->price->set($str, [$price, $this->price->get($str)[1]]);
		$this->price->save();
		$shop = $this->getShopByItemDamage($id, $damage);
		if($shop !== null){
			$shop->setBuyPrice($price);
		}
	}

	public function setSellPrice($id, $damage, $price){
		$str = $damage === 0 ? (string) $id : $id . ":" . $damage;
		$this->price->set($str, [$this->price->get($str)[0], $price]);
		$this->price->save();
		$shop = $this->getShopByItemDamage($id, $damage);
		if($shop !== null){
			$shop->setSellPrice($price);
		}
	}

	public function __construct(UIShop $plugin){
		$this->plugin = $plugin;
		@mkdir($this->plugin->getDataFolder());
		$this->price = new Config($this->plugin->getDataFolder() . "price.json");
		$this->conf = new Config($this->plugin->getDataFolder() . "shops.yml");
		if(!$this->conf->exists(Shop::CROPS)){
			foreach($this->getTypes() as $type){
				$this->conf->set($type, []);
			}
		}
		foreach($this->getTypes() as $type){
			$this->shops[$type] = [];
		}
		foreach($this->conf->getAll() as $type => $itemArray){
			foreach($itemArray as $item){
				$explode = explode(":", $item);
				$id = (int) $explode[0];
				$damage = isset($explode[1]) ? (int) $explode[1] : 0;
				$this->shops[$type] [] = new Shop($plugin, new ShopMetadata($plugin, $id, $damage, $type, $this->price));
			}
		}
		$this->conf->save();
	}

	public function getShops(){
		return $this->shops;
	}

	public function addShop($type, $id, $damage){
		$getAll = $this->conf->getAll();
		if(!isset($getAll[$type])){
			return;
		}
		$getAll[$type][] = $id . ":" . $damage;
		$this->shops[$type][] = new Shop($this->plugin, new ShopMetadata($this->plugin, $id, $damage, $type, $this->price));
		$this->conf->setAll($getAll);
		$this->conf->save();
		$this->plugin->updateUiArray();
	}

	public function removeShop($type, $id, $damage){
		$shopObjArr = $this->shops[$type] ?? [];
		$getAll = $this->conf->getAll();
		$shopDBArr = $getAll[$type];
		$objKey = -1;
		$dbKey = -1;
		foreach($shopObjArr as $key => $shop){
			if($shop->meta->getItem()->getId() === $id and $shop->meta->getItem()->getDamage() === $damage){
				$objKey = $key;
			}
		}
		foreach($shopDBArr as $key => $db){
			$exp = explode(":", $db);
			$compareId = (int) $exp[0];
			$compareDamage = (int) $exp[1];
			if($compareId === $id and $compareDamage === $damage){
				$dbKey = $key;
			}
		}
		if($dbKey !== -1){
			array_splice($getAll[$type], $dbKey, 1);
			$this->conf->setAll($getAll);
			$this->conf->save();
		}
		if($objKey !== -1){
			array_splice($this->shops[$type], $objKey, 1);
			$this->plugin->updateUiArray();
		}
	}

	public function getShop($type, $id){
		return $this->shops[$type][$id] ?? null;
	}

	public function getTypes(){
		return [Shop::CROPS, Shop::WEAPON, Shop::ARMOR, Shop::TOOL, Shop::MINERAL, Shop::BLOCK, Shop::FOOD, Shop::ETC];
	}
	
	public function getShopByItemDamage(int $id, int $damage){
		foreach($this->shops as $type=>$array){
			foreach($array as $key=>$shop){
				if($shop->meta->getItem()->getId() === $id and $shop->meta->getItem()->getDamage() === $damage)
					return $shop;
			}
		}
		return null;
	}

	public function sellAll(Player $p){
		$inv = $p->getInventory();
		foreach($inv->getContents() as $item){
			foreach($this->shops as $type => $shopArr){
				foreach($shopArr as $shop){
					if($shop->meta->getItem()->getId() === $item->getId() and $shop->meta->getItem()->getDamage() === $item->getDamage()){
						if($shop->meta->getSellPrice() === 0){
							continue;
						}
						$shop->sell($p, $item->getCount());
					}
				}
			}
		}
	}
}

?>