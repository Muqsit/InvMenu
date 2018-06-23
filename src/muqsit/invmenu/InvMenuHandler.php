<?php

/*
 *  ___            __  __
 * |_ _|_ ____   _|  \/  | ___ _ __  _   _
 *  | || '_ \ \ / / |\/| |/ _ \ '_ \| | | |
 *  | || | | \ V /| |  | |  __/ | | | |_| |
 * |___|_| |_|\_/ |_|  |_|\___|_| |_|\__,_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author Muqsit
 * @link http://github.com/Muqsit
 *
*/

namespace muqsit\invmenu;

use muqsit\invmenu\inventories\BaseFakeInventory;

use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\plugin\PluginBase;

class InvMenuHandler implements Listener {

    /** @var PluginBase */
    private static $registrant;

    private function __construct()
    {
    }

    public static function getRegistrant() : ?PluginBase
    {
        return self::$registrant;
    }

    public static function register(PluginBase $plugin) : void
    {
        if (self::isRegistered()) {
            throw new \Error("EventHandler is already registered by plugin '" . self::$registrant->getName() . "'");
        }

        self::$registrant = $plugin;
        $plugin->getServer()->getPluginManager()->registerEvents(new InvMenuHandler(), $plugin);
        $plugin->getLogger()->info("Registered InvMenuHandler");
    }

    public static function isRegistered() : bool
    {
        return self::$registrant !== null;
    }

    public function onInventoryTransaction(InventoryTransactionEvent $event) : void
    {
        $tr = $event->getTransaction();

        $inventoryActions = [];
        $otherActions = [];

        $menu = null;

        foreach ($tr->getActions() as $action) {
            if ($action instanceof SlotChangeAction) {
                $inventory = $action->getInventory();
                if ($inventory instanceof BaseFakeInventory) {
                    $inventoryActions[] = $action;

                    $menu = $inventory->getMenu();
                    if ($menu->isReadonly()) {
                        $event->setCancelled();
                    }

                    if (!$menu->isListenable()) {
                        return;
                    }

                    continue;
                }
            }

            $otherActions[] = $action;
        }

        if (
            $menu !== null &&
            !empty($inventoryActions)
        ) {
            $listener = $menu->getListener();
            foreach ($inventoryActions as $inventoryAction) {
                if (!$listener(
                    $tr->getSource(),
                    $inventoryAction->getSourceItem(),
                    $inventoryAction->getTargetItem(),
                    $inventoryAction,
                    $otherActions
                )) {
                    $event->setCancelled();
                    return;
                }
            }
        }
    }
}
