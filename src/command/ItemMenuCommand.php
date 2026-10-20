<?php

declare(strict_types=1);

namespace Unknown\ItemMenu\command;

use Unknown\Forja\command\BaseCommand;
use Unknown\ItemMenu\ItemMenu;

/**
 * /itemmenu — administração dos itens de menu.
 */
final class ItemMenuCommand extends BaseCommand {

	public const PERMISSION_RELOAD = "itemmenu.command.reload";
	public const PERMISSION_GIVE = "itemmenu.command.give";
	public const PERMISSION_LIST = "itemmenu.command.list";

	public function __construct(ItemMenu $plugin) {
		parent::__construct(
			$plugin,
			"itemmenu",
			"Gerencia os itens de menu",
			["imenu"],
			self::PERMISSION_RELOAD . ";" . self::PERMISSION_GIVE . ";" . self::PERMISSION_LIST
		);
		$this->registerSubCommand(new ReloadSubCommand($plugin));
		$this->registerSubCommand(new GiveSubCommand($plugin));
		$this->registerSubCommand(new ListSubCommand($plugin));
	}
}
