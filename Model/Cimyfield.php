<?php
	class Cimyfield {
		static private function _getFieldIdByNameSql($name) {
			global $wpdb_fields_table;

			$sql = "
				SELECT 
					ID
				FROM
					{$wpdb_fields_table}
				WHERE
					NAME = '{$name}'
			";

			return $sql;
		}

		static private function _getFieldValueExistsSql($user_id, $field_id) {
			global $wpdb_data_table;

			$sql = "
				SELECT 
					ID
				FROM
					{$wpdb_data_table}
				WHERE
					USER_ID = {$user_id} AND 
					FIELD_ID = {$field_id}
			";

			return $sql;
		}

		static private function _getInsertFieldValueSql($user_id, $field_id, $value) {
			global $wpdb_data_table;

			$sql = "
				INSERT INTO
					{$wpdb_data_table}
				SET
					USER_ID = {$user_id}, 
					FIELD_ID = {$field_id},
					VALUE = '{$value}'
			";

			return $sql;
		}

		static private function _getUpdateFieldValueSql($id, $value) {
			global $wpdb_data_table;

			$sql = "
				UPDATE
					{$wpdb_data_table}
				SET
					VALUE = '{$value}'
				WHERE
					ID = {$id}
			";

			return $sql;
		}

		static public function getFieldIdByName($name) {
			global $wpdb;

			$sql = self::_getFieldIdByNameSql($name);

			return $wpdb->get_var($sql);
		}

		static public function fieldValueExists($user_id, $field_id) {
			global $wpdb;

			$sql = self::_getFieldValueExistsSql($user_id, $field_id);
			
			return $wpdb->get_var($sql);
		}

		static public function insertFieldValue($user_id, $field_id, $value) {
			global $wpdb;

			$sql = self::_getInsertFieldValueSql($user_id, $field_id, $value);
			
			return $wpdb->query($sql);
		}

		static public function updateFieldValue($id, $value) {
			global $wpdb;

			$sql = self::_getUpdateFieldValueSql($id, $value);
			
			return $wpdb->query($sql);
		}

		static public function upsertFieldValue($user_id, $field_id, $value = null) {
			//check if there is already a record for this user/field id combo
			$value_id = self::fieldValueExists($user_id, $field_id);

			if ($value_id == NULL) {
				//insert
				self::insertFieldValue($user_id, $field_id, $value);
			} else {
				//update
				self::updateFieldValue($value_id, $value);
			}
		}
	}