<?php

/**
 * Provides an interface for storing configuration values set at runtime into the relational database.
 */
class Config_Storage_Driver_db extends Config_Storage_Driver
{
	const TABLE_NAME = "config_data";

	/**
	 * Writes configured values into a persistent data store.
	 */
	public /*void*/ function save(/*array<string:mixed>*/ &$data, /*array<string:bool> modified*/ &$modified)
	{
		// Write to cache.
		Cache::put('app_config', $data);

		// Write to persistent storage.
		$db = App::getDatabase();
		$update = $db->prepare("REPLACE INTO `".self::TABLE_NAME."` (`key`, `value`) VALUES (?, ?);");
		$delete = $db->prepare("DELETE FROM `".self::TABLE_NAME."` WHERE `key` = ?;");

		foreach($modified as $key => $isModified) {
			if($isModified) {
				if(isset($data[$key])) {
					$tmp = serialize($data[$key]);
					if(strlen($tmp) > 255)
						throw new RuntimeException("Error: Object for key ".$key." is too large to be stored as a configuration value.");
					$update->execute($key, $tmp);
				}
				else {
					$delete->execute($key);
				}
			}
		}
	}

	/**
	 * Reads configured values from a persistent data store.
	 */
	public /*array<string:mixed>*/ function load()
	{
		return Cache::remember('app_config', function(){
			$db = App::getDatabase();
			$result = $db->query("SELECT * FROM `".self::TABLE_NAME."`;");
			
			$data = array();

			foreach($result as $row) {
				$data[$row['key']] = unserialize($row['value']);
			}

			return $data;
		});
	}
}
