<?php 
//负责管理form对象，对应复数表单
class form2 {

/**
 * フォームインスタンスキャッシュプール
 * @var array 
 * @link http://
 */
	private $storage = array();

/**
 * 最後に生成したフォームのid
 * @var string 
 * @link http://
 */
	private $last_id = null;

/**
 * 自動生成id用プリフィックス
 * @var string 
 * @link http://
 */
	private $id = "myform";

/**
 * 自動生成id時用ユニークid値
 * @var integer 
 * @link http://
 */
	private $count = 0;
	
/**
 * 再帰的にエスケープ、オブジェクトに無動作
 * @param mix 
 * @return
 */
	public static function escape($data) {
		if(is_array($data)){
			foreach($data as $key => $value){
				$data[$key]=self::escape($value);
			}
			return $data;
		}elseif(is_string($data)){
			if(strpos($data, "<script") !== false) {
				$data = preg_replace("/<script[\s\S]+?<\/script>/", "", $data);
			}
			return htmlspecialchars($data,ENT_QUOTES);
		}else{
			return $data;
		}
	}

/**
 * 再帰的にアンエスケープ、オブジェクトに無動作(現状はまともに動きません、理由は不明)
 * @param mix 
 * @return
 */
	public static function unescape($data) {
		if(is_array($data)){
			foreach($data as $key => $value){
				$data[$key]=self::unescape($value);
			}
			return $data;
		}elseif(is_string($data)){
			return htmlspecialchars_decode($data,ENT_QUOTES);
		}else{
			return $data;
		}
	}
	
/**
 * 生成用文字列をクオートする
 * @param string 
 * @return
 */
	public static function quote($val){
		return "'" . self::escape($val) . "'";
	}

/**
 * 要素の属性文字列を生成
 * @param array 
 * @return
 */
	public static function attr_format($attrs) {
		$attr = array();
		foreach($attrs as $name => $attr_value) {
			$name = self::escape($name);
			$attr_value = self::quote($attr_value);
			$attr[] = "{$name}={$attr_value}";
		}
		return join(" ", $attr);
	}

/**
 * フォームオブジェクトを生成する
 * @param string 
 * @return
 */
	public function create($id = null){
		if(empty($id)){
			$id = $this->id . "_" . (++$this->count);
		}
		if(!empty($this->storage[$id])) {
			trigger_error("FormHelper:requested form_id was used,old form should be overwrite", E_USER_NOTICE);
		}
		$this->last_id=$id;
		$this->storage[$id] = new form_obj2($id);
		return $this->storage[$id];
	}

/**
 * 生成したフォームオブジェクトを取得する
 * @param string 
 * @return
 */
	public function find($id=null){
		if(empty($id)){
			$id=$this->lastId;
		}
		if(empty($this->storage[$id])){
			trigger_error("FormHelper:undefined Form",E_USER_NOTICE);
			return null;
		}
		return $this->storage[$id];
	}
	
}

