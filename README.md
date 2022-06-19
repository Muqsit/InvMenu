# InvMenu
**InvMenu is a PocketMine-MP virion that eases creating and managing fake inventories!**
[![](https://poggit.pmmp.io/shield.state/InvMenu)](https://poggit.pmmp.io/p/InvMenu)

## Installation
You can get the compiled .phar file on poggit by clicking [here](https://poggit.pmmp.io/ci/Muqsit/InvMenu/~).

## Usage
InvMenu supports creating a GUI out of any kind of `Inventory`.

**NOTE:** You MUST register `InvMenuHandler` during plugin enable before you can begin creating `InvMenu` instances.
```php
if(!InvMenuHandler::isRegistered()){
	InvMenuHandler::register($this);
}
```

## Creating an InvMenu instance
`InvMenu::create($identifier)` creates a new instance of InvMenu. `$identifier` must be an identifier of a registered `InvMenuType` object. InvMenu comes with 3 pre-registered `InvMenuType` identifiers: `InvMenu::TYPE_CHEST`, `InvMenu::TYPE_DOUBLE_CHEST` and `InvMenu::TYPE_HOPPER`.

```php
$menu = InvMenu::create(InvMenu::TYPE_CHEST);
```

To access this menu's inventory, you can use:
```php
$inventory = $menu->getInventory();
```

The `$inventory` implements pocketmine's `Inventory` interface, so you can access all the fancy pocketmine inventory methods.
```php
$menu->getInventory()->setContents([
	VanillaItems::DIAMOND_SWORD(),
	VanillaItems::DIAMOND_PICKAXE()
]);
$menu->getInventory()->addItem(VanillaItems::DIAMOND_AXE());
$menu->getInventory()->setItem(3, VanillaItems::GOLD_INGOT());
```
To send the menu to a player, use:
```php
/** @var Player $player */
$menu->send($player);
```
Yup, that's it. It's that simple.

## Specifying a custom name to the menu
To set a custom name to a menu, use
```php
$menu->setName("Custom Name");
```
You can also specify a different menu name for each player separately during `InvMenu::send()`.
```php
/** @var Player $player */
$menu->send($player, "Greetings, " . $player->getName());
```

## Verifying whether the menu was sent to the player
Not a common occurrence but it's possible for plugins to disallow players from opening inventories.
This can also occur as an attempt to drop garbage `InvMenu::send()` requests (if you send two menus simultaneously without any delay in betweeen, the first menu request may be regarded as garbage).
```php
/** @var string|null $name */
$menu->send($player, $name, function(bool $sent) : void{
	if($sent){
		// do something
	}
});
```

## Handling menu item transactions
To handle item transactions happening to and from the menu's inventory, you may specify a `Closure` handler that gets triggered by `InvMenu` every time a transaction occurs. You may allow, cancel and do other things within this handler. To register a transaction handler to a menu, use:
```php
/** @var Closure $listener */
$menu->setListener($listener);
```
What's **`$listener`**?
```php
/**
 * @param InvMenuTransaction $transaction
 *
 * Must return an InvMenuTransactionResult instance.
 * Return $transaction->continue() to continue the transaction.
 * Return $transaction->discard() to cancel the transaction.
 * @return InvMenuTransactionResult
 */
Closure(InvMenuTransaction $transaction) : InvMenuTransactionResult;
```
`InvMenuTransaction` holds all the item transction data.<br>
`InvMenuTransaction::getPlayer()` returns the `Player` that triggered the transaction.<br>
`InvMenuTransaction::getItemClicked()` returns the `Item` the player clicked in the menu.<br>
`InvMenuTransaction::getItemClickedWith()` returns the `Item` the player had in their hand when clicking an item.<br>
`InvMenuTransaction::getAction()` returns a `SlotChangeAction` instance, to get the slot index of the item clicked from the menu's inventory.<br>
`InvMenuTransaction::getTransaction()` returns the complete `InventoryTransaction` instance.<br>
```php
$menu->setListener(function(InvMenuTransaction $transaction) : InvMenuTransactionResult{
	$player = $transaction->getPlayer();
	$itemClicked = $transaction->getItemClicked();
	$itemClickedWith = $transaction->getItemClickedWith();
	$action = $transaction->getAction();
	$invTransaction = $transaction->getTransaction();
	return $transaction->continue();
});
```
A handler that doesn't allow players to take out apples from the menu's inventory:
```php
$menu->setListener(function(InvMenuTransaction $transaction) : InvMenuTransactionResult{
	if($transaction->getItemClicked()->getId() === ItemIds::APPLE){
		$player->sendMessage("You cannot take apples out of that inventory.");
		return $transaction->discard();
	}
	return $transaction->continue();
});
```

### Preventing inventory from being changed by players
There are two ways you can go with to prevent players from modifying the inventory contents of a menu.
#### Method #1: Calling `InvMenuTransaction::discard()`
```php
$menu->setListener(function(InvMenuTransaction $transaction) : InvMenuTransactionResult{
	// do something
	return $transaction->discard();
});
```
#### Method #2: Using `InvMenu::readonly()`
```php
$menu->setListener(InvMenu::readonly());
```
```php
$menu->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) : void{
	// do something
}));
```
Based on your use-case, you may find one better than the other. While `Method #1` gives you full control over a transaction (you can conditionally cancel a transaction, f.e based on whether player has permission, or player is in a specific area etc), `Method #2` reduces boilerplate `InvMenuTransactionResult` imports and calls to `InvMenutransaction::discard()`.

## Executing a task post-transaction
A few actions are impossible to be done at the time a player is viewing an inventory, such as sending a form — a player won't be able to view a form while viewing an inventory. To do this, you will need to close the menu inventory and make sure they've closed it by waiting for a response from their side. You can do this by supplying a callback to `InvMenuTransactionResult::then()`.
```php
$menu->setListener(function(InvMenuTransaction $transaction) : InvMenuTransactionResult{
	$transaction->getPlayer()->removeCurrentWindow();
	return $transaction->discard()->then(function(Player $player) : void{ // $player === $transaction->getPlayer()
		// assert($player->isOnline());
		$player->sendForm(new Form());
	});
});
```
```php
$menu->setListener(InvMenu::readonly(function(DeterministicInvMenuTransaction $transaction) : void{
	$transaction->getPlayer()->removeCurrentWindow();
	$transaction->then(function(Player $player) : void{
		$player->sendForm(new Form());
	});
}));
```

## Listening players closing or no longer viewing the inventory
To listen inventory close triggers, specify the inventory close Closure using:
```php
/** @var Closure $listener */
$menu->setInventoryCloseListener($listener);
```
What's **`$listener`**?
```php
/**
 * @param Player $player the player who closed the inventory.
 *
 * @param Inventory $inventory the inventory instance closed by the player.
 */
Closure(Player $player, Inventory $inventory) : void;
```
To forcefully close or remove the menu from a player:
```php
/** @var Player $player */
$player->removeCurrentWindow();
```

## Registering a custom InvMenu type
So let's say you'd like to send players a dispenser inventory. While InvMenu doesn't ship with a `InvMenu::TYPE_DISPENSER`, you can still create a dispenser InvMenu by registering an `InvMenuType` object with the information about what a dispenser inventory looks like.
```php
public const TYPE_DISPENSER = "myplugin:dispenser";

protected function onEnable() : void{
	InvMenuHandler::getTypeRegistry()->register(self::TYPE_DISPENSER, InvMenuTypeBuilders::BLOCK_ACTOR_FIXED()
		->setBlock(BlockFactory::getInstance()->get(BlockLegacyIds::DISPENSER, 0))
		->setBlockActorId("Dispenser")
		->setSize(9)
		->setNetworkWindowType(WindowTypes::DISPENSER)
	->build());
}
```
Sweet! Now you can create a dispenser menu using
```php
$menu = InvMenu::create(self::TYPE_DISPENSER);
```

## InvMenu Wiki
Applications, examples, tutorials and featured projects using InvMenu can be found on the [InvMenu Wiki](https://github.com/Muqsit/InvMenu/wiki/InvMenu-v4.0).

