<?php

declare(strict_types=1);

namespace Unknown\ItemMenu\command;

use pocketmine\command\CommandSender;
use Unknown\Forja\command\Args;
use Unknown\Forja\command\CommandFeedback;
use Unknown\Forja\command\SubCommand;
use Unknown\ItemMenu\ItemMenu;
use function count;
use function implode;
use function mb_strtolower;
use function trim;

final class GiveSubCommand extends SubCommand {

	public function __construct(private ItemMenu $plugin) {
		parent::__construct(
			"give",
			"Entrega um item de menu (sem id: todos os itens fixos)",
			"<jogador> [id]",
			["dar"],
			ItemMenuCommand::PERMISSION_GIVE
		);
	}

	public function run(CommandSender $sender, Args $args) : void {
		$player = $args->player(0);
		$catalog = $this->plugin->getCatalog();
		$prefix = $this->plugin->getSettings()->prefix;

		if(!$player->isAlive()){
			throw new CommandFeedback($prefix . "§c{$player->getName()} está morto. Os itens fixos serão entregues ao renascer.");
		}

		if(!$args->has(1)){
			if(count($catalog->fixedItems()) === 0){
				throw new CommandFeedback($prefix . "§cNão há itens fixos configurados. Informe o id de um item.");
			}
			$this->plugin->refreshPlayer($player);
			$sender->sendMessage($prefix . "§aItens fixos entregues a §f{$player->getName()}§a.");
			return;
		}

		$id = mb_strtolower(trim($args->string(1)), "UTF-8");
		$item = $catalog->get($id);
		if($item === null){
			$ids = $catalog->ids();
			throw new CommandFeedback($prefix . "§cItem desconhecido: §f{$id}§c. " . (count($ids) > 0 ? "Itens: §f" . implode(", ", $ids) : "Nenhum item configurado."));
		}
		$this->plugin->giveItem($player, $item);
		$sender->sendMessage($prefix . "§aItem §f$id §aentregue a §f{$player->getName()}§a.");
	}
}
