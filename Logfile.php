<?php
	class Logfile {
		const LOG_TYPE_INFORMATION 	= 'INFORMATION';

		const LOG_TYPE_WARNING 		= 'WARNING';

		const LOG_TYPE_ERROR 		= 'ERROR';
		
		private $_filename = false;

		private $_path = false;

		public function __construct($filename, $path) {
			$this->_filename = $filename;
			$this->_path = $path;
		}

		public function log($msg, $type = self::LOG_TYPE_INFORMATION) {
			$dirpath =  '/' . $this->_path . '/';
			$fullpath = $dirpath . $this->_filename;
			
			$date = date("Y-d-m"); 
			$time = date("H:i"); 
			$line = $date . ' ' . $time . ' - ' . $type . ' - ' . $msg . "\n";

			if (is_writable($dirpath)) {
				file_put_contents($fullpath, $line, FILE_APPEND | LOCK_EX);
			} else {
				throw new Exception("Log location not writable '$fullpath'", 1);
			}
		}
	}