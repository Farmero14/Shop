<?php

declare(strict_types=1);

namespace Farmero\shop\Forms;

use pocketmine\player\Player;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;

use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;

use Farmero\moneysystem\MoneySystem;

use Farmero\shop\Shop;

class ShopForm {

    private $plugin;
    private $config;
    private $moneyManager;

    public function __construct(Shop $plugin) {
        $this->plugin = $plugin;
        $this->config = new Config($this->plugin->getDataFolder() . "shop.yml", Config::YAML);
    }

    public function sendShopUI(Player $player): void {
        $form = new SimpleForm(function (Player $player, ?int $data) {
            if ($data === null) {
                return;
            }
            $categories = $this->config->getAll();
            $selectedCategory = array_keys($categories)[$data];
            $selectedCategoryData = $categories[$selectedCategory];
            $categoryImage = isset($selectedCategoryData["image"]) ? $selectedCategoryData["image"] : "";
            $selectedItems = $selectedCategoryData["items"];
            $this->sendItemSelectionUI($player, $selectedCategory, $categoryImage, $selectedItems);
        });
        $form->setTitle("Shop");
        $form->setContent("Please select a category:");

        foreach ($this->config->getAll() as $category => $data) {
            $image = isset($data["image"]) ? $data["image"] : "";
            if ($image !== "") {
                $form->addButton($category, 0, $image);
            } else {
                $form->addButton($category);
            }
        }
        $player->sendForm($form);
    }

    private function sendItemSelectionUI(Player $player, string $category, string $categoryImage, array $items): void {
        $form = new SimpleForm(function (Player $player, ?int $data) use ($items) {
            if ($data === null) {
                return;
            }
            $selectedItem = $items[$data];
            $this->handleShopSelection($player, $selectedItem);
        });
        $form->setTitle("$category Category");
        $form->setContent("Select an item:");

        foreach ($items as $item) {
            $itemName = $item["name"];
            $itemImage = isset($item["item_image"]) ? $item["item_image"] : "";
            if ($itemImage !== "") {
                $form->addButton($itemName, 0, $itemImage);
            } else {
                $form->addButton($itemName);
            }
        }
        $player->sendForm($form);
    }

    private function handleShopSelection(Player $player, array $selectedItem): void {
        $itemName = $selectedItem["name"];
        $itemPrice = $selectedItem["price"];
        $itemId = $selectedItem["id"];
        $itemImage = isset($selectedItem["item_image"]) ? $selectedItem["item_image"] : "";

        if ($itemId === null) {
            $player->sendMessage("§l§c[!]§r§f Invalid item ID...");
            return;
        }

        $this->money = MoneySystem::getInstance()->getMoneyManager();
        $balance = $this->money->getMoney($player);
        if ($balance >= (int)$itemPrice) {
            $form = new CustomForm(function (Player $player, ?array $data) use ($itemName, $itemPrice, $itemId, $itemImage) {
                if ($data === null) {
                    return;
                }
                $amount = (int)$data[1];
                $totalPrice = $itemPrice * $amount;
                $this->sendConfirm($player, $itemId, $itemName, $amount, $totalPrice, $itemImage);
            });
            $form->setTitle("Purchase {$itemName}");
            $form->addLabel("Price:§e $" . $itemPrice . "§f per item");
            $form->addInput("Amount:", "enter an amount", "1");
            $player->sendForm($form);
        } else {
            $player->sendMessage("§l§e[!]§r§f You don't have enough money to purchase §e{$itemName}§f!");
        }
    }

    private function sendConfirm(Player $player, string $itemId, string $itemName, int $amount, int $totalPrice, string $itemImage = ""): void {
        try {
            $parsedItem = StringToItemParser::getInstance()->parse($itemId) ?? LegacyStringToItemParser::getInstance()->parse($itemId);
        } catch (\InvalidArgumentException $e) {
            $player->sendMessage("§l§c[!]§r§f Invalid item ID...");
            return;
        }

        $form = new SimpleForm(function (Player $player, ?int $data) use ($itemName, $amount, $totalPrice, $parsedItem) {
            if ($data === null) {
                return;
            }
            if ($data === 0) {
                $itemPrice = $totalPrice / $amount;
                $this->money = MoneySystem::getInstance()->getMoneyManager();
                $this->money->removeMoney($player, $totalPrice);
                $item = $parsedItem->setCount($amount);
                $player->getInventory()->addItem($item);
                $player->sendMessage("§l§a[!]§r§f You purchased §e{$amount} {$itemName}§f for §a$" . $totalPrice . "§f!");
            } else {
                $player->sendMessage("§l§e[!]§r§f Purchase canceled!");
            }
        });
        $form->setTitle("Confirm Purchase");
        $form->setContent("Are you sure you want to buy §e{$amount} {$itemName}§f for §a$" . $totalPrice . "§f?");
        $form->addButton("Yes");
        $form->addButton("No");
        $player->sendForm($form);
    }
}
