<?php
namespace muqsit\invmenu\inventories;

use muqsit\invmenu\InvMenu;

use pocketmine\block\Block;
use pocketmine\inventory\BaseInventory;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\{CompoundTag, StringTag};
use pocketmine\network\mcpe\protocol\{BlockEntityDataPacket, ContainerClosePacket, ContainerOpenPacket};
use pocketmine\Player;

abstract class BaseFakeInventory extends BaseInventory {

    const SEND_BLOCKS_FAKE = 0;
    const SEND_BLOCKS_REAL = 1;

    const FAKE_BLOCK_ID = 0;
    const FAKE_BLOCK_DATA = 0;
    const FAKE_TILE_ID = "";

    const INVENTORY_HEIGHT = 3;

    /** @var \pocketmine\math\Vector3[] */
    private $holders = [];

    /** @var InvMenu */
    private $menu;

    /** @var BigEndianNBTStream|null */
    private static $nbtWriter;

    public function __construct(InvMenu $menu)
    {
        $this->menu = $menu;
        parent::__construct();
    }

    public function getMenu() : InvMenu
    {
        return $this->menu;
    }

    public function onOpen(Player $player) : void
    {
        parent::onOpen($player);

        $this->holders[$player->getId()] = $holder = $player->round()->add(0, self::INVENTORY_HEIGHT);
        $this->sendBlocks($player, self::SEND_BLOCKS_FAKE);

        $pk = new BlockEntityDataPacket();
        $pk->x = $holder->x;
        $pk->y = $holder->y;
        $pk->z = $holder->z;

        $writer = self::$nbtWriter ?? (self::$nbtWriter = new NetworkLittleEndianNBTStream());
        $nbt = new CompoundTag("", [
            new StringTag("id", static::FAKE_TILE_ID)
        ]);
        $customName = $this->menu->getName();
        if ($customName !== null) {
            $nbt->setString("CustomName", $customName);
        }
        $writer->setData($nbt);

        $pk->namedtag = $writer->write();
        $player->dataPacket($pk);

        $pk = new ContainerOpenPacket();
        $pk->windowId = $player->getWindowId($this);
        $pk->type = $this->getNetworkType();
        $pk->x = $holder->x;
        $pk->y = $holder->y;
        $pk->z = $holder->z;
        $player->dataPacket($pk);

        $this->sendContents($player);
    }

    public function onClose(Player $player) : void
    {
        $pk = new ContainerClosePacket();
        $pk->windowId = $player->getWindowId($this);
        $player->dataPacket($pk);

        if (isset($this->holders[$player->getId()])) {
            $this->sendBlocks($player, self::SEND_BLOCKS_REAL);
            unset($this->holders[$player->getId()]);
        }

        parent::onClose($player);
        $this->menu->onInventoryClose($player);
    }

    protected function sendBlocks(Player $player, int $type) : void
    {
        switch ($type) {
            case self::SEND_BLOCKS_FAKE:
                $holder = $this->holders[$player->getId()];
                $player->getLevel()->sendBlocks([$player], [Block::get(static::FAKE_BLOCK_ID, static::FAKE_BLOCK_DATA)->setComponents($holder->x, $holder->y, $holder->z)]);
                return;
            case self::SEND_BLOCKS_REAL:
                $holder = $this->holders[$player->getId()];
                $player->getLevel()->sendBlocks([$player], [$player->getLevel()->getBlockAt($holder->x, $holder->y, $holder->z)]);
                return;
        }

        throw new \Error("Unhandled type $type provided.");
    }
}