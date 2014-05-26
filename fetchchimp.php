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

		static public function getMailchimpApiUrl($list_id) {
			return 'http://' . self::getMailchimpDataCentre() . '.api.mailchimp.com/export/1.0/list?apikey=' . self::getMailchimpApiKey() . '&id=' . $list_id;
		}

		static public function getChunkSize() {
			return 4096;
		}

		static public function fetch_list($list_id) {
			$handle = @fopen(self::getMailchimpApiUrl($list_id), 'r');
			
			if ($handle) {
  				$i = 0;
  				$header = array();
  
  				while (!feof($handle)) {
    				$buffer = fgets($handle, self::getChunkSize());

    				if (trim($buffer)!='') {
      					$obj = json_decode($buffer);

      					if ($i==0) {
        					//store the header row
        					$header = $obj;
      					} else {
        					//echo, write to a file, queue a job, etc.
        					echo $header[0].': '.$obj[0]."\n";
      					}
      			
      					$i++;
    				}
  				}
  		
  				fclose($handle);
			}

			return self;
		}

		static public function fetch() {
			foreach(self::getMailchimpListIds() as $list_id) {
				self::fetch_list($list_id);
			}
		}
	}