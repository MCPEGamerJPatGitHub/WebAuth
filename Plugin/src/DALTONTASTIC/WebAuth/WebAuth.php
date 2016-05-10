<?php

namespace DALTONTASTIC\WebAuth;

use DALTONTASTIC\WebAuth\task\AuthTask;

use pocketmine\event\Listener;

use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerAchievementAwardedEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;

use pocketmine\event\block\BlockBreakEvent;

use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;

use pocketmine\Player;

use pocketmine\plugin\PluginBase;

use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

class WebAuth extends PluginBase implements Listener{
    
    const PREFIX = '[WebAuth] ';
    
    /** @var Player[] */
    protected $authed_users = [];
    protected $api_url;

    public function onEnable(){
        @mkdir($this->getDataFolder());
        $this->setting = new Config($this->getDataFolder() . "config.yml", Config::YAML, [
            'api-url' => "http://yourwebsite.ext/api.php"
        ]);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->api_url = $this->setting->get('api-url');
    }
    
    /**
     * @priority HIGHEST
     */
    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $player->sendMessage(TextFormat::PURPLE . self::PREFIX . TextFormat::WHITE . 'Enter your password into chat to play.');
    }

    public function authComplete($name, $result){
        if(!$this->isAuthed($name)){
            $player = $this->getServer()->getPlayer($name);
            if($player instanceof Player){
                if($result === true){
                    $this->authPlayer($player);
                }else{
                    $player->sendMessage(TextFormat::RED . 'Login failed!');
                }
            }
        }else{
            $this->getLogger()->warning(TextFormat::RED . "Extraneous request detected. Result ignored.");
        }
    }
    
    public function isAuthed($player){
        if($player instanceof Player){
            $player = $player->getName();
        }
        return isset($this->authed_users[$player]);
    }
    
    public function authPlayer(Player $player){
        $this->authed_users[$player->getName()] = true;
        $player->sendMessage(TextFormat::GREEN . 'You have been authenticated.');
    }
    
    /**
     * @priority HIGHEST
     */
    public function onQuit(PlayerQuitEvent $event){
        $name = $event->getPlayer()->getName();
        if($this->isAuthed($name)){
            unset($this->authed_users[$name]);
        }
    }
    
    /**
     * @priority HIGHEST
     */
    public function onMove(PlayerMoveEvent $event){
        $name = $event->getPlayer()->getName();
        if(!$this->isAuthed($name)){
            $event->setCancelled();
        }
    }
    
    /**
     * @priority HIGHEST
     */
    public function onPlayerChat(PlayerChatEvent $event){
        $player = $event->getPlayer();
        $message = $event->getMessage();
        if(!$this->isAuthed($player)){
            $event->setCancelled();
            $task = new AuthTask($player->getName(), $player->getAddress(), $message, $this->api_url);
            $this->getServer()->getScheduler()->scheduleAsyncTask($task);
        }
        $recipients = $event->getRecipients();
        foreach($recipients as $key => $recipient){
            if($recipient instanceof Player){
                if(!$this->isAuthed($recipient)){
                    unset($recipients[$key]);
                }
            }
        }
        $event->setRecipients($recipients);
    }
    
    /**
     * @priority HIGHEST
     */
    public function onInteract(PlayerInteractEvent $event){
        $name = $event->getPlayer()->getName();
        if(!$this->isAuthed($name)){
            $event->setCancelled();
        }
    }
    
    /**
     * @priority HIGHEST
     */
    public function onBreak(BlockBreakEvent $event){
        $name = $event->getPlayer()->getName();
        if(!$this->isAuthed($name)){
            $event->setCancelled();
        }
    }
    
    /**
     * @priority HIGHEST
     */
    public function onAward(PlayerAchievementAwardedEvent $event){
        $name = $event->getPlayer()->getName();
        if(!$this->isAuthed($name)){
            $event->setCancelled();
        }
    }
    
    /**
     * @priority HIGHEST
     */
    public function onDrop(PlayerDropItemEvent $event){
        $name = $event->getPlayer()->getName();
        if(!$this->isAuthed($name)){
            $event->setCancelled();
        }
    }
    
    /**
     * @priority HIGHEST
     */
    public function onItemCosume(PlayerItemConsumeEvent $event){
        $name = $event->getPlayer()->getName();
        if(!$this->isAuthed($name)){
            $event->setCancelled();
        }
    }
    
    /**
     * @priority HIGHEST
     */
    public function onCommandPreprocess(PlayerCommandPreprocessEvent $event){
        $name = $event->getPlayer()->getName();
        $cmd = $event->getMessage();
        if(!$this->isAuthed($name) && $cmd[0] === '/'){
            $event->setCancelled();
        }
    }
    
    
    /**
     * @priority HIGHEST
     */
    public function onEntityDamage(EntityDamageEvent $event){
        $player = $event->getEntity();
        if($player instanceof Player){
            if(!$this->isAuthed($player)){
                $event->setCancelled();
            }
        }
        if($event instanceof EntityDamageByEntityEvent){
            $damager = $event->getDamager();
            if($damager instanceof Player){
                if(!$this->isAuthed($damager)){
                    $event->setCancelled();
                }
            }
        }
    }
}
