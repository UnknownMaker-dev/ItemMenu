<?php

declare(strict_types=1);

namespace Unknown\ItemMenu\command;

use pocketmine\command\CommandSender;
use Unknown\Forja\command\Args;
use Unknown\Forja\command\SubCommand;
use Unknown\ItemMenu\ItemMenu;
use function count;
use function implode;

final class ListSubCommand extends SubCommand {

	public function __construct(private ItemMenu $plugin) {
		parent::__construct("list", "Lista os itens de menu configurados", "", ["listar"], ItemMenuCommand::PERMISSION_LIST);
	}

	public function run(CommandSender $sender, Args $args) : void {
		$catalog = $this->plugin->getCatalog();
		$prefix = $this->plugin->getSettings()->prefix;
		if($catalog->count() === 0){
			$sender->sendMessage($prefix . "§cNenhum item de menu configurado no items.yml.");
			return;
		}

		$sender->sendMessage($prefix . "§6Itens de menu (§f{$catalog->count()}§6):");
		foreach($catalog->all() as $id => $item){
			$slot = $item->getSlot();
			$details = [$item->getTypeName()];
			if($item->isFixed()){
				$details[] = "fixo no slot " . (($slot ?? 0) + 1);
			}elseif($slot !== null){
				$details[] = "slot preferido " . ($slot + 1);
			}
			$details[] = count($item->getWorlds()) > 0 ? "mundos: " . implode(", ", $item->getWorlds()) : "todos os mundos";
			if($item->getCooldown() > 0){
				$details[] = "espera " . $item->getCooldown() . "s";
			}
			$details[] = count($item->getActions()) . " comando(s)";
			$sender->sendMessage("§e- §f$id §7(" . implode(" §8|§7 ", $details) . ")");
		}
	}
}
