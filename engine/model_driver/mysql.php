<?php
/**
 *
 *   Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 *   author: chenhan,gpgkd906@gmail.com
 *   website: http://dev.gpgkd906.com/MyProject/
 */
class model_driver {
	use base_core;
/**
 * PDO接続
 * @var resource 
 * @link http://
 */
	private static $conn = null;

/**
 * PDOstatmentセット
 * @var array 
 * @link http://
 */
	private $stmt = null;

/**
 * プリペーア用値配列
 * @var array 
 * @link http://
 */
	private $args = array();

/**
 * プリペーア用キー配列
 * @var array 
 * @link http://
 */
	protected $set = array();

/**
 * プリペーア用「?」配列
 * @var array 
 * @link http://
 */
	protected $set_args = array();

/**
 * 検索条件格納
 * @var array 
 * @link http://
 */
  protected $find = array();

/**
 * 持続的検索条件格納
 * @var array 
 * @link http://
 */
  protected $filter = array();

/**
 * 結合関係格納
 * @var array 
 * @link http://
 */
  protected $join = array();

/**
 * 目標モデル
 * @var string 
 * @link http://
 */
  protected $from = null;

/**
 * 目標テーブル
 * @var string 
 * @link http://
 */
  protected $table = null;

/**
 * SQL用検索条件格納
 * @var array 
 * @link http://
 */
  protected $where = array();

/**
 * 直指定検索条件格納
 * @var array 
 * @link http://
 */
  protected $where_condition = array();
 
/**
 * 持続的直指定検索条件格納
 * @var array 
 * @link http://
 */
 protected $where_filter = array();

/**
 * ソート条件
 * @var string 
 * @link http://
 */
  protected $order= null;

/**
 * グループ条件
 * @var string 
 * @link http://
 */
  protected $group = null;

/**
 * リミット条件
 * @var integer/array
 * @link http://
 */
  protected $limit=array();

/**
 * 最後に発行したプリペアクエリ
 * @var array 
 * @link http://
 */
  protected $lastQuery=array();

/**
 * カレントのモデルのカラム情報
 * @var array 
 * @link http://
 */
  protected $columns = array();

/**
 * カレントのモデルの完全なるカラム情報(DB依存)
 * @var array 
 * @link http://
 */
  protected $full_columns = array();

/**
 * カレントのモデルと結合関係を持つモデルのカラム情報
 * @var array 
 * @link http://
 */
  protected $relate_columns = array();

/**
 * 実際に結合する全てのモデルのカラム情報
 * @var array 
 * @link http://
 */
  protected $join_columns = array();

/**
 * 持続的な検索条件の例外
 * @var array 
 * @link http://
 */
  protected $skip_filter = array();

/**
 * 結合情報
 * @var array 
 * @link http://
 */
  protected $relate = array();

/**
 * 結合のスキップ情報
 * @var array 
 * @link http://
 */
  protected $skip_relate = array();

/**
 * 最後にスキップした結合情報
 * @var array 
 * @link http://
 */
  protected $last_skip_relate = array();

/**
 * プリペアキャッシュ
 * @var array 
 * @link http://
 */
  protected $prepare_cache = array();

/**
 * 結合する際の同名カラムの回避情報
 * @var array 
 * @link http://
 */
  protected $alias = array();

/**
 * デバッグ情報
 * @var array 
 * @link http://
 */
  public $debug=array(
		      "error"=>array(),
		      "info"=>array(),
			  "total" => 0,
		      );

/**
 * デバッグモード
 * @var boolean
 * @link http://
 */
  public static $debug_mode = true;

/**
 * カレントのモデルのインデックス情報
 * @var array 
 * @link http://
 */
  public $indexes = array();

/**
 * handlersocketのクライアント
 * handlersocketがインスタンスされることはhandlersocketを使用することも意味している
 * @var mix
 * @link http://
 */
  public static $handlersocket = false;

/**
 * handlersocketのポート情報
 * @var mix
 * @link http://
 */
  private static $handlersocket_port = 9999;

/**
 * handlersocketのdb情報
 * @var mix
 * @link http://
 */
  private static $handlersocket_dbname = null;

/**
 * handlersocket用のカラム情報
 * @var mix
 * @link http://
 */
  private $handlersocket_cols = false;

/**
 * handlersocketで挿入する時のinsert_id;
 * @var mix
 * @link http://
 */
  private $handlersocket_insert_id = false;

/**
 * 構造器、DB接続構造
 * @param array $DSN データベース接続情報
 * @return void
 */
  public function __construct($DSN){
    if(self::$conn === null){
		self::$conn = new PDO("mysql:host={$DSN['host']};dbname={$DSN['dbname']}", $DSN["user"], $DSN["password"]);
		//mysql_select_db($DSN["dbname"], self::$conn);
		self::$conn->exec("SET NAMES {$DSN['charset']}");
    }
  }


/**
 * モデルバインド
 * @param string $table テーブル名
 * @return $this
 */
  public function from($table){
	  $this->table = $table;
	  $this->from='`' . $table . '`';
	  if(self::$handlersocket !== false) {
		  $this->handlersocket_cols = array_diff($this->columns, array($this->get_primary_key()));
	  }
	  return $this;
  }

  /**
   * a "find" is just a flash, but a filter will live long...unless u remove them
   */
/**
 * 持続的な検索条件を設定する
 * @param string $key フィルタ名
 * @param max 　　$where 検索カラム名(集)
 * @param mix 　　$bind  検索値(集)
 * @param string $opera 検索方法
 * @return void
 */
  public function add_filter($key, $where, $bind = null, $opera = "=") {
    $this->filter[$key] = array($where, $bind, $opera);
  }

