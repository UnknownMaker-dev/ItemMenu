<?php

declare(strict_types=1);

namespace Unknown\ItemMenu;

use pocketmine\item\Durable;
use Unknown\Forja\config\ConfigView;
use Unknown\Forja\util\Items;
use Unknown\Forja\util\Text;
use function array_diff;
use function array_is_list;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function is_array;
use function is_float;
use function is_int;
use function is_string;
use function mb_strtolower;
use function preg_match;
use function substr;
use function trim;

/**
 * Lê e valida o items.yml. Nunca lança: cada entrada inválida é ignorada com um aviso
 * explicando o motivo, e as demais continuam valendo.
 */
final class CatalogLoader {

	/** Tamanho do inventário principal do jogador (slots 1 a 36 no items.yml). */
	public const INVENTORY_SLOTS = 36;

	private const ID_PATTERN = '/^[a-z0-9_-]{1,32}$/';

	private const KNOWN_KEYS = [
		"item", "name", "lore", "fixed", "slot", "worlds", "cooldown", "permission", "run-as", "commands"
	];

	private const MAX_COOLDOWN = 86400.0;

	private function __construct() {}

	public static function load(ConfigView $file) : Catalog {
		$logger = $file->getLogger();
		$source = $file->getSourceName();

		$raw = $file->raw("items");
		if($raw === null){
			$logger->warning("[$source] seção 'items' ausente ou vazia: nenhum item de menu carregado.");
			return new Catalog();
		}
		if(!is_array($raw)){
			$logger->warning("[$source] 'items' precisa ser uma seção com os itens: nenhum item de menu carregado.");
			return new Catalog();
		}

		$section = $file->section("items");
		/** @var array<string, MenuItem> $items */
		$items = [];
		/** @var array<int, string> $slotOwners índice => id do item fixo */
		$slotOwners = [];
		$ignored = 0;

		foreach($raw as $key => $value){
			$rawId = (string) $key;
			$id = mb_strtolower(trim($rawId), "UTF-8");
			$error = null;

			if(preg_match(self::ID_PATTERN, $id) !== 1){
				$error = "id inválido (use letras minúsculas, números, '_' ou '-', até 32 caracteres)";
			}elseif(isset($items[$id])){
				$error = "id repetido (já existe um item '$id')";
			}elseif(!is_array($value) || ($value !== [] && array_is_list($value))){
				$error = "a entrada precisa ser uma seção com as chaves do item";
			}

			$item = null;
			if($error === null){
				$item = self::parseEntry($id, $section->section($rawId), $slotOwners, $error);
			}

			if($item === null){
				$logger->warning("[$source] item '$rawId' ignorado: " . ($error ?? "entrada inválida"));
				$ignored++;
				continue;
			}

			$items[$id] = $item;
			if($item->isFixed() && $item->getSlot() !== null){
				$slotOwners[$item->getSlot()] = $id;
			}
		}

		return new Catalog($items, $ignored);
	}