//负责管理要素对象和数据对象
class form_obj2 {

/**
 * 要素インスタンスキャッシュプール
 * @var array 
 * @link http://
 */
	private $elements = array();

/**
 * フォームの属性配列
 * @var array 
 * @link http://
 */
	private $attrs=array("id" => "", "method" => "POST", "action" => "", "accept-charset" => "UTF-8", "enctype" => "multipart/form-data" );

/**
 * フォームがサブミットされたかどか
 * @var boolean 
 * @link http://
 */
	private $submitted = false;

/**
 * フォームが確認ページ生成されたかどか
 * @var boolean 
 * @link http://
 */
	private $confirmed = false;

/**
 * フォーム処理が完成されたかどか
 * @var boolean 
 * @link http://
 */
	private $completed = false;

/**
 * サブミットされたデータはバリデーションされたかどか
 * @var boolean 
 * @link http://
 */
	private $validated = false;

/**
 * バリデーション処理の結果
 * @var boolean 
 * @link http://
 */
	private $no_error = true;

/**
 * ライブラリ用データも含む、全ての入力データ
 * @var array 
 * @link http://
 */
	private $all_data = null;

/**
 * ユーザ入力データ
 * @var array 
 * @link http://
 */
	private $request_data = null;

/**
 * 追加するデータ
 * @var array 
 * @link http://
 */
	private $preprocess_data = array();

/**
 * ライブラリ用データのキーの配列
 * @var array 
 * @link http://
 */
	private $except = array("form_id" => true, "form_mode" => true, "submit" => true, "reset" => true);

/**
 * メール用ハンドラ
 * @var resource 
 * @link http://
 */
	private $mail_handler = null;


/**
 * 構造器、フォームオブジェクトを生成
 * @param string 
 * @return
 */
	public function __construct($id){
		$this->id=$id;
		$this->append("hidden", "form_id", $id);
		$this->append("hidden", "form_mode", "confirm");
		$this->append("submit", "submit", "確認する")->class("btn btn-success");
		$this->append("reset", "reset", "リセット")->class("btn btn-danger");
		$this->set("id", $id);
	}


/**
 * メールハンドラを設定(必須ではない)
 * @param object 
 * @return
 */
	public function use_mailer($mailer) {
		$this->mail_handler = $mailer;
	}


/**
 * フォームの開始タグ出力
 * @return
 */
	public function start() {
		$attr = form2::attr_format($this->attrs);
		$html = array("<form {$attr}>",
			$this->form_id, $this->form_mode
					   );
		echo join(PHP_EOL, $html);
	}


/**
 * フォームの閉じタグ出力
 * @return
 */
	public function end() {
		echo "</form>";
	}


/**
 * フォームの属性設定
 * @param string $attr 属性名
 * @param mix $val 属性値
 * @return
 */
	public function set($attr, $val) {
		$this->attrs[$attr] = $val;
	}
	

/**
 * フォームに要素を追加
 * @param string $type 要素タイプ
 * @param integer $name 要素ネーム
 * @param mix $val 要素の値
 * @param resource 要素の初期値
 * @return object
 */
	public function append($type = "text", $name, $val = null, $default = null) {
		$this->elements[$name] = new form_element($this, $name, $type, $val, $default);
		if($type == "file") {
			$this->set("enctype", 'multipart/form-data');
		}
		return $this->elements[$name];
	}
	
