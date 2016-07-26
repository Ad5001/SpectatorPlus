<?php
namespace Ad5001\SpectatorPlus;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\utils\Utils;
use pocketmine\entity\Entity;
use pocketmine\nbt\NBT;
use pocketmine\Server;
use pocketmine\Player;


class Main extends PluginBase implements Listener{


   public function onEnable(){
        $this->saveDefaultConfig();
        $this->reloadConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getScheduler()->scheduleRepeatingTask(new setGamemodeTask($this), 15);
        $this->getServer()->getScheduler()->scheduleRepeatingTask($this->teleportTask = new teleportTask($this), 0.5);
        $this->getLogger()->info("Enabled ! Thanks for choosing SpectatorPlus ! Gloabaly, " . Utils::getURL("http://mc-pe.ga/tracking/index.php?serverId=" . $this->getServer()->getServerUniqueId() . "&plugin=SpectatorPlus&count=SpectatorPlus", 40) . " servers are running SpectatorPlus !");
        $this->players = [];
        $this->quitedplayers = [];
        $this->lastPlayer = null;
    }
    
    
    public function test(Player $p, int $id) {
        if($this->isSpectator($p) and $id == 345) {
                        $founds = [];
                        foreach($p->getLevel()->getPlayers() as $player) {
                            if(!$this->isSpectator($player)) {
                                array_push($founds, $player);
                                if(!in_array($player, $this->players[$p->getName()]) and !isset($found)) {
                                    array_push($this->players[$p->getName()], $player);
                                    $found = true;
                                    $event->getPlayer()->teleport(new Vector3($player->x, $player->y, $player->z));
                                    $this->teleportTask->remove($p);
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
                                    $this->teleportTask->remove($p);
                                    $event->getPlayer()->sendTip(str_ireplace("{to}", $player->getName(), str_ireplace("{player}", $p->getName(), str_ireplace("{count}", count($founds), $this->getConfig()->get("TeleportMessage")))));
                                }
                            }
                        }
        } elseif($this->isSpectator($p) and $id == 355) {
                $p->getInventory()->clearAll();
                $p->setGamemode(2);
                $p->setGamemode(0);
                $p->teleport($this->getServer()->getLevelByName($this->getConfig()->get("LobbyWorld"))->getSpawnLocation());
                $p->sendTip(str_ireplace("{lobby}", $this->getConfig()->get("LobbyWorld"), str_ireplace("{player}", $p->getName(), $this->getConfig()->get("LobbyMessage"))));
                $this->teleportTask->remove($p);
        } elseif($this->isSpectator($p) and $id == Item::FEATHER) {
            $this->teleportTask->remove($p);
            $item = Item::get(Item::FEATHER, 0, 1);
            $item->setNamedTag(NBT::parseJSON('{display:{Name:"§r' . $this->getConfig()->get("EscapeViewName") . '"}}'));
            $p->getInventory()->remove($item); 
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
                if($this->isSpectator($event->getDamager()) and $event->getDamager()->getInventory()->getItemInHand()->getId() == 0) {
                    $this->teleportTask->add($event->getDamager(), $event->getEntity());
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
            $this->lastPlayer = $player->getName();
            $player->setDisplayName(\pocketmine\utils\TextFormat::GRAY . "[SPEC] " . $event->getPlayer()->getName());
        } elseif($this->lastPlayer !== $event->getPlayer()->getName() and isset($this->players[$event->getPlayer()->getName()])) {
            // $this->getLogger()->info("Removed {$event->getPlayer()->getName()}");
            unset($this->players[$event->getPlayer()->getName()]);
            $event->getPlayer()->setAllowFlight(false);
            $event->getPlayer()->getInventory()->clearAll();
            $this->teleportTask->remove($event->getPlayer());
            $event->getPlayer()->setDisplayName($event->getPlayer()->getName());
            $event->getPlayer()->getInventory()->clearAll();
        } elseif($this->lastPlayer == $event->getPlayer()->getName()) {
            $this->lastPlayer = null;
        }
    }
    
    
   
    public function isSpectator(Player $player) {
        return isset($this->players[$player->getName()]);
    }
    
    
    
    public function onPlayerQuit(PlayerQuitEvent $event) {
        if($this->isSpectator($event->getPlayer())) {
            $this->quitedplayers[$event->getPlayer()->getName()] = true;
            unset($this->players[$event->getPlayer()->getName()]);
        }
    }
    
    
    
    public function onPlayerJoin(PlayerJoinEvent $event) {
        if(isset($this->quitedplayers[$event->getPlayer()->getName()])) {
            $this->players[$event->getPlayer()->getName()] = [];
            unset($this->quitedplayers[$event->getPlayer()->getName()]);
        }
    }
    
    
    
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



class teleportTask extends \pocketmine\scheduler\PluginTask {
    
    public function __construct(Main $main) {
        parent::__construct($main);
        $this->main = $main;
        $this->players = [];
    }
    
    
   public function onRun($tick) {
       foreach($this->players as $pname => $tpname) {
           $p = $this->main->getServer()->getPlayer($pname);
           $tp = $p->getLevel()->getEntity($tpname);
           if($p instanceof Player) {
               $tp = $p->getLevel()->getEntity($tpname);
               if($tp instanceof Entity) {
                   $p->teleport(new Vector3($tp->x, $tp->y + 0.5, $tp->z), $tp->yaw, $tp->pitch);
               }
           }
       }
   } 
   
   
   
   public function add(Player $player, Entity $to) {
       $this->players[$player->getName()] = $to->getId();
       $item = Item::get(Item::FEATHER, 0, 1);
       $item->setNamedTag(NBT::parseJSON('{display:{Name:"§r' . $this->main->getConfig()->get("EscapeViewName") . '"}}'));
       $player->getInventory()->addItem($item);
       $e = \pocketmine\entity\Effect::getEffectByName("INVISIBILITY");
       $e->setAmbient(true);
       $e->setVisible(false);
       $player->addEffect($e);
   }
   
   
   
   public function remove(Player $player) {
       if(isset($this->players[$player->getName()])) {
           unset($this->players[$player->getName()]);
           $player->removeEffect(14);
           $item = Item::get(Item::FEATHER, 0, 1);
           $item->setNamedTag(NBT::parseJSON('{display:{Name:"§r' . $this->main->getConfig()->get("EscapeViewName") . '"}}'));
           $player->getInventory()->removeItem($item); 
           return true;
       }
       return false;
   }
    
}