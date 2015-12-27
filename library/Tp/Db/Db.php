<?php

namespace Tp\Db;

use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Expression;

class Db {

	public static function select($db = false) {
		return DbExeption::run($db)->select();
	}
	
	public static function insert($db = false) {
		return DbExeption::run($db)->insert();
	}
	
	public static function update($db = false) {
		return DbExeption::run($db)->update();
	}
	
	public static function delete($db = false) {
		return DbExeption::run($db)->delete();
	}
	
	public static function rowSet($q) {
		return DbExeption::run()->rowSet($q);
	}
	
	public static function row($q) {	
		return DbExeption::run()->row($q);	
	}
	
	public static function query($q, $db = false) {
		return DbExeption::run($db)->query($q);
	}
	
	public static function execute($q) {
		return DbExeption::run()->result($q);
	}

	public static function insertReplace($db = false) {
		return DbExeption::run($db)->insertReplace();
	}
	
	public static function Predicate() {
		return new Predicate;
	}
	
	public static function Expression($expression = '', $parameters = null, array $types = array()) {
		return new Expression($expression, $parameters, $types);
	}
	
}