	public function remove($name) {
		unset($this->elements[$name]);
	}
	
/**
 * ライブラリ用データも含む、全ての入力データを返す
 * @return
 */
	private function all_data() {
		if($this->all_data == null) {
			$data = array();
			switch(strtolower($this->attrs["method"])) {
				case "post":
					if(!empty($_FILES)) {
						foreach($_FILES as $key => $file) {
							if($file["size"] != 0) {
								$data[$key] = $file;								
							}
						}
					}
					$data = array_merge($data, $_POST); break;
				case "get" : 
					$data = $_GET; break;
			}
			$this->all_data = empty($data) ? $this->preprocess_data : array_merge($this->preprocess_data, $data);
		}
		return $this->all_data;
	}


/**
 * ユーザ入力データを返す、キーを指定する場合は指定されたデータが返されるが、その以外の場合は全データ返される。
 * @param string $name データキー
 * @return
 */
	public function get_data($name = null) {
		if($this->request_data == null) {
			$data = $this->all_data();
			foreach($this->except as $key => $except) {
				unset($data[$key]);
			}
			$this->request_data = $data;
		}
		if($name === null) {
			return $this->request_data;
		} else {
			if(isset($this->request_data[$name])) {
				return $this->request_data[$name];
			}
		}
		return null;
	}


/**
 * データ値を上書きする
 * @param string $name 上書きしたいデータのキー
 * @param mix $val 上書きしたいデータの値
 * @return
 */
	public function set_data($name, $val) {
		if(!empty($this->request_data)) {
			$this->request_data[$name] = $val;
			$this->all_data[$name] = $val;
		} else {
			$this->preprocess_data[$name] = $val;
		}
		if(isset($this->elements[$name])) {
			$this->elements[$name]->value($val);
		}
	}


/**
 * データを一気に上書きする
 * @param array $data 上書きするデータ
 * @return
 */
	public function assign($data) {
		if(is_array($data)) {
			foreach($data as $name => $val) {
				$this->set_data($name, $val);
			}
		}
	}

/**
 * データを一気に廃棄する
 * @param array $data 上書きするデータ
 * @return
 */
	public function clear() {
		switch(strtolower($this->attrs["method"])) {
			case "post": $_POST = array_diff_key($_POST, $this->elements); break;
			case "get" : $_GET = array_diff_key($_GET, $this->elements); break;
		}
		$this->all_data = null;
		$this->request_data = null;
		$this->each(function($name, $element) {
				$element->clear();				
			});
	}

/**
 * 要素をループして操作する
 * @param array $call 要素に対する処理
 * @return
 */
	public function each($call) {
		$elements = array_diff_key($this->elements, $this->except);
		foreach($elements as $name => $element) {
			call_user_func($call, $name, $element);
		}
	}


/**
 * バリデーション処理、リセットデータの検知
 * @return
 */
	public function validata() {
		if($this->validated) {
			return $this->no_error;
		}
		$data = $this->all_data();
		if(isset($data["reset"])) {
			return $this->force_error();
		}
		$elements = array_diff_key($this->elements, $this->except);
		foreach($elements as $name => $element) {
			if(isset($data[$name])) {
				$element->value($data[$name]);
			}
			if($element->validata() === false) {
				$this->no_error = false;
			}
		}
		$this->validated = true;
		return $this->no_error;
	}


/**
 * 強制エラー
 * @return
 */
	public function force_error() {
		return $this->no_error = false;
	}
	

/**
 * サブミットされたかどかのチェック
 * @return
 */
	public function submitted() {
		if(!$this->submitted) {
			$data = $this->all_data();
			if(isset($data["form_id"]) && $data["form_id"] == $this->id) {
				$this->submitted = true;
			}
		}
		return $this->submitted;
	}
	

/**
 * 確認ページ処理されるかどかのチェック
 * @return
 */
	public function confirmed() {
		if(!$this->confirmed) {
			$data = $this->all_data();
			if(isset($data["form_id"]) && $data["form_id"] == $this->id && $data["form_mode"] == "confirm" && !isset($data["reset"])) {
				$this->confirmed = true;
			}
		}
		return $this->confirmed;		
	}


/**
 * 完了処理をされるかどかのチェック
 * @return
 */
	public function completed() {
		if(!$this->completed) {
			$data = $this->all_data();
			if(isset($data["form_id"]) && $data["form_id"] == $this->id && $data["form_mode"] == "complete" && !isset($data["reset"])) {
				$this->completed = true;
			}
		}
		return $this->completed;		
	}


/**
 * サブミット処理
 * @param closure $callback コールバック 
 * @return
 */
	public function submit($callback = null) {
		if($this->submitted()) {
			if(!$this->validata()) {
				return false;
			}
			$data = $this->get_data();
			if(is_callable($callback)) {
				return call_user_func($callback, $data, $this, $this->mail_handler);
			}
		}
	}
	
/**
 * 確認及び完了処理
 * @param closure コールバック
 * @param closure コールバック
 * @return
 */
	public function confirm($confirm = null, $complete = null) {
		if($this->submitted()) {
			if(!$this->validata()) {
				return false;
			}
			$data = $this->get_data();
			//完了処理かどか?
			if(is_callable($complete) && ($this->completed() || $confirm === false)) {
				return call_user_func($complete, $data, $this, $this->mail_handler);
			}
			//確認処理かどか?
			//$confirm : false =>　確認ページ生成しない, null => 確認ページ生成するが、callbackは実行しない
			if($confirm !== false && $this->confirmed()) {
				$this->confirm_config();
				if(is_callable($confirm)) {
					call_user_func($confirm, $data, $this);
				}
				return $this;
			}
		}
	}


/**
 * 確認ページ生成時必要の処理
 * @return
 */
	private function confirm_config() {
		$this->each(function($name, $element) {
				$element->confirm_mode();
			});
		$this->elements["form_mode"]->value("complete");
		$this->elements["submit"]->value("送信する");
		$this->elements["reset"]->type("submit")->value("戻る");
	}


/**
 * 生成した要素をアクセスする
 * @param string $name 要素名
 * @return
 */
	public function __get($name) {
		if(isset($this->elements[$name])) {
			return $this->elements[$name];
		}
	}


/**
 * 要素追加の部分関数
 * @param string $name 要素名
 * @param array $param 部分関数名
 * @return
 */
	public function __call($name, $param) {
		if(strpos($name, "add_") !== false) {
			$type = str_replace("add_", "", $name);
			array_unshift($param, $type);
			return call_user_func_array(array($this, "append"), $param);
		}
	}
}

