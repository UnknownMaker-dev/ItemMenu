<?php

declare(strict_types=1);

namespace Unknown\ItemMenu;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use Unknown\Forja\config\SafeConfig;
use Unknown\Forja\util\Cooldowns;
use Unknown\Forja\util\Inventories;
use Unknown\ItemMenu\command\ItemMenuCommand;
use function mb_strtolower;

final class ItemMenu extends PluginBase {

	private Catalog $catalog;
	private Settings $settings;
	private Cooldowns $cooldowns;

	protected function onLoad() : void {
		$this->catalog = new Catalog();
		$this->settings = Settings::defaults();
		$this->cooldowns = new Cooldowns();
	}

	protected function onEnable() : void {
		$this->loadFiles();

		$this->getServer()->getPluginManager()->registerEvents(new MenuListener($this), $this);
		$this->getServer()->getCommandMap()->register($this->getName(), new ItemMenuCommand($this));
	}

	protected function onDisable() : void {
		$this->cooldowns->clearAll();
	}

	/**
	 * Lê config.yml e items.yml de novo e reaplica os itens fixos de quem está online.
	 */
	public function reload() : void {
		$this->loadFiles();
		foreach($this->getServer()->getOnlinePlayers() as $player){
			if($player->isAlive()){
				$this->refreshPlayer($player);
			}
		}
	}

	private function loadFiles() : void {
		$this->settings = Settings::fromView(SafeConfig::load($this, "config.yml"));
		$this->catalog = CatalogLoader::load(SafeConfig::load($this, "items.yml"));

		$message = "{$this->catalog->count()} item(ns) de menu carregado(s)";
		if($this->catalog->getIgnoredCount() > 0){
			$message .= ", {$this->catalog->getIgnoredCount()} ignorado(s) por erro (veja os avisos acima)";
		}
		$this->getLogger()->info($message . ".");
	}

	public function getCatalog() : Catalog {
		return $this->catalog;
	}

	public function getSettings() : Settings {
		return $this->settings;
	}

	public function getCooldowns() : Cooldowns {
		return $this->cooldowns;
	}

	/** Prefixo das chaves de cooldown de um jogador. */
	public static function cooldownPrefix(Player $player) : string {
		return mb_strtolower($player->getName(), "UTF-8") . ":";
	}

	/**
	 * Entrega os itens fixos depois do atraso configurado (entrada e renascimento).
	 */
	public function scheduleRefresh(Player $player) : void {
		$delay = $this->settings->giveDelayTicks;
		if($delay <= 0){
			$this->refreshPlayer($player);
			return;
		}
		$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function() use ($player) : void {
			if($player->isConnected() && $player->isAlive()){
				$this->refreshPlayer($player);
			}
		}), $delay);
	}

	/**
	 * Remove itens de menu que não existem mais (se configurado) e coloca todos os itens
	 * fixos nos seus slots.
	 */
	public function refreshPlayer(Player $player) : void {
		if($this->settings->removeUnknownItems){
			SlotPlacer::removeUnknown($player->getInventory(), $this->catalog);
			SlotPlacer::removeUnknown($player->getOffHandInventory(), $this->catalog);
			SlotPlacer::removeUnknown($player->getCursorInventory(), $this->catalog);
		}
		foreach($this->catalog->fixedItems() as $item){
			$this->giveItem($player, $item);
		}
	}

	/**
	 * Entrega um item de menu. Itens fixos vão para o slot deles (o que estava lá é movido
	 * ou dropado, nunca apagado); os demais vão para o slot preferido, se estiver vazio, ou
	 * para onde couber (o que não couber é dropado).
	 */
	public function giveItem(Player $player, MenuItem $item) : void {
		$inventory = $player->getInventory();
		$slot = $item->getSlot();

		if(!$item->isFixed()){
			if($slot !== null && $slot < $inventory->getSize() && $inventory->isSlotEmpty($slot)){
				$inventory->setItem($slot, $item->createItem());
			}else{
				Inventories::giveOrDrop($player, $item->createItem());
			}
			return;
		}
		if($slot === null){
			return;
		}

		$result = SlotPlacer::place($inventory, $slot, $item->createItem(), $item->getId(), $this->catalog->reservedSlots(), $leftovers);
		if($result === SlotPlacer::RESULT_MOVED){
			$this->notify($player, $this->settings->message("slot-moved", ["slot" => $slot + 1]));
		}elseif($result === SlotPlacer::RESULT_OVERFLOW){
			$position = $player->getPosition();
			foreach($leftovers as $leftover){
				if($position->isValid()){
					$position->getWorld()->dropItem($position, $leftover);
				}else{
					$this->getLogger()->warning("Não foi possível dropar o item de {$player->getName()} (mundo descarregado): {$leftover}");
				}
			}
			$this->notify($player, $this->settings->message("slot-dropped", ["slot" => $slot + 1]));
		}
	}

	public function notify(Player $player, string $message) : void {
		if($message !== ""){
			$player->sendMessage($message);
		}
	}
}
