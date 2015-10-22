<?php

namespace Tp\Db;

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
	
	public static function rowSet($q, $db = false) {
		return DbExeption::run($db)->rowSet($q);
	}
	
	public static function row($q, $db = false) {	
		return DbExeption::run($db)->row($q);	
	}
	
	public static function query($q, $db = false) {
		return DbExeption::run($db)->query($q);
	}
	
	public static function insertReplace($db = false) {
		DbExeption::run($db)->insertReplace();
	}
	
}