	/**
	 * @param array<int, string> $slotOwners
	 * @param-out string|null $error
	 */
	private static function parseEntry(string $id, ConfigView $entry, array $slotOwners, ?string &$error) : ?MenuItem {
		$logger = $entry->getLogger();
		$source = $entry->getSourceName();
		$path = "items.$id";

		$unknown = array_diff($entry->keys(), self::KNOWN_KEYS);
		if(count($unknown) > 0){
			$logger->warning("[$source] item '$id': chave(s) desconhecida(s) ignorada(s): " . implode(", ", $unknown));
		}

		$typeName = trim($entry->string("item", ""));
		if($typeName === ""){
			$error = "falta a chave 'item' (tipo do item, ex.: compass)";
			return null;
		}
		$item = Items::parse($typeName);
		if($item === null || $item->isNull()){
			$error = "item desconhecido: '$typeName'";
			return null;
		}

		$fixed = $entry->bool("fixed", false);

		$slot = null;
		$rawSlot = $entry->raw("slot");
		if($rawSlot !== null){
			$number = self::toInt($rawSlot);
			if($number === null || $number < 1 || $number > self::INVENTORY_SLOTS){
				$error = "'slot' precisa ser um número de 1 a " . self::INVENTORY_SLOTS;
				return null;
			}
			$slot = $number - 1;
		}

		if($fixed){
			if($slot === null){
				$error = "itens fixos precisam da chave 'slot' (1 a " . self::INVENTORY_SLOTS . ")";
				return null;
			}
			if(isset($slotOwners[$slot])){
				$error = "o slot " . ($slot + 1) . " já é usado pelo item fixo '{$slotOwners[$slot]}'";
				return null;
			}
		}

		$worlds = [];
		foreach($entry->stringList("worlds") as $world){
			$world = mb_strtolower(trim($world), "UTF-8");
			if($world !== ""){
				$worlds[] = $world;
			}
		}
		$worlds = array_values(array_unique($worlds));

		$cooldown = $entry->float("cooldown", 0.0, 0.0, self::MAX_COOLDOWN);

		$permission = trim($entry->string("permission", ""));

		$runAs = mb_strtolower(trim($entry->string("run-as", "player")), "UTF-8");
		$defaultConsole = self::parseRunner($runAs);
		if($defaultConsole === null){
			$logger->warning("[$source] chave '$path.run-as': esperado \"player\" ou \"console\", recebido \"$runAs\"; usando player");
			$defaultConsole = false;
		}

		$actions = [];
		foreach($entry->stringList("commands") as $index => $line){
			$action = self::parseAction($line, $defaultConsole);
			if($action === null){
				$logger->warning("[$source] chave '$path.commands.$index': comando vazio ignorado");
				continue;
			}
			$actions[] = $action;
		}
		if(count($actions) === 0){
			$logger->warning("[$source] item '$id' não tem comandos: usar o item não fará nada.");
		}

		$name = $entry->string("name", "");
		if($name !== ""){
			$item->setCustomName(Text::color($name));
		}
		$lore = [];
		foreach($entry->stringList("lore") as $line){
			$lore[] = Text::color($line);
		}
		if(count($lore) > 0){
			$item->setLore($lore);
		}
		if($item instanceof Durable){
			$item->setUnbreakable();
		}
		$item->setCount(1);
		ItemTag::mark($item, $id);

		return new MenuItem(
			$id,
			$item,
			$slot,
			$fixed,
			$worlds,
			$cooldown,
			$permission !== "" ? $permission : null,
			$actions
		);
	}

	/**
	 * "console: say oi" ⇒ console; "player: menu" ⇒ jogador; sem prefixo ⇒ o padrão do item.
	 * Devolve null se não sobrar comando.
	 */
	public static function parseAction(string $line, bool $defaultConsole) : ?MenuAction {
		$line = trim($line);
		$asConsole = $defaultConsole;
		if(preg_match('/^([a-zA-Z]+)\s*:\s*(.*)$/s', $line, $match) === 1){
			$runner = self::parseRunner(mb_strtolower($match[1], "UTF-8"));
			if($runner !== null){
				$asConsole = $runner;
				$line = trim($match[2]);
			}
		}
		$line = trim($line);
		while($line !== "" && $line[0] === "/"){
			$line = trim(substr($line, 1));
		}
		return $line !== "" ? new MenuAction($asConsole, $line) : null;
	}

	/** true = console, false = jogador, null = valor desconhecido. */
	private static function parseRunner(string $value) : ?bool {
		return match($value){
			"console" => true,
			"player", "jogador" => false,
			default => null
		};
	}

	private static function toInt(mixed $value) : ?int {
		if(is_int($value)){
			return $value;
		}
		if(is_float($value) && $value === (float) (int) $value){
			return (int) $value;
		}
		if(is_string($value) && preg_match('/^\s*\d{1,3}\s*$/', $value) === 1){
			return (int) trim($value);
		}
		return null;
	}
}
