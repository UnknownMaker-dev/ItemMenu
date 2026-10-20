<?php

declare(strict_types=1);

namespace Unknown\ItemMenu;

use pocketmine\item\Item;
use function array_filter;
use function array_keys;
use function array_values;
use function count;
use function usort;

/**
 * Conjunto de itens de menu carregados do items.yml.
 */
final class Catalog {

	/** @var list<MenuItem> itens fixos ordenados pelo slot */
	private array $fixed;

	/**
	 * @param array<string, MenuItem> $items id => item
	 * @param int $ignored quantas entradas foram ignoradas por serem inválidas
	 */
	public function __construct(
		private array $items = [],
		private int $ignored = 0
	) {
		$fixed = array_values(array_filter($items, static fn(MenuItem $item) : bool => $item->isFixed()));
		usort($fixed, static fn(MenuItem $a, MenuItem $b) : int => ($a->getSlot() ?? 0) <=> ($b->getSlot() ?? 0));
		$this->fixed = $fixed;
	}

	public function get(string $id) : ?MenuItem {
		return $this->items[$id] ?? null;
	}

	/** Item de menu correspondente ao item do jogador (pela marca na NBT), ou null. */
	public function match(Item $item) : ?MenuItem {
		$id = ItemTag::idOf($item);
		return $id !== null ? ($this->items[$id] ?? null) : null;
	}

	/** True se o item é um item fixo (travado) deste catálogo. */
	public function isLocked(Item $item) : bool {
		return $this->match($item)?->isFixed() === true;
	}

	/**
	 * @return array<string, MenuItem>
	 */
	public function all() : array {
		return $this->items;
	}

	/**
	 * @return list<string>
	 */
	public function ids() : array {
		return array_keys($this->items);
	}

	/**
	 * @return list<MenuItem>
	 */
	public function fixedItems() : array {
		return $this->fixed;
	}

	/**
	 * Índices de inventário reservados para itens fixos.
	 *
	 * @return list<int>
	 */
	public function reservedSlots() : array {
		$slots = [];
		foreach($this->fixed as $item){
			$slot = $item->getSlot();
			if($slot !== null){
				$slots[] = $slot;
			}
		}
		return $slots;
	}

	public function count() : int {
		return count($this->items);
	}

	public function getIgnoredCount() : int {
		return $this->ignored;
	}
}
