<?php

class model_core extends model_driver {

/**
 * プライマリキー
 * @var array 
 * @link http://
 */
  public $primary_keys = array();

/**
 * モデルのインスタンスキャッシュ
 * @var array 
 * @link http://
 */
  public static $select_from = array();

/**
 * データベース接続情報
 * @var array 
 * @link http://
 */
  public static $DSN = null;

/**
 * モデルのクラスファイルパース
 * @var string 
 * @link http://
 */
  public static $model_path = null;

/**
 * 結合情報
 * @var array 
 * @link http://
 */
  public $relation = array();

/**
 * オンメモリーのキャッシュ機構
 * @var array
 * @link http://
 */
  protected $cache = array();
  
/**
 * ビヘイビア設定機構
 * @var array
 * @link http://
 */
  public $acts_as = array(); 

/**
 * ビヘイビア機構
 * @var array
 * @link http://
 */
  protected $behaviors_pool = array();

/**
 * ビヘイビア機構(共通)
 * @var array
 * @link http://
 */
  static private $behaviors = array();
/**
 * ビヘイビア設定パス
 * @var array
 * @link http://
 */
  static private $behaviors_path = "";
/**
 * カリー方法
 * @param object 
 * @return closure
 */
  protected static function curry($closure) {
	  return function($arg) use($closure) {
		  return function() use($closure, $arg) {
			  $args = func_get_args();
			  array_unshift($args, $arg);
			  return call_user_func_array($closure, $args);
		  };
	  };
  }


/**
 * 構造器、構造時に自動的に結合関係を適用する
 * @param array $DSN  
 * @return
 */
  public function __construct($DSN) {
    parent::__construct($DSN);
    if(!empty($this->relation)) {
      foreach($this->relation as $key => $relate) {
		  $relate[0] = self::select_model($relate[0]);
		  array_unshift($relate, $key);
		  call_user_func_array(array($this, "add_relation"), $relate);
      }
    }
	$this->load_behavior("default");
	if(!empty($this->acts_as)) {
		foreach($this->acts_as as $behavior) {
			$this->load_behavior($behavior);
		}
	}
  }

/**
 * 定義した結合情報を取得する
 * @return array
 */
  public function get_defined_relation() {
    return $this->relation;
  }


/**
 * モデルのインスタンスを生成する
 * @param string $from テーブル名 
 * @param string $path モデルファイルパース 
 * @param array $DSN 接続情報
 * @return object モデルのインスタンス
 */
  public static function select_model($from, $path = null, $DSN = null) {
    if(!isset(self::$select_from[$from])) {
		$DSN = isset($DSN) ? $DSN : self::$DSN;
		$path = isset($path) ? $path : self::$model_path;
		//feed back
		self::$DSN = $DSN;
		self::$model_path = $path;
		$from_path = $path . $from . ".model.php";
		if(is_file($from_path)) {
			$model_name = $from . "_model";
			require_once $from_path;
			$model = new $model_name($DSN);
		} else {
			$model = new model_core($DSN);
		}
		$model->from($from);
		self::$select_from[$from] = $model;
    }
    return self::$select_from[$from];
  }
  
  public function from($table,$reset=true){
    parent::from($table, $reset);
    if(!isset($this->primary_keys[$this->from])) {
		foreach($this->indexes() as $index) {
			if(strtolower($index["Key_name"]) === "primary"){
				$this->primary_keys[$this->from] = $index["Column_name"];
				break;
			}
		}
    }
    self::$select_from[$table] = $this;
    return $this;
  }

  /**
   * 結果セットからデータを読み出す,active_record風でラップして返る
   */
  public function fetch($caller = null){
    $row = parent::fetch();
    if($row) {
		$active_record_name = $this->active_record_name;
		$row = new $active_record_name ($row);
		$row = $this->_active_record_bind($row);
    }
	$this->behavior_call("after_find", array($row));	
    return $row;
  }

  public function fetchall($caller = null) {
    $data = parent::fetchall();
	$active_record_name = $this->active_record_name;
    foreach($data as $key => $row) {
		$record = new $active_record_name($row);
		$data[$key] = $this->_active_record_bind($record);
    }
	$this->behavior_call("after_findall", array($data));	
    return $data;
  }

