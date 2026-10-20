<?php

declare(strict_types=1);

namespace Unknown\ItemMenu;

use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBlockPickEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerEntityInteractEvent;
use pocketmine\event\player\PlayerEntityPickEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\item\Item;
use pocketmine\item\NameTag;
use pocketmine\player\Player;
use Throwable;
use Unknown\Forja\util\Commands;
use Unknown\Forja\util\Duration;
use function ceil;

/**
 * Uso dos itens de menu (clique no ar ou em bloco) e trava dos itens fixos.
 */
final class MenuListener implements Listener {

	/** Sufixo da chave do intervalo anti-duplo-clique (ids nunca contêm "#"). */
	private const KEY_CLICK = "#click";
	/** Sufixo da chave que limita o aviso de item travado a um por segundo. */
	private const KEY_LOCKED = "#locked";

	public function __construct(private ItemMenu $plugin) {}

	/**
	 * Clique no ar. Prioridade baixa para agir antes de plugins de proteção (que costumam
	 * cancelar interações no lobby); a ação normal do item é sempre cancelada.
	 *
	 * @priority LOWEST
	 */
	public function onItemUse(PlayerItemUseEvent $event) : void {
		if($this->handleUse($event->getPlayer(), $event->getItem())){
			$event->cancel();
		}
	}

