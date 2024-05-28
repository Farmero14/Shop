<?php

declare(strict_types=1);

namespace Farmero\shop;

use pocketmine\plugin\PluginBase;
use pocketmine\Server;

use Farmero\shop\Command\ShopCommand;
use Farmero\shop\Forms\ShopForm;

class Shop extends PluginBase {

    private $shopForm;

    public function onEnable(): void {
        $this->saveResource("shop.yml");
        $this->shopForm = new ShopForm($this);
        $this->getServer()->getCommandMap()->register("shop", new ShopCommand($this));
        if (Server::getInstance()->getPluginManager()->getPlugin("MoneySystem") === null) {
            $this->getLogger()->info("Disabling Shop, MoneySystem not found... Please make sure to have it installed before trying again!");
            Server::getInstance()->getPluginManager()->disablePlugin($this);
            return;
        }
    }

    public function getShopForm(): ShopForm {
        return $this->shopForm;
    }
}
