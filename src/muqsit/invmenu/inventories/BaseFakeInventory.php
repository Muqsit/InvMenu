<?php
namespace muqsit\invmenu\inventories;

use muqsit\invmenu\InvMenu;

use pocketmine\block\Block;
use pocketmine\inventory\BaseInventory;
use pocketmine\math\Vector3;
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

    /** @var Vector3[] */
    protected $holders = [];

    /** @var InvMenu */
    protected $menu;

    /** @var BigEndianNBTStream|null */
    protected static $nbtWriter;

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

        $this->sendFakeTile($player);

        $this->sendInventoryInterface($player);
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

    public function sendInventoryInterface(Player $player) : void
    {
        $holder = $this->holders[$player->getId()];

        $pk = new ContainerOpenPacket();
        $pk->windowId = $player->getWindowId($this);
        $pk->type = $this->getNetworkType();
        $pk->x = $holder->x;
        $pk->y = $holder->y;
        $pk->z = $holder->z;
        $player->dataPacket($pk);

        $this->sendContents($player);
    }

    protected function sendFakeTile(Player $player) : void
    {
        $holder = $this->holders[$player->getId()];

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
    }

    protected function sendBlocks(Player $player, int $type) : void
    {
        switch ($type) {
            case self::SEND_BLOCKS_FAKE:
                $player->getLevel()->sendBlocks([$player], $this->getFakeBlocks($this->holders[$player->getId()]));
                return;
            case self::SEND_BLOCKS_REAL:
                $player->getLevel()->sendBlocks([$player], $this->getRealBlocks($player, $this->holders[$player->getId()]));
                return;
        }

        throw new \Error("Unhandled type $type provided.");
    }

    protected function getFakeBlocks(Vector3 $holder) : array
    {
        return [
            Block::get(static::FAKE_BLOCK_ID, static::FAKE_BLOCK_DATA)->setComponents($holder->x, $holder->y, $holder->z)
        ];
    }

    protected function getRealBlocks(Player $player, Vector3 $holder) : array
    {
        return [
            $player->getLevel()->getBlockAt($holder->x, $holder->y, $holder->z)
        ];
    }
}