  /**
   * the only way u can remove a filter
   */
/**
 * 設定した持続的な検索条件を削除する
 * @param string $key フィルタ名
 * @return void
 */
  public function remove_filter($key) {
	  if(isset($this->filter[$key])) {
		  unset($this->filter[$key]);
	  }
  }
  

/**
 * 設定した全ての持続的な検索条件を削除する
 * @return void
 */
  public function clear_filter() {
    $this->filter = array();
  }


/**
 * 設定した持続的な検索条件をスキップするように、スキップ情報を設定
 * @param string $key フィルタ名
 * @return void
 */
  public function skip_filter($key = "all") {
    if($key === "all") {
      $this->skip_filter = $this->filter;
    } else {
      $this->skip_filter[$key] = true;
    }
  }

/**
 * 設定したフィルタを取得
 * @param string $key フィルタ名
 * @return array フィルタ情報
 */
  public function get_filter($key) {
    return $this->filter[$key];
  }

  
/**
 * 設定したフィルタをスキップ済みして取得する
 * @return array フィルタ情報
 */
  public function get_all_filter() {
    $filter = array_diff_key($this->filter, $this->skip_filter);
    $this->skip_filter = array();
    return $filter;
  }
  
/**
 * カラム名の別名を設定
 * @param string $col カラム名
 * @param string $alias 別名
 * @return
 */
  public function alias($col, $alias) {
    $this->alias[$col] = $alias;
  }
  
