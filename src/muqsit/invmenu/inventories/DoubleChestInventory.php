<?php
namespace muqsit\invmenu\inventories;

class DoubleChestInventory extends ChestInventory {

    public function getName() : string
    {
        return "DoubleChestInventory";
    }

    public function getDefaultSize() : int
    {
        return 54;
    }
}