<?php

declare(strict_types=1);

namespace Unknown\ItemMenu;

use Unknown\Forja\config\ConfigView;
use Unknown\Forja\util\Text;
use function strtr;

/**
 * Configurações gerais e mensagens (config.yml).
 */
final class Settings {

	/** Mensagens padrão, usadas quando a chave falta ou tem tipo errado. */
	public const DEFAULT_MESSAGES = [
		"world-disabled" => "&cEste item não pode ser usado no mundo &f{world}&c.",
		"cooldown" => "&cAguarde &f{time} &cpara usar este item de novo.",
		"no-permission" => "&cVocê não tem permissão para usar este item.",
		"locked" => "&cEste item é fixo: não pode ser movido nem dropado.",
		"slot-moved" => "&eO item que estava no slot &f{slot} &efoi movido para outro espaço do inventário.",
		"slot-dropped" => "&eSeu inventário estava cheio: o item que estava no slot &f{slot} &efoi dropado no chão."
	];

	/**
	 * @param array<string, string> $messages chave => texto já colorido ("" = não enviar)
	 */
	public function __construct(
		public readonly string $prefix,
		public readonly float $antiDoubleClick,
		public readonly int $giveDelayTicks,
		public readonly bool $removeUnknownItems,
		private array $messages
	) {}

	public static function fromView(ConfigView $config) : self {
		$messages = [];
		$section = $config->section("messages");
		foreach(self::DEFAULT_MESSAGES as $key => $default){
			$messages[$key] = Text::color($section->string($key, $default));
		}
		return new self(
			Text::color($config->string("prefix", "&8[&bItemMenu&8]&r ")),
			$config->float("anti-double-click", 0.4, 0.0, 5.0),
			$config->int("give-delay-ticks", 5, 0, 200),
			$config->bool("remove-unknown-items", true),
			$messages
		);
	}

	public static function defaults() : self {
		$messages = [];
		foreach(self::DEFAULT_MESSAGES as $key => $text){
			$messages[$key] = Text::color($text);
		}
		return new self(Text::color("&8[&bItemMenu&8]&r "), 0.4, 5, true, $messages);
	}

	/**
	 * Mensagem com prefixo e variáveis trocadas, ou "" se estiver desativada.
	 *
	 * @param array<string, string|int> $vars nome sem chaves => valor
	 */
	public function message(string $key, array $vars = []) : string {
		$text = $this->messages[$key] ?? "";
		if($text === ""){
			return "";
		}
		$replacements = [];
		foreach($vars as $name => $value){
			$replacements["{" . $name . "}"] = (string) $value;
		}
		return $this->prefix . strtr($text, $replacements);
	}
}
