<?php

namespace Tp\Db;

use Zend\Db\Sql\Sql;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Adapter;
use Tp\Db\Insert\Replace;

class DbException {

	private $_database = null;
	private $_adapter = null;
	private $_sql = null;
	private $connect;
	private static $db = 'db1';

	private function __construct($db) {
		$this->_database = $db;
		
		$this->connect = new Adapter($this->_database);
		
		$this->_sql = new Sql($this->connect);
		
	}
	
	public function query($q) {
		return $this->connect->query($q, Adapter::QUERY_MODE_EXECUTE);
	}
	
	public function select() {
		return $this->_sql->select();
	}
	
	public function insert() {
		return $this->_sql->insert();
	}
	
	public function update() {
		return $this->_sql->update();
	}
	
	public function delete() {
		return $this->_sql->delete();
	}
	
	public function insertReplace() {
		return new Replace();
	}
	
	public function rowSet($q) {
		if(!is_object($q)) {
			throw new Exception("It's not object");
		}
		$result = new ResultSet();
		$result->initialize($this->result($q));
		
		return $result;
	}
	
	public function row($q) {
		if(!is_object($q)) {
			throw new Exception("It's not object");
		}	
		$result = $this->result($q);
		
		return $result->current();
		
	}
	
	public function result($q) {
		$string = $this->_sql->prepareStatementForSqlObject($q);
		
		return $string->execute();	
	}
	
	public static function run($database = false) {
	
		global $bb_cfg;
		
		$database = ($database) ? $database : self::$db;

		$database = $bb_cfg['connect'][$database];
		$class = __CLASS__;

		return new $class($database);
	}
	
}