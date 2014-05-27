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

	include_once "Logfile.php";
	include_once "Model/Cimyfield.php";

	/**
	 *	Tells wordpress to ask fetchchimp::trigger_export to parse the request which handles it if required
	 */

	add_action('parse_request', array('Fetchchimp', 'try_trigger_import')); 

	/**
 	 * 	fetchchimp class encapsulates all functionality to allow wordpress to pull data from Mailchimp
 	 *	using the Mailchimp export Api. Depends on Autochimp for all the mailchimp connectivity settings.
 	 */

	class Fetchchimp {
		static private $_field_labels = array();

		static private $_merge_vars = array();

		static private $_logfile;

		static function log($msg, $type = Logfile::LOG_TYPE_INFORMATION) {
			$ip = self::getClientIP();

			if (self::$_logfile == null) {
				self::$_logfile = new Logfile('activity.log', WP_PLUGIN_DIR . '/fetchchimp/log');
			}

			self::$_logfile->log($msg . " ($ip)", $type);
		}

		/**
     	 * 	Checks if current URL is the trigger URL and triggers import if so
     	 *
     	 *	@return 	Fetchchimp 	self
 	 	 *	@access 	public
     	 * 	@static 	
     	 * 	@since 		0.0.1
     	 */

		static public function try_trigger_import() {
			//if our path is being used then do some work and exit
			if ($_SERVER["REQUEST_URI"] == '/fetchchimp/trigger') {
				self::fetch();
				
				self::log('Mailchimp import complete');

				exit();
			}
		}

		static public function getClientIP() {
			return $_SERVER['REMOTE_ADDR'];
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

		/**
		 * 	Returns a default chunksize for iterating over mailchimp payload. Included for future compatibility
		 *
		 *	@return 	int 	The size in bytes
		 *	@access 	public
     	 * 	@static 	
     	 * 	@since 		0.0.1
		 */

		static public function getChunkSize() {
			return 4096;
		}

		/**
		 * 	Takes an array of field labels and stored them in a class parameter. Also takes a mailchimp list_id and
		 *	calls the mailchimp api listMergeVars method to get the field names, rather than the labels, from mailchimp
		 *
		 *	@param 		array 	$field_labels 	An array of field labels
		 *	@param 		string 	$list_id 		A mailchimp list id, used to fetch the field names from mailchimp
		 *
		 *	@return 	null
		 *	@access 	protected
     	 * 	@static 	
     	 * 	@since 		0.0.1
		 */

		static public function setFieldLabels($field_labels, $list_id) {
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

  			//assign the field names
			self::$_field_labels = $field_labels;
		}

		/**
		 * 	Take an $index and return a field label for it, using _field_labels, which are indexed in the same way as
		 * 	the field values, from where the $index param is derived
		 *
		 *	@param 		int 	$index 		The index to turn into a field label
		 *
		 *	@return 	mixed 	Field label if found, false if not
		 *	@access 	protected
     	 * 	@static 	
     	 * 	@since 		0.0.1
		 */

		static protected function _getFieldLabelFromIndex($index) {
			return (isset(self::$_field_labels[$index])) ? self::$_field_labels[$index] : false;
		}

		/**
		 * 	Take a label and return a field name for it, using _merge_vars (Mailchimp field names)
		 *
		 *	@param 		string 	$label 		The label to turn into a field name
		 *
		 *	@return 	mixed 	Field name if found, false if not
		 *	@access 	protected
     	 * 	@static 	
     	 * 	@since 		0.0.1
		 */

		static protected function _getFieldNameFromLabel($label) {
			return (isset(self::$_merge_vars[$label])) ? self::$_merge_vars[$label] : false;
		}

		/**
		 * 	Takes index and returns a field name for that index, uses the _field_labels array which is indexed
		 *	in the same way as the field values for a record which is where the $index is derived
		 *
		 *	@param 		int 	$index 		The index of the field value, which corresponds to the index of the _field_label
		 *
		 *	@return 	mixed 	Field name if found, false if not
		 *	@access 	protected
     	 * 	@static 	
     	 * 	@since 		0.0.1
		 */

		static protected function _getFieldNameFromIndex($index) {
			//turn the index into a label
			if ($label = self::_getFieldLabelFromIndex($index)) {
				//turn a label into a field name a return it
				if ($field_name = self::_getFieldNameFromLabel($label)) {
					return $field_name;
				}
			}
			
			return false;
		}

		/**
		 * 	Takes an option value and returns sql to select the option name
		 *
		 *	@param 		string 	$value 		Value to be used to get the option name
		 *
		 *	@return 	string 	The sql to be used in the query
		 *	@access 	protected
     	 * 	@static 	
     	 * 	@since 		0.0.1
		 */

		static protected function _getOptionByValueSql($value) {
			$sql = "
				SELECT 
					option_name
				FROM
					{$wpdb->options}
				WHERE
					option_value = '{$value}'
			";

			return $sql;
		}

		/**
		 * 	Takes an option value and returns the option name for that value
		 *
		 *	@param 		string 	$value 		Value to be used to get the option name
		 *
		 *	@return 	mixed 	Option name string if found or false if not
		 *	@access 	protected
     	 * 	@static 	
     	 * 	@since 		0.0.1
		 */

		static protected function _getOptionByValue($value) {
			global $wpdb;

			$sql = self::_getOptionByValueSql($value);

			return $wpdb->get_var($sql);
		}

		/**
		 *	Finds the name of a cimy field as mapped to a given Mailchimp field. These mappings are stored in the 
		 *	Wordpress options table
		 *
		 *	@param 		int 	$mailchimp_field 		Mailchimp field name
		 *
		 *	@return 	mixed 	Cimy field name as string if found or false if not.
		 *	@access 	protected
     	 * 	@static 	
     	 * 	@since 		0.0.1
		 */

		static protected function _getCimyFieldForMailchimpField($mailchimp_field) {
			$cimy_prefix = 'wp88_mc_cimy_uef_';

			//get the option name
			if ($option_name = self::_getOptionByValue($mailchimp_field)) {
				//does the option name include the cimy bits?
				if (strpos($option_name, $cimy_prefix) !== false) {
					//if so return
					return str_replace($cimy_prefix, '', $option_name);
				}
			}

			//we didn't find a field mapping
			return false;
		}

		/**
		 *	Gets the cimy field id from the name then calls upsert to either insert or update as required. Returns
		 *	true if the field is found and false if not. Exceptions come from Cimyfield if something goes wrong at
		 *	the DB level.
		 *
		 *	@param 		int 	$user_id 		Wordpress user ID
		 *	@param 		string 	$field_name 	The name of the Cimy field
		 *	@param 		mixed 	$value 			The value for the field for this user
		 *
		 *	@return 	bool 	true if field is found and no exception is raised by upsert or false if not found
		 *	@access 	protected
     	 * 	@static 	
     	 * 	@since 		0.0.1
		 */

		static protected function _upsert_cimyfield($user_id, $field_name, $value) {
			//does the field exist?
			if ($cimy_field_id = Cimyfield::getFieldIdByName($field_name)) {
				//we found the field in cimy, upsert it
				Cimyfield::upsertFieldValue($user_id, $cimy_field_id, $value);

				return true;
			}

			return false;
		}

		/**
		 *	Updates wordpress user data and cimy extra fields data based on $data. Logs errors on specific fields but doesn't stop
		 *
		 *	@param 		array 	$data 	Array of field values (field labels are indexed in self::$_field_labels)
		 *
		 * 	@throws		Exception 	If it can't update the wordpress user record
		 *	@return 	null
		 *	@access 	protected
     	 * 	@static 	
     	 * 	@since 		0.0.1
		 */

		static protected function _process_record($data) {
			$email_address = false;
			$user = false;
			$user_data = false;
			$wordpress_field_keys = array(1, 2);

			foreach ($data as $key => $value) {
				if ($key === 0) {
					//1. find the user based on email address (index:0 in the $data array)
					$email_address = $data[0];

					//try and get the wordpress user
					if (!$user = get_user_by('email', $email_address)) {
						//couldn't find the user, log to file and break out
						self::log("User ' . $email_address . ' not found", Logfile::LOG_TYPE_WARNING);

						break;
					} else {
						//found the user so continue to the next field
						$user_data = $user->data;

						continue;
					}
				} else if (in_array($key, $wordpress_field_keys)) {
					//2. Now we're on wordpress fields (just supporting first and last name at the moment)
					switch ($key) {
						case 1:
							$user_data->first_name = $value;

							break;

						case 2:
							$user_data->last_name = $value;

							break;
					}
				} else {
					//3. Now we're on fields other than the email address and other wordpress fields (Cimy Extra fields for instance)
					//What is the Mailchimp field name?
					if ($field_name = self::_getFieldNameFromIndex($key)) {
						//What is the cimy field name
						if ($cimy_field_name = self::_getCimyFieldForMailchimpField($field_name)) {
							try {
								if (!self::_upsert_cimyfield($user_data->ID, $cimy_field_name, $value)) {
									throw new Exception('Unknown error - Check the Mailchimp and Cimy fields are named correctly', 1);
								}
							} catch (Exception $e) {
								self::log("Error updating value for '$field_name' for '$email_address' ({$e->getMessage()})", Logfile::LOG_TYPE_ERROR);
							}
						}
					}
				}
			}

			//save the user
			if (!wp_update_user($user_data)) {
				throw new Exception('Unable to update Wordpress user record');
			}
		}

		/**
		 *	Calls Mainchimp API list method for the given list ID then iterates over result 
		 *	calling process_record for each one
		 *
		 *	@param 		string 	$list_id 	Mailchimp list id 
		 *
		 *	@return 	null
		 *	@access 	public
     	 * 	@static 	
     	 * 	@since 		0.0.1
		 */

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
        					self::setFieldLabels($obj, $list_id);
      					} else {
        					self::_process_record($obj);
      					}
      			
      					$i++;
    				}
  				}
  		
  				fclose($handle);
			}
		}

		/**
		 *	Iterates over all lists selected in Autochimp and calls fetch for each one
		 *
		 *	@return 	null
		 *	@access 	public
     	 * 	@static 	
     	 * 	@since 		0.0.1
		 */

		static public function fetch() {
			foreach (self::getMailchimpListIds() as $list_id) {
				self::fetch_list($list_id);
			}
		}
	}