//负责表示要素对象
class form_element {

/**
 * フォームインスタンスの参照
 * @var resource 
 * @link http://
 */
	private $form = null;

/**
 * 要素名
 * @var string 
 * @link http://
 */
	private $name = null;

/**
 * 要素タイプ
 * @var string 
 * @link http://
 */
	private $type = null;

/**
 * 要素値(候補含む、checkbox,radio,selectなど)
 * @var resource 
 * @link http://
 */
	private $val = null;

/**
 * 要素値
 * @var resource 
 * @link http://
 */
	private $value = null;

/**
 * 出力モード
 * @var string 
 * @link http://
 */
	private $mode = "input";

/**
 * インプットモードカスタマイズ
 * @var resource 
 * @link http://
 */
	private $input_formater = null;

/**
 * 確認モードカスタマイズ
 * @var resource 
 * @link http://
 */
	private $confirm_formater = null;

/**
 * バリデーションルールキュー
 * @var array 
 * @link http://
 */
	private $queue = array();

/**
 * バリデーションエラーメッセージ
 * @var string 
 * @link http://
 */
	public $error = "";

/**
 * 要素の属性
 * @var array 
 * @link http://
 */
	private $attrs = array();
	

/**
 * 要素の属性アクセサー、値の設定または参照
 * @param string $name 属性名
 * @param array $value 属性値
 * @return
 */
	public function __call($name, $value) {
		if(empty($value)) {
			return $this->get($name);
		} else {
			return $this->set($name, $value[0]);
		}
	}


/**
 * 要素の属性アクセサー、値の設定
 * @param string $name 属性名
 * @param array $value 属性値
 * @return
 */
	public function set($name, $value) {
		if(property_exists($this, $name)) {
			$this->{$name} = $value;
		} else {
			$this->attrs[$name] = $value;
		}
		return $this;
	}


/**
 * 要素の属性アクセサー、値の参照
 * @param string $name 属性名
 * @return
 */
	public function get($name) {
		if(isset($this->attrs[$name])) {
			return $this->attrs[$name];
		} elseif(isset($this->{$name})) {
			return $this->{$name};
		} elseif($name === "value") {
			return $this->get_value();
		}
	}

/**
 * 要素のclass追加
 * @param array $class class名
 * @return
 */
	public function add_class($class) {
		$cls = explode(" ", $this->get("class"));
		if(!in_array($class, $cls)) {
			$cls[] = $class;
		}
		$cls = join(" ", $cls);
		$this->set("class", $cls);
		return $this;
	}

/**
 * 要素のclass削除
 * @param array $class class名
 * @return
 */
	public function remove_class($class) {
		$cls = explode(" ", $this->get("class"));
		if(in_array($class, $cls)) {
			$cls = array_diff($cls, array($class));
		}
		$cls = join(" ", $cls);
		$this->set("class", $cls);
		return $this;
	}

/**
 * 要素値の参照
 * @return
 */
	private function get_value() {
		return isset($this->value) ? $this->value : $this->form->get_data($this->name);
	}

/**
 * 要素値の廃棄
 * @return
 */
	public function clear() {
		$this->value = null;
	}

/**
 * 要素の生成
 * @param object $form 親フォームのインスタンス参照
 * @param string $name 要素名
 * @param integer $type 要素タイプ
 * @param mix $val 要素値(checkbox, radio, selectなど用)
 * @param string/integer $default 要素の初期値 
 * @return
 */
	public function __construct($form, $name, $type, $val = null, $default = null) {
		$this->form = $form;
		$this->name = $name;
		$this->type = $type;
		$this->val = $val;
		if($default !== null ) {
			$this->value = $default;
		}
	}
	

/**
 * バリデーションルール設定
 * @param integer $rule バリデーションチェッカールール値
 * @param string $error_message エラーメッセージ
 * @return
 */
	public function must_be($rule, $error_message = null) {
		$this->queue[] = array(
			"rule" => $rule, "message" => $error_message
							   );
		return $this;
	}

/**
 * バリデーションルールを解除する
 * @param integer $rule バリデーションチェッカールール値
 * @return
 */
	public function remove_must($rule = null) {
		if(empty($rule)) {
			$this->queue = array();
		} else {
			unset($this->queue[$rule]);
		}
		return $this;
	}
	
/**
 * バリデーション処理
 * @return
 */
	public function validata() {
		$value = $this->get_value();
		foreach($this->queue as $set) {
			$result = checker::myFormCheck($value, $set);
			if($result["status"] == "error") {
				$this->error = "<span class='myform_error'>".$result["message"]."</span>";
				return false;
			}
		}
		return true;
	}

/**
 * 要素を強制的にエラーにする
 * @param string $error_message
 * @return
 */
	public function force_error($error_message = null) {
		$this->error = "<span class='myform_error'>" . $error_message . "</span>";
		$this->form->force_error();
		return $this;
	}
	
/**
 * 要素を確認モードにする
 * @return
 */
	public function confirm_mode() {
		$this->mode = "confirm";
		return $this;
	}


/**
 * 要素をインプットモードにする
 * @return
 */
	public function input_mode() {
		$this->mode = "input";
		return $this;
	}
	

/**
 * インプットカスタマイズを設定する
 * @param closure $formater インプットカスタマイズ
 * @return
 */
	public function input($formater) {
		$this->input_formater = $formater;
		return $this;
	}
	

/**
 * 確認カスタマイズを設定する
 * @param closure $formater 確認カスタマイズ
 * @return
 */
	public function confirm($formater) {
		$this->confirm_formater = $formater;
		return $this;
	}
	

/**
 * 要素を出力する
 * @return
 */
	public function __toString() {
		$value = $this->get_value();
		$value = form2::escape($value);
		switch($this->mode) {
			case "input": return $this->input_element($value); break;
			case "confirm": return $this->confirm_element($value); break;
		}
	}


/**
 * 要素をインプットモードで出力する
 * @param string/integer $value 要素値
 * @return
 */
	private function input_element($value) {
		if(is_callable($this->input_formater)) {
			return call_user_func_array($this->input_formater, array($this->val, $value, $this->attrs));
		}
		$attr = form2::attr_format($this->attrs);
		switch($this->type) {
			case "checkbox": return $this->make_checkbox($value, $attr); break;
			case "radio": return $this->make_radio($value, $attr); break;
			case "select": return $this->make_select($value, $attr); break;
			case "textarea": return $this->make_textarea($value, $attr); break;
			case "file": return $this->make_file($value, $attr); break;
			default: return $this->make_default($value, $attr); break;
		}
	}


/**
 * checkboxのインプットモード
 * @param string/integer $value 要素値
 * @param array 要素の属性
 * @return
 */
	private function make_checkbox($value = null, $attr) {
		$html = array();
		foreach($this->val as $key => $val) {
			if($value !== null && in_array($val, $value)) {
				$html[] = "<label class='form_label form_checkbox'><input type='checkbox' name='{$this->name}[]' value='{$val}' {$attr} checked>" . $key . "</label>";
			} else {
				$html[] = "<label class='form_label form_checkbox'><input type='checkbox' name='{$this->name}[]' value='{$val}' {$attr}>" . $key . "</label>";
			}
		}
		return join("", $html);
	}


/**
 * radioのインプットモード
 * @param string/integer $value 要素値
 * @param array 要素の属性
 * @return
 */
	private function make_radio($value = null, $attr) {
		$html = array();
		foreach($this->val as $key => $val) {
			if($value !== null && $val == $value) {
				$html[] = "<label class='form_label form_radio'><input type='radio' name='{$this->name}' value='{$val}' {$attr} checked>" . $key . "</label>";
			} else {
				$html[] = "<label class='form_label form_radio'><input type='radio' name='{$this->name}' value='{$val}' {$attr}>" . $key . "</label>";
			}
		}
		return join("", $html);
	}


/**
 * selectのインプットモード
 * @param string/integer $value 要素値
 * @param array 要素の属性
 * @return
 */
	private function make_select($value = null, $attr) {
		$html = array("<select name='{$this->name}' {$attr}>");
		foreach($this->val as $key => $val) {
			if($value !== null && $val == $value) {
				$html[] = "<option value='{$val}' selected>" . $key . "</option>";
			} else {
				$html[] = "<option value='{$val}'>" . $key . "</option>";
			}
		}
		$html[] = "</select>";
		return join("", $html);
	}


/**
 * テキストエリアのインプットモード
 * @param string/integer $value 要素値
 * @param array 要素の属性
 * @return
 */
	private function make_textarea($value = null, $attr) {
		if($value === null) {
			$value = $this->val;
		}
		$html = array(
			"<textarea name='{$this->name}' {$attr}>",
			$value,
			"</textarea>"
					  );
		return join("", $html);		
	}


/**
 * 一般要素のインプットモード
 * @param string/integer $value 要素値
 * @param array 要素の属性
 * @return
 */
	private function make_default($value = null, $attr) {
		if($value === null) {
			$value = $this->val;
		}
		$html =	"<input type='{$this->type}' name='{$this->name}' value='{$value}' {$attr}>";
		return $html;
	}

/**
 * ファイル要素のインプットモード
 * @param string/integer $value 要素値
 * @param array 要素の属性
 * @return
 */
	private function make_file($value = null, $attr) {
		if($value === null) {
			$value = $this->val;
		}
		if(empty($value)) {
			$value = "";
		}
		if(is_array($value) ) {
			$html = array("<label class='form_label form_{$this->type}'>");
			foreach($value as $key => $val) {
				$html[] = "<input type='hidden' name='{$this->name}[{$key}]' value='{$val}'>";
			}
			if(isset($value["link"])) {
				$html[] = "<img src='{$value['link']}'></label>";
			}
			$html[] = "<input type='{$this->type}' name='{$this->name}' {$attr}>";
			$html = join("", $html);
		} else {
			$html =	"<input type='{$this->type}' name='{$this->name}' value='{$value}' {$attr}>";
		}
		return $html;
	}
	

/**
 * 要素の確認モード
 * @param string/integer $value 要素値
 * @return
 */
	private function confirm_element($value = null) {
		if(is_callable($this->confirm_formater)) {
			return call_user_func_array($this->confirm_formater, array($this->val, $value, $this->attrs));
		}
		switch($this->type) {
			case "checkbox": return $this->confirm_multi_label($value); break;
			case "radio": case "select": return $this->confirm_single_label($value); break;
			case "password": return $this->confirm_password($value); break;
			case "file" : return $this->confirm_file($value); break;
			case "textarea": return $this->confirm_textarea($value); break;
			default: return $this->confirm_default($value); break;
		}
	}		


/**
 * 複数候補の選択要素の確認モード
 * @param string/integer $value 要素値
 * @return
 */
	private function confirm_multi_label($value) {
		$html = array();
		foreach($this->val as $key => $val) {
			if($value !== null && in_array($val, $value)) {
				$html[] = "<label class='form_label form_{$this->type}'><input type='hidden' name='{$this->name}[]' value='{$val}'>" . nl2br($key) . "</label>";
			}
		}
		return join("", $html);
	}
	

/**
 * 複数候補の単一選択要素の確認モード
 * @param string/integer $value 要素値
 * @return
 */
	private function confirm_single_label($value) {
		$html = "";
		foreach($this->val as $key => $val) {
			if($value !== null && $val == $value) {
				$html = "<label class='form_label form_{$this->type}'><input type='hidden' name='{$this->name}' value='{$value}'>" . nl2br($key) . "</label>";
				break;
			}
		}
		return $html;
	}
	
/**
 * パスワードの確認モード
 * @param string/integer $value 要素値
 * @return
 */
	private function confirm_password($value) {
		return "<label class='form_label form_{$this->type}'><input type='hidden' name='{$this->name}' value='{$value}'>" . str_pad("", strlen($value), "*") . "</label>";
	}

/**
 * 単一候補の単一選択要素の確認モード
 * @param string/integer $value 要素値
 * @return
 */
	private function confirm_default($value) {
		return "<label class='form_label form_{$this->type}'><input type='hidden' name='{$this->name}' value='{$value}'>" . nl2br($value) . "</label>";
	}

/**
 * textarea要素の確認モード(javascript editor運用の配慮)
 * @param string/integer $value 要素値
 * @return
 */
	private function confirm_textarea($value) {
		return "<div><label class='form_label form_{$this->type}'><input type='hidden' name='{$this->name}' value='{$value}'>" . nl2br(htmlspecialchars_decode($value ,ENT_QUOTES)) . "</label></div>";
	}


/**
 * file要素の確認モード
 * @param string/integer $value 要素値
 * @return
 */
	private function confirm_file($value) {
		if(is_array($value)) {
			$html = array("<label class='form_label form_{$this->type}'>");
			foreach($value as $key => $val) {
				$html[] = "<input type='hidden' name='{$this->name}[{$key}]' value='{$val}'>";
			}
			if(isset($value["link"])) {
				$html[] = "<img src='{$value['link']}'></label>";
			}
			return join("", $html);
		} else {
			return "<label class='form_label form_{$this->type}'><input type='hidden' name='{$this->name}' value='{$value}'>" . nl2br($value) . "</label>";
		}
	}

}

