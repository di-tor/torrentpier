<?php

namespace Tp\Bt;

use Tp\Bencode\Bencode;
use Tp\Db\Db;
use Tp\Cache\Cache;

class Announce
{
	const PASSKEY_KEY = 'uk';
	const MAX_LEFT_VAL = '536870912000';   // 500 GB
	const MAX_UP_DOWN_VAL = '5497558138880';  // 5 TB
	const MAX_UP_ADD_VAL = '85899345920';    // 80 GB
	const MAX_DOWN_ADD_VAL = '85899345920';    // 80 GB
	const U_PREFIX = 'user_';
	protected $ip;
	protected $peer_hash;
	protected $stopped = false;
	protected $seeder;
	protected $upTime;
	protected $user_id;
	protected $topic_id;
	protected $releaser;
	protected $tor_type;
	protected $tor_info;
	protected $speed_down = 0;
	protected $speed_up = 0;
	protected $up_add;
	protected $down_add;
	protected $up_add_user;
	protected $down_add_user;
	protected $cacheTime;
	protected $passkey;
	protected $intReqVal = array('port', 'uploaded', 'downloaded', 'left', 'numwant', 'compact');
	protected $interval;
		
	protected function __construct() {
		$this->interval = $this->config('announce_interval');
		$this->ip = $_SERVER['REMOTE_ADDR'];
		$this->passkey = $this->request(self::PASSKEY_KEY);
		$this->seeder = (!$this->request('left')) ? 1 : 0;
		$this->peer_hash = md5(rtrim($this->request('info_hash'), ' ') . $this->passkey . $this->ip . $this->request('port'));
		$this->cacheTime = round($this->interval * (0.85 * $this->config('expire_factor')));
	}
		
	private function config($q) {
		
		global $tr_cfg, $bb_cfg, $rating_limits;
		
		if(isset($tr_cfg[$q])) $ret = $tr_cfg[$q];
		elseif(isset($bb_cfg[$q])) $ret = $bb_cfg[$q];
		else $ret = $rating_limits;
		
		return $ret;
			
	}
	
	private function event() {	
		switch($this->request('event', false)) {
			case 'completed':
				Cache::remove($this->peer_hash);
				$this->dummy_exit(mt_rand(600, 1200));
				break;
				
			case 'stopped':
				$this->stopped = true;
				break;
				
		}
		
		$this->upTime = ($this->stopped) ? 0 : TIMENOW;
	}
	
	private function verifyTorrentIsRegistred() {
			
		if(!Cache::check($this->peer_hash)) {
			$sql = Db::select()->from(BB_BT_TRACKER)->where(['peer_hash' => $this->peer_hash])->limit(1);
			$this->tor_info = Db::row($sql);
		} else $this->tor_info = Cache::get($this->peer_hash);
	}
	
	private function drop_fast_announce () {
		if($this->tor_info['update_time'] < (TIMENOW - $this->interval / + 60))
		{
			return;  // if announce interval correct
		}

		$new = $this->tor_info['update_time'] + $this->interval - TIMENOW;

		$this->dummy_exit($new);
	}

	private function verifyTorrentSql() {
		
		$info = rtrim($this->request('info_hash'), ' ');
		
		$sql = Db::select()->from(BB_BT_TORRENTS)->where(['info_hash' => $info])->limit(1);
		$row = Db::row($sql);

		if(!$row['topic_id']) $this->torError('Torrent not registered, info_hash = ' . bin2hex($info));
		
		$this->topic_id = $row['topic_id'];
		$this->releaser = (int) ($this->user_id == $row['poster_id']);
		$this->tor_type = $row['tor_type'];
		
		$this->userRectrited();
	}
	
