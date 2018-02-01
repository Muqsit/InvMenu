# InvMenu
InvMenu is a PocketMine-MP plugin for developers that helps you create and manage fake inventories with ease!


### Installation
You can get the compiled .phar file on poggit by clicking here.

### Usage
You'll need to import the `muqsit\invmenu\InvMenu.php` class. This is the main class and probably the only class you'll need (for the most part) to create and manage fake inventories.
```php
<?php
use muqsit\invmenu\InvMenu;
```

InvMenu supports 3 types of menus, but you can always create your own menu type. More on that later. Let's have a look at the 3 main types of menus that InvMenu offers:
```php
class InvMenu {
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

### Handling item transactions happening in the GUI
Another thing `InvMenu` offers is simplified inventory transaction handling. Let's see whatever that means!
There's no way you haven't come across interactive GUIs in Minecraft. You can handle Inventory Transactions by using PHP `callables`.
```php
$menu->setLisener(function(Player $player, Item $itemClickedOn, Item $itemClickedWith) : bool{
    if($itemClickedOn->getId() === Item::DIAMOND_SWORD){
        $player->sendMessage(TextFormat::GREEN."You clicked on a diamond sword!");
    }
    return true;
});
```
Callable parameters:
- Player `$player` - The player responsible for the inventory transaction.
- Item `$itemClickedOn` - The item that the player clicked in the GUI.
- Item `$itemClickedWith` - The item that the player put in the GUI. This can also be the item that the player clicked `$itemClickedOn` with as players are able to put and takeout items from an inventory in one go.

The function is called during `InventoryTransactionEvent` that `InvMenu` handles itself. The function **must** return a `bool` value.
If the function returns `false`, the `InventoryTransactionEvent` gets cancelled.
**NOTE:** If you have your menu set to readonly, then the return value of the function does not matter. `InventoryTransactionEvent` gets cancelled any way.


### Sessions
Now, yeah. InvMenu by default doesn't create a new Inventory instance for every player. In fact, the SAME Inventory is sent to all the players that you `InvMenu::send()` the inventory to.
You may want to sessionize InvMenu for creating mechanisms like `PlayerVaults` where every player needs their own inventory.
You can use `InvMenu::sessionize()` to for this.
```php
$menu->sessionize();
```
It is also important to note that the sessionizing InvMenu `clones` the `Inventory` instance that `InvMenu` holds, which means if you create an InvMenu this way:
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
InvMenu will delete the inventory instance when the inventory gets closed. You can handle inventory closings too, just like handling inventory transactions.

```php
$menu->setInventoryCloseListener(function(Player $player, BaseFakeInventory $inventory) : void{
    $player->sendMessage(TextFormat::GRAY."You have closed ".$inventory->getName()." while it had ".count($inventory->getContents())." items in it!");
});
```

### InvMenu application examples
Server-selector GUI
```php
class A {

    /** @var InvMenu */
    private $menu;

    public function __construct(){
        $this->menu = InvMenu::create(InvMenu::TYPE_CHEST)
            ->readonly()
            ->setListener([$this, "onServerSelectorTransaction"])//you can call class functions this way
            ->onInventoryClose(function(Player $player) : void{
                $player->sendMessage(TextFormat::GREEN."You are being transferred...");
            });
        $server1 = Item::get(Item::DIAMOND_SWORD)->setCustomName("Join Server1");
        $server1->getNamedTag()->setByte("Server", "server1.invmenu.test:19132");
        $this->menu->getInventory()->addItem($server1);
    }

    public function onServerSelectorTransaction(Player $player, Item $itemClickedOn) : bool{
        [$ip, $port] = explode(":", $itemClickedOn->getNamedTag()->getString("Server", "play.withinvmenu.plugin:19132"));
        $player->transfer($ip, $port);
        return true;
    }

    public function onCommand(CommandSender $issuer, Command $cmd, string $label, array $args) : bool{
        $this->menu->send($issuer);
        return true;
    }
}
```