class checker {
  //check handler number
  const Exists=0;
  const Number=1;
  const Jtext=2;
  const Kana=3;
  const Email=4;
  const PostZip=5;
  const Tel=6;
  const Int=7;
  const Float=8;
  const Ip=9;
  const Url=10;
  const Boolean=11;
  const AlphaNum=12;
  const Jname=13;
  const TagCheck=14;
  const Roma=15;
  const Date=16;
  const NoTag=17;
  //filter
  private static $validate=array(
			  "bool"=>258,
			  "mail"=>274,
			  "email"=>274,
			  "float"=>259,
			  "int"=>257,
			  "ip"=>275,
			  "url"=>273,
			  "regexp"=>272,
			  );
  private static $sanitize=array(
			  "mail"=>517,
			  "encode"=>514,
			  "magic_quotes"=>521,
			  "float"=>520,
			  "int"=>519,
			  "html"=>515,
			  "string"=>513,
			  "url"=>518,
			  );
  private static $flag=array(
		      "strip_low"=>4,
		      "strip_hign"=>8,
		      "fraction"=>4096,
		      "thousand"=>8192,
		      "scientific"=>16384,
		      "quotes"=>128,
		      "encode_low"=>16,
		      "encode_hign"=>32,
		      "amp"=>64,
		      "octal"=>1,
		      "hex"=>2,
		      "IPv4"=>1048576,
		      "IPv6"=>2097152,
		      "no_private static "=>8388608,
		      "no_res"=>4194304,
		      "host"=>131072,
		      "path"=>262144,
		      "required"=>524288,
		      "return_null"=>134217728,
		      "return_array"=>60108864,
		      "require_array"=>16777216,
		      );
  private static $reg=array(                            
                            "name"=>"/^[あ-んァ-ヾ一-龠\s]+$/",
                            "mail"=>"/^[\w\-\.]+@[\w-]+(\.[\w]+)+$/",
                            "email"=>"/^[\w\-\.]+@[\w-]+(\.[\w]+)+$/",
                            "kana"=>"/^[あ-んァ-ヾ\s]*$/",
                            "url"=>"/^https?:\/\/([^/:]+)(:(\d+))?(\/.*)?$/",
                            "id"=>"/^[a-zA-Z0-9]+$/",
                            "roma"=>"/^[A-Za-z\s]+$/",
                            "number"=>"/^\s*(\d+([-\s]\d+)*)$/",
                            "Jtext"=>"/^[あ-んァ-ヾ一-龠\w\s,、，。,.@\-]*$/",
                            "filter"=>"/<[^\d](?:\"[^\"]*\"|'[^']*'|[^'\">*])*>/",
                            );
  
