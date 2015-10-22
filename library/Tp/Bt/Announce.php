<?php

namespace Tp\Bt;

use Tp\Bencode\Bencode;
use Tp\Db\Db;


class Announce
{

	const PASSKEY_KEY = 'uk';
	const MAX_LEFT_VAL = '536870912000';   // 500 GB
	const MAX_UP_DOWN_VAL = '5497558138880';  // 5 TB
	const MAX_UP_ADD_VAL = '85899345920';    // 80 GB
	const MAX_DOWN_ADD_VAL = '85899345920';    // 80 GB
	public $stopped = false;
	public $user_id;
	public $topic_id;
	public $releaser;
	public $tor_type;
	public $intReqVal = array('port', 'uploaded', 'downloaded', 'left', 'numwant', 'compact');

	
	function config($q) {
		
		global $tr_cfg, $bb_cfg, $rating_limits;
		
		if(isset($tr_cfg[$q])) $ret = $tr_cfg[$q];
		elseif(isset($bb_cfg[$q])) $ret = $bb_cfg[$q];
		else $ret = $rating_limits;
		
		return $ret;
			
	}
	
	function event() {

		switch($this->request('event')) {
			case 'completed':
				$this->dummy_exit(mt_rand(600, 1200));
				break;
				
			case 'stopped':
				$this->stopped = true;
				break;
				
		}
		
		
	}
		

	function verifyTorrentSql() {
		
		// Verify if torrent registered on tracker and user authorized
		$info_hash_sql = rtrim($this->request('info_hash'), ' ');
		$passkey_sql   = $this->request(self::PASSKEY_KEY);

		$sql = Db::select()->from(BB_BT_TORRENTS)->where(['info_hash' => $info_hash_sql])->limit(1);
		$sql2 = Db::select()->from(BB_BT_USERS)->where(['auth_key' => $passkey_sql])->limit(1);
		
		$row = Db::row($sql);
		$row2 = Db::row($sql2);

		if(!$row['topic_id']) $this->torError('Torrent not registered, info_hash = ' . bin2hex($info_hash_sql));
		if(!$row2['user_id']) $this->torError('Please LOG IN and REDOWNLOAD this torrent (user not found)');

		$this->user_id  = $row2['user_id'];
		$this->topic_id = $row['topic_id'];
		$this->releaser = (int) ($this->user_id == $row['poster_id']);
		$this->tor_type = $row['tor_type'];
		
		$seeder = ($this->request('left') == 0) ? 1 : 0;
		
		// Ratio limits
		if ((TR_RATING_LIMITS || $this->config('limit_concurrent_ips') && $vip_s) && !$this->stopped)
		{
			$user_ratio = ($row2['u_down_total'] && $row2['u_down_total'] > MIN_DL_FOR_RATIO) ? ($row2['u_up_total'] + $row2['u_up_release'] + $row2['u_up_bonus']) / $row2['u_down_total'] : 1;
			$rating_msg = '';

			if (!$seeder)
			{
				foreach ($this->config('rating_limits') as $ratio => $limit)
				{
					if ($user_ratio < $ratio)
					{
						$rating_msg = " (ratio < $ratio)";
						break;
					}
				}
			}

			/*// Limit active torrents
			if (!isset($this->config('unlimited_users')[$user_id]) && $this->config('limit_active_tor') && !in_array($user_id, $this->config('d_restrictions')) && (($this->config('limit_seed_count') && $seeder) || ($this->config('limit_leech_count') && !$seeder)))
			{
				$sql = "SELECT COUNT(DISTINCT topic_id) AS active_torrents
					FROM ". BB_BT_TRACKER ."
					WHERE user_id = $user_id
						AND seeder = $seeder
						AND topic_id != $topic_id";

				if (!$seeder && $this->config('leech_expire_factor') && $user_ratio < 0.5)
				{
					$sql .= " AND update_time > ". (TIMENOW - 60 * $this->config('leech_expire_factor'));
				}
				$sql .= "	GROUP BY user_id";

				if ($row = DB()->fetch_row($sql))
				{
					if ($seeder && $this->config('limit_seed_count') && $row['active_torrents'] >= $this->config('limit_seed_count'))
					{
						$this->torError('Only '. $this->config('limit_seed_count') .' torrent(s) allowed for seeding');
					}
					elseif (!$seeder && $this->config('limit_leech_count') && $row['active_torrents'] >= $this->config('limit_leech_count'))
					{
						$this->torError('Only '. $this->config('limit_leech_count') .' torrent(s) allowed for leeching'. $rating_msg);
					}
				}
			}*/
		}	
	}
		
	function outputSql() {
		
			// Retrieve peers
			$numwant = (int) $this->config('numwant');
			$numwant = (!in_array($this->user_id, $this->config('d_restrictions'))) ? $numwant : '0';
			$compact_mode = ($this->config('compact_mode') || $this->request('compact'));
			
			$rand = new \Zend\Db\Sql\Expression('RAND()');
			
			$sql = Db::select()->from(BB_BT_TRACKER)->where(['topic_id' => $this->topic_id])->order($rand)->limit($numwant);

			$peers = $this->peersList(Db::rowSet($sql), $compact_mode);	

			$seeders  = 0;
			$leechers = 0;

			if($this->config('scrape')) {
				$sql = Db::select()->from(BB_BT_TRACKER_SNAP)->where(['topic_id' => $this->topic_id])->limit(1);		
				$row = Db::row($sql);
				
				$seeders  = $row['seeders'];
				$leechers = $row['leechers'];
			}

			$output = [
				'interval'     => (int) $this->config('announce_interval'),
				'min interval' => (int) $this->config('announce_interval'),
				'peers'        => $peers,
				'complete'     => (int) $seeders,
				'incomplete'   => (int) $leechers,
			];

		// Return data to client
		echo Bencode::encode($output);	
		
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
	
	function decode_ip($ip) {
		return long2ip("0x{$ip}");
	}
	
	function dummy_exit($interval = 1800) {

		$peer = [
			'ip'   => $_SERVER['REMOTE_ADDR'],
			'port' => ($this->request('port')) ? intval($this->request('port')) : mt_rand(1000, 65000)
			];
		
		$msg = Bencode::encode([
			'interval'     => (int) $interval,
			'min interval' => (int) $interval,
			'peers'        => (string) $this->packPeers($peer),
		]);

		$this->msg($msg);
	}
	
	function start() {
		$this->event();
		$this->clientErrors();
		$this->verifyTorrentSql();
		$this->outputSql();
	}
	

	function packPeers($peer) {
		if(is_array($peer)) return pack('Nn', ip2long($peer['ip']), $peer['port']);
	}
	
	function torError($msg) {
		$output = Bencode::encode([
			'min interval'    => (int) 1800,
			'failure reason'  => (string) $msg,
			'warning message' => (string) $msg,
		]);

		$this->msg($output);
	}
	
	function msg($msg) {
		die($msg);
	}
	
	function clientErrors() {

		if (strpos($_SERVER['REQUEST_URI'], 'scrape') !== false)
		{
			$this->torError('Please disable SCRAPE!');
		}
		if (!is_string($this->request(self::PASSKEY_KEY)) || strlen($this->request(self::PASSKEY_KEY)) != BT_AUTH_KEY_LENGTH)
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
		if (!$this->verify_id($this->request(self::PASSKEY_KEY), BT_AUTH_KEY_LENGTH))
		{
			$this->torError('Invalid passkey');
		}
		
	}
	
	function verify_id ($id, $length) {
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