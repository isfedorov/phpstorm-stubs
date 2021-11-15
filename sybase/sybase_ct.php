<?php
/**
 * Opens a Sybase server connection
 * @param string $servername [optional]
 * @param string $username [optional]
 * @param string $password [optional]
 * @param string $charset [optional]
 * @param string $appname [optional]
 * @return resource|false a positive Sybase link identifier on success, or false
 * @removed 7.0
 */
function sybase_connect($servername = null, $username = null, $password = null, $charset = null, $appname = null) {}

/**
 * Open persistent Sybase connection
 * @param string $servername [optional]
 * @param string $username [optional]
 * @param string $password [optional]
 * @param string $charset [optional]
 * @param string $appname [optional]
 * @return resource|false a positive Sybase persistent link identifier on success
 * @removed 7.0
 */
function sybase_pconnect($servername = null, $username = null, $password = null, $charset = null, $appname = null) {}

/**
 * Closes a Sybase connection
 * @param resource $link_identifier [optional]
 * @return bool
 * @removed 7.0
 */
function sybase_close($link_identifier = null) {}

/**
 * Selects a Sybase database
 * @param string $database_name
 * @param resource $link_identifier [optional]
 * @return bool
 * @removed 7.0
 */
function sybase_select_db($database_name, $link_identifier = null) {}

/**
 * Sends a Sybase query
 * @param string $query
 * @param resource $link_identifier [optional]
 * @return mixed|false a positive Sybase result identifier on success, false on error,
 * @removed 7.0
 */
function sybase_query($query, $link_identifier = null) {}

/**
 * Send a Sybase query and do not block
 * @param string $query
 * @param resource $link_identifier
 * @param bool $store_result [optional]
 * @return resource|false a positive Sybase result identifier on success, or false on
 * @removed 7.0
 */
function sybase_unbuffered_query($query, $link_identifier, $store_result = null) {}

/**
 * Frees result memory
 * @param resource $result
 * @return bool
 * @removed 7.0
 */
function sybase_free_result($result) {}

/**
 * Returns the last message from the server
 * @return string the message as a string.
 * @removed 7.0
 */
function sybase_get_last_message() {}

/**
 * Get number of rows in a result set
 * @param resource $result
 * @return int the number of rows as an integer.
 * @removed 7.0
 */
function sybase_num_rows($result) {}

/**
 * Gets the number of fields in a result set
 * @param resource $result
 * @return int the number of fields as an integer.
 * @removed 7.0
 */
function sybase_num_fields($result) {}

/**
 * Get a result row as an enumerated array
 * @param resource $result
 * @return array|false an array that corresponds to the fetched row, or false if there
 * @removed 7.0
 */
function sybase_fetch_row($result) {}

/**
 * Fetch row as array
 * @param resource $result
 * @return array|false an array that corresponds to the fetched row, or false if there
 * @removed 7.0
 */
function sybase_fetch_array($result) {}

/**
 * Fetch a result row as an associative array
 * @param resource $result
 * @return array|false an array that corresponds to the fetched row, or false if there
 * @removed 7.0
 */
function sybase_fetch_assoc($result) {}

/**
 * Fetch a row as an object
 * @param resource $result
 * @param mixed $object [optional]
 * @return object an object with properties that correspond to the fetched row, or
 * @removed 7.0
 */
function sybase_fetch_object($result, $object = null) {}

/**
 * Moves internal row pointer
 * @param resource $result_identifier
 * @param int $row_number
 * @return bool
 * @removed 7.0
 */
function sybase_data_seek($result_identifier, $row_number) {}

/**
 * Get field information from a result
 * @param resource $result
 * @param int $field_offset [optional]
 * @return object an object containing field information.
 * @removed 7.0
 */
function sybase_fetch_field($result, $field_offset = null) {}

/**
 * Sets field offset
 * @param resource $result
 * @param int $field_offset
 * @return bool
 * @removed 7.0
 */
function sybase_field_seek($result, $field_offset) {}

/**
 * Get result data
 * @param resource $result
 * @param int $row
 * @param mixed $field
 * @return string
 * @removed 7.0
 */
function sybase_result($result, $row, $field) {}

/**
 * Gets number of affected rows in last query
 * @param resource $link_identifier [optional]
 * @return int the number of affected rows, as an integer.
 * @removed 7.0
 */
function sybase_affected_rows($link_identifier = null) {}

/**
 * Sets minimum client severity
 * @param int $severity
 * @return void
 * @removed 7.0
 */
function sybase_min_client_severity($severity) {}

/**
 * Sets minimum server severity
 * @param int $severity
 * @return void
 * @removed 7.0
 */
function sybase_min_server_severity($severity) {}

/**
 * Sets the handler called when a server message is raised
 * @param callable $handler
 * @param resource $connection [optional]
 * @return bool
 * @removed 7.0
 */
function sybase_set_message_handler($handler, $connection = null) {}

/**
 * Sets the deadlock retry count
 * @param int $retry_count
 * @return void
 * @removed 7.0
 */
function sybase_deadlock_retry_count($retry_count) {}
