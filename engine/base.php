<?php
//traitにしたいが、5.4以降しか使えないので、しばらくは待つか......
trait base_core {
	protected $resources = array();
	protected $fake_event = array();
	
	/**
	 * event driven(fake)
	 */
	public function send_message($message, $resource = null) {
		$this->resources[$message] = $resource;
		if(isset($this->fake_event[$message])) {
			foreach($this->fake_event[$message] as $callback) {
				if(is_callable($callback)) {
					call_user_func($callback, $resource);
				}
			}
		}
	}
	
	public function on_message($message, $callback) {
		$this->fake_event[$message] = array( $callback );
	}
	
	public function add_event_listener($message, $callback) {
		if(empty($this->fake_event[$message])) {
			$this->fake_event[$message] = array();
		}
		$this->fake_event[$message][] = $callback;
	}
	
	public function read_message($message) {
		if(isset($this->resources[$message])) {
			return $this->resources[$message];
		}
	}
	
}