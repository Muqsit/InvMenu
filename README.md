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

InvMenu supports creating a GUI out of any kind of inventory that can be created by extending it's `BaseFakeInventory` class.

**NOTE:** You'll need to allow InvMenu to handle inventory events (such as InventoryTransactionEvent) if you'd like `InvMenu::readonly()` and InvMenu listeners to work. For this, you can do the following when your plugin enables...
```php
if(!InvMenuHandler::isRegistered()){
	InvMenuHandler::register($this);
}
```

### Creating an InvMenu instance
`InvMenu::create($inventory_class)` creates a new instance of InvMenu. `$inventory_class` should be a path to an inventory class extending InvMenu's `BaseFakeInventory` class. InvMenu comes with 3 inventory classes by default: `ChestInventory`, `DoubleChestInventory` and `HopperInventory`. The path to these inventory classes can either be accessed by specifying the path to the inventory class or by the constants `InvMenu::TYPE_CHEST`, `InvMenu::TYPE_DOUBLE_CHEST` and `InvMenu::TYPE_HOPPER`.

```php
$menu = InvMenu::create(InvMenu::TYPE_CHEST);
//This is the same as InvMenu::create(\muqsit\invmenu\inventories\ChestInventory::class);
```

To access this menu's inventory, you can use:
```php
$inventory = $menu->getInventory();
```

The inventory instance extends pocketmine's `Inventory` class, so you can use all the fancy pocketmine inventory methods.
```php
$menu->getInventory()->setContents([
	Item::get(Item::DIAMOND_SWORD),
	Item::get(Item::DIAMOND_PICKAXE)
]);
$menu->getInventory()->addItem(Item::get(Item::DIAMOND_AXE));
```
To send the menu to a player, use:
```php
/** @var Player $player */
$menu->send($player);
```
Yup, that's it. It's that simple.

### Disallowing players from modifying the menu's contents
This can be an essential in cases where you'd want to use a menu as a UI. InvMenu comes with a method that disallows players to move items in and out of the menu. It's as simple as calling:
```php
$menu->readonly();
```
That's all you have to do to completely stop players from moving items in and out of your menu.

### Specifying a custom name to the menu
To set a custom name to a menu, use
```php
$menu->setName("Custom Name");
```
You can also specify a different inventory name for each player separately during `InvMenu::send()`.
```php
/** @var Player $player */
$menu->send($player, "Greetings, " . $player->getName());
```

### Handling menu item transactions
Inventory transactions are menu-based. To handle inventory transactions happening in a menu, you will need to specify a callable which will get called every time an item is either put in or taken out of the menu's inventory. You can do this using
```php
/** @var callable $listener */
$menu->setListener($listener);
```
What's **`$listener`**?
```php
/**
 * @param Player $player the player who tried modifying the inventory.
 *
 * @param Item $itemClicked the item that the player clicked / took out of the
 * menu.
 *
 * @param Item $itemClickedWith the item that the player clicked $itemClicked
 * using / put in the menu.
 *
 * @param SlotChangeAction $action the SlotChangeAction instance created during the
 * transaction. You can use this to fetch the inventory instance by
 * using $action->getInventory().
 *
 * @return bool whether to not cancel the item movement. If it returns
 * true, then the transaction is not cancelled. If it returns false,
 * the transaction is cancelled.
 *
 * NOTE: If the menu is set to readonly, the transaction will be
 * forcefully cancelled irrespective of this callable's return value.
 */
bool callback(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action);
```

### Inventory closing — Listening to inventory close triggers & How to!
To listen to inventory close triggers, you can specify the inventory close callable using
```php
/** @var callable $listener */
$menu->setInventoryCloseListener($listener);
```
What's **`$listener`**?
```php
/**
 * @param Player $player the player who closed the inventory.
 *
 * @param BaseFakeInventory $inventory the inventory instance closed by the player.
 */
void callback(Player $player, BaseFakeInventory $inventory);
```
To forcefully close or remove the menu from a player, you can use
```php
/** @var Player $player */
$player->removeWindow($menu->getInventory($player));
```
To forcefully close or remove the menu when a player modifies the inventory, you can use
```php
$menu->setListener(function(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action) : bool{
	$player->removeWindow($action->getInventory());
	return false;
});
```
### Sessionizing menu — "per player inventory"
InvMenu supports having a different inventory instance for each player. By default menu instances aren't sesionized, so all players whom you `$menu->send($player)` the menu to are accessing the same inventory. Either you can create a different InvMenu instance for each player or use the `sessionize` feature of InvMenu by calling
```php
$menu->sessionize();
```
What this does is it creates a different inventory instance for each player. You can access a player's inventory using:
```php
/** @var Player $player */
$inventory = $menu->getInventory($player);
```
**NOTE:** Inventory instances aren't persistent. They get destroyed as soon as the player closes the inventory or quits the server. If your plugin likes to make the inventory persist, you can listen to inventory close triggers and store the inventory contents somewhere and then set the inventory contents while sending the inventory to the player.

### Writing a custom inventory class
So let's say you'd like to send players a dispenser inventory. Sadly, InvMenu doesn't ship with a `InvMenu::TYPE_DISPENSER` or `DispenserInventory::class`. BUT that won't stop you from doing what you want to do! You can write your own DispenserInventory class and it should be valid as long as you specified the correct identifiers and it extends the `BaseFakeInventory` class. InvMenu consists of a `SingleBlockInventory` class which is a simplified version of the `BaseFakeInventory` class.
```php
<?php
namespace spacename;

use muqsit\invmenu\inventories\SingleBlockInventory;

use pocketmine\block\Block;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\tile\Tile;

class DispenserInventory extends SingleBlockInventory{

	public function getBlock() : Block{
		return Block::get(Block::DISPENSER);
	}

	public function getNetworkType() : int{
		return WindowTypes::DISPENSER;
	}

	public function getTileId() : string{
		return "Dispenser";
	}

	public function getName() : string{
		return "Dispenser";
	}

	public function getDefaultSize() : int{
		return 9;
	}
}
```
Sweet! Now you can create a dispenser menu using
```php
$menu = InvMenu::create(\spacename\DispenserInventory::class);
```

### InvMenu applications / examples
Read the [wiki](https://github.com/Muqsit/InvMenu/wiki/Examples) for examples on the different ways InvMenu can be used on servers.

