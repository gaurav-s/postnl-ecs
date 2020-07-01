<?php
	class Failederrors {
		protected $OrderiD;
		protected $arrayObject = array();
        
        public function __construct() {
			
		}
        
        // Add new element
        
        
        public function addError($errror) {
			array_push($this->arrayObject, $errror);
        }
        
		public function get_errors() {
			return $this->arrayObject;
		}
		public function set_orderID($OrderiD) {
			$this->OrderiD = $OrderiD;
        }
        
		public function get_orderID() {
			return $this->OrderiD;
		}

	}