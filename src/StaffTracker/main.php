<?php

namespace stafftracker;

use pocketmine\event\Listener;

use pocketmine\plugin\PluginBase;

use pocketmine\event\player\PlayerCommandPreprocessEvent;

use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;


class Main extends PluginBase implements Listener{

    public function onEnable(){

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		$this->saveDefaultConfig();
		$this->reloadConfig();
		$this->config = new Config($this->getDataFolder(). "config.yml", Config::YAML);
		$this->log = new Config($this->getDataFolder(). "logs.yml", Config::YAML);
		
		$mysql = $this->config->get("MySQL Details");
		$mysql_hostname = $mysql["host"];
		$mysql_port = $mysql["port"];
		$mysql_user = $mysql["user"];
		$mysql_password = $mysql["password"];
		$mysql_database = $mysql["database"];

		if($this->getConfig()->get("Enable MySQL") == true){
			$this->getLogger()->info("Connecting to MySQL Database ...");
			$this->db = @mysqli_connect($mysql_hostname, $mysql_user, $mysql_password, $mysql_database, $mysql_port);
			$this->db_check = @fsockopen($mysql_hostname, $mysql_port, $errno, $errstr, 5);

			if (!$this->db_check)
			{
				$this->getLogger()->critical("Cant find MySQL Server running.");
				$this->getLogger()->critical("Disabling MySQL option.");
				$this->config->set("Enable MySQL", false);
				$this->config->save();
				$this->config->getAll();
				$this->getLogger()->info("Disabled MySQL option.");
			}
			else{
				if(!$this->db){
					$this->getLogger()->critical("Invalid MySQL Details!");
					$this->getLogger()->critical("Disabling MySQL option.");
					$this->config->set("Enable MySQL", false);
					$this->config->save();
					$this->config->getAll();
					$this->getLogger()->info("Disabled MySQL option.");
				}
				else{
					$exists_table_stafftracker = @mysqli_query($this->db, "SELECT * FROM stafftracker LIMIT 0");

					if(!$exists_table_stafftracker){
					$this->getLogger()->critical("StaffTracker table doesnt exist.");
					$this->getLogger()->info("Generating table ...");
					
					$sql= "CREATE TABLE IF NOT EXISTS stafftracker(
						username		VARCHAR(30) NOT NULL,
						cmd			TEXT NOT NULL,
						time			TEXT NOT NULL
						) ENGINE=INNODB;";
					
						if (@mysqli_query($this->db,$sql)) {
							$this->getLogger()->info(TextFormat::YELLOW ."Successfully created \"stafftracker\" table!");
						}
						else {
							$this->getLogger()->info(TextFormat::RED ."Can't create the database!");
						}
					}

					$this->getLogger()->info(TextFormat::BLUE ."MySQL Status: " . TextFormat::GREEN . "Connected!");
				}
			}
		}
		else{
			$this->getLogger()->info("TIP: You can also enable MySQL option by editing the config.yml");
		}
		$this->getLogger()->info(TextFormat::DARK_GREEN ."StaffTracker Enabled!");
	}
    
    public function onCommandExecute(PlayerCommandPreprocessEvent $event) {
        
		$command = $event->getMessage();
		$commandarray = explode(' ',trim($command));
		$message = $commandarray[0];
		$player = $event->getPlayer()->getName();
		$time1 = intval(time());
		$time = date("m-d-Y H:i:s", $time1);
		
		if($this->getConfig()->get("Enable MySQL") == true){
			if ($event->getPlayer()->isOp()) {
				if ($message == "/kick" or $message == "/ban" or $message == "/banip" or $message == "/pardon" or $message == "/pardon-ip") {

					$execute_query = "INSERT INTO stafftracker(username, cmd, time)VALUES('$player', '$command', '$time')";
					@mysqli_query($this->db, $execute_query);
					
						$this->log->set($time, "Player: " . $player . " | cmd: " . $command);
						$this->log->save();
						$this->log->getAll();
				}
			}
		}
		else{
			if ($event->getPlayer()->isOp()) {
				if ($message == "/kick" or $message == "/ban" or $message == "/banip" or $message == "/pardon" or $message == "/pardon-ip") {
						$this->log->set($time, "Player: " . $player . " | cmd: " . $command);
						$this->log->save();
						$this->log->getAll();
				}
			}
		}
	}
	
    public function onDisable(){
	
		$this->config->getAll();
		$this->log->getAll();
		$this->config->save();
		$this->log->save();
		
		if($this->getConfig()->get("Enable MySQL") == true){
			$this->getLogger()->info("Closing MySQL Connection ...");
			@mysqli_close($this->db);
		}
		
		$this->getLogger()->info(TextFormat::DARK_BLUE . "StaffTracker Disabled!");
    }

}