  public static function check($val,$flag=null){
    if($flag===null){
      trigger_error("YOU SHOULD ASSIGNATION A FLAG FOR CHECKER",E_USER_NOTICE);
      return;
    }
    switch(true){
    case isset(self::$validate[$flag]):
      return self::validate($val,$flag);
      break;
    case isset(self::$reg[$flag]):
      return self::regCheck($val,$flag);
      break;
    default:
      return self::regFilter($val);
      break;
    }
  }

  public static function notNull($val){
    if(empty($val)){
      return false;
    }
    return true;
  }
  
  private static function validate($val,$flag){
    $_val=filter_var($val,self::$validate[$flag]);
    if($_val!==$val){
      return false;
    }
    return true;
  }

  private static function regCheck($val,$flag){
    if(!preg_match(self::$reg[$flag],$val)){
      return false;
    }
    return true;
  }

  private static function regFilter($val){
    if(!preg_match(self::$reg["filter"],$val)){
      return false;
    }
    return true;
  }

  public static function simple($val, $rule){
    $set = array("rule" => $rule);
    $res = self::myFormCheck($val, $set);
    if($res["status"] === "error") {
      return $res["message"];
    }
  }
  
  public static function package($post, $rules){
    $error = array();
    foreach($rules as $key => $rule) {
      if(!empty($post[$key]) && $res = self::simple($post[$key] ,$rule)) {
        $error[$key] = $res;
        unset($post[$key]);
      }
    }
    return array($post, $error);
  }