  //relate (long live join)

/**
 * 結合情報を追加する
 */
  public function add_relation() {
    $args = func_get_args();
    $key = array_shift($args);
    $this->relate[$key] = $args;
  }

/**
 * 設定した結合情報を削除する
 * @param string $key $結合情報名
 * @return void
 */
  public function remove_relation($key) {
	  if(isset($this->relate[$jey])) {
		  unset($this->relate[$jey]);
	  }
  }

/**
 * 設定した結合情報を全て削除する
 * @param string 
 * @param integer 
 * @param array 
 * @param resource 
 * @param object 
 * @param mix 
 * @return
 */
  public function clear_relation() {
    $this->relate = array();
  }

/**
 * 結合のスキップ情報を設定する
 * @param string $key 結合情報名
 * @return void
 */
  public function skip_relation($key = "all") {
    if($key === "all") {
      $this->skip_relate = $this->relate;
    } else {
      $this->skip_relate[$key] = true;
    }
  }


/**
 * 最後の結合のスキップ情報を取得
 * @return array 
 */
  public function get_last_skipped_relation() {
	  return $this->last_skip_relate;
  }


/**
 * 設定した結合情報で実結合処理
 * @return void
 */
  protected function check_relation() {
	  $relate = array_diff_key($this->relate, $this->skip_relate);
	  $this->last_skip_relate = $this->skip_relate;
	  $this->skip_relate = array();
	  foreach($relate as $set) {
		  call_user_func_array(array($this, "join"), $set);
	  }
  }


/**
 * モデル名を取得
 * @return string 
 */
  public function get_from(){
    return $this->from;
  }

/**
 * モデルのテーブル名を取得
 * @return string 
 */
  public function get_table(){
    return $this->table;
  }

/**
 * 検索条件を設定する
 * @param max 　　$where 検索カラム名(集)
 * @param mix 　　$bind  検索値(集)
 * @param string $opera 検索方法
 * @return $this
 */
  public function find($where,$bind=null,$opera="="){
    $this->find[]=array($where,$bind,$opera);
    return $this;
  }


/**
 * 設定した検索条件を取得し、全ての検索条件を解除する
 * @return array
 */
  public function fetch_find() {
    $find = $this->find;
    $this->find = array();
    return $find;
  }
  

/**
 * 設定した検索条件をテーブルのカラム情報を照合し、情報の正当性をチェックする
 * @param array/string $where 検索キー
 * @return 照合結果、失敗する場合はfalseを返す
 */
  public function check_where($where) {
	  $_check = array_reduce($where, function($set, $item) {
			  if(strpos($item, ".")){
				  list($_table, $_column) = explode(".", $item);
				  $set[] = $_column;
			  } else {
				  $set[] = $item;
			  }
			  return $set;
		  }, array());
	  if(empty($this->join_columns)) {
		  $this->join_columns = $this->columns();
	  }
	  $diff = array_diff($_check, $this->join_columns);
	  return empty($diff);
  }


/**
 * NULL検索条件を設定する
 * @param string/array $where 検索カラム
 * @param boolean $null NULLかどか
 * @return
 */
  public function is_null($where, $null = true) {
    if($null) {
      $this->where[]="(`". $where . "` IS NULL)";
    } else {
      $this->where[]="(`". $where . "` IS NOT NULL)";      
    }
  }

/**
 * 遅的にバンドする 
 * 設定した検索条件をSQL文に変換する
 * @param max 　　$where 検索カラム名(集)
 * @param mix 　　$bind  検索値(集)
 * @param string $opera 検索方法
 * @return
 */
  protected function _find($where,$bind=null,$opera = "="){
    // 0 => array(0), "" => array("") ==> not empty
    // null => array(), array() => array() ==> empty
    $bind=(array) $bind;
    if(empty($bind)) {
      return false;
    }
	$primary = false;
    if(strpos($where, ",") !== false){
		if($where === $this->get_primary_key()) {
			$primary = true;
		}
		$where = explode(",", $where);
    }else{
		$where = array($where);
    }
    if(!$this->check_where($where)) {
		trigger_error("unsafe column:[" . join(",", $where) . "] for model:{$this->table}", E_USER_WARNING);
		return false;
    }
    $arglen = count(func_get_args());
    $opera = strtolower($opera);
    if($arglen === 1){
      //do nothing
    }elseif(count($where) === 1 && count($bind) > 1){
      list($where, $bind) = $this->_find_single($where, $bind, $opera);
    }else{
      list($where, $bind) = $this->_find_multi($where, $bind, $opera);      
    }
	if($primary) {
		$this->where[] = "(" . join(" AND ", $where) . ")";		
	} else {
		$this->where[] = "(" . join(" OR ", $where) . ")";
	}
    $this->args = array_merge($this->args, $bind);
    return $this;
  }

/**
 * 遅的にバンドする 
 * 設定した検索条件をSQL文に変換するサブ処理、検索カラムと検査値は一対一という場合に働く
 * @param max 　　$where 検索カラム名(集)
 * @param mix 　　$bind  検索値(集)
 * @param string $opera 検索方法
 * @return
 */
  protected function _find_single($where, $bind, $opera) {
      if(strpos($where[0], ".") > 0) {
		  $col = "`" . $this->escape(join("`.`", explode(".", str_replace("`", "", $where[0])))) . "`";
      }else {
		  $from = $this->from;
		  if(!in_array($where[0], $this->columns)) {
			  foreach($this->relate_columns as $_from => $columns) {
				  if(in_array($where[0], $columns)) {
					  $from = $_from;
					  break;
				  }
			  }
		  }
		  $col = $from.".`".$where[0]."`";
      }
      if($opera === "between"){
		  $where[0] = $col . " between ? and ?";
      }elseif($opera === "=" || $opera === "<>" || $opera === "not"){
		  $_v = array();
		  foreach($bind as $dummy){
			  $_v[] = "?";
		  }
		  if($opera === "=") {
			  $where[0] = $col . " in (" . join(",", $_v) . ")";
		  } else {
			  $where[0] = $col . " not in (" . join(",", $_v) . ")";
		  }
      } else {
		  $_set = array();
		  list($opera, $bind) = $this->_check_like($opera, $bind);
		  foreach($bind as $key => $dummy){
			  $_set[] = $col . "` {$opera} ?";
		  }
		  $where=$_set;
      }
      return array($where, $bind);
  }
  
/**
 * 遅的にバンドする 
 * 設定した検索条件をSQL文に変換するサブ処理、検索カラムと検査値は一対多という場合に働く
 * @param max 　　$where 検索カラム名(集)
 * @param mix 　　$bind  検索値(集)
 * @param string $opera 検索方法
 * @return
 */
  protected function _find_multi($where, $bind, $opera) {
      $_b = array();
      list($opera, $bind) = $this->_check_like($opera, $bind);
      foreach($bind as $key => $val){
		  if(!isset($where[$key])){
			  break;
		  }
		  $_w = $where[$key];
		  
		  if(strpos($_w, ".") > 0) {
			  $col = "`" . $this->escape(join("`.`", explode(".", str_replace("`", "", $_w)))) . "`";
		  }else {
			  $from = $this->from;
			  if(!in_array($_w, $this->columns)) {
				  foreach($this->relate_columns as $_from => $columns) {
					  if(in_array($_w, $columns)) {
						  $from = $_from;
						  break;
					  }
				  }
			  }
			  $col = $from . ".`" . $_w . "`";
		  }
		  if(is_array($val)){
			  $_v = array();
			  foreach($val as $dummy){
				  $_v[] = "?";
			  }
			  if($opera === "=") {
				  $_w = $col . " in (" . join(",", $_v) . ")";
			  } else {
				  $_w = $col . " not in (" . join(",", $_v) . ")";
			  }
			  $_b = array_merge($_b, $val);
		  }else{
			  if(strpos($_w, "?") === false){
				  $_w = $col . " {$opera} ?";
			  }
			  $_b[] = $val;
		  }
		  $where[$key] = $_w;
      }
      return array($where, $_b);
  }
  

/**
 * 検索条件のLIKEを整形する
 * @param string $opera 検索方法: like, %like, like%, %like%
 * @param array/string  $_b 検索値
 * @return array
 */
  protected function _check_like($opera, $_b) {
	  if($opera === "like") {
		  $_b = array_map(function($i) {
				  return "%" . $i . "%";
			  }, $_b);
	  } elseif($opera === "like%") {
		  $_b = array_map(function($i) {
				  return $i . "%";
			  }, $_b);
		  $opera = "like";
	  } elseif($opera === "%like") {
		  $_b = array_map(function($i) {
				  return "%" . $i;
			  }, $_b);
		  $opera = "like";
	  }
	  return array($opera, $_b);
  }

/**
 * SQL文の検索条件を直指定する
 * @param string $where_condition SQL文の検索条件
 * @param boolean $filter 持続的にするかどか
 * @return void
 */
  public function where($where_condition, $filter = false) {
    if($filter) {
      $this->where_filter[] = $where_condition;
    } else {
      $this->where_condition[] = $where_condition;
    }
  }


/**
 * 設定したSQL文の検索条件を取得する
 * @return array
 */
  protected function _where() {
    $where = array_merge($this->where_filter, $this->where_condition);
    foreach($where as $wc) {
      $this->where[] = "(" . $wc . ")";
    }
  }

/**
 * 更新条件を設定する
 * @param string $set 更新カラム
 * @param mix $bind 更新値
 * @return $this;
 */
  public function set($set,$bind=null){
    $this->set[] = "`" . $set . "`";
    $_bind = (array) $bind;
    if(empty($_bind)) {
      $_bind = array($bind);
    }
    $this->set_args = array_merge($this->set_args, $_bind);
    return $this;
  }

/**
 * ソート条件設定
 * @param string $order ソート条件
 * @return $this
 */
  public function order($order){
	  $this->order = "ORDER BY {$order}";
	  return $this;
  }

/**
 * グループ情報設定
 * @param string $group グループ情報
 * @return $this
 */
  public function group($group){
    $this->group = "GROUP BY {$group}";
    return $this;
  }
  

/**
 * リミット条件設定
 * $l1だけ設定される場合は制限値、$l2設定される場合、$l1はオフセット値になる
 * @param integer $l1 オフセット/制限値 
 * @param integer $l2 制限値
 * @return $this
 */
  public function limit($l1 ,$l2=null){
	  $this->limit = array();
	  $this->limit[] = $l1;
	  empty($l2) || ($this->limit[] = $l2);
	  return $this;
  }
 
/**
 * トランザクション開始
 * @return
 */
  public static function begin() {
	  self::$conn->beginTransaction();
  }

/**
 * トランザクション開始
 * @return
 */
  public static function commit() {
	  self::$conn->commit();
  }

/**
 * トランザクション開始
 * @return
 */
  public static function rollback() {
	  self::$conn->rollBack();
  }

/**
 * inner_joinの別名
 * @return
 */
  public function join() {
    return call_user_func_array(array($this, "inner_join"), func_get_args());
  }
 

/**
 * モデルを双方向で結合させる
 * @return
 */
  public function inner_join() {
    list($target1, $target2, $col1, $col2) = $this->set_join_request(func_get_args());
    list($join, $from, $target) = $this->set_join_table($target1, $target2);
    $this->set_join_meta($from, $col1, $col2, $join, "INNER JOIN");
    return $this;
  }

/**
 * モデルを往方向で結合させる
 * @return
 */
  public function left_join() {
    list($target1, $target2, $col1, $col2) = $this->set_join_request(func_get_args());
    list($join, $from, $target) = $this->set_join_table($target1, $target2);
    $this->set_join_meta($from, $col1, $col2, $join, "LEFT JOIN");
    return $this;
  }

/**
 * モデルを復方向で結合させる
 * @return
 */
  public function right_join() {
    list($target1, $target2, $col1, $col2) = $this->set_join_request(func_get_args());
    list($join, $from, $target) = $this->set_join_table($target1, $target2);
    $this->set_join_meta($from, $col1, $col2, $join, "RIGHT JOIN");
    return $this;
  }


/**
 * 結合情報を整形する
 * @param array $argv 結合情報
 * @return
 */
  private function set_join_request($argv) {
    switch(count($argv)) {
    case 4:
      list($target1, $target2, $col1, $col2) = $argv;
      break;
    case 3:
      $target1 = $this;
      list($target2, $col1, $col2) = $argv;      
      break;
    case 2:
      $target1 = $this;
      $target2 = $argv[0];
      $col1 = $col2 = $argv[1];
      break;
    }
    return array($target1, $target2, $col1, $col2);
  }


/**
 * 結合テーブルの前後関係を決定する
 * @param object $target1 モデル１
 * @param object $target2 モデル２
 * @return
 */
  private function set_join_table($target1, $target2) {
	  $table1 = $target1->get_from();
	  $table2 = $target2->get_from();
	  $this->relate_columns[$table1] = $target1->columns();
	  $this->relate_columns[$table2] = $target2->columns();
	  
	  if(isset($this->join[$table1]) || $table1 === $this->from) {
		  $join=array(
			  "from_table"=>$table1,
			  "target_table"=>$table2,
					  );
		  $from = $target1;
		  $target = $target2;
		  $target_table = $table2;
	  } else {
		  $join=array(
			  "from_table"=>$table2,
			  "target_table"=>$table1,
					  );
		  $from = $target2;
		  $target = $target1;
		  $target_table = $table1;
	  }
	  $this->join_columns = array_merge($this->join_columns, $from->columns());
	  $target_columns = $target->columns();
	  //$intersect = array_intersect($this->join_columns, $target_columns);
	  foreach($target_columns as $_col) {
		  $_alias = "alias_" . str_replace('`', '', $target_table) . '_' . $_col;
		  $_col = $target_table . '.`' . $_col . '`';
		  $this->alias($_col, $_alias);
	  }
	  $this->join_columns = array_merge($this->join_columns, $target_columns);
	  //join table filter check
	  /**
	   * todo: find: $where => find: $alias
	   */
	  foreach($target->get_all_filter() as $filter) {
		  list($where, $bind, $opera) = $filter;
		  $where = $target_table . "." . $where;
		  $this->find($where, $bind, $opera);
	  }
	  return array($join, $from, $target);
  }
  

/**
 * 結合情報の最終整形、結合するカラムの前後関係を決定する
 * @param object $from 結合元モデル
 * @param string $col1 結合カラム名1
 * @param string $col2 結合カラム名2
 * @param array $join 整形中結合情報
 * @param string $type 結合方向
 * @return array $join 整形済み結合情報
 */
  private function set_join_meta($from, $col1, $col2, $join, $type) {
	  $from_columns = $from->columns();
	  $from_table = $from->get_from();
	  $check_from = in_array($col1, $from_columns) ? true : false;
	  if($check_from || $col1 === $col2) {
		  $join["from_column"] = $col1;
		  $join["target_column"] = $col2;
	  } else {
		  $join["from_column"] = $col2;
		  $join["target_column"] = $col1;        
	  }
	  $join["join_type"] = $type;
	  $this->join[$from_table . "_" . $join["target_table"]] = $join;
  }
  

/**
 * 整形済み結合情報を取得する
 * @return array
 */
  public function get_join() {
	  return $this->join;
  }


/**
 * 検索用出力カラム取得
 * @return string 
 */
  private function select_column() {
	  if(empty($this->alias)) {
		  return "*";
	  }
	  $select = $this->make_all_column();
	  return join(", ", $select);
  }


/**
 * 検索用出力カラムの整形
 * @param boolean $use_alias 別名を利用するかしないか
 * @return array
 */
  private function make_all_column($use_alias = true) {
	  $select = $this->make_column($this->from, $this->columns(), $use_alias);
	  $fored = array($this->from);
	  foreach($this->join as $join) {
		  $from = $join["from_table"];
		  $target = $join["target_table"];
		  if(!in_array($from, $fored)) {
			  $select = array_merge($select, $this->make_column($from, $this->relate_columns[$from], $use_alias));
			  $fored[] = $from;
		  }
		  if(!in_array($target, $fored)) {
			  $select = array_merge($select, $this->make_column($target, $this->relate_columns[$target], $use_alias));	
			  $fored[] = $target;
		  }
	  }
	  return $select;
  }


/**
 * 出力カラムを`テーブル`.`カラム`という形式に整形する
 * @param string $from テーブル名
 * @param string $col カラム名
 * @param boolean $use_alias 別名をしようするかしないか
 * @return
 */
  private function make_column($from, $cols, $use_alias = true) {
	  $select = array();
	  foreach($cols as $col) {
		  $_col = $from . '.`' . $col . '`';
		  if(isset($this->alias[$_col]) && $use_alias) {
			  $select[] = $_col . " as " . $this->alias[$_col];
		  } else {
			  $select[] = $_col;
		  }
	  }
	  return $select;
  }
  
  
/**
 * 検索用SQL文を構成し、発行する
 * @return $this
 */
  public function select(){
	  $set = func_get_args();
	  foreach($set as $_k => $_v) {
		  $set[$_k] = $this->escape($_v);
	  }
	  $this->check_relation();
	  if(empty($this->join) && self::$handlersocket !== false) {
		  if($this->handlersocket_try_select() !== null) {
			  $this->tracking("handlersocket_select", null);
			  return $this->reset();
		  }
	  }
	  $halfSql = array("SELECT", empty($set) ? $this->select_column() : join(",", $set), "FROM " . $this->from);
	  foreach($this->join as $join) {
		  $halfSql[] = $join["join_type"] . " " . $join["target_table"] . " ON " . $join["from_table"] . ".`".$join["from_column"]."` = ".$join["target_table"].".`".$join["target_column"]."`";
	  }
	  $sql = $this->build_where($halfSql);
	  $this->query($sql, $this->args);
	  return $this->reset();
  }

/**
 * handlersocketでの取得可能かどかをチェックし、可能であればは取得を試す
 * @return object handlersocket_stmt $stmt
 */ 
  private function handlersocket_try_select() {
	  $filter = $this->get_all_filter();
	  $primary = $this->get_primary_key();
	  
	  if(empty($filter) && count($this->find) === 1 && $primary === $this->find[0][0]) {
		  $val = $this->find[0][1];
		  $opera = isset($this->find[0][2]) ? $this->find[0][2] : "=";
		  return $this->stmt = new handlersocket_stmt($this->handlersocket_select($val, $opera));			  
	  } else {
		  if(empty($this->find) && count($filter) === 1 && $filter = array_pop($filter)) {
			  if($primary === $filter[0]) {
				  $val = $filter[1];
				  $opera = isset($filter[2]) ? $filter[2] : "=";
				  return $this->stmt = new handlersocket_stmt($this->handlersocket_select($val, $opera));			  
			  }
		  }
	  }
  }

/**
 * 新規用SQL文を構成し、発行する
 * @param array $args 直指定するプリペア値
 * @return $this
 */
  public function insert(){
	  $this->args = array_merge($this->set_args, $this->args);
	  $_set = join(",", $this->set);
	  if(self::$handlersocket !== false) {
		  $_set = str_replace("`", "", $_set);
		  $this->handlersocket_insert_id = $this->handlersocket_insert($this->args, $_set);
		  $this->tracking("handlersocket_insert", null);
		  return $this->reset();
	  }
	  $sql = join(" ", array(
	  		  "INSERT INTO",
	  		  $this->from,
	  		  "(" . $_set . ")",
	  		  "VALUES",
	  		  "(" . preg_replace("/[^,]++/", "?", $_set) . ")"));
	  $this->query($sql, $this->args);
	  return $this->reset();
  }


/**
 * 更新用SQL文を構成し、発行する
 * @param boolean $active_record アクティブレコードからの更新かどか
 * @return
 */
  public function update($active_record = false){
	  $this->args = array_merge($this->set_args, $this->args);
	  if(self::$handlersocket !== false) {
		  if($this->handlersocket_try_update()) {
			  $this->tracking("handlersocket_update", null);
			  return $this->reset();
		  }
	  }
	  $set = array();
	  $target = $this->from;
	  if($active_record) {
		  $target = $this->active_record_update_config();
	  }
	  foreach($this->set as $item){
		  if(strpos($item, "=") === false){
			  $item = $item . "=?";
		  }
		  $set[] = $item;
	  }
	  $halfSql = array(
		  "UPDATE",
		  $target,
		  "SET",
		  join(",", $set));
	  $sql = $this->build_where($halfSql);
	  $this->query($sql, $this->args);
	  return $this->reset();
  }

/**
 * handlersocketでの更新可能かどかをチェックし、可能であればは更新を試す
 * @return object handlersocket_stmt $stmt
 */ 
  private function handlersocket_try_update() {
	  $filter = $this->get_all_filter();
	  $primary = $this->get_primary_key();
	  $res = $val = false;
	  if(empty($filter) && count($this->find) === 1 && $primary === $this->find[0][0]) {
		  $val = $this->find[0][1];
		  $opera = isset($this->find[0][2]) ? $this->find[0][2] : "=";
	  } else {
		  if(empty($this->find) && count($filter) === 1 && $filter = array_pop($filter)) {
			  if($primary === $filter[0]) {
				  $val = $filter[1];
				  $opera = isset($filter[2]) ? $filter[2] : "=";
			  }
		  }
	  }
	  if($val !== false) {
		  $_set = str_replace("`", "", join(",", $this->set));
		  $res = $this->handlersocket_update($val, $this->args, $opera, $_set);
	  }
	  return $res;
  }

/**
 * アクティブレコードが結合相手のテーブルも更新させることがあるので、情報を設定して見る
 * @return
 */
  private function active_record_update_config() {
	  $tables = array();
	  $this->check_relation();
	  $this->set = $this->make_all_column(false);
	  if(empty($this->join)) {
		  $tables = array($this->from);
	  } else {
		  foreach($this->join as $join) {
			  $this->where($join["from_table"] . ".`".$join["from_column"] . "` = " . $join["target_table"] . ".`" . $join["target_column"] . "`");
			  $tables[] = $join["from_table"];
			  $tables[] = $join["target_table"];
		  }
	  }
	  return join(",", array_unique($tables));
  }
  
