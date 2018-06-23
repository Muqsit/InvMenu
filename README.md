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

InvMenu supports 4 types of menus, but you can always create your own menu type. More on that later. Let's have a look at the 3 main types of menus that InvMenu offers:
```php
class InvMenu {
    const TYPE_CUSTOM = -1;
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

**BUT WAIT!** Players can move the items from the inventory. You can disable this by using `InvMenu::readonly()`. That will make sure no one can take out items from the fake inventory!
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

The function is called during `InventoryTransactionEvent` that `InvMenu` handles itself. The function **must** return a `bool` value.
If the function returns `false`, the `InventoryTransactionEvent` gets cancelled.

**NOTE:** If you have your menu set to readonly, then the return value of the function does not matter. `InventoryTransactionEvent` gets cancelled any way.


### Sessions
InvMenu by default doesn't create a new Inventory instance for every player. In fact, the SAME Inventory is sent to all the players that you `InvMenu::send()` the inventory to.
You may want to sessionize InvMenu for creating mechanisms like PlayerVaults where every player needs their own inventory.
You can use `InvMenu::sessionize()` for this.
```php
$menu->sessionize();
```
It is also important to note that sessionizing InvMenu *clones* the `Inventory` instance that InvMenu holds, which means if you create an InvMenu this way:
```php
$menu = InvMenu::create(InvMenu::TYPE_CHEST);
$menu->getInventory()->setContents([
    Item::get(Item::DIAMOND_SWORD),
    Item::get(Item::DIAMOND_PICKAXE)
]);
$menu->sessionize();
```
the player will be able to take away the diamond sword and diamond pickaxe but the items respawn the next time they request for viewing the InvMenu.
```php
$menu->send($player);//you can move the diamond sword and pickaxe to your inventory.

//** CLOSE INVENTORY **

$menu->send($player);//the diamond sword and diamond pickaxes are back!
```
InvMenu will delete the inventory instance when the inventory gets closed when InvMenu is sessionized. You can handle inventory closings too, just like handling inventory transactions.

```php
$menu->setInventoryCloseListener(function(Player $player, BaseFakeInventory $inventory) : void{
    $player->sendMessage(TextFormat::GRAY."You have closed ".$inventory->getName()." while it had ".count($inventory->getContents())." items in it!");
});
```

### Adding your own custom Inventory instance
You can specify your own Inventory instance by creating an Inventory class that extends `BaseFakeInventory`. Here's an example of creating a Brewing stand inventory even though it doesn't exist in the InvMenu code. You'll need to create an InvMenu instance with the type `InvMenu::TYPE_CUSTOM`. This type will NOT create an Inventory instance for the InvMenu so you are forced to specify an inventory using `InvMenu::setInventory()`.
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

### InvMenu application examples
Read the [wiki](https://github.com/Muqsit/InvMenu/wiki/Examples) for examples on the different ways InvMenu can be used on servers.

