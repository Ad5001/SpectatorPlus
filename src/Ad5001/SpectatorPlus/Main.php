<?php
namespace Ad5001\SpectatorPlus;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\Server;
use pocketmine\Player;


class Main extends PluginBase implements Listener{


   public function onEnable(){
        $this->saveDefaultConfig();
        $this->reloadConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new setGamemodeTask($this), 5);
        $this->players = [];
        $this->lastPlayer = null;
    }
    
    
    public function test(Player $p, int $id) {
        $this->getLogger()->info("Item : " . $id . ". Is spectator : " . $this->isSpectator($p));
        if($this->isSpectator($p) and $id == 345) {
            // if($event->getPacket() instanceof \pocketmine\network\protocol\UseItemPacket) {
                        $founds = [];
                        foreach($p->getLevel()->getPlayers() as $player) {
                            if(!$this->isSpectator($player)) {
                                array_push($founds, $player);
                                if(!in_array($player, $this->players[$p->getName()]) and !isset($found)) {
                                    array_push($this->players[$p->getName()], $player);
                                    $found = true;
                                    $event->getPlayer()->teleport(new Vector3($player->x, $player->y, $player->z));
                                    $event->getPlayer()->sendTip(str_ireplace("{to}", $player->getName(), str_ireplace("{player}", $p->getName(), str_ireplace("{count}", count($founds), $this->getConfig()->get("TeleportMessage")))));
                                }
                            }
                        }
                        if(!isset($found)) {
                            $this->players[$p->getName()] = [];
                        }
                        foreach($p->getLevel()->getPlayers() as $player) {
                            if(!$this->isSpectator($player)) {
                                array_push($founds, $player);
                                if(!in_array($player, $this->players[$p->getName()]) and !isset($found)) {
                                    array_push($this->players[$p->getName()], $player);
                                    $found = true;
                                    $p->teleport(new Vector3($player->x, $player->y, $player->z));
                                    $event->getPlayer()->sendTip(str_ireplace("{to}", $player->getName(), str_ireplace("{player}", $p->getName(), str_ireplace("{count}", count($founds), $this->getConfig()->get("TeleportMessage")))));
                                }
                            }
                        }
            // }
        } elseif($this->isSpectator($p) and $id == 355) {
            // if($event->getPacket() instanceof \pocketmine\network\protocol\UseItemPacket) {
                $p->getInventory()->clearAll();
                $p->teleport($this->getServer()->getLevelByName($this->getConfig()->get("LobbyWorld"))->getSpawnLocation());
                $p->sendTip(str_ireplace("{lobby}", $this->getConfig()->get("LobbyWorld"), str_ireplace("{player}", $p->getName(), $this->getConfig()->get("LobbyMessage"))));
            // }
        }
    }
    
    public function onInteract(PlayerInteractEvent $event) {
        $this->test($event->getPlayer(), $event->getPlayer()->getInventory()->getItemInHand()->getId());
    }
    
    public function onBlockPlace(BlockPlaceEvent $event) {
        $this->test($event->getPlayer(), $event->getBlock()->getId());
        if($this->isSpectator($event->getPlayer())) {
            $event->setCancelled();
        }
    }
    
    public function onBlockBreak(BlockBreakEvent $event) {
        $this->test($event->getPlayer(), $event->getPlayer()->getInventory()->getItemInHand()->getId());
        if($this->isSpectator($event->getPlayer())) {
            $event->setCancelled();
        }
    }
    
    public function onEntityDamage(EntityDamageEvent $event) {
        if($event->getEntity() instanceof Player) {
            if($this->isSpectator($event->getEntity())) {
                $event->setCancelled();
            }
        }
        if($event instanceof \pocketmine\event\entity\EntityDamageByEntityEvent) {
            if($event->getDamager() instanceof Player) {
                if($this->isSpectator($event->getDamager())) {
                    $event->setCancelled();
                    $this->test($event->getDamager(), $event->getDamager()->getInventory()->getItemInHand()->getId());
                }
            }
        }
    }
    
    public function onPlayerChat(PlayerChatEvent $event) {
        if($this->getConfig()->get("PrivateSpecChat") == "true") {
            foreach($event->getPlayer()->getLevel()->getPlayers() as $p) {
                if($this->isSpectator($p)) {
                    $p->sendMessage(\pocketmine\utils\TextFormat::GRAY . "[SPEC] " . $event->getPlayer()->getName() . " > " . $event->getMessage());
                }
            }
            $event->setCancelled();
        }
    }
    
    
    public function onPlayerGameModeChange(PlayerGameModeChangeEvent $event) {
        if($event->getNewGamemode() == 3) { // Testing if spectator.
            $player = $event->getPlayer();
            $this->players[$player->getName()] = [];
            $this->lastPlayer = $player;
        } elseif($this->lastPlayer !== $event->getPlayer() and isset($this->players[$event->getPlayer()->getName()])) {
            unset($this->players[$event->getPlayer()->getName()]);
            $event->getPlayer()->setAllowFlight(false);
            $event->getPlayer()->getInventory()->clearAll();
            $this->lastPlayer = null;
        }
    }
    
    
   
    public function isSpectator(Player $player) {
        return isset($this->players[$player->getName()]);
    }
    
    
    
            $this->players[$player->getName()] = [];
    public function onCommand(CommandSender $sender, Command $cmd, $label, array $args){
        switch($cmd->getName()){
            case "default":
            break;
        }
     return false;
    }
}


class setGamemodeTask extends \pocketmine\scheduler\PluginTask {
    
    public function __construct(Main $main) {
        parent::__construct($main);
        $this->main = $main;
    }
    
    
    public function onRun($tick) {
        foreach($this->main->players as $playername => $tped) {
            $player = $this->main->getServer()->getPlayer($playername);
            // $this->main->getLogger()->info($player->getGamemode() . "/" . $player->isSpectator());
            if($player->isSpectator()) {
            $player->setGamemode(0);
            $compass = Item::get(345, 0, 1);
            $compass->setNamedTag(NBT::parseJSON('{display:{Name:"§r' . $this->main->getConfig()->get("TPCompassName") . '"}}'));
            $player->getInventory()->addItem($compass);
            $compass = Item::get(Item::BED, 0, 1);
            $compass->setNamedTag(NBT::parseJSON('{display:{Name:"§r' . $this->main->getConfig()->get("BedBackName") . '"}}'));
            $player->getInventory()->addItem($compass);
            foreach($player->getLevel()->getPlayers() as $p) {
                if(!$this->main->isSpectator($p)) {
                    $p->hidePlayer($player);
                }
            }
            $player->setAllowFlight(true);
            }
        }
    }
}