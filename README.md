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
`InvMenu::create($identifier)` creates a new instance of InvMenu. `$identifier` should be an identifier of a registered `MenuMedata` object. InvMenu comes with 3 pre-registered `MenuMetadata` identifiers by default: `InvMenu::TYPE_CHEST`, `InvMenu::TYPE_DOUBLE_CHEST` and `InvMenu::TYPE_HOPPER`.

```php
$menu = InvMenu::create(InvMenu::TYPE_CHEST);
```

To access this menu's inventory, you can use:
```php
$inventory = $menu->getInventory();
```

The `$inventory` extends pocketmine's `Inventory` class, so you can use all the fancy pocketmine inventory methods.
```php
$menu->getInventory()->setContents([
	Item::get(Item::DIAMOND_SWORD),
	Item::get(Item::DIAMOND_PICKAXE)
]);
$menu->getInventory()->addItem(Item::get(Item::DIAMOND_AXE));
$menu->getInventory()->setItem(3, Item::get(Item::GOLD_INGOT));
```
To send the menu to a player, use:
```php
/** @var Player $player */
$menu->send($player);
```
Yup, that's it. It's that simple.

### Disallowing players from modifying the menu's contents
This is useful in cases where you'd want to use an inventory as a UI. You can disallows players from modifying the inventory by setting the `InvMenu` instance as read-only. This only disallows players. The inventory contents can still be modified thru code.
```php
$menu->readonly();
```

### Specifying a custom name to the menu
To set a custom name to a menu, use
```php
$menu->setName("Custom Name");
```
You can also specify a different menu name for each player separately during `InvMenu::send()`.
```php
/** @var Player $player */
$menu->send($player, "Greetings, " . $player->getName());
```

### Handling menu item transactions
To handle inventory transactions happening in a menu, you will need to specify a callable which will get called every time an item is either put in or taken out of the menu's inventory. You can do this using
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
 * Should return void if InvMenu is readonly, bool otherwise. Return false if you
 * want to cancel the inventory change from occuring.
 * @return bool|void
 */
bool|void callback(Player $player, Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action);
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
 * @param InvMenuInventory $inventory the inventory instance closed by the player.
 */
void callback(Player $player, InvMenuInventory $inventory);
```
To forcefully close or remove the menu from a player, you can use
```php
/** @var Player $player */
$player->removeCurrentWindow();
```
### Sessionizing menu — "per player inventory"
The "sessionize" feature allows you to create one `InvMenu` instance but send a different inventory instance to each player. You can create a sessionized InvMenu instance using
```php
$menu = InvMenu::createSessionized(InvMenu::TYPE_CHEST);
```
The difference between `InvMenu::create()` and `InvMenu::createSessionized()` is that sessionized InvMenu instances create a separate inventory for each player while unsessionized InvMenu instances send the same inventory to each player. In sessionized InvMenu instances, each player has their own separate inventory which is undisturbed by others who are viewing the same InvMenu.
To access a player's inventory in a sessionized InvMenu, use:
```php
/** @var Player $player */
$inventory = $menu->getInventory($player);
```
**NOTE:** Inventory instances aren't persistent. They get destroyed as soon as the player closes the inventory or quits the server. If you want inventory contents to persist, you may listen to inventory close triggers and store the inventory contents.

### Writing a custom inventory class
So let's say you'd like to send players a dispenser inventory. Sadly, InvMenu doesn't ship with a `InvMenu::TYPE_DISPENSER`. You can still create a dispenser InvMenu by registering a `MenuMetadata` object with the information about what a dispenser inventory looks like.
```php
public const TYPE_DISPENSER = "myplugin:dispenser";

public function registerCustomMenuTypes() : void{
	$type = new SingleBlockMenuMetadata(
		self::TYPE_DISPENSER, // identifier
		9, // number of slots
		WindowTypes::DISPENSER, // mcpe window type id
		BlockFactory::get(Block::DISPENSER), // Block
		"Dispenser" // block entity identifier
	);
	InvMenuHandler::registerMenuType($menu);
}

$this->registerCustomMenuTypes();
```
Sweet! Now you can create a dispenser menu using
```php
$menu = InvMenu::create(self::TYPE_DISPENSER);
```

### InvMenu applications / examples
Read the [wiki](https://github.com/Muqsit/InvMenu/wiki/Examples) for examples on the different ways InvMenu can be used on servers.

