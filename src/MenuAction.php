<?php

declare(strict_types=1);

namespace Unknown\ItemMenu;

/**
 * Um comando de um item de menu e quem o executa.
 */
final class MenuAction {

	public function __construct(
		public readonly bool $asConsole,
		public readonly string $command
	) {}

	public function describe() : string {
		return ($this->asConsole ? "console" : "player") . ": " . $this->command;
	}
}
