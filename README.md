# InvMenu
**InvMenu is a PocketMine-MP virion that eases creating and managing fake inventories!**
[![](https://poggit.pmmp.io/shield.state/InvMenu)](https://poggit.pmmp.io/p/InvMenu)

### Installation
You can get the compiled .phar file on poggit by clicking [here](https://poggit.pmmp.io/ci/Muqsit/InvMenu/~).

### Usage
You'll need to import the `muqsit\invmenu\InvMenu.php` class. This is the main class and probably the only class you'll need (for the most part) to create and manage fake inventories.
```php
<?php
use muqsit\invmenu\InvMenu;
```

InvMenu supports 3 types of menus, but you can always create your own menu type. More on that later. Below are the 3 types of InvMenu-provided menus.
```php
class InvMenu {
    const TYPE_CUSTOM = -1; //This is not a type of menu. You'll find out what this is used for, later
    const TYPE_CHEST = 0;
    const TYPE_HOPPER = 1;
    const TYPE_DOUBLE_CHEST = 2;
}
```

### Creating a GUI
`InvMenu::create()` creates a new instance of InvMenu. You can then use the instance to send an inventory to multiple players.

Here's an example on how to create a Chest GUI
```php
$menu = InvMenu::create(InvMenu::TYPE_CHEST);
```
Congratulations! Your fake inventory menu is now ready to be sent to players. But before that, let's fill the menu's inventory with some items.
```php
$menu->getInventory()->setContents([
    Item::get(Item::DIAMOND_SWORD),
    Item::get(Item::DIAMOND_PICKAXE)
]);
$menu->getInventory()->addItem(Item::get(Item::DIAMOND_AXE));
```
You can now send the fake inventory menu to a player using `InvMenu::send()`.
```php
/** @var Player $player */
$menu->send($player);
```
Yup, that's it. It's that simple.

**BUT, HOLD ON!** Players can move the items from the inventory. You can disable this by using `InvMenu::readonly()`. That will make sure no one can take out items from the fake inventory!
```php
$menu->readonly();
```

### Specifying a custom name to the GUI
You can specify a custom title to the inventory using `InvMenu::setName()`.
```php
$menu->setName("Custom GUI Name");
```

### Handling item transactions happening in the GUI
**WARNING!** If you have a plugin that listens to InventoryTransactionEvent at a superior priority than what InvMenu listens to on, it may cause unexpected behaviour. InvMenu listens to InventoryTransactionEvent and simplifies the event's output. Make sure the plugin is letting a way for other plugins to handle the event just as efficiently as without the plugin.

Before we begin, make sure to register the `InvMenuHandler` class on server startup from your plugin.
```php
/** @var PluginBase $plugin */
if(!InvMenuHandler::isRegistered()){
    InvMenuHandler::register($plugin);
}
```

You can handle Inventory Transactions by using PHP `callables`.

```php
$menu->setListener(function(Player $player, Item $itemClickedOn, Item $itemClickedWith) : bool{
    if($itemClickedOn->getId() === Item::DIAMOND_SWORD){
        $player->sendMessage(TextFormat::GREEN."You clicked on a diamond sword!");
    }
    return true;
});
```
**Callable parameters:**
- **Player `$player` -** *The player responsible for the inventory transaction.*
- **Item `$itemClickedOn` -** *The item that the player clicked in the GUI.*
- **Item `$itemClickedWith` -** *The item that the player put in the GUI. This can also be the item that the player clicked `$itemClickedOn` with as players are able to put and takeout items from an inventory in one go.*
- **SlotChangeAction `$inventoryAction` -** *The inventory-sided SlotChangeAction. You can get the Inventory instance and the inventory slot that was clicked using this.*
- **InventoryAction[] `$otherActions` -** *The InventoryActions caused outside the InvMenu inventory which affected the InvMenu inventory.*

It's not mandatory to specify each and every parameter in the `callable`. You are good to go even by specifying only the parameters you'll be using.

The callable is called during `InventoryTransactionEvent` that `InvMenu` handles by itself. The callable **must** return a `bool` value.
If the callable returns `false`, the `InventoryTransactionEvent` gets cancelled and the whole inventory transaction is reverted.

**NOTE:** If you have your menu set to readonly, then the return value of the function does not matter. `InventoryTransactionEvent` gets cancelled any way.


### Sessions
InvMenu by default doesn't create a new Inventory instance for every player. In fact, the SAME Inventory is sent to all the players that you `InvMenu::send()` the inventory to.
You may want to sessionize InvMenu for creating mechanisms like PlayerVaults where every player needs their own inventory instance.
You can use `InvMenu::sessionize()` for this.
```php
$menu->sessionize();
```
It is also important to note that sessionizing InvMenu *recreates* the `Inventory` instance that InvMenu holds, which means if you create an InvMenu this way:
```php
$menu = InvMenu::create(InvMenu::TYPE_CHEST);
$menu->getInventory()->setContents([
    Item::get(Item::DIAMOND_SWORD),
    Item::get(Item::DIAMOND_PICKAXE)
]);
$menu->sessionize();
```
the player will still be sent an empty inventory. 

It is also important to note that a player's "InvMenu session" lives only while the player is viewing the inventory. As soon as the player closes the inventory, the inventory instance is deleted (of course this doesn't affect InvMenu instances that aren't sessionized). If this is a problem, you can handle inventory closing, just like handling inventory transactions.

```php
$menu->setInventoryCloseListener(function(Player $player, BaseFakeInventory $inventory) : void{
    $player->sendMessage(TextFormat::GRAY."You have closed ".$inventory->getName()." while it had ".count($inventory->getContents())." items in it!");
});
```
**Callable parameters:**
- **Player `$player` -** *The player responsible for the inventory transaction.*
- **BaseFakeInventory `$inventory` -** *The inventory instance that the player closed.*

### Adding your own custom Inventory instance
You can specify your own Inventory instance by creating an Inventory class that extends `BaseFakeInventory`. Here's an example of creating a Brewing stand inventory menu even though InvMenu doesn't proovide a brewing stand menu by default. You'll need to create an InvMenu instance with the type `InvMenu::TYPE_CUSTOM` with the second parameter of `InvMenu::create()` being the path to the class that extends `BaseFakeInventory`.
```php
<?php
namespace spacename;

use muqsit\invmenu\inventories\BaseFakeInventory;

use pocketmine\block\BlockIds;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\tile\Tile;

class BrewingInventory extends BaseFakeInventory {

    const FAKE_BLOCK_ID = BlockIds::BREWING_STAND_BLOCK;
    const FAKE_TILE_ID = Tile::BREWING_STAND;

    public function getName() : string{
        return "BrewingInventory";
    }

    public function getDefaultSize() : int{
        return 4;
    }

    public function getNetworkType() : int{
        return WindowTypes::BREWING_STAND;
    }
}

$menu = InvMenu::create(InvMenu::TYPE_CUSTOM, BrewingInventory::class);
$menu->getInventory()->setContents([
    Item::get(Item::NETHER_WART),
    Item::get(Item::POTION),
    Item::get(Item::POTION),
    Item::get(Item::POTION)
]);
```

### Behaviour of cloning an InvMenu instance
Cloning an InvMenu instance will recreate the BaseFakeInventory instance that the InvMenu instance holds.
```php
/** @var InvMenu $menu */
$menu->getInventory()->clear(0);

$cloned = clone $menu;
$cloed->getInventory()->setItem(0, Item::get(Item::BOOK));

var_dump(
    $menu->getInventory()->getItem(0), //Item is Air
    $cloned->getInventory()->getItem(0), //Item is Book
);
```
Cloning may be helpful when you want to send the same InvMenu instance to multiple players but with different inventory names. For example:
```php
/** @var InvMenu $menu */
/** @var Player[] $players */
foreach($players as $player){
    $cloned = clone $menu;
    $cloned->setName($player->getName() . "'s GUI");
    $cloned->send($player);
}
```

### InvMenu applications / examples
Read the [wiki](https://github.com/Muqsit/InvMenu/wiki/Examples) for examples on the different ways InvMenu can be used on servers.

