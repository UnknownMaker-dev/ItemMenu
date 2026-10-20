<?php

declare(strict_types=1);

namespace Unknown\ItemMenu;

use InvalidArgumentException;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use function in_array;

/**
 * Coloca itens fixos no inventário sem nunca apagar um item do jogador.
 */
final class SlotPlacer {

	/** O slot estava vazio ou já tinha este mesmo item de menu. */
	public const RESULT_PLACED = 0;
	/** O item do jogador que ocupava o slot foi movido para outro espaço do inventário. */
	public const RESULT_MOVED = 1;
	/** O inventário estava cheio: o item do jogador (ou parte dele) precisa ser dropado. */
	public const RESULT_OVERFLOW = 2;

	private function __construct() {}

	/**
	 * Põe `$item` (já marcado com `$id`) no índice `$index`:
	 * - cópias do mesmo item de menu em outros slots são removidas (são do plugin);
	 * - se o slot tinha outro item, ele vai para um espaço vazio que não seja reservado a
	 *   itens fixos; sem espaço assim, vai para onde couber; o que sobrar é devolvido em
	 *   `$leftovers` para ser dropado.
	 *
	 * @param list<int> $reserved índices reservados a itens fixos
	 * @param list<Item> $leftovers
	 * @param-out list<Item> $leftovers
	 * @return int uma das constantes RESULT_*
	 */
	public static function place(Inventory $inventory, int $index, Item $item, string $id, array $reserved = [], ?array &$leftovers = null) : int {
		$leftovers = [];
		if($index < 0 || $index >= $inventory->getSize()){
			throw new InvalidArgumentException("Slot $index fora do inventário (tamanho {$inventory->getSize()})");
		}

		foreach($inventory->getContents() as $slot => $content){
			if($slot !== $index && ItemTag::idOf($content) === $id){
				$inventory->clear($slot);
			}
		}

		$current = $inventory->getItem($index);
		$inventory->setItem($index, $item);
		if($current->isNull() || ItemTag::idOf($current) === $id){
			return self::RESULT_PLACED;
		}

		for($slot = 0, $size = $inventory->getSize(); $slot < $size; $slot++){
			if($slot !== $index && !in_array($slot, $reserved, true) && $inventory->isSlotEmpty($slot)){
				$inventory->setItem($slot, $current);
				return self::RESULT_MOVED;
			}
		}

		$rest = [];
		foreach($inventory->addItem($current) as $left){
			if(!$left->isNull()){
				$rest[] = $left;
			}
		}
		$leftovers = $rest;
		return $leftovers === [] ? self::RESULT_MOVED : self::RESULT_OVERFLOW;
	}

	/**
	 * Remove itens de menu cujo id não existe no catálogo.
	 *
	 * @return int quantos slots foram limpos
	 */
	public static function removeUnknown(Inventory $inventory, Catalog $catalog) : int {
		$removed = 0;
		foreach($inventory->getContents() as $slot => $content){
			$id = ItemTag::idOf($content);
			if($id !== null && $catalog->get($id) === null){
				$inventory->clear($slot);
				$removed++;
			}
		}
		return $removed;
	}
}
