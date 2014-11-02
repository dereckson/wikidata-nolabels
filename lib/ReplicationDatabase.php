<?php

/**
 * Provides access to MySQL improved instances connected to Tools Labs replication databases
 */
class ReplicationDatabaseFactory {
	/**
	 * Hashtable of stored resources
	 *
	 * @var array each item is a mysqli instance
	 */
	private static $hashtable = array();

	/**
	 * Initializes a new ReplicationDatabase instance or gets already available one
	 *
	 * @param string $database the database to select (e.g. enwiki-p)
	 */
	public static function get ($database) {
		if (!array_key_exists($database, self::$hashtable)) {
			self::$hashtable[$database] = self::connect($database);
		}
		return self::$hashtable[$database];
	}

	/**
	 * Connects to a Wikipedia toolserver replication database and gets the resource
	 *
	 * @param string $database the database to select (e.g. enwiki)
	 * @return mysqli a MySQL improved instance
	 * @throws Exception if the MySQL database couldn't be selected
	 */
	private static function connect ($database) {
		$pw = posix_getpwuid(posix_getuid());
		$mycnf = parse_ini_file($pw['dir'] . "/replica.my.cnf");
		$host = $database . '.labsdb';
		try {
			$db = new mysqli($host, $mycnf['user'], $mycnf['password'], $database . '_p');
		} catch (Exception $ex) {
			//TODO: process errors / status (mysqli doesn't throw exception)
			throw new Exception("Can't select $database database. " . $ex->getMessage());
		}
		return $db;
	}
}
