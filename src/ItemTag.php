<?php

declare(strict_types=1);

namespace Unknown\ItemMenu;

use pocketmine\item\Item;
use pocketmine\nbt\tag\StringTag;

/**
 * Marca própria gravada na NBT dos itens criados pelo plugin. É ela (e não o tipo do
 * item) que identifica um item de menu: uma bússola comum nunca é confundida com a
 * bússola do menu.
 */
final class ItemTag {

	/** Nome da tag de texto que guarda o id da entrada do items.yml. */
	public const TAG = "itemmenu:id";

	private function __construct() {}

	/** Grava o id no item (altera e devolve o próprio item). */
	public static function mark(Item $item, string $id) : Item {
		$nbt = clone $item->getNamedTag();
		$nbt->setString(self::TAG, $id);
		$item->setNamedTag($nbt);
		return $item;
	}

	/** Id gravado no item, ou null se o item não foi criado por este plugin. */
	public static function idOf(Item $item) : ?string {
		if($item->isNull()){
			return null;
		}
		$tag = $item->getNamedTag()->getTag(self::TAG);
		if(!$tag instanceof StringTag){
			return null;
		}
		$id = $tag->getValue();
		return $id !== "" ? $id : null;
	}

	public static function isMarked(Item $item) : bool {
		return self::idOf($item) !== null;
	}
}