	private function getUserInfo() {
		if(!Cache::check(self::U_PREFIX . $this->peer_hash)) {
			$sql = Db::select()->from(array('bt' => BB_BT_USERS));
			$sql->join(array('u' => BB_USERS), 'bt.user_id = u.user_id');
			$sql->where(['auth_key' => $this->passkey]);
			$row = Db::row($sql);
			Cache::set(self::U_PREFIX . $this->peer_hash, $row, $this->cacheTime);
		} else $row = Cache::get(self::U_PREFIX . $this->peer_hash);
	
		if(!$row) $this->torError('Please LOG IN and REDOWNLOAD this torrent (user not found)');
		
		$this->user_id  = $row['user_id'];
	}
	
	private function userRectrited() {
		$row = Cache::get(self::U_PREFIX . $this->peer_hash);

		// Ratio limits
		if(TR_RATING_LIMITS || $this->config('limit_concurrent_ips')) {
			$user_ratio = ($row['u_down_total'] && $row['u_down_total'] > MIN_DL_FOR_RATIO) ? ($row['u_up_total'] + $row['u_up_release'] + $row['u_up_bonus']) / $row['u_down_total'] : 1;
			$rating_msg = '';

			if (!$this->seeder) {
				foreach ($this->config('rating_limits') as $ratio => $limit) {
					if ($user_ratio < $ratio) {
						$rating_msg = " (ratio < $ratio)";
						break;
					}
				}
			}

			// Limit active torrents
			if(!isset($this->config('unlimited_users')[$this->user_id]) && $this->config('limit_active_tor') && !in_array($this->user_id, $this->config('d_restrictions')) && (($this->config('limit_seed_count') && $this->seeder) || ($this->config('limit_leech_count') && !$this->seeder))) {
				$Predicate = Db::Predicate;
				$sql = Db::select()->from(BB_BT_TRACKER);
				$sql->where([
					'user_id' => $this->user_id,
					'seeder' => $this->seeder,
					$Predicate->notEqualTo('topic_id', $this->topic_id)
				]);
				$sql->columns(['active_torrents' => Db::Expression('COUNT(DISTINCT topic_id)')]);
				$sql->group('user_id');
				

				if (!$this->seeder && $this->config('leech_expire_factor') && $user_ratio < 0.5) {
					$sql->where([$Predicate->greaterThan('update_time', TIMENOW - 60 * $this->config('leech_expire_factor'))]);
				}

				if ($row = Db::row($sql)) {
					if ($this->seeder && $this->config('limit_seed_count') && $row['active_torrents'] >= $this->config('limit_seed_count')) {
						$this->torError('Only '. $this->config('limit_seed_count') .' torrent(s) allowed for seeding');
					} elseif(!$this->seeder && $this->config('limit_leech_count') && $row['active_torrents'] >= $this->config('limit_leech_count')) {
						$this->torError('Only '. $this->config('limit_leech_count') .' torrent(s) allowed for leeching'. $rating_msg);
					}
				}
			}
		}	
	}
	
	private function outputSql() {
		
		// Retrieve peers
		$numwant = (int) $this->config('numwant');
		$compact_mode = ($this->config('compact_mode') || $this->request('compact'));

		$sql = Db::select()->from(BB_BT_TRACKER)->where(['topic_id' => $this->topic_id])->order(Db::Expression('RAND()'))->limit($numwant);

		$peers = $this->peersList(Db::rowSet($sql), $compact_mode);	
		$seeders = 0;
		$leechers = 0;

		if($this->config('scrape')) {
			$sql = Db::select()->from(BB_BT_TRACKER_SNAP)->where(['topic_id' => $this->topic_id])->limit(1);		
			$row = Db::row($sql);
				
			$seeders  = $row['seeders'];
			$leechers = $row['leechers'];
		}

		$out = [
			'peers'        => $peers,
			'complete'     => (int) $seeders,
			'incomplete'   => (int) $leechers,
		];

		$this->outBuilder($out);	
	}
	