  /**
   * 結果セットからデータを読み出す,配列として返る
   */
  public function fetch_as_array($caller = null) {
	  $row =  parent::fetch();
	  if($_row = $this->behavior_call("after_find_array", array($row))) {
		  $row = $_row;
	  }	
	  return $row;
  }

  public function fetchall_as_array($caller = null) {
	  $res = parent::fetchall();
	  if($_res = $this->behavior_call("after_findall_array", array($res))) {
		  $res = $_res;
	  } 
	  return $res;
  }

  public function select() {
	  $args = func_get_args();
	  $this->behavior_call("before_find");
	  return call_user_func_array("parent::select", $args);
  }

  public function delete($args = null) {
	  $this->behavior_call("before_delete");	  
	  parent::delete($args);
	  $this->behavior_call("after_delete");	  
  }

  public function insert() {
	  $this->behavior_call("before_insert");	  
	  parent::insert();
	  $this->behavior_call("after_insert");	  
  }
  
  public function update($active_record = false) {
	  $this->behavior_call("before_update");	  
	  parent::update($active_record);
	  $this->behavior_call("after_update");	  	  
  }

  public function get_primary_key(){
	  return $this->primary_keys[$this->from];
  }

  public function get_primary_set(){
	  return $this->primary_sets[$this->from];
  }

  /**
   * active_record風の新しいレコードをメモリ上生成する(saveするまでデータベースに反映しない)
   */
  public function new_record(){
	  $record = array_fill_keys($this->columns(), "");
	  $key = $this->get_primary_key();
	  unset($record[$key]);
	  $active_record_name = $this->active_record_name;
	  $record = new $active_record_name($record);
	  $record = $this->_active_record_bind($record);
	  return $record;
  }

  /**
   * new そして saveするレコード
   */
  public function create_record($data){
	  $record = $this->new_record();
	  $record->assign($data);
	  $record->save();
	  return $record;
  }
  
/**
 * モデルのカラムであるかどか
 * @param string $col カラム
 * @return boolean
 */
  public function is_column($col) {
    $this->columns();
    return in_array($col, $this->columns); 
  }


  public function truncate() {
	  $this->query("TRUNCATE table " . $this->get_from());
  }

/**
 * select * from {table} [where ...] limit 1;
 * アクティブレコード式
 * @return
 */
  public function get($caller = null){
	  return $this->limit(1)->select()->fetch($caller);
  }
  

/**
 * select * from {table} [where ...] limit 1;
 * 配列式
 * @return
 */
  public function get_as_array($caller = null) {
	  return $this->limit(1)->select()->fetch_as_array($caller);
  }
  

/**
 * select * from {table} [where ...];
 * アクティブレコード式
 * @return
 */
  public function getAll($caller = null){
	  return $this->select()->fetchall($caller);
  }
  

/**
 * select * from {table} [where ...];
 * 配列式
 * @return 
 */
  public function getAll_as_array($caller = null){
	  return $this->select()->fetchall_as_array($caller);
  }
  

/**
 * DISTINCT条件を設定する
 * @return
 */
  protected function check_distinct() {
    $distinct = false;
    if(strpos($this->group, "GROUP BY") !== false) {
      $distinct = str_replace("GROUP BY", "", $this->group);
      $this->group = null;
    }
    return $distinct;
  }


/**
 * 件数を数える
 * @param mix $col カラム名
 * @return 数えた結果
 */
  public function count($col = null){
    $distinct = $this->check_distinct();
    if($distinct) {
      $select = "COUNT(DISTINCT{$distinct}) as cnt";
    } else {
      $select = ($col !== null && $this->is_column($col)) ? "COUNT(" . $this->quote($col) . ") as cnt" : "COUNT(*) as cnt";
    }
    $cnt = $this->select($select)->fetch_as_array();
    return $cnt["cnt"];
  }

