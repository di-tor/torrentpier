<?php

namespace Tp\Db;

use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Expression;

class Db {

	public static function select($db = false) {
		return DbException::run($db)->select();
	}
	
	public static function insert($db = false) {
		return DbException::run($db)->insert();
	}
	
	public static function update($db = false) {
		return DbException::run($db)->update();
	}
	
	public static function delete($db = false) {
		return DbException::run($db)->delete();
	}
	
	public static function rowSet($q) {
		return DbException::run()->rowSet($q);
	}
	
	public static function row($q) {	
		return DbException::run()->row($q);	
	}
	
	public static function query($q, $db = false) {
		return DbException::run($db)->query($q);
	}
	
	public static function execute($q) {
		return DbException::run()->result($q);
	}

	public static function insertReplace($db = false) {
		return DbException::run($db)->insertReplace();
	}
	
	public static function Predicate() {
		return new Predicate;
	}
	
	public static function Expression($expression = '', $parameters = null, array $types = array()) {
		return new Expression($expression, $parameters, $types);
	}
	
}