	private function peerReg() {

		$val = [
			'peer_hash'   => $this->peer_hash,
			'topic_id'    => $this->topic_id,
			'user_id'     => $this->user_id,
			'ip' 		  => $this->encode_ip($this->ip),
			'port' 		  => $this->request('port'),
			'seeder' 	  => $this->seeder,
			'releaser' 	  => $this->releaser,
			'tor_type'    => $this->tor_type,
			'remain'      => $this->request('left'),
			'update_time' => $this->upTime,
			'client'      => $_SERVER['HTTP_USER_AGENT'],
			'speed_up'    => $this->speed_up,
			'speed_down'  => $this->speed_down,
		];
	
		if($this->tor_info) $this->regReconnection($val);
		else $this->regFirstConnection($val);	
	}
	
	private function regReconnection(array $val) {
		
		if($this->request('uploaded') >= $this->tor_info['uploaded']) {
			$up_add = $this->request('uploaded') - $this->up_add_user;
			$val['uploaded'] = $this->request('uploaded');
			$val['up_add'] = Db::Expression("up_add + ". $up_add);
			$val['up_add_user'] = $this->up_add_user + $up_add;
		}

		if($this->request('downloaded') >= $this->tor_info['downloaded']) {
			$down_add = $this->request('downloaded') - $this->down_add_user;
			$val['downloaded'] = $this->request('downloaded');
			$val['down_add'] = Db::Expression("down_add + ". $down_add);
			$val['down_add_user'] = $this->down_add_user + $down_add;
		}

		Db::execute(Db::update()->table(BB_BT_TRACKER)->set($val)->where(['peer_hash' => $this->peer_hash]));
	
		unset($val['up_add'], $val['down_add']);
		
		$val['down_add_user'] = (isset($val['down_add_user'])) ? $val['down_add_user'] : $this->down_add_user;
		$val['up_add_user'] = (isset($val['up_add_user'])) ? $val['up_add_user'] : $this->up_add_user;
		
		Cache::replace($this->peer_hash, $val, $this->cacheTime);
	}
	
	private function regFirstConnection(array $val) {
		$val['uploaded'] = $this->request('uploaded');
		$val['downloaded'] = $this->request('downloaded');
		
		Db::execute(Db::insertReplace()->into(BB_BT_TRACKER)->values($val));
		
		$val['down_add_user'] = $this->up_add_user;
		$val['up_add_user'] = $this->down_add_user;

		Cache::set($this->peer_hash, $val, $this->cacheTime);
	}

	private function peersList($sql, $compact_mode) {
		if ($compact_mode) {
			$peers = '';
			foreach ($sql as $peer) {
				$peer['ip'] = $this->decode_ip($peer['ip']);
				$peers .= $this->packPeers($peer);			
			}
		} else {
			foreach ($sql as $peer) {
				$peers[] = [
					'ip'   => $this->decode_ip($peer['ip']),
					'port' => intval($peer['port']),
				];
			}
		}
		return $peers;
	}
	
	private function decode_ip($ip) {
		return long2ip("0x{$ip}");
	}
	
	private function encode_ip($ip) {
		$d = explode('.', $ip);
		return sprintf('%02x%02x%02x%02x', $d[0], $d[1], $d[2], $d[3]);
	}
	
	private function dummy_exit($interval = 1800) {

		$peer = [
			'ip'   => $this->ip,
			'port' => ($this->request('port')) ? intval($this->request('port')) : mt_rand(1000, 65000)
			];
		
		$msg = [
			'peers' => (string) $this->packPeers($peer),
		];

		$this->outBuilder($msg);
	}
	
	private function outBuilder(array $message, $interval = false) {
		
		if(!$interval) $interval = $this->interval;
		
		$msg = [
			'interval'     => (int) $interval,
			'min interval' => (int) $interval,
		];
		
		$msg = array_merge($msg, $message);

		$this->msg(Bencode::encode($msg));
		
	}
	
	private function start() {
		
		$this->event();
		$this->clientErrors();
		$this->verifyTorrentIsRegistred();
		$this->getUserInfo();

		if($this->tor_info) {
			$this->user_id  = $this->tor_info['user_id'];
			$this->topic_id = $this->tor_info['topic_id'];
			$this->releaser = $this->tor_info['releaser'];
			$this->tor_type = $this->tor_info['tor_type'];	
		} else $this->verifyTorrentSql();
		
		$this->statistics();
		$this->peerReg();
		$this->outputSql();
	}
	

