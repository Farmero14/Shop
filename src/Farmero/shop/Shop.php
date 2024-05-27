<?php

declare(strict_types=1);

namespace Farmero\shop;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

use Farmero\shop\Command\ShopCommand;
use Farmero\shop\Forms\ShopForm;

class Shop extends PluginBase {

    private $shopForm;

    public function onEnable(): void {
        $this->saveResource("shop.yml");
        $this->shopForm = new ShopForm($this);
        $this->getServer()->getCommandMap()->register("shop", new ShopCommand($this));
    }

    public function getShopForm(): ShopForm {
        return $this->shopForm;
    }
}