  public static function myFormCheck($val,$set){
    $res=array(
               "status"=>"success",
               "message"=>$set["message"]
	       );
    switch($set["rule"]){
    case self::Exists:
      if(!self::notNull($val)){
        $res["status"]="error";
        $res["message"]=$res["message"]?$res["message"]:"※必須項目";
      }
      break;
    case self::Number:
      if(!self::regCheck($val,"number")){
        $res["status"]="error";
        $res["message"]=$res["message"]?$res["message"]:"※数字を入力してください";
      }
      break;
    case self::Jtext:
      if(!self::regCheck($val,"Jtext")){
        $res["status"]="error";
        $res["message"]=$res["message"]?$res["message"]:"※日本語文章を入力してください";
      }
      break;
    case self::Kana:
      if(!self::regCheck($val,"kana")){
        $res["status"]="error";
        $res["message"]=$res["message"]?$res["message"]:"※ふりがなを入力してください";
      }
      break;
    case self::Email:
      if(!self::validate($val,"mail")){
        $res["status"]="error";
        $res["message"]=$res["message"]?$res["message"]:"※不正なメールアドレス形式";
      }
      break;
    case self::PostZip:
      if(!self::regCheck($val,"number")){
        $res["status"]="error";
        $res["message"]=$res["message"]?$res["message"]:"※不正な郵便番号";
      }
      break;
    case self::Tel:
      if(!self::regCheck($val,"number")){
        $res["status"]="error";
        $res["message"]=$res["message"]?$res["message"]:"※不正な電話番号";
      }
      break;
    case self::Int:
      if(!self::validate($val,"int")){
        $res["status"]="error";
        $res["message"]=$res["message"]?$res["message"]:"※不正な入力値";
      }      
      break;
    case self::Float:
      if(!self::validate($val,"float")){
        $res["status"]="error";
        $res["message"]=$res["message"]?$res["message"]:"※不正な入力値";
      }            
      break;
    case self::Ip:
      if(!self::validate($val,"ip")){
        $res["status"]="error";
        $res["message"]=$res["message"]?$res["message"]:"※不正なIPアドレス";
      }                  
      break;
    case self::Url:
      if(!self::validate($val,"url")){
        $res["status"]="error";
        $res["message"]=$res["message"]?$res["message"]:"※不正なURL";
      }                        
      break;
    case self::Boolean:
      if(!self::validate($val,"url")){
        $res["status"]="error";
        $res["message"]=$res["message"]?$res["message"]:"※不正な値";
      }                              
      break;
    case self::AlphaNum:
      if(!self::regCheck($val,"id")){
        $res["status"]="error";
        $res["message"]=$res["message"]?$res["message"]:"※英数字だけを入力してください";
      }
      break;
    case self::Roma:
      if(!self::regCheck($val,"roma")){
        $res["status"]="error";
        $res["message"]=$res["message"]?$res["message"]:"※ローマ字を入力してください";
      }
      break;
    case self::Jname:
      if(!self::regCheck($val,"name")){
        $res["status"]="error";
        $res["message"]=$res["message"]?$res["message"]:"※漢字かがなを入力してください";
      }
      break;
    case self::Date:
      $vals = preg_split("/年|月|日|-|\//", $val);
      foreach($vals as $v) {
        if(empty($v)) {
          continue;
        }
        if(!self::regCheck($v,"number")){
          $res["status"]="error";
          $res["message"]=$res["message"]?$res["message"]:"※正しい日付を入力してください";
          break;
        }
      }
      break;
    case self::TagCheck:
    default:
      if(is_callable($set["rule"])){
        $res=call_user_func_array($set["rule"],$val);
      }elseif(!self::regFilter($val)){
        $res["status"]="error";
        $res["message"]=$res["message"]?$res["message"]:"※不正な文章";	
      }
      break;
    }
    return $res;
  }
  
}