  /**
   * maybe too complex to use this multi update?
   * well, let us see that...
   */
/**
 * 多量なアクティブレコードを一つのSQL文で更新する
 * @param array アクティブレコード集 
 * @return
 */
  public function multi_update($records) {
	  $this->put($records[0]->to_array());
	  $target = $this->active_record_update_config();
	  $args = $ids_set = $ids = $set = $set_args = $sql_set = array();
	  $primary_key = $this->get_primary_key();
	  $sql_key = $this->get_from() . '.`' . $primary_key . '`';
	  $set_length = count($this->set);
	  $last = $set_length - 1;
	  foreach($records as $record) {
		  $row = $record->to_array();
		  $ids[] = $id = $row[$primary_key];
		  $ids_set[] = "?";
		  $row = array_values($row);
		  for($i = 0; $i < $set_length; $i++) {
			  $key = $this->set[$i];
			  if(!isset($set[$key])) {
				  $set[$key] = array("{$key} = (case {$sql_key}");
				  $set_args[$key] = array();
			  }
			  $set[$key][] = "when ? then ?";
			  $set_args[$key] = array_merge($set_args[$key], array($id, $row[$i]));
		  }
	  }
	  $sql = array("update", $target, "set");
	  for($i = 0; $i < $set_length; $i++) {
	  	  $key = $this->set[$i];
	  	  $set[$key][] = "else {$key} end)";
		  $sql_set[] = join(" ", $set[$key]);		  
		  $args = array_merge($args, $set_args[$key]);
	  }
	  $sql[] = join(",", $sql_set);
	  $sql[] = " where {$sql_key} in (" . join(",", $ids_set) . ")";
	  foreach($this->where_condition as $where) {
		  $sql[] = "AND ({$where})";
	  }
	  $args = array_merge($args, $ids);
	  $this->query(join(" ",$sql), $args);
	  return $this->reset();
  }


/**
 * 削除用SQL文を構成し、発行する
 * @param array $args 直指定するプリペア値 
 * @return
 */
  public function delete($args = null){
	  if(self::$handlersocket !== false) {
		  if($this->handlersocket_try_delete()) {
			  $this->tracking("handlersocket_delete", null);
			  return $this->reset();
		  }
	  }
	  $halfSql = array();
	  $halfSql[] = "DELETE FROM";
	  $halfSql[] = $this->from;
	  $sql = $this->build_where($halfSql);
	  $this->args = array_merge($this->args, (array)$args);
	  $this->query($sql, $this->args);
	  return $this->reset();
  }

