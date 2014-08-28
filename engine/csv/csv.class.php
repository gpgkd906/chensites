<?php
/**
 *
 *   Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 *   author: chenhan,gpgkd906@gmail.com
 *   website: http://dev.gpgkd906.com/MyProject/
 */
class csv {
  private $fileHandler;
  private $reader = "fgetcsv";
  private $writer = "fputcsv";
  private $from = "auto";
  private $to = "utf8";
  private $autoConvert = false;
  private $header = null;
  private $cache = array();

  public function header($header) {
    $this->header = $header;
  }

  public function cache($row) {
    $this->cache[] = $row;
  }

  public function flush() {
    if(!empty($this->header)) {
      $this->write($header);
    }
    foreach($this->cache as $row) {
      $this->write($row);
    }
  }

  public function __construct(){
    $args = func_get_args();
    $this->fileHandler = $args;
  }

  public function encode($from = "auto",$to = "utf8"){
    $this->from = $from;
    $this->to = $to;
    $this->autoConvert = true;
  }
  
  public function get(){
    $row = call_user_func_array($this->reader,$this->fileHandler);
    if($this->autoConvert){
      $row = $this->convert($row);
    }
    return $row;
  }

  public function read(){
    return $this->get();
  }

  public function setReadHandler($handler){
    if(is_callable($handler)){
      $this->reader = $handler;
    }
  }
  
  public function getAll(){
    $data = array();
    while( $row = $this->get() ){
      $data[] = $row;
    }
    return $data;
  }
  
  public function readAll(){
    return $this->getAll();
  }

  public function setFileHandler($handler){
    if(gettype($handler) !== "resource"){
      throw new Exception("ファイルハンドラーではありません");
    }
    $this->fileHandler = $handler;
  }

  public function open($file) {
	  $this->fileHandler = array(fopen($file, "r+"));
  }

  public function write($data){
    if($this->autoConvert){
      $data = $this->convert($data);
    }
    return call_user_func_array($this->writer, array($this->fileHandler[0],$data));
  }

  public function setWriteHandler($handler){
    if(is_callable($handler)){
      $this->writer = $handler;
    }    
  }
  
  private function convert($data){
    if(is_array($data)){
      foreach($data as $key  => $value){
	$data[$key] = $this->convert($value);
      }
      return $data;
    }elseif(is_string($data)){
      return mb_convert_encoding($data,$this->to,$this->from);
    }else{
      return $data;
    }
  }

}