  /**
   * serializedしたものかどかのチェック
   * wordpressから借りたもの、wordpressありがとう
   */
  public static function is_serialized($data, $strict = true) {
	  // if it isn't a string, it isn't serialized
	  if ( ! is_string( $data ) )
		  return false;
	  $data = trim( $data );
	  if ( 'N;' === $data )
		  return true;
	  $length = strlen( $data );
	  if ( $length < 4 )
		  return false;
	  if ( ':' !== $data[1] )
		  return false;
	  if ( $strict ) {
		  $lastc = $data[ $length - 1 ];
		  if ( ';' !== $lastc && '}' !== $lastc )
			  return false;
	  } else {
		  $semicolon = strpos( $data, ';' );
		  $brace     = strpos( $data, '}' );
		  // Either ; or } must exist.
		  if ( false === $semicolon && false === $brace )
			  return false;
		  // But neither must be in the first X characters.
		  if ( false !== $semicolon && $semicolon < 3 )
			  return false;
		  if ( false !== $brace && $brace < 4 )
			  return false;
	  }
	  $token = $data[0];
	  switch ( $token ) {
		  case 's' :
			  if ( $strict ) {
				  if ( '"' !== $data[ $length - 2 ] )
					  return false;
			  } elseif ( false === strpos( $data, '"' ) ) {
				  return false;
			  }
			  // or else fall through
		  case 'a' :
		  case 'O' :
			  return (bool) preg_match( "/^{$token}:[0-9]+:/s", $data );
		  case 'b' :
		  case 'i' :
		  case 'd' :
			  $end = $strict ? '$' : '';
			  return (bool) preg_match( "/^{$token}:[0-9.E-]+;$end/", $data );
	  }
	  return false;
  }	
  
  

/**
 * rails風アクション群
 * @param string $name アクション名
 * @param mix $param アクションパラメタ
 * @return
 */
  public function __call($name, $param){
	  if(isset($param[0])) {
		  if(strpos($name, "find_by_") === 0) {
			  $name = str_replace("find_by_", "", $name);
			  $this->find($name, $param[0])->limit(1);
			  if(isset($param[1]) && $param[1] === true) {
				  return $this->get_as_array();
			  } else {
				  return $this->get();
			  }
		  } elseif(strpos($name, "find_all_by_") === 0) {
			  $name = str_replace("find_all_by_", "", $name);
			  $this->find($name, $param[0]);
			  if(isset($param[1]) && $param[1] === true) {
				  return $this->getall_as_array();
			  } else {
				  return $this->getall();
			  }
		  } elseif(strpos($name, "update_by_") === 0) {
			  return $this->_update_by($name, $param);
		  } elseif(strpos($name, "write_by_") === 0) {
			  return $this->_upgrade_by($name, $param);
		  } elseif(strpos($name, "count_by_") === 0) {
			  return $this->_count_by($name, $param);
		  } elseif(strpos($name, "count_value_by_") === 0) {
			  return $this->_count_by_value($name, $param);
		  } elseif(strpos($name, "exists_by_") === 0) {
			  return $this->_exists_by($name, $param);
		  } elseif(strpos($name, "upgrade_by_") === 0) {
			  return $this->_upgrade_by($name, $param);
		  }
	  }
	  return $this->behavior_call($name, $param);
  }

/**
 * 条件に一致するレコードを全更新する
 * @return
 */
  private function _update_by($name, $param) {
	  $name = str_replace("update_by_", "", $name);
	  return $this->find($name, $param[0])->put($param[1])->update();
  }

/**
 * 同じカラムの違う値を数える
 * @return
 */
  private function _count_by($name, $param) {
	  $name = str_replace("count_by_", "", $name);
	  return $this->find($name, $param[0])->group($this->quote($name))->count($name);
  }

/**
 * 同じカラムの指定した値のそれぞれの数を数える
 * @return
 */
  private function _count_by_value($name, $param) {
	  $val = isset($param[0]) ? $param[0] : null;
	  $name = str_replace("count_value_by_", "", $name);
	  $tmp = $this->find($name, $val)->group($this->quote($name))->select("count(" . $this->get_primary_key() . ") as cnt", $this->quote($name))->fetchall_as_array();
	  if(is_array($val)) {
		  $res = array();
		  foreach($tmp as $row) {
			  $res[$row[$name]] = $row["cnt"];
		  }
		  foreach($val as $v) {
			  if(!isset($res[$v])) {
				  $res[$v] = 0;
			  }
		  }
	  } else {
		  $res = $tmp[0]["cnt"];
	  }
	  return $res;
  }

/**
 * カラム指定してレコード存在するかどかをチェック
 * @return
 */
  private function _exists_by($name, $param) {
	  $name = str_replace("exists_by_", "", $name);
	  return $this->find($name, $param[0])->group($this->quote($name))->select($this->quote($name))->fetchall_as_array();
  }

/**
 * カラム指定してレコードを更新する
 * @return
 */
  private function _upgrade_by($name, $param) {
	  $val = $param[0];
	  $name = str_replace("upgrade_by_", "", $name);
	  $data = $param[1];
	  $record = $this->find($name, $val)->limit(1)->get();
	  if(!$record){
		  $record = $this->new_record();
		  $data[$name] = $val;
	  }
	  $record->assign($data);
	  $record->save();
	  return $record->to_array();
  }

/**
 * カラム名を`テーブル`.`カラム`形式で取得する
 * @return
 */
  public function __get($name){
	  if($name === "active_record_name") {
		  $name_array = explode("_", get_class($this));
		  $just_try = $name_array[0] . "_active_record";
		  if(class_exists($just_try)) {
			  $this->action_record_name = $just_try;
		  } else {
			  $this->action_record_name = "active_record_core";
		  }
		  return $this->action_record_name;
	  }
	  if(in_array($name, $this->columns())) {
		  return $this->get_from() . ".`" . $name . "`";
	  }
  }
  