  private function handlersocket_try_delete() {
	  $filter = $this->get_all_filter();
	  $primary = $this->get_primary_key();
	  $res = $val = false;
	  if(empty($filter) && count($this->find) === 1 && $primary === $this->find[0][0]) {
		  $val = $this->find[0][1];
		  $opera = isset($this->find[0][2]) ? $this->find[0][2] : "=";
	  } else {
		  if(empty($this->find) && count($filter) === 1 && $filter = array_pop($filter)) {
			  if($primary === $filter[0]) {
				  $val = $filter[1];
				  $opera = isset($filter[2]) ? $filter[2] : "=";
			  }
		  }
	  }
	  if($val !== false) {
		  $res = $this->handlersocket_delete($val, $opera);
	  }
	  return $res;
  }

/**
 * 検索条件SQL文を最終構成
 * @param string $halfSql 検索・新規・更新・削除SQLのWHERE以前部分 
 * @return
 */
  protected function build_where($halfSql){
	  foreach($this->find as $obj){
		  $this->_find($obj[0],$obj[1],$obj[2]);
	  }
	  foreach($this->get_all_filter() as $key => $obj){
		  $this->_find($obj[0],$obj[1],$obj[2]);
	  }
	  $this->_where();
	  if(!empty($this->where)){
		  $halfSql[]="WHERE ".join(" AND ",$this->where);
	  }
	  $halfSql[] = join(" ", array($this->group, $this->order));
	  if(!empty($this->limit)){
		  $halfSql[]="LIMIT ".join(",",$this->limit);
	  }
	  return join(" ",$halfSql);
  }


/**
 * クエリ発行中に一時に利用した情報を解放する
 * @return $this
 */
  protected function reset(){
	  $this->alias = $this->limit = $this->where_condition = $this->where = $this->join = $this->find = $this->set_args = $this->set = $this->args = array();
	  $this->order = $this->group = null;
	  return $this;
  }

/**
 * 数字や数字の文字列以外を全てエスケープする(Mysql)
 * @param mix $val エスケープ目標
 * @param string $quote
 * @return mix エスケープ済み値
 */
  public function escape($val, $quote = "") {
	  if(!is_numeric($val)) {
		  $val = self::$conn->quote($val);
		  if(empty($quote)) {
			  $val = substr($val, 1, -1);
		  }
	  }
	  return $val;
  }

/**
 * 目標文字列に「`」で囲む
 * @param mix $val 目標文字列
 * @param string $str
 * @return string
 */
  public function quote($str) {
	  return '`' . $str . '`';
  }

/**
 * SQL発行、デバッグ情報キャッシュ 
 * @param string SQL文
 * @param mix プリペア値
 * @return $this
 */
  public function query($sql, $data=null){
	  $this->lastQuery = array($sql,$data);
	  $this->tracking($sql, $data);
	  $this->stmt = $this->_query($sql, $data);
	  return $this;
  }


/**
 * SQL発行,prepare statement使用
 * @param string 構成したプリペアSQL
 * @param mix プリペア値
 * @return resource $res クエリ結果
 */
  private function _query($sql, $data = null) {
	  if(empty($data)){
		  $res = $stmt = self::$conn->query($sql);
	  }else{
		  if(isset($this->prepare_cache[$sql])) {
			  $stmt = $this->prepare_cache[$sql];
		  } else {
			  $stmt = self::$conn->prepare($sql);
			  if(!$stmt) {
				  $this->debug["error"][] = array($sql, $stmt->errorInfo());
				  return $stmt;
			  }
			  $this->prepare_cache[$sql] = $stmt;
		  }
		  $res = $stmt->execute($data);
	  }
	  if(!$res){
		  if($stmt === false) {
			  $this->debug["error"][] = array($sql, self::$conn->errorInfo());			  
		  } else {
			  $this->debug["error"][] = array($sql, $stmt->errorInfo());
		  }
	  }
	  return $stmt;
  }

/**
 * デバッグモードをオンにする
 * @return array
 */
  public static function track(){
	  self::$debug_mode = true;
  }

/**
 * デバッグモードをオフにする
 * @return array
 */
  public static function track_off(){
	  self::$debug_mode = false;
  }

/**
 * デバッグ情報をオフにする、ログするsqlセットは最大10件にする
 * @return array
 */
  private function tracking($sql, $data) {
	  if(self::$debug_mode) {
		  $this->debug["info"][] = array($sql, $data);
		  if($this->debug["total"] > 10) {
			  array_shift($this->debug["info"]);
		  }
		  $this->debug["total"] ++;
	  }
  }

/**
 * 最後に発行したクエリを取得する
 * @return array
 */
  public function get_last_query(){
	  return $this->lastQuery;
  }


/**
 * 最後に新規挿入したデータのプライマリキーを取得
 * @return integer
 */
  public function last_id(){
	  if(self::$handlersocket !== false) {
		  return $this->handlersocket_insert_id;
	  } else {
		  return self::$conn->lastInsertId();
	  }
  }


/**
 * 結果集からデータを一件抽出する
 * @return array
 */
  public function fetch($type = 2){
	  $record = false;
	  if($this->stmt) {
		  $record = $this->stmt->fetch($type);
	  }
	  if(!$record) {
		  $this->stmt = null;
	  }
	  return $record;
  }
  
/**
 * 結果集からデータを全件抽出する
 * @return array
 */
  public function fetchall($type = 2){
	  $res = array();
	  if($this->stmt) {
		  $res = $this->stmt->fetchAll($type);
		  $this->stmt = null;
	  }
	  return $res;
  }


/**
 * モデルのカラム情報を取得する
 * @param boolean $force 強制再取得するかしないか
 * @return array
 */
  public function columns($force = false){
	  if(empty($this->columns) || $force) {
		  $this->columns = array();
		  foreach($this->query("show columns from " . $this->from)->fetchall_as_array(2) as $row) {
			  $this->columns[] = $row["Field"];
		  }
	  }
	  return $this->columns;
  }
  
/**
 * モデルのカラム情報を整形せずに全取得する
 * @return array
 */
  public function full_columns($force = false){
	  if(empty($this->full_columns) || $force) {
		  $this->full_columns = $this->query("show columns from " . $this->from)->fetchall_as_array(2);
	  }
	  return $this->full_columns;
  }

/**
 * カラム情報を再構成する
 * @return array
 */
  public function alter_column($name, $caller){
	  $info = null;
	  $full_columns = $this->full_columns();
	  foreach($full_columns as $key => $_info) {
		  if($_info["Field"] === $name) {
			  preg_match("/(\w+)(?:\((.*)\))?()/", $_info["Type"], $m);
			  list($dummy, $_info["Type"], $_info["Ext"]) = $m;
			  if(in_array($_info["Type"], array("enum", "set"))) {
				  $_info["Ext"] = explode(",", str_replace(array("'", '"'), "", $_info["Ext"]));
			  }
			  unset($_info["Field"]);
			  $info = call_user_func($caller, $_info);
			  if(!empty($info)) {
				  $col = $this->{$name};
				  if(is_array($info["Ext"])) {
					  $info["Ext"] = "'" . join("', '", $info["Ext"]) . "'";
				  }
				  $null = $info["Null"] === "YES" ? "" : "NOT ";
				  $sql = "ALTER TABLE  {$this->from} CHANGE  {$col}  {$col} {$info['Type']}({$info['Ext']}) CHARACTER SET utf8 COLLATE utf8_general_ci {$null}NULL DEFAULT  '{$info['Default']}'";
				  $this->query($sql);
				  $info["Field"] = $name;
				  $info["Type"] = $info["Type"] . "(" . $info['Ext'] . ")";
				  $this->full_columns[$key] = $info;
			  }
			  break;
		  }
	  }
  }

/**
 * モデルのインデックス情報を取得する
 * @param boolean $force 強制再取得するかしないか
 * @return array
 */
  public function indexes($force = false) {
	  if(empty($this->indexes) || $force) {
		  $this->query("show index from " . $this->from);
		  $this->indexes = $this->fetchall_as_array(2);
	  }
	  return $this->indexes;
  }

/**
 * handlersocket使用するかしないかの設定 ※handlersocketはmysqlだけ使える
 * handlersocketは実験的にライブラリの内部で使用する、現状はプライマリキーをメインで操作する
 * 将来的にuniqueキーでも対応したいのだが、とにかく実験的に運用する
 * @return array
 */
  public static function use_handlersocket($DSN) {
	  if(class_exists("HandlerSocket") && self::$handlersocket === false && $DSN["type"] === "mysql") {
		  if(isset($DSN["handlersocket_port"])) {
			  self::$handlersocket_port = $DSN["handlersocket_port"];
		  }
		  self::$handlersocket = new HandlerSocket($DSN["host"], self::$handlersocket_port);
		  self::$handlersocket_dbname = $DSN["dbname"];
	  }
	  return self::$handlersocket;
  }

/**
 * handlersocketでの取得
 * @return array
 */
  private function handlersocket_select($primary_key, $opera = "=") {
	  $columns = $this->columns;
	  self::$handlersocket->openIndex(1, self::$handlersocket_dbname, $this->table, HandlerSocket::PRIMARY, join(",", $columns));
	  if(!is_array($primary_key)) {
		  $primary_key = array($primary_key);
		  $res = array(self::$handlersocket->executeSingle(1, $opera, $primary_key));	  
	  } else {
		  $select = array();
		  foreach($primary_key as $key) {
			  $select[] = array(1, $opera, $key);
		  }
		  $res = self::$handlersocket->executeMulti($select);	  		  
	  }
	  $_res = array();
	  foreach($res as $row) {
		  if(empty($row)) {
			  continue;
		  } else {
			  $_res[] = array_combine($columns, $row[0]);
		  }
	  }
	  return empty($_res) ? array(false) : $_res;
  }

/**
 * handlersocketでの挿入
 * @return array
 */
  private function handlersocket_insert($data, $cols = null) {
	  if($cols === null) {
		  self::$handlersocket->openIndex(3, self::$handlersocket_dbname, $this->table, HandlerSocket::PRIMARY, join(",", $this->handlersocket_cols));
	  } else {
		  self::$handlersocket->openIndex(3, self::$handlersocket_dbname, $this->table, HandlerSocket::PRIMARY, $cols);	  
	  }
	  return self::$handlersocket->executeInsert(3, $data);
  }

/**
 * handlersocketでの更新
 * @return array
 */
  private function handlersocket_update($primary_key, $data, $opera = "=", $cols = null) {
	  if($cols === null) {
		  self::$handlersocket->openIndex(2, self::$handlersocket_dbname, $this->table, HandlerSocket::PRIMARY, join(",", $this->handlersocket_cols));
	  } else {
		  self::$handlersocket->openIndex(2, self::$handlersocket_dbname, $this->table, HandlerSocket::PRIMARY, $cols);
	  }
	  return self::$handlersocket->executeUpdate(2, $opera, $primary_key, $data, 1, 0);
  }

/**
 * handlersocketでの削除
 * @return array
 */
  private function handlersocket_delete($primary_key, $opera = "=") {
	  self::$handlersocket->openIndex(4, self::$handlersocket_dbname, $this->table, HandlerSocket::PRIMARY, "");
	  if(!is_array($primary_key)) {
		  $primary_key = array($primary_key);
	  }
	  return self::$handlersocket->executeDelete(4, $opera, $primary_key);
  }

}

class handlersocket_stmt {
	private $result = array();

	public function __construct($result) {
		$this->result = array_reverse($result);
	}

	public function fetch() {
		return array_pop($this->result);
	}

	public function fetchall() {
		return array_reverse($this->result);
	}

}