	/**
	 * Clique em bloco.
	 *
	 * @priority LOWEST
	 */
	public function onInteract(PlayerInteractEvent $event) : void {
		if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			return;
		}
		if($this->handleUse($event->getPlayer(), $event->getItem())){
			$event->cancel();
		}
	}

	/**
	 * Garantia extra: um item de menu nunca é colocado como bloco.
	 *
	 * @priority HIGH
	 */
	public function onBlockPlace(BlockPlaceEvent $event) : void {
		if(ItemTag::isMarked($event->getItem())){
			$event->cancel();
		}
	}

	/**
	 * Itens de menu nunca são consumidos (comida, poções...).
	 *
	 * @priority HIGH
	 */
	public function onConsume(PlayerItemConsumeEvent $event) : void {
		if(ItemTag::isMarked($event->getItem())){
			$event->cancel();
		}
	}

	/**
	 * Uma etiqueta (name tag) de menu com nome seria gasta ao clicar numa entidade.
	 *
	 * @priority HIGH
	 */
	public function onEntityInteract(PlayerEntityInteractEvent $event) : void {
		$item = $event->getPlayer()->getInventory()->getItemInHand();
		if($item instanceof NameTag && ItemTag::isMarked($item)){
			$event->cancel();
		}
	}

	/**
	 * Mover, trocar, craftar ou dropar pelo inventário: cancela se um item fixo estiver envolvido.
	 *
	 * @priority HIGH
	 */
	public function onTransaction(InventoryTransactionEvent $event) : void {
		$transaction = $event->getTransaction();
		$catalog = $this->plugin->getCatalog();
		foreach($transaction->getActions() as $action){
			if($catalog->isLocked($action->getSourceItem()) || $catalog->isLocked($action->getTargetItem())){
				$event->cancel();
				$this->warnLocked($transaction->getSource());
				return;
			}
		}
	}

	/**
	 * @priority HIGH
	 */
	public function onDrop(PlayerDropItemEvent $event) : void {
		if($this->plugin->getCatalog()->isLocked($event->getItem())){
			$event->cancel();
			$this->warnLocked($event->getPlayer());
		}
	}

	/**
	 * "Pegar bloco" no criativo poderia trocar o item fixo da mão de lugar ou substituí-lo.
	 *
	 * @priority HIGH
	 */
	public function onBlockPick(PlayerBlockPickEvent $event) : void {
		if($this->plugin->getCatalog()->isLocked($event->getPlayer()->getInventory()->getItemInHand())){
			$event->cancel();
		}
	}

	/**
	 * @priority HIGH
	 */
	public function onEntityPick(PlayerEntityPickEvent $event) : void {
		if($this->plugin->getCatalog()->isLocked($event->getPlayer()->getInventory()->getItemInHand())){
			$event->cancel();
		}
	}

	/**
	 * Itens fixos não caem na morte: saem dos drops e voltam ao renascer.
	 *
	 * @priority HIGH
	 */
	public function onDeath(PlayerDeathEvent $event) : void {
		$catalog = $this->plugin->getCatalog();
		$drops = [];
		$changed = false;
		foreach($event->getDrops() as $drop){
			if($catalog->isLocked($drop)){
				$changed = true;
				continue;
			}
			$drops[] = $drop;
		}
		if($changed){
			$event->setDrops($drops);
		}
	}

	/**
	 * @priority MONITOR
	 */
	public function onJoin(PlayerJoinEvent $event) : void {
		$this->plugin->scheduleRefresh($event->getPlayer());
	}

	/**
	 * @priority MONITOR
	 */
	public function onRespawn(PlayerRespawnEvent $event) : void {
		$this->plugin->scheduleRefresh($event->getPlayer());
	}

	/**
	 * @priority MONITOR
	 */
	public function onQuit(PlayerQuitEvent $event) : void {
		$this->plugin->getCooldowns()->clearPrefix(ItemMenu::cooldownPrefix($event->getPlayer()));
	}

	/**
	 * Executa os comandos do item, se for um item de menu.
	 *
	 * @return bool true se o item foi criado pelo plugin (a ação normal deve ser cancelada)
	 */
	private function handleUse(Player $player, Item $item) : bool {
		$id = ItemTag::idOf($item);
		if($id === null){
			return false;
		}
		$menuItem = $this->plugin->getCatalog()->get($id);
		if($menuItem === null){
			return true; // item de uma entrada que não existe mais: não faz nada
		}

		$settings = $this->plugin->getSettings();
		$cooldowns = $this->plugin->getCooldowns();
		$prefix = ItemMenu::cooldownPrefix($player);

		if($cooldowns->isActive($prefix . self::KEY_CLICK)){
			return true;
		}
		$cooldowns->set($prefix . self::KEY_CLICK, $settings->antiDoubleClick);

		$permission = $menuItem->getPermission();
		if($permission !== null && !$player->hasPermission($permission)){
			$this->plugin->notify($player, $settings->message("no-permission"));
			return true;
		}

		$world = $player->getWorld()->getFolderName();
		if(!$menuItem->isAllowedIn($world)){
			$this->plugin->notify($player, $settings->message("world-disabled", ["world" => $world]));
			return true;
		}

		$key = $prefix . $menuItem->getId();
		if($menuItem->getCooldown() > 0 && !$player->hasPermission("itemmenu.bypass.cooldown")){
			$remaining = $cooldowns->remaining($key);
			if($remaining > 0){
				$this->plugin->notify($player, $settings->message("cooldown", ["time" => Duration::format((int) ceil($remaining))]));
				return true;
			}
			$cooldowns->set($key, $menuItem->getCooldown());
		}

		$this->runActions($player, $menuItem);
		return true;
	}

	private function runActions(Player $player, MenuItem $menuItem) : void {
		$server = $this->plugin->getServer();
		$logger = $this->plugin->getLogger();
		foreach($menuItem->getActions() as $action){
			if(!$player->isConnected()){
				return;
			}
			if($action->asConsole){
				Commands::runAsConsole($server, $action->command, ["player" => $player], $logger);
				continue;
			}
			$command = Commands::build($action->command, ["player" => $player]);
			if($command === ""){
				continue;
			}
			try{
				// não interno: passa pelo CommandEvent, então bloqueios de comando de outros plugins valem
				$server->dispatchCommand($player, $command);
			}catch(Throwable $e){
				$logger->error("Falha ao executar '/$command' como {$player->getName()} (item '{$menuItem->getId()}'): " . $e::class . ": " . $e->getMessage());
			}
		}
	}

	private function warnLocked(Player $player) : void {
		$cooldowns = $this->plugin->getCooldowns();
		$key = ItemMenu::cooldownPrefix($player) . self::KEY_LOCKED;
		if($cooldowns->isActive($key)){
			return;
		}
		$cooldowns->set($key, 1.0);
		$this->plugin->notify($player, $this->plugin->getSettings()->message("locked"));
	}
}