  /**
   * active_recordのラップ処理
   */
  private function _active_record_bind($record){
	  $self = $this;
	  $record->set_primary_key($this->get_primary_key());
	  $record->bind(function($method, $param) use($self) {
	  		  return call_user_func_array(array($self, $method), $param);
	  	  });
	  return $record;
  }

/**
 * オンメモリーのキャッシュ機構読み込み処理
 * @param string $key キャッシュキー
 * @param mix $param 
 * @link http://
 */
  protected function get_cache($key) {
	  /*
	  //memcachedをここで使いたいかもしれません、出来ればhandlersocketを検討したいですが、handlersocketが使えない場合はmemcachedもわるくはない。
	  return memcached->get($key);
	   */
	  if(isset($this->cache[$key])) {
		  return $this->cache[$key];
	  }
  }

/**
 * オンメモリーのキャッシュ機構書き込み処理
 * @param string $key キャッシュキー
 * @param mix $value キャッシュ値
 * @param number $expire キャッシュ寿命(memcachedを利用する際だけ、有効になる)
 * @link http://
 */
  protected function set_cache($key, $value, $expire = 60) {
	  /*
	  //memcachedをここで使いたいかもしれません、出来ればhandlersocketを検討したいですが、handlersocketが使えない場合はmemcachedもわるくはない。
	  memcached->set($key, $value, $expire);
	  */
	  $this->cache[$key] = $value;
  }


/**
 * ビヘイビアをロードする
 * @param string $behavior
 * @return
 */
  public function load_behavior($behavior) {
	  if(($behavior = self::import_behavior($behavior)) && !in_array($behavior, $this->behaviors_pool)) {
		  array_unshift($this->behaviors_pool, $behavior);
	  }
	  return $this;
  }

/**
 * ビヘイビアをアンロードする
 * @param string $behavior
 * @return
 */
  public function unload_behavior() {
	  $unloads = func_get_args();
	  $tmp = array();
	  foreach($unloads as $unload) {
		  $tmp[] = self::import_behavior($unload);
	  }
	  $this->behaviors_pool = array_diff($this->behaviors_pool, $tmp);
	  return $this;
  }

  final public function behavior_call($name, $param = array()) {
	  array_unshift($param, $this);
	  foreach($this->behaviors_pool as $behavior) {
		  if(method_exists($behavior, $name) && is_callable(array($behavior, $name))) {
			  return call_user_func_array(array($behavior, $name), $param);
		  }
	  }
  }
  
  static public function behavior_path($behavior_path) {
	  self::$behaviors_path = $behavior_path;
  }

  static private function import_behavior($behavior) {
	  if(isset(self::$behaviors[$behavior])) {
		  return self::$behaviors[$behavior];
	  }
	  $behavior_file = self::$behaviors_path . $behavior . ".behavior.php";
	  if(is_file($behavior_file)) {
		  require_once $behavior_file;
		  return self::$behaviors[$behavior] = $behavior . "_behavior";
	  } else {
	  	  return self::$behaviors[$behavior] = false;
	  }
  }
  
/**
 * print_r($this->get_last_query());
 * @return
 */
  public function log() {
	  echo "<pre>";
	  print_r($this->get_last_query());
	  echo "</pre>";
  }
}

class active_record_core {
	private $store = array();
	private $extend = array();
	private $primary_key = null;
	private $primary_value = null;
	private $store_schema = null;
	private $model_handler = null;
	private $real_changed = false;

	public function bind($handler) {
		$this->model_handler = $handler;
	}
	
	public function __call($name, $param) {
		return call_user_func($this->model_handler, $name, $param);
	}
	
	public function __construct($record){
		$this->store = $record;
	}

	public function has_column($col) {
		if(empty($this->store_schema)) {
			$this->store_schema = array_flip(array_keys($this->store));
		}
		return isset($this->store_schema[$col]);
	}
	
	public function __get($name){
		return isset($this->store[$name]) ? $this->store[$name] : (isset($this->extend[$name]) ? $this->extend[$name] : null);
	}
	
	public function __isset($name) {
		return isset($this->store[$name]) ? true : (isset($this->extend[$name]) ? true : false);
	}

	public function __set($name, $value){
		if(!$this->has_column($name) && $name !== $this->primary_key) {
			return $this->extend[$name] = $value;
		}
		//レコードの値が再設定されれば...
		if(!isset($this->store[$name]) || $this->store[$name] !== $value) {
			$this->real_changed = true;
		}
		return $this->store[$name] = $value;
	}
	
	/**
	 * 主キー設定
	 */
	public function set_primary_key($key){
		$this->primary_key = $key;
		$this->primary_value = $this->get_primary_value($key);
	}

	public function get_primary_value ($key) {
		if(isset($this->store[$key])) {
			return $this->store[$key];
		} elseif(strpos($key, ",") !== false) {
			$primary = explode(",", $key);
			$value = array();
			foreach($primary as $k) {
				if(!isset($this->store[$k])) {
					return null;
				}
				$value[] = $this->store[$k];
			}
			return $value;
		} 
		return null;
	}

	/**
	 * 主キー値取得
	 */
	public function get_primary_key(){
		return $this->primary_value;
	}
	
	
	/**
	 * データ一括設定
	 */
	public function assign($data) {
		if(empty($data)) {
			return false;
		}
		foreach($data as $name => $value) {
			$this->__set($name, $value);
		}
	}
	
	/**
	 * データバインド
	 * bind array <=> assign hash
	 */
	public function bind_array($arr, $type = null) {
		if($type === null) {
			$type = array_keys($this->store);
		}
		$data = array_combine($type, $arr);
		$this->assign($data);
	}

	/**
	 * タイプ取得
	 */
	public function get_type() {
		return array_keys($this->store);
	}

	/**
	 * データ取得
	 */
	public function to_array() {
		return array_merge($this->store, $this->extend);
	}
	
	/**
	 * データ変更反映
	 */
	public function save(){
		if(empty($this->store)) {
			trigger_error("invalid record, invalid store", E_USER_NOTICE);
			return false;
		}
		$value = null;
		if(isset($this->primary_value)) {
			//値が実質的に変更されてない場合はSQLの生成及び発行を飛ばす：「最も早いSQLはSQLを発行しないこと」
			if(!$this->real_changed) {
				return false;
			}
			if($this->has_column("update_dt")) {
				$this->store["update_dt"] = $_SERVER["REQUEST_TIME"];				
			}
			//update(true)の役割は単純にモデルオブジェクトに「この更新はactive_recordから呼び出されたものだ」を通知するだけ、モデルはこれに対してどう動くかは知らん
			$this->skip_filter();
			$this->find($this->primary_key, $this->primary_value)->put($this->store)->update(true);
			$this->real_changed = false;
			//ここの挙動は微妙だ、論理上プライマリキーは変更すべきものではないものの、実際変更される場合あるかもしれないし;
			$this->primary_value = $this->get_primary_value($this->primary_key);
		} else {
			if($this->has_column("register_dt")) {
				$this->store["register_dt"] = $this->store["update_dt"] = $_SERVER["REQUEST_TIME"];
			}
			$this->put($this->store)->insert();
			$value = $this->last_id();
			$this->primary_value = $value;
			$this->store = array_merge(array($this->primary_key => $this->primary_value), $this->store);
		}
		return $value;
	}
	
	/**
	 * データ削除
	 */
	public function delete() {
		if(isset($this->primary_value)) {
			$this->store = array();
			return $this->find($this->primary_key, $this->primary_value)->delete();
		}
	}
}

class model_behavior_core {
	
}
