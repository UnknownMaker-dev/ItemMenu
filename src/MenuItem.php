<?php

declare(strict_types=1);

namespace Unknown\ItemMenu;

use pocketmine\item\Item;
use function in_array;
use function mb_strtolower;

/**
 * Uma entrada válida do items.yml.
 */
final class MenuItem {

	/**
	 * @param int|null $slot índice do inventário (0 a 35) ou null se não houver
	 * @param list<string> $worlds pastas de mundo em minúsculas; vazio = todos
	 * @param list<MenuAction> $actions
	 */
	public function __construct(
		private string $id,
		private Item $template,
		private ?int $slot,
		private bool $fixed,
		private array $worlds,
		private float $cooldown,
		private ?string $permission,
		private array $actions
	) {}

	public function getId() : string {
		return $this->id;
	}

	/** Uma cópia nova do item, já marcada com o id. */
	public function createItem() : Item {
		return clone $this->template;
	}

	/** Tipo do item para exibição (ex.: "Compass"). */
	public function getTypeName() : string {
		return $this->template->getVanillaName();
	}

	public function getSlot() : ?int {
		return $this->slot;
	}

	/** Item fixo: entregue no slot ao entrar/renascer e travado no inventário. */
	public function isFixed() : bool {
		return $this->fixed;
	}

	/**
	 * @return list<string>
	 */
	public function getWorlds() : array {
		return $this->worlds;
	}

	public function isAllowedIn(string $worldFolder) : bool {
		return $this->worlds === [] || in_array(mb_strtolower($worldFolder, "UTF-8"), $this->worlds, true);
	}

	public function getCooldown() : float {
		return $this->cooldown;
	}

	public function getPermission() : ?string {
		return $this->permission;
	}

	/**
	 * @return list<MenuAction>
	 */
	public function getActions() : array {
		return $this->actions;
	}
}
