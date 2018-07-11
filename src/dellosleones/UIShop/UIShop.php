<?php
namespace dellosleones\UIShop;

use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\utils\{Config, TextFormat};
use pocketmine\event\player\{PlayerChatEvent, PlayerInteractEvent};
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\command\{Command, CommandSender};
use dellosleones\UIShop\ui\{TypeSelector, ItemSelector, TradeSelector};

class UIShop extends PluginBase implements Listener {
	private $settings;
	private $shopdb;
	private $typeSelector;
	private $itemSelectors = [ ];
	private $tradeSelectors = [ ];
	public $buyQueue = [ ], $sellQueue = [ ];
	public function onEnable(){
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->saveResource("price.json", false);
		$this->shopdb = new ShopDB($this);
		$this->settings = new Config($this->getDataFolder() . "settings.yml", Config::YAML, ["item"=>"341:0"]);
		$this->typeSelector = new TypeSelector($this, $this->shopdb);
		foreach($this->shopdb->getTypes() as $type){
			$this->itemSelectors[$type] = new ItemSelector($this, $this->shopdb, $type);
		}
		foreach($this->shopdb->getShops() as $typeArray){
			foreach($typeArray as $shop){
				$this->tradeSelectors[spl_object_hash($shop)] = new TradeSelector($this, $this->shopdb, $shop);
			}
		}
	}
	public function updateUiArray(){
		$this->shopSelectors = [ ];
		$this->tradeSelectors = [ ];
		foreach($this->shopdb->getShops() as $typeArray){
			foreach($typeArray as $shop){
				$this->tradeSelectors[spl_object_hash($shop)] = new TradeSelector($this, $this->shopdb, $shop);
			}
		}
	}
	public function getTradeSelector(Shop $shop){
		return $this->tradeSelectors[spl_object_hash($shop)] ?? null;
	}
	public function getItemSelector($type){
		return $this->itemSelectors[$type] ?? null;
	}
	public function onChat(PlayerChatEvent $event){
		$p = $event->getPlayer();
		$message = $event->getMessage();
		if(isset($this->buyQueue[$p->getName()])){
			$event->setCancelled();
			if($message === "취소"){
				$p->sendMessage(TextFormat::RED . "아이템 구매가 취소되었습니다.");
				unset($this->buyQueue[$p->getName()]);
				return;
			}
			if(! is_numeric($message)){
				$p->sendMessage(TextFormat::RED . "잘못된 숫자를 입력하셨습니다. 처음부터 다시 시도해주세요.");
				unset($this->buyQueue[$p->getName()]);
				return true;
			}
			$count = (int) $message;
			$this->buyQueue[$p->getName()]->buy($p, $count);
			unset($this->buyQueue[$p->getName()]);
			return true;
		}
		if(isset($this->sellQueue[$p->getName()])){
			$event->setCancelled();
			if($message === "취소"){
				$p->sendMessage(TextFormat::RED . "아이템 판매가 취소되었습니다.");
				unset($this->sellQueue[$p->getName()]);
				return;
			}
			if(! is_numeric($message)){
				$p->sendMessage(TextFormat::RED . "잘못된 숫자를 입력하셨습니다. 처음부터 다시 시도해주세요.");
				unset($this->sellQueue[$p->getName()]);
				return true;
			}
			$count = (int) $message;
			$this->sellQueue[$p->getName()]->sell($p, $count);
			unset($this->sellQueue[$p->getName()]);
			return true;
		}
	}
	public function onPacketReceive(DataPacketReceiveEvent $event){
		$p = $event->getPlayer();
		if(($pk = $event->getPacket()) instanceof ModalFormResponsePacket){
			if($pk->formData === null) return;
			if($pk->formId === TypeSelector::FORM_ID){
				$this->typeSelector->handle($p, $pk->formData);
			} else if($pk->formId === ItemSelector::FORM_ID){
				foreach($this->itemSelectors as $s){
					$s->handle($p, $pk->formData);
				}
			} else if($pk->formId === TradeSelector::FORM_ID){
				foreach($this->tradeSelectors as $s){
					$s->handle($p, $pk->formData);
				}
			}
		}
	}
	public function onTouch(PlayerInteractEvent $event){
		$p = $event->getPlayer();
		$explode = explode(":", $this->settings->get("item"));
		$id = (int) $explode[0];
		$damage = (int) $explode[1];
		$item = $event->getItem();
		if($item->getId() === $id and $item->getDamage() === $damage){
			$this->typeSelector->sendTo($p);
		}
	}
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) : bool {
		if($command->getName() === "상점추가"){
			if(! isset($args[0])){
				$sender->sendMessage(TextFormat::BLUE . "사용법: /상점추가 (아이템코드:대미지) (광물/농작물/음식/무기/방어구/블럭/기타) §f또는 아이템을 손에 들고 §9/상점추가 (광물/농작물/음식/무기/방어구/블럭/기타) ");
				return true;
			}
			if(! isset($args[1])){
				if(! in_array($args[0], $this->shopdb->getTypes())){
					$sender->sendMessage(TextFormat::BLUE . "사용법: /상점추가 (아이템코드:대미지) (광물/농작물/음식/무기/방어구/블럭/기타) §f또는 아이템을 손에 들고 §9/상점추가 (광물/농작물/음식/무기/방어구/블럭/기타) ");
					return true;
				}
				if(! $sender instanceof Player){
					$sender->sendMessage(TextFormat::RED . "콘솔 내에서는 /상점추가 (아이템코드:대미지) (상점분류) 만 가능합니다.");
					return true;
				}
				$item = $sender->getInventory()->getItemInHand();
				$this->shopdb->addShop($args[0], $item->getId(), $item->getDamage());
				$sender->sendMessage(TextFormat::AQUA . "상점 생성이 완료되었습니다.");
				return true;
			}
			$type = $args[1];
			$exp = explode(":", $args[0]);
			$id = (int) $exp[0];
			$damage = (int) ($exp[1] ?? 0);
			$this->shopdb->addShop($type, $id, $damage);
			$sender->sendMessage("상점이 추가되었습니다.");
			return true;
		}
		if($command->getName() === "상점제거"){
			if(! isset($args[0])){
				$sender->sendMessage(TextFormat::BLUE . "사용법: /상점제거 (아이템코드:대미지) (농작물/음식/무기/방어구/블럭/광물/기타) §f또는 아이템을 손에 들고§9 /상점제거 (농작물/음식/무기/방어구/블럭/광물/기타)");
				return true;
			}
			if(! isset($args[1])){
				if(! in_array($args[0], $this->shopdb->getTypes())){
					$sender->sendMessage(TextFormat::BLUE . "사용법: /상점제거 (아이템코드:대미지) (농작물/음식/무기/방어구/블럭/광물/기타) §f또는 아이템을 손에 들고§9 /상점제거 (농작물/음식/무기/방어구/블럭/광물/기타)");
					return true;
				}
				if(! $sender instanceof Player){
					$sender->sendMessage(TextFormat::RED . "콘솔 내에서는 /상점제거 (아이템코드:대미지) (상점분류) 만 가능합니다.");
					return true;
				}
				$this->shopdb->removeShop($args[0], $sender->getInventory()->getItemInHand()->getId(), $sender->getInventory()->getItemInHand()->getDamage());
				$sender->sendMessage(TextFormat::AQUA . "상점 삭제가 완료되었습니다.");
				return true;
			}
			$exp = explode(":", $args[0]);
			$id = (int) $exp[0];
			$damage = (int) ($exp[1] ?? 0);
			$type = $args[1];
			$this->shopdb->removeShop($type, $id, $damage);
			$sender->sendMessage("상점이 제거되었습니다");
			return true;
		}
		if($command->getName() === "가격설정"){
			if(! isset($args[0]) || ! isset($args[1]) || ! isset($args[2]) || ($args[0] !== "구매" and $args[0] !== "판매") || ! is_numeric($args[2])){
				$sender->sendMessage(TextFormat::BLUE . "사용법: /가격설정 (구매/판매) (아이템코드:대미지) (가격)");
				return true;
			}
			$exp = explode(":", $args[1]);
			$id = $exp[0];
			$damage = $exp[1];
			$price = (int) $args[2];
			if($args[0] === "구매"){
				$this->shopdb->setBuyPrice($id, $damage, $price);
			} else {
				$this->shopdb->setSellPrice($id, $damage, $price);
			}
			$sender->sendMessage(TextFormat::AQUA . "가격 설정이 완료되었습니다.");
			return true;
		}
		if($command->getName() === "전체판매"){
			if($sender instanceof Player){
				$this->shopdb->sellAll($sender);
				return true;
			}
		}
		return true;
	}
}
?>