<?php

declare(strict_types=1);

namespace Unknown\ItemMenu\command;

use pocketmine\command\CommandSender;
use Unknown\Forja\command\Args;
use Unknown\Forja\command\SubCommand;
use Unknown\ItemMenu\ItemMenu;

final class ReloadSubCommand extends SubCommand {

	public function __construct(private ItemMenu $plugin) {
		parent::__construct("reload", "Recarrega config.yml e items.yml", "", ["recarregar"], ItemMenuCommand::PERMISSION_RELOAD);
	}

	public function run(CommandSender $sender, Args $args) : void {
		$this->plugin->reload();
		$catalog = $this->plugin->getCatalog();
		$prefix = $this->plugin->getSettings()->prefix;
		$sender->sendMessage($prefix . "§aConfiguração recarregada: §f{$catalog->count()} §aitem(ns) de menu.");
		if($catalog->getIgnoredCount() > 0){
			$sender->sendMessage($prefix . "§c{$catalog->getIgnoredCount()} entrada(s) do items.yml ignorada(s) por erro. Veja os avisos no console.");
		}
	}
}