	private function statistics() {
	
		$upload = $this->request('uploaded');
		$downloaded = $this->request('downloaded');
	
		$this->up_add = ($upload > $this->tor_info['up_add_user']) ? $upload - $this->tor_info['up_add_user'] : 0;
		$this->down_add = ($downloaded > $this->tor_info['down_add_user']) ? $downloaded - $this->tor_info['down_add_user'] : 0;

		$this->up_add_user = ($this->tor_info) ? $this->tor_info['up_add_user'] : 0;
		$this->down_add_user = ($this->tor_info) ? $this->tor_info['down_add_user'] : 0;

		if($this->tor_info['update_time'] < TIMENOW) {
			
			if($this->request('uploaded') > $this->tor_info['up_add_user']) {
				$this->speed_up = ceil(($this->request('uploaded') - $this->tor_info['up_add_user']) / (TIMENOW - $this->tor_info['update_time']));
			}
			if ($this->request('downloaded') > $this->tor_info['down_add_user']) {
				$this->speed_down = ceil(($this->request('downloaded') - $this->tor_info['down_add_user']) / (TIMENOW - $this->tor_info['update_time']));
			}
			
		}
	}
	
	private function packPeers($peer) {
		return pack('Nn', ip2long($peer['ip']), $peer['port']);
	}
	
	private function torError($msg) {
		$out = [
			'failure reason'  => (string) $msg,
			'warning message' => (string) $msg,
		];

		$this->outBuilder($out);
	}
	
	private function msg($msg) {
		die($msg);
		exit();
	}
	
	private function clientErrors() {

		if (strpos($_SERVER['REQUEST_URI'], 'scrape') !== false)
		{
			$this->torError('Please disable SCRAPE!');
		}
		if (!is_string($this->passkey) || strlen($this->passkey) != BT_AUTH_KEY_LENGTH)
		{
			$this->torError('Please LOG IN and REDOWNLOAD this torrent (passkey not found)');
		}	
		if (strlen($this->request('info_hash')) != 20)
		{
			$this->torError('Invalid info_hash');
		}
		if (strlen($this->request('peer_id')) != 20)
		{
			$this->torError('Invalid peer_id');
		}
		if ($this->request('port') < 0 || $this->request('port') > 0xFFFF)
		{
			$this->torError('Invalid port');
		}
		if ($this->request('uploaded') < 0 || $this->request('uploaded') > self::MAX_UP_DOWN_VAL || $this->request('uploaded') == 1844674407370)
		{
			$this->torError('Invalid uploaded value');
		}
		if ($this->request('downloaded') < 0 || $this->request('downloaded') > self::MAX_UP_DOWN_VAL || $this->request('downloaded') == 1844674407370)
		{
			$this->torError('Invalid downloaded value');
		}
		if ($this->request('left') < 0 || $this->request('left') > self::MAX_LEFT_VAL)
		{
			$this->torError('Invalid left value');
		}
		if (!$this->verify_id($this->passkey, BT_AUTH_KEY_LENGTH))
		{
			$this->torError('Invalid passkey');
		}	
	}
	
	private function verify_id($id, $length) {
		return (is_string($id) && preg_match('#^[a-zA-Z0-9]{'. $length .'}$#', $id));
	}
	
	public static function output() {
		
		$class = __CLASS__;
		$load = new $class();
		
		return $load->start();
	}

	private function request($p, $s = true) {
		
		if(isset($_REQUEST[$p])) {
			
			$ret = $_REQUEST[$p];
			
			if(in_array($p, $this->intReqVal)) $ret = (float) $ret;
			else $ret = (string) $ret;
			return $ret;
		}
		elseif($s) $this->torError('Invalid '.$p.' value');	
		else return false;	
	}	
}