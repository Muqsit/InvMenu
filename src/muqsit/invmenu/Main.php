<?php
namespace muqsit\invmenu;

use pocketmine\plugin\PluginBase;

class Main extends PluginBase {

    public function onEnable() : void
    {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }
}