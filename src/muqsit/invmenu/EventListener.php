<?php
namespace muqsit\invmenu;

use muqsit\invmenu\inventories\BaseFakeInventory;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\inventory\{PlayerCursorInventory, PlayerInventory};
use pocketmine\inventory\transaction\action\SlotChangeAction;

class EventListener implements Listener {

    public function onInventoryTransaction(InventoryTransactionEvent $event) : void
    {
        $tr = $event->getTransaction();

        $inventoryAction = null;
        $playerAction = null;
        $menu = null;

        foreach ($tr->getActions() as $action) {
            if ($action instanceof SlotChangeAction) {
                $inventory = $action->getInventory();
                if ($inventory instanceof BaseFakeInventory) {
                    $inventoryAction = $action;
                    $menu = $inventory->getMenu();
                    if ($menu->isReadonly()) {
                        $event->setCancelled();
                    }
                    if (!$menu->isListenable()) {
                        return;
                    }
                } elseif ($inventory instanceof PlayerInventory || $inventory instanceof PlayerCursorInventory) {
                    $playerAction = $action;
                }
            }
        }

        if (
            $inventoryAction !== null &&
            $playerAction !== null &&
            !$menu->getListener()(
                $tr->getSource(),
                $inventoryAction->getSourceItem(),
                $playerAction->getSourceItem(),
                $inventoryAction,
                $playerAction
            )
        ) {
            $event->setCancelled();
        }
    }
}