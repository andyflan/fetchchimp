<?php
	error_reporting(E_ERROR);
	ini_set('display_errors', '1');

	/*
		@package fetchchimp
		@version 0.0.1
	 	Plugin Name: Fetchchimp
	 	Depends: autochimp
	 	Plugin URI: 
		Description: A wordpress plugin to add fetching to autochimp using Mailchimp export API
		Author: Andrew Flannery
		Version: 0.0.1
		Author URI: 
	*/

	/**
	 *	Tells wordpress to ask fetchchimp::trigger_export to parse the request which handles it if required
	 */

	add_action('parse_request', array('Fetchchimp', 'try_trigger_import')); 

	/**
 	 * 	fetchchimp class encapsulates all functionality to allow wordpress to pull data from Mailchimp
 	 *	using the Mailchimp export Api. Depends on Autochimp for all the mailchimp connectivity settings.
 	 */

	class Fetchchimp {
		static private $_field_names = array();

		static private $_merge_vars = array();

		/**
     	 * 	Checks if current URL is the trigger URL and triggers import if so
     	 *
     	 *	@return 	Fetchchimp 	self
 	 	 *	@access 	public
     	 * 	@static 	
     	 * 	@since 		0.0.1
     	 */

		static public function try_trigger_import() {
			//if (isset($_GET['myplugin']) && $_SERVER["REQUEST_URI"] == '/custom_url') {
			if ($_SERVER["REQUEST_URI"] == '/fetchchimp/trigger') {
				self::fetch();

				exit();
			}

			return self;
		}

		static public function getMailchimpApiKey() {
			return get_option(WP88_MC_APIKEY, '0');
		}

		static public function getMailchimpDataCentre() {
			$api_key = self::getMailchimpApiKey();

			list($key, $data_centre) = explode('-', $api_key);

			return $data_centre;
		}

		static public function getMailchimpListIds() {
			//return '31f2a7da27';

			$ids = explode(',', get_option(WP88_MC_LISTS)); 
			
			foreach ($ids as $index => $id) {
				if (trim($id) !== '') {
					$ids[$index] = str_replace('wp88_mc', '', trim($id));
				} else {
					unset($ids[$index]);
				}
			}

			return $ids;
		}

		static public function getMailchimpApiUrlList($list_id) {
			return 'http://' . self::getMailchimpDataCentre() . '.api.mailchimp.com/export/1.0/list?apikey=' . self::getMailchimpApiKey() . '&id=' . $list_id;
		}

		static public function getMailchimpApiUrlFields($list_id) {
			return 'http://' . self::getMailchimpDataCentre() . '.api.mailchimp.com/1.3/?method=listMergeVars&apikey=' . self::getMailchimpApiKey() . '&id=' . $list_id;
		}

		static public function getChunkSize() {
			return 4096;
		}

		static public function setFieldNames($field_names, $list_id) {
			//go and get the merge vars
			$handle = @fopen(self::getMailchimpApiUrlFields($list_id), 'r');
			
			if ($handle) {
  				$i = 0;
  
  				while (!feof($handle)) {
  					$buffer = fgets($handle, self::getChunkSize());

  					if (trim($buffer) != '') {
  						$obj = json_decode($buffer, true);

  						foreach ($obj as $field) {
      						self::$_merge_vars[$field['name']] = $field['tag'];
      					}
      			
      					$i++;
  					}
  				}
  			}

  			echo '<h3>The fields:</h3>';
			echo '<pre>';
			print_r($field_names);
			echo '</pre>';
			echo '<hr />';

  			//assign the field names
			self::$_field_names = $field_names;

			return self;
		}

		static protected function _process_record($data) {
			//locate the user
			echo '<h3>A mailchimp user:</h3>';
			echo '<pre>';
			print_r($data);
			echo '</pre>';
			echo '<hr />';
		}

		static public function fetch_list($list_id) {
			$handle = @fopen(self::getMailchimpApiUrlList($list_id), 'r');
			
			if ($handle) {
  				$i = 0;
  
  				while (!feof($handle)) {
    				$buffer = fgets($handle, self::getChunkSize());

    				if (trim($buffer) != '') {
      					$obj = json_decode($buffer);

      					if ($i == 0) {
        					//store the header row
        					self::setFieldNames($obj, $list_id);
      					} else {
        					self::_process_record($obj);
      					}
      			
      					$i++;
    				}
  				}
  		
  				fclose($handle);
			}

			return self;
		}

		static public function fetch() {
			foreach (self::getMailchimpListIds() as $list_id) {
				self::fetch_list($list_id);
			}
		}
	}