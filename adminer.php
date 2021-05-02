<?php
error_reporting(0);
//error_reporting(6135); // errors and warnings
if (extension_loaded("xdebug") && file_exists(sys_get_temp_dir() . "/adminer_coverage.ser")) {
	function save_coverage() {
		$coverage_filename = sys_get_temp_dir() . "/adminer_coverage.ser";
		$coverage = unserialize(file_get_contents($coverage_filename));
		foreach (xdebug_get_code_coverage() as $filename => $lines) {
			foreach ($lines as $l => $val) {
				if (!$coverage[$filename][$l] || $val > 0) {
					$coverage[$filename][$l] = $val;
				}
			}
			file_put_contents($coverage_filename, serialize($coverage));
		}
	}
	xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
	register_shutdown_function('save_coverage');
}
$filter = !preg_match('~^(unsafe_raw)?$~', ini_get("filter.default"));
if ($filter || ini_get("filter.default_flags")) {
	foreach (array('_GET', '_POST', '_COOKIE', '_SERVER') as $val) {
		$unsafe = filter_input_array(constant("INPUT$val"), FILTER_UNSAFE_RAW);
		if ($unsafe) {
			$$val = $unsafe;
		}
	}
}
if (function_exists("mb_internal_encoding")) {
	mb_internal_encoding("8bit");
}
/** Get database connection
* @return Min_DB
*/
function connection() {
	// can be used in customization, $connection is minified
	global $connection;
	return $connection;
}

/** Get Adminer object
* @return Adminer
*/
function adminer() {
	global $adminer;
	return $adminer;
}

/** Get Adminer version
* @return string
*/
function version() {
	global $VERSION;
	return $VERSION;
}

/** Unescape database identifier
* @param string text inside ``
* @return string
*/
function idf_unescape($idf) {
	$last = substr($idf, -1);
	return str_replace($last . $last, $last, substr($idf, 1, -1));
}

/** Escape string to use inside ''
* @param string
* @return string
*/
function escape_string($val) {
	return substr(q($val), 1, -1);
}

/** Remove non-digits from a string
* @param string
* @return string
*/
function number($val) {
	return preg_replace('~[^0-9]+~', '', $val);
}

/** Get regular expression to match numeric types
* @return string
*/
function number_type() {
	return '((?<!o)int(?!er)|numeric|real|float|double|decimal|money)'; // not point, not interval
}

/** Disable magic_quotes_gpc
* @param array e.g. (&$_GET, &$_POST, &$_COOKIE)
* @param bool whether to leave values as is
* @return null modified in place
*/
function remove_slashes($process, $filter = false) {
	if (function_exists("get_magic_quotes_gpc") && get_magic_quotes_gpc()) {
		while (list($key, $val) = each($process)) {
			foreach ($val as $k => $v) {
				unset($process[$key][$k]);
				if (is_array($v)) {
					$process[$key][stripslashes($k)] = $v;
					$process[] = &$process[$key][stripslashes($k)];
				} else {
					$process[$key][stripslashes($k)] = ($filter ? $v : stripslashes($v));
				}
			}
		}
	}
}

/** Escape or unescape string to use inside form []
* @param string
* @param bool
* @return string
*/
function bracket_escape($idf, $back = false) {
	// escape brackets inside name="x[]"
	static $trans = array(':' => ':1', ']' => ':2', '[' => ':3', '"' => ':4');
	return strtr($idf, ($back ? array_flip($trans) : $trans));
}

/** Check if connection has at least the given version
* @param string required version
* @param string required MariaDB version
* @param Min_DB defaults to $connection
* @return bool
*/
function min_version($version, $maria_db = "", $connection2 = null) {
	global $connection;
	if (!$connection2) {
		$connection2 = $connection;
	}
	$server_info = $connection2->server_info;
	if ($maria_db && preg_match('~([\d.]+)-MariaDB~', $server_info, $match)) {
		$server_info = $match[1];
		$version = $maria_db;
	}
	return (version_compare($server_info, $version) >= 0);
}

/** Get connection charset
* @param Min_DB
* @return string
*/
function charset($connection) {
	return (min_version("5.5.3", 0, $connection) ? "utf8mb4" : "utf8"); // SHOW CHARSET would require an extra query
}

/** Return <script> element
* @param string
* @param string
* @return string
*/
function script($source, $trailing = "\n") {
	return "<script" . nonce() . ">$source</script>$trailing";
}

/** Return <script src> element
* @param string
* @return string
*/
function script_src($url) {
	return "<script src='" . h($url) . "'" . nonce() . "></script>\n";
}

/** Get a nonce="" attribute with CSP nonce
* @return string
*/
function nonce() {
	return ' nonce="' . get_nonce() . '"';
}

/** Get a target="_blank" attribute
* @return string
*/
function target_blank() {
	return ' target="_blank" rel="noreferrer noopener"';
}

/** Escape for HTML
* @param string
* @return string
*/
function h($string) {
	return str_replace("\0", "&#0;", htmlspecialchars($string, ENT_QUOTES, 'utf-8'));
}

/** Convert \n to <br>
* @param string
* @return string
*/
function nl_br($string) {
	return str_replace("\n", "<br>", $string); // nl2br() uses XHTML before PHP 5.3
}

/** Generate HTML checkbox
* @param string
* @param string
* @param bool
* @param string
* @param string
* @param string
* @param string
* @return string
*/
function checkbox($name, $value, $checked, $label = "", $onclick = "", $class = "", $labelled_by = "") {
	$return = "<input type='checkbox' name='$name' value='" . h($value) . "'"
		. ($checked ? " checked" : "")
		. ($labelled_by ? " aria-labelledby='$labelled_by'" : "")
		. ">"
		. ($onclick ? script("qsl('input').onclick = function () { $onclick };", "") : "")
	;
	return ($label != "" || $class ? "<label" . ($class ? " class='$class'" : "") . ">$return" . h($label) . "</label>" : $return);
}

/** Generate list of HTML options
* @param array array of strings or arrays (creates optgroup)
* @param mixed
* @param bool always use array keys for value="", otherwise only string keys are used
* @return string
*/
function optionlist($options, $selected = null, $use_keys = false) {
	$return = "";
	foreach ($options as $k => $v) {
		$opts = array($k => $v);
		if (is_array($v)) {
			$return .= '<optgroup label="' . h($k) . '">';
			$opts = $v;
		}
		foreach ($opts as $key => $val) {
			$return .= '<option' . ($use_keys || is_string($key) ? ' value="' . h($key) . '"' : '') . (($use_keys || is_string($key) ? (string) $key : $val) === $selected ? ' selected' : '') . '>' . h($val);
		}
		if (is_array($v)) {
			$return .= '</optgroup>';
		}
	}
	return $return;
}

/** Generate HTML radio list
* @param string
* @param array
* @param string
* @param string true for no onchange, false for radio
* @param string
* @return string
*/
function html_select($name, $options, $value = "", $onchange = true, $labelled_by = "") {
	if ($onchange) {
		return "<select name='" . h($name) . "'"
			. ($labelled_by ? " aria-labelledby='$labelled_by'" : "")
			. ">" . optionlist($options, $value) . "</select>"
			. (is_string($onchange) ? script("qsl('select').onchange = function () { $onchange };", "") : "")
		;
	}
	$return = "";
	foreach ($options as $key => $val) {
		$return .= "<label><input type='radio' name='" . h($name) . "' value='" . h($key) . "'" . ($key == $value ? " checked" : "") . ">" . h($val) . "</label>";
	}
	return $return;
}

/** Generate HTML <select> or <input> if $options are empty
* @param string
* @param array
* @param string
* @param string
* @param string
* @return string
*/
function select_input($attrs, $options, $value = "", $onchange = "", $placeholder = "") {
	$tag = ($options ? "select" : "input");
	return "<$tag$attrs" . ($options
		? "><option value=''>$placeholder" . optionlist($options, $value, true) . "</select>"
		: " size='10' value='" . h($value) . "' placeholder='$placeholder'>"
	) . ($onchange ? script("qsl('$tag').onchange = $onchange;", "") : ""); //! use oninput for input
}

/** Get onclick confirmation
* @param string
* @param string
* @return string
*/
function confirm($message = "", $selector = "qsl('input')") {
	return script("$selector.onclick = function () { return confirm('" . ($message ? js_escape($message) : lang('Are you sure?')) . "'); };", "");
}

/** Print header for hidden fieldset (close by </div></fieldset>)
* @param string
* @param string
* @param bool
* @return null
*/
function print_fieldset($id, $legend, $visible = false) {
	echo "<fieldset><legend>";
	echo "<a href='#fieldset-$id'>$legend</a>";
	echo script("qsl('a').onclick = partial(toggle, 'fieldset-$id');", "");
	echo "</legend>";
	echo "<div id='fieldset-$id'" . ($visible ? "" : " class='hidden'") . ">\n";
}

/** Return class='active' if $bold is true
* @param bool
* @param string
* @return string
*/
function bold($bold, $class = "") {
	return ($bold ? " class='active $class'" : ($class ? " class='$class'" : ""));
}

/** Generate class for odd rows
* @param string return this for odd rows, empty to reset counter
* @return string
*/
function odd($return = ' class="odd"') {
	static $i = 0;
	if (!$return) { // reset counter
		$i = -1;
	}
	return ($i++ % 2 ? $return : '');
}

/** Escape string for JavaScript apostrophes
* @param string
* @return string
*/
function js_escape($string) {
	return addcslashes($string, "\r\n'\\/"); // slash for <script>
}

/** Print one row in JSON object
* @param string or "" to close the object
* @param string
* @return null
*/
function json_row($key, $val = null) {
	static $first = true;
	if ($first) {
		echo "{";
	}
	if ($key != "") {
		echo ($first ? "" : ",") . "\n\t\"" . addcslashes($key, "\r\n\t\"\\/") . '": ' . ($val !== null ? '"' . addcslashes($val, "\r\n\"\\/") . '"' : 'null');
		$first = false;
	} else {
		echo "\n}\n";
		$first = true;
	}
}

/** Get INI boolean value
* @param string
* @return bool
*/
function ini_bool($ini) {
	$val = ini_get($ini);
	return (preg_match('~^(on|true|yes)$~i', $val) || (int) $val); // boolean values set by php_value are strings
}

/** Check if SID is neccessary
* @return bool
*/
function sid() {
	static $return;
	if ($return === null) { // restart_session() defines SID
		$return = (SID && !($_COOKIE && ini_bool("session.use_cookies"))); // $_COOKIE - don't pass SID with permanent login
	}
	return $return;
}

/** Set password to session
* @param string
* @param string
* @param string
* @param string
* @return null
*/
function set_password($vendor, $server, $username, $password) {
	$_SESSION["pwds"][$vendor][$server][$username] = ($_COOKIE["adminer_key"] && is_string($password)
		? array(encrypt_string($password, $_COOKIE["adminer_key"]))
		: $password
	);
}

/** Get password from session
* @return string or null for missing password or false for expired password
*/
function get_password() {
	$return = get_session("pwds");
	if (is_array($return)) {
		$return = ($_COOKIE["adminer_key"]
			? decrypt_string($return[0], $_COOKIE["adminer_key"])
			: false
		);
	}
	return $return;
}

/** Shortcut for $connection->quote($string)
* @param string
* @return string
*/
function q($string) {
	global $connection;
	return $connection->quote($string);
}

/** Get list of values from database
* @param string
* @param mixed
* @return array
*/
function get_vals($query, $column = 0) {
	global $connection;
	$return = array();
	$result = $connection->query($query);
	if (is_object($result)) {
		while ($row = $result->fetch_row()) {
			$return[] = $row[$column];
		}
	}
	return $return;
}

/** Get keys from first column and values from second
* @param string
* @param Min_DB
* @param bool
* @return array
*/
function get_key_vals($query, $connection2 = null, $set_keys = true) {
	global $connection;
	if (!is_object($connection2)) {
		$connection2 = $connection;
	}
	$return = array();
	$result = $connection2->query($query);
	if (is_object($result)) {
		while ($row = $result->fetch_row()) {
			if ($set_keys) {
				$return[$row[0]] = $row[1];
			} else {
				$return[] = $row[0];
			}
		}
	}
	return $return;
}

/** Get all rows of result
* @param string
* @param Min_DB
* @param string
* @return array of associative arrays
*/
function get_rows($query, $connection2 = null, $error = "<p class='error'>") {
	global $connection;
	$conn = (is_object($connection2) ? $connection2 : $connection);
	$return = array();
	$result = $conn->query($query);
	if (is_object($result)) { // can return true
		while ($row = $result->fetch_assoc()) {
			$return[] = $row;
		}
	} elseif (!$result && !is_object($connection2) && $error && defined("PAGE_HEADER")) {
		echo $error . error() . "\n";
	}
	return $return;
}

/** Find unique identifier of a row
* @param array
* @param array result of indexes()
* @return array or null if there is no unique identifier
*/
function unique_array($row, $indexes) {
	foreach ($indexes as $index) {
		if (preg_match("~PRIMARY|UNIQUE~", $index["type"])) {
			$return = array();
			foreach ($index["columns"] as $key) {
				if (!isset($row[$key])) { // NULL is ambiguous
					continue 2;
				}
				$return[$key] = $row[$key];
			}
			return $return;
		}
	}
}

/** Escape column key used in where()
* @param string
* @return string
*/
function escape_key($key) {
	if (preg_match('(^([\w(]+)(' . str_replace("_", ".*", preg_quote(idf_escape("_"))) . ')([ \w)]+)$)', $key, $match)) { //! columns looking like functions
		return $match[1] . idf_escape(idf_unescape($match[2])) . $match[3]; //! SQL injection
	}
	return idf_escape($key);
}

/** Create SQL condition from parsed query string
* @param array parsed query string
* @param array
* @return string
*/
function where($where, $fields = array()) {
	global $connection, $jush;
	$return = array();
	foreach ((array) $where["where"] as $key => $val) {
		$key = bracket_escape($key, 1); // 1 - back
		$column = escape_key($key);
		$return[] = $column
			. ($jush == "sql" && is_numeric($val) && preg_match('~\.~', $val) ? " LIKE " . q($val) // LIKE because of floats but slow with ints
				: ($jush == "mssql" ? " LIKE " . q(preg_replace('~[_%[]~', '[\0]', $val)) // LIKE because of text
				: " = " . unconvert_field($fields[$key], q($val))
			))
		; //! enum and set
		if ($jush == "sql" && preg_match('~char|text~', $fields[$key]["type"]) && preg_match("~[^ -@]~", $val)) { // not just [a-z] to catch non-ASCII characters
			$return[] = "$column = " . q($val) . " COLLATE " . charset($connection) . "_bin";
		}
	}
	foreach ((array) $where["null"] as $key) {
		$return[] = escape_key($key) . " IS NULL";
	}
	return implode(" AND ", $return);
}

/** Create SQL condition from query string
* @param string
* @param array
* @return string
*/
function where_check($val, $fields = array()) {
	parse_str($val, $check);
	remove_slashes(array(&$check));
	return where($check, $fields);
}

/** Create query string where condition from value
* @param int condition order
* @param string column identifier
* @param string
* @param string
* @return string
*/
function where_link($i, $column, $value, $operator = "=") {
	return "&where%5B$i%5D%5Bcol%5D=" . urlencode($column) . "&where%5B$i%5D%5Bop%5D=" . urlencode(($value !== null ? $operator : "IS NULL")) . "&where%5B$i%5D%5Bval%5D=" . urlencode($value);
}

/** Get select clause for convertible fields
* @param array
* @param array
* @param array
* @return string
*/
function convert_fields($columns, $fields, $select = array()) {
	$return = "";
	foreach ($columns as $key => $val) {
		if ($select && !in_array(idf_escape($key), $select)) {
			continue;
		}
		$as = convert_field($fields[$key]);
		if ($as) {
			$return .= ", $as AS " . idf_escape($key);
		}
	}
	return $return;
}

/** Set cookie valid on current path
* @param string
* @param string
* @param int number of seconds, 0 for session cookie
* @return bool
*/
function cookie($name, $value, $lifetime = 2592000) { // 2592000 - 30 days
	global $HTTPS;
	return header("Set-Cookie: $name=" . urlencode($value)
		. ($lifetime ? "; expires=" . gmdate("D, d M Y H:i:s", time() + $lifetime) . " GMT" : "")
		. "; path=" . preg_replace('~\?.*~', '', $_SERVER["REQUEST_URI"])
		. ($HTTPS ? "; secure" : "")
		. "; HttpOnly; SameSite=lax",
		false);
}

/** Restart stopped session
* @return null
*/
function restart_session() {
	if (!ini_bool("session.use_cookies")) {
		session_start();
	}
}

/** Stop session if possible
* @param bool
* @return null
*/
function stop_session($force = false) {
	$use_cookies = ini_bool("session.use_cookies");
	if (!$use_cookies || $force) {
		session_write_close(); // improves concurrency if a user opens several pages at once, may be restarted later
		if ($use_cookies && @ini_set("session.use_cookies", false) === false) { // @ - may be disabled
			session_start();
		}
	}
}

/** Get session variable for current server
* @param string
* @return mixed
*/
function &get_session($key) {
	return $_SESSION[$key][DRIVER][SERVER][$_GET["username"]];
}

/** Set session variable for current server
* @param string
* @param mixed
* @return mixed
*/
function set_session($key, $val) {
	$_SESSION[$key][DRIVER][SERVER][$_GET["username"]] = $val; // used also in auth.inc.php
}

/** Get authenticated URL
* @param string
* @param string
* @param string
* @param string
* @return string
*/
function auth_url($vendor, $server, $username, $db = null) {
	global $drivers;
	preg_match('~([^?]*)\??(.*)~', remove_from_uri(implode("|", array_keys($drivers)) . "|username|" . ($db !== null ? "db|" : "") . session_name()), $match);
	return "$match[1]?"
		. (sid() ? SID . "&" : "")
		. ($vendor != "server" || $server != "" ? urlencode($vendor) . "=" . urlencode($server) . "&" : "")
		. "username=" . urlencode($username)
		. ($db != "" ? "&db=" . urlencode($db) : "")
		. ($match[2] ? "&$match[2]" : "")
	;
}

/** Find whether it is an AJAX request
* @return bool
*/
function is_ajax() {
	return ($_SERVER["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest");
}

/** Send Location header and exit
* @param string null to only set a message
* @param string
* @return null
*/
function redirect($location, $message = null) {
	if ($message !== null) {
		restart_session();
		$_SESSION["messages"][preg_replace('~^[^?]*~', '', ($location !== null ? $location : $_SERVER["REQUEST_URI"]))][] = $message;
	}
	if ($location !== null) {
		if ($location == "") {
			$location = ".";
		}
		header("Location: $location");
		exit;
	}
}

/** Execute query and redirect if successful
* @param string
* @param string
* @param string
* @param bool
* @param bool
* @param bool
* @param string
* @return bool
*/
function query_redirect($query, $location, $message, $redirect = true, $execute = true, $failed = false, $time = "") {
	global $connection, $error, $adminer;
	if ($execute) {
		$start = microtime(true);
		$failed = !$connection->query($query);
		$time = format_time($start);
	}
	$sql = "";
	if ($query) {
		$sql = $adminer->messageQuery($query, $time, $failed);
	}
	if ($failed) {
		$error = error() . $sql . script("messagesPrint();");
		return false;
	}
	if ($redirect) {
		redirect($location, $message . $sql);
	}
	return true;
}

/** Execute and remember query
* @param string or null to return remembered queries, end with ';' to use DELIMITER
* @return Min_Result or array($queries, $time) if $query = null
*/
function queries($query) {
	global $connection;
	static $queries = array();
	static $start;
	if (!$start) {
		$start = microtime(true);
	}
	if ($query === null) {
		// return executed queries
		return array(implode("\n", $queries), format_time($start));
	}
	$queries[] = (preg_match('~;$~', $query) ? "DELIMITER ;;\n$query;\nDELIMITER " : $query) . ";";
	return $connection->query($query);
}

/** Apply command to all array items
* @param string
* @param array
* @param callback
* @return bool
*/
function apply_queries($query, $tables, $escape = 'table') {
	foreach ($tables as $table) {
		if (!queries("$query " . $escape($table))) {
			return false;
		}
	}
	return true;
}

/** Redirect by remembered queries
* @param string
* @param string
* @param bool
* @return bool
*/
function queries_redirect($location, $message, $redirect) {
	list($queries, $time) = queries(null);
	return query_redirect($queries, $location, $message, $redirect, false, !$redirect, $time);
}

/** Format elapsed time
* @param float output of microtime(true)
* @return string HTML code
*/
function format_time($start) {
	return lang('%.3f s', max(0, microtime(true) - $start));
}

/** Get relative REQUEST_URI
* @return string
*/
function relative_uri() {
	return str_replace(":", "%3a", preg_replace('~^[^?]*/([^?]*)~', '\1', $_SERVER["REQUEST_URI"]));
}

/** Remove parameter from query string
* @param string
* @return string
*/
function remove_from_uri($param = "") {
	return substr(preg_replace("~(?<=[?&])($param" . (SID ? "" : "|" . session_name()) . ")=[^&]*&~", '', relative_uri() . "&"), 0, -1);
}

/** Generate page number for pagination
* @param int
* @param int
* @return string
*/
function pagination($page, $current) {
	return " " . ($page == $current
		? $page + 1
		: '<a href="' . h(remove_from_uri("page") . ($page ? "&page=$page" . ($_GET["next"] ? "&next=" . urlencode($_GET["next"]) : "") : "")) . '">' . ($page + 1) . "</a>"
	);
}

/** Get file contents from $_FILES
* @param string
* @param bool
* @return mixed int for error, string otherwise
*/
function get_file($key, $decompress = false) {
	$file = $_FILES[$key];
	if (!$file) {
		return null;
	}
	foreach ($file as $key => $val) {
		$file[$key] = (array) $val;
	}
	$return = '';
	foreach ($file["error"] as $key => $error) {
		if ($error) {
			return $error;
		}
		$name = $file["name"][$key];
		$tmp_name = $file["tmp_name"][$key];
		$content = file_get_contents($decompress && preg_match('~\.gz$~', $name)
			? "compress.zlib://$tmp_name"
			: $tmp_name
		); //! may not be reachable because of open_basedir
		if ($decompress) {
			$start = substr($content, 0, 3);
			if (function_exists("iconv") && preg_match("~^\xFE\xFF|^\xFF\xFE~", $start, $regs)) { // not ternary operator to save memory
				$content = iconv("utf-16", "utf-8", $content);
			} elseif ($start == "\xEF\xBB\xBF") { // UTF-8 BOM
				$content = substr($content, 3);
			}
			$return .= $content . "\n\n";
		} else {
			$return .= $content;
		}
	}
	//! support SQL files not ending with semicolon
	return $return;
}

/** Determine upload error
* @param int
* @return string
*/
function upload_error($error) {
	$max_size = ($error == UPLOAD_ERR_INI_SIZE ? ini_get("upload_max_filesize") : 0); // post_max_size is checked in index.php
	return ($error ? lang('Unable to upload a file.') . ($max_size ? " " . lang('Maximum allowed file size is %sB.', $max_size) : "") : lang('File does not exist.'));
}

/** Create repeat pattern for preg
* @param string
* @param int
* @return string
*/
function repeat_pattern($pattern, $length) {
	// fix for Compilation failed: number too big in {} quantifier
	return str_repeat("$pattern{0,65535}", $length / 65535) . "$pattern{0," . ($length % 65535) . "}"; // can create {0,0} which is OK
}

/** Check whether the string is in UTF-8
* @param string
* @return bool
*/
function is_utf8($val) {
	// don't print control chars except \t\r\n
	return (preg_match('~~u', $val) && !preg_match('~[\0-\x8\xB\xC\xE-\x1F]~', $val));
}

/** Shorten UTF-8 string
* @param string
* @param int
* @param string
* @return string escaped string with appended ...
*/
function shorten_utf8($string, $length = 80, $suffix = "") {
	if (!preg_match("(^(" . repeat_pattern("[\t\r\n -\x{10FFFF}]", $length) . ")($)?)u", $string, $match)) { // ~s causes trash in $match[2] under some PHP versions, (.|\n) is slow
		preg_match("(^(" . repeat_pattern("[\t\r\n -~]", $length) . ")($)?)", $string, $match);
	}
	return h($match[1]) . $suffix . (isset($match[2]) ? "" : "<i>…</i>");
}

/** Format decimal number
* @param int
* @return string
*/
function format_number($val) {
	return strtr(number_format($val, 0, ".", lang(',')), preg_split('~~u', lang('0123456789'), -1, PREG_SPLIT_NO_EMPTY));
}

/** Generate friendly URL
* @param string
* @return string
*/
function friendly_url($val) {
	// used for blobs and export
	return preg_replace('~[^a-z0-9_]~i', '-', $val);
}

/** Print hidden fields
* @param array
* @param array
* @param string
* @return bool
*/
function hidden_fields($process, $ignore = array(), $prefix = '') {
	$return = false;
	foreach ($process as $key => $val) {
		if (!in_array($key, $ignore)) {
			if (is_array($val)) {
				hidden_fields($val, array(), $key);
			} else {
				$return = true;
				echo '<input type="hidden" name="' . h($prefix ? $prefix . "[$key]" : $key) . '" value="' . h($val) . '">';
			}
		}
	}
	return $return;
}

/** Print hidden fields for GET forms
* @return null
*/
function hidden_fields_get() {
	echo (sid() ? '<input type="hidden" name="' . session_name() . '" value="' . h(session_id()) . '">' : '');
	echo (SERVER !== null ? '<input type="hidden" name="' . DRIVER . '" value="' . h(SERVER) . '">' : "");
	echo '<input type="hidden" name="username" value="' . h($_GET["username"]) . '">';
}

/** Get status of a single table and fall back to name on error
* @param string
* @param bool
* @return array
*/
function table_status1($table, $fast = false) {
	$return = table_status($table, $fast);
	return ($return ? $return : array("Name" => $table));
}

/** Find out foreign keys for each column
* @param string
* @return array array($col => array())
*/
function column_foreign_keys($table) {
	global $adminer;
	$return = array();
	foreach ($adminer->foreignKeys($table) as $foreign_key) {
		foreach ($foreign_key["source"] as $val) {
			$return[$val][] = $foreign_key;
		}
	}
	return $return;
}

/** Print enum input field
* @param string "radio"|"checkbox"
* @param string
* @param array
* @param mixed int|string|array
* @param string
* @return null
*/
function enum_input($type, $attrs, $field, $value, $empty = null) {
	global $adminer;
	preg_match_all("~'((?:[^']|'')*)'~", $field["length"], $matches);
	$return = ($empty !== null ? "<label><input type='$type'$attrs value='$empty'" . ((is_array($value) ? in_array($empty, $value) : $value === 0) ? " checked" : "") . "><i>" . lang('empty') . "</i></label>" : "");
	foreach ($matches[1] as $i => $val) {
		$val = stripcslashes(str_replace("''", "'", $val));
		$checked = (is_int($value) ? $value == $i+1 : (is_array($value) ? in_array($i+1, $value) : $value === $val));
		$return .= " <label><input type='$type'$attrs value='" . ($i+1) . "'" . ($checked ? ' checked' : '') . '>' . h($adminer->editVal($val, $field)) . '</label>';
	}
	return $return;
}

/** Print edit input field
* @param array one field from fields()
* @param mixed
* @param string
* @return null
*/
function input($field, $value, $function) {
	global $types, $adminer, $jush;
	$name = h(bracket_escape($field["field"]));
	echo "<td class='function'>";
	if (is_array($value) && !$function) {
		$args = array($value);
		if (version_compare(PHP_VERSION, 5.4) >= 0) {
			$args[] = JSON_PRETTY_PRINT;
		}
		$value = call_user_func_array('json_encode', $args); //! requires PHP 5.2
		$function = "json";
	}
	$reset = ($jush == "mssql" && $field["auto_increment"]);
	if ($reset && !$_POST["save"]) {
		$function = null;
	}
	$functions = (isset($_GET["select"]) || $reset ? array("orig" => lang('original')) : array()) + $adminer->editFunctions($field);
	$attrs = " name='fields[$name]'";
	if ($field["type"] == "enum") {
		echo h($functions[""]) . "<td>" . $adminer->editInput($_GET["edit"], $field, $attrs, $value);
	} else {
		$has_function = (in_array($function, $functions) || isset($functions[$function]));
		echo (count($functions) > 1
			? "<select name='function[$name]'>" . optionlist($functions, $function === null || $has_function ? $function : "") . "</select>"
				. on_help("getTarget(event).value.replace(/^SQL\$/, '')", 1)
				. script("qsl('select').onchange = functionChange;", "")
			: h(reset($functions))
		) . '<td>';
		$input = $adminer->editInput($_GET["edit"], $field, $attrs, $value); // usage in call is without a table
		if ($input != "") {
			echo $input;
		} elseif (preg_match('~bool~', $field["type"])) {
			echo "<input type='hidden'$attrs value='0'>" .
				"<input type='checkbox'" . (preg_match('~^(1|t|true|y|yes|on)$~i', $value) ? " checked='checked'" : "") . "$attrs value='1'>";
		} elseif ($field["type"] == "set") { //! 64 bits
			preg_match_all("~'((?:[^']|'')*)'~", $field["length"], $matches);
			foreach ($matches[1] as $i => $val) {
				$val = stripcslashes(str_replace("''", "'", $val));
				$checked = (is_int($value) ? ($value >> $i) & 1 : in_array($val, explode(",", $value), true));
				echo " <label><input type='checkbox' name='fields[$name][$i]' value='" . (1 << $i) . "'" . ($checked ? ' checked' : '') . ">" . h($adminer->editVal($val, $field)) . '</label>';
			}
		} elseif (preg_match('~blob|bytea|raw|file~', $field["type"]) && ini_bool("file_uploads")) {
			echo "<input type='file' name='fields-$name'>";
		} elseif (($text = preg_match('~text|lob|memo~i', $field["type"])) || preg_match("~\n~", $value)) {
			if ($text && $jush != "sqlite") {
				$attrs .= " cols='50' rows='12'";
			} else {
				$rows = min(12, substr_count($value, "\n") + 1);
				$attrs .= " cols='30' rows='$rows'" . ($rows == 1 ? " style='height: 1.2em;'" : ""); // 1.2em - line-height
			}
			echo "<textarea$attrs>" . h($value) . '</textarea>';
		} elseif ($function == "json" || preg_match('~^jsonb?$~', $field["type"])) {
			echo "<textarea$attrs cols='50' rows='12' class='jush-js'>" . h($value) . '</textarea>';
		} else {
			// int(3) is only a display hint
			$maxlength = (!preg_match('~int~', $field["type"]) && preg_match('~^(\d+)(,(\d+))?$~', $field["length"], $match) ? ((preg_match("~binary~", $field["type"]) ? 2 : 1) * $match[1] + ($match[3] ? 1 : 0) + ($match[2] && !$field["unsigned"] ? 1 : 0)) : ($types[$field["type"]] ? $types[$field["type"]] + ($field["unsigned"] ? 0 : 1) : 0));
			if ($jush == 'sql' && min_version(5.6) && preg_match('~time~', $field["type"])) {
				$maxlength += 7; // microtime
			}
			// type='date' and type='time' display localized value which may be confusing, type='datetime' uses 'T' as date and time separator
			echo "<input"
				. ((!$has_function || $function === "") && preg_match('~(?<!o)int(?!er)~', $field["type"]) && !preg_match('~\[\]~', $field["full_type"]) ? " type='number'" : "")
				. " value='" . h($value) . "'" . ($maxlength ? " data-maxlength='$maxlength'" : "")
				. (preg_match('~char|binary~', $field["type"]) && $maxlength > 20 ? " size='40'" : "")
				. "$attrs>"
			;
		}
		echo $adminer->editHint($_GET["edit"], $field, $value);
		// skip 'original'
		$first = 0;
		foreach ($functions as $key => $val) {
			if ($key === "" || !$val) {
				break;
			}
			$first++;
		}
		if ($first) {
			echo script("mixin(qsl('td'), {onchange: partial(skipOriginal, $first), oninput: function () { this.onchange(); }});");
		}
	}
}

/** Process edit input field
* @param one field from fields()
* @return string or false to leave the original value
*/
function process_input($field) {
	global $adminer, $driver;
	$idf = bracket_escape($field["field"]);
	$function = $_POST["function"][$idf];
	$value = $_POST["fields"][$idf];
	if ($field["type"] == "enum") {
		if ($value == -1) {
			return false;
		}
		if ($value == "") {
			return "NULL";
		}
		return +$value;
	}
	if ($field["auto_increment"] && $value == "") {
		return null;
	}
	if ($function == "orig") {
		return (preg_match('~^CURRENT_TIMESTAMP~i', $field["on_update"]) ? idf_escape($field["field"]) : false);
	}
	if ($function == "NULL") {
		return "NULL";
	}
	if ($field["type"] == "set") {
		return array_sum((array) $value);
	}
	if ($function == "json") {
		$function = "";
		$value = json_decode($value, true);
		if (!is_array($value)) {
			return false; //! report errors
		}
		return $value;
	}
	if (preg_match('~blob|bytea|raw|file~', $field["type"]) && ini_bool("file_uploads")) {
		$file = get_file("fields-$idf");
		if (!is_string($file)) {
			return false; //! report errors
		}
		return $driver->quoteBinary($file);
	}
	return $adminer->processInput($field, $value, $function);
}

/** Compute fields() from $_POST edit data
* @return array
*/
function fields_from_edit() {
	global $driver;
	$return = array();
	foreach ((array) $_POST["field_keys"] as $key => $val) {
		if ($val != "") {
			$val = bracket_escape($val);
			$_POST["function"][$val] = $_POST["field_funs"][$key];
			$_POST["fields"][$val] = $_POST["field_vals"][$key];
		}
	}
	foreach ((array) $_POST["fields"] as $key => $val) {
		$name = bracket_escape($key, 1); // 1 - back
		$return[$name] = array(
			"field" => $name,
			"privileges" => array("insert" => 1, "update" => 1),
			"null" => 1,
			"auto_increment" => ($key == $driver->primary),
		);
	}
	return $return;
}

/** Print results of search in all tables
* @uses $_GET["where"][0]
* @uses $_POST["tables"]
* @return null
*/
function search_tables() {
	global $adminer, $connection;
	$_GET["where"][0]["val"] = $_POST["query"];
	$sep = "<ul>\n";
	foreach (table_status('', true) as $table => $table_status) {
		$name = $adminer->tableName($table_status);
		if (isset($table_status["Engine"]) && $name != "" && (!$_POST["tables"] || in_array($table, $_POST["tables"]))) {
			$result = $connection->query("SELECT" . limit("1 FROM " . table($table), " WHERE " . implode(" AND ", $adminer->selectSearchProcess(fields($table), array())), 1));
			if (!$result || $result->fetch_row()) {
				$print = "<a href='" . h(ME . "select=" . urlencode($table) . "&where[0][op]=" . urlencode($_GET["where"][0]["op"]) . "&where[0][val]=" . urlencode($_GET["where"][0]["val"])) . "'>$name</a>";
				echo "$sep<li>" . ($result ? $print : "<p class='error'>$print: " . error()) . "\n";
				$sep = "";
			}
		}
	}
	echo ($sep ? "<p class='message'>" . lang('No tables.') : "</ul>") . "\n";
}

/** Send headers for export
* @param string
* @param bool
* @return string extension
*/
function dump_headers($identifier, $multi_table = false) {
	global $adminer;
	$return = $adminer->dumpHeaders($identifier, $multi_table);
	$output = $_POST["output"];
	if ($output != "text") {
		header("Content-Disposition: attachment; filename=" . $adminer->dumpFilename($identifier) . ".$return" . ($output != "file" && preg_match('~^[0-9a-z]+$~', $output) ? ".$output" : ""));
	}
	session_write_close();
	ob_flush();
	flush();
	return $return;
}

/** Print CSV row
* @param array
* @return null
*/
function dump_csv($row) {
	foreach ($row as $key => $val) {
		if (preg_match('~["\n,;\t]|^0|\.\d*0$~', $val) || $val === "") {
			$row[$key] = '"' . str_replace('"', '""', $val) . '"';
		}
	}
	echo implode(($_POST["format"] == "csv" ? "," : ($_POST["format"] == "tsv" ? "\t" : ";")), $row) . "\r\n";
}

/** Apply SQL function
* @param string
* @param string escaped column identifier
* @return string
*/
function apply_sql_function($function, $column) {
	return ($function ? ($function == "unixepoch" ? "DATETIME($column, '$function')" : ($function == "count distinct" ? "COUNT(DISTINCT " : strtoupper("$function(")) . "$column)") : $column);
}

/** Get path of the temporary directory
* @return string
*/
function get_temp_dir() {
	$return = ini_get("upload_tmp_dir"); // session_save_path() may contain other storage path
	if (!$return) {
		if (function_exists('sys_get_temp_dir')) {
			$return = sys_get_temp_dir();
		} else {
			$filename = @tempnam("", ""); // @ - temp directory can be disabled by open_basedir
			if (!$filename) {
				return false;
			}
			$return = dirname($filename);
			unlink($filename);
		}
	}
	return $return;
}

/** Open and exclusively lock a file
* @param string
* @return resource or null for error
*/
function file_open_lock($filename) {
	$fp = @fopen($filename, "r+"); // @ - may not exist
	if (!$fp) { // c+ is available since PHP 5.2.6
		$fp = @fopen($filename, "w"); // @ - may not be writable
		if (!$fp) {
			return;
		}
		chmod($filename, 0660);
	}
	flock($fp, LOCK_EX);
	return $fp;
}

/** Write and unlock a file
* @param resource
* @param string
*/
function file_write_unlock($fp, $data) {
	rewind($fp);
	fwrite($fp, $data);
	ftruncate($fp, strlen($data));
	flock($fp, LOCK_UN);
	fclose($fp);
}

/** Read password from file adminer.key in temporary directory or create one
* @param bool
* @return string or false if the file can not be created
*/
function password_file($create) {
	$filename = get_temp_dir() . "/adminer.key";
	$return = @file_get_contents($filename); // @ - may not exist
	if ($return || !$create) {
		return $return;
	}
	$fp = @fopen($filename, "w"); // @ - can have insufficient rights //! is not atomic
	if ($fp) {
		chmod($filename, 0660);
		$return = rand_string();
		fwrite($fp, $return);
		fclose($fp);
	}
	return $return;
}

/** Get a random string
* @return string 32 hexadecimal characters
*/
function rand_string() {
	return md5(uniqid(mt_rand(), true));
}

/** Format value to use in select
* @param string
* @param string
* @param array
* @param int
* @return string HTML
*/
function select_value($val, $link, $field, $text_length) {
	global $adminer;
	if (is_array($val)) {
		$return = "";
		foreach ($val as $k => $v) {
			$return .= "<tr>"
				. ($val != array_values($val) ? "<th>" . h($k) : "")
				. "<td>" . select_value($v, $link, $field, $text_length)
			;
		}
		return "<table cellspacing='0'>$return</table>";
	}
	if (!$link) {
		$link = $adminer->selectLink($val, $field);
	}
	if ($link === null) {
		if (is_mail($val)) {
			$link = "mailto:$val";
		}
		if (is_url($val)) {
			$link = $val; // IE 11 and all modern browsers hide referrer
		}
	}
	$return = $adminer->editVal($val, $field);
	if ($return !== null) {
		if (!is_utf8($return)) {
			$return = "\0"; // htmlspecialchars of binary data returns an empty string
		} elseif ($text_length != "" && is_shortable($field)) {
			$return = shorten_utf8($return, max(0, +$text_length)); // usage of LEFT() would reduce traffic but complicate query - expected average speedup: .001 s VS .01 s on local network
		} else {
			$return = h($return);
		}
	}
	return $adminer->selectVal($return, $link, $field, $val);
}

/** Check whether the string is e-mail address
* @param string
* @return bool
*/
function is_mail($email) {
	$atom = '[-a-z0-9!#$%&\'*+/=?^_`{|}~]'; // characters of local-name
	$domain = '[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])'; // one domain component
	$pattern = "$atom+(\\.$atom+)*@($domain?\\.)+$domain";
	return is_string($email) && preg_match("(^$pattern(,\\s*$pattern)*\$)i", $email);
}

/** Check whether the string is URL address
* @param string
* @return bool
*/
function is_url($string) {
	$domain = '[a-z0-9]([-a-z0-9]{0,61}[a-z0-9])'; // one domain component //! IDN
	return preg_match("~^(https?)://($domain?\\.)+$domain(:\\d+)?(/.*)?(\\?.*)?(#.*)?\$~i", $string); //! restrict path, query and fragment characters
}

/** Check if field should be shortened
* @param array
* @return bool
*/
function is_shortable($field) {
	return preg_match('~char|text|json|lob|geometry|point|linestring|polygon|string|bytea~', $field["type"]);
}

/** Get query to compute number of found rows
* @param string
* @param array
* @param bool
* @param array
* @return string
*/
function count_rows($table, $where, $is_group, $group) {
	global $jush;
	$query = " FROM " . table($table) . ($where ? " WHERE " . implode(" AND ", $where) : "");
	return ($is_group && ($jush == "sql" || count($group) == 1)
		? "SELECT COUNT(DISTINCT " . implode(", ", $group) . ")$query"
		: "SELECT COUNT(*)" . ($is_group ? " FROM (SELECT 1$query GROUP BY " . implode(", ", $group) . ") x" : $query)
	);
}

/** Run query which can be killed by AJAX call after timing out
* @param string
* @return array of strings
*/
function slow_query($query) {
	global $adminer, $token, $driver;
	$db = $adminer->database();
	$timeout = $adminer->queryTimeout();
	$slow_query = $driver->slowQuery($query, $timeout);
	if (!$slow_query && support("kill") && is_object($connection2 = connect()) && ($db == "" || $connection2->select_db($db))) {
		$kill = $connection2->result(connection_id()); // MySQL and MySQLi can use thread_id but it's not in PDO_MySQL
		?>
<script<?php echo nonce(); ?>>
var timeout = setTimeout(function () {
	ajax('<?php echo js_escape(ME); ?>script=kill', function () {
	}, 'kill=<?php echo $kill; ?>&token=<?php echo $token; ?>');
}, <?php echo 1000 * $timeout; ?>);
</script>
<?php
	} else {
		$connection2 = null;
	}
	ob_flush();
	flush();
	$return = @get_key_vals(($slow_query ? $slow_query : $query), $connection2, false); // @ - may be killed
	if ($connection2) {
		echo script("clearTimeout(timeout);");
		ob_flush();
		flush();
	}
	return $return;
}

/** Generate BREACH resistant CSRF token
* @return string
*/
function get_token() {
	$rand = rand(1, 1e6);
	return ($rand ^ $_SESSION["token"]) . ":$rand";
}

/** Verify if supplied CSRF token is valid
* @return bool
*/
function verify_token() {
	list($token, $rand) = explode(":", $_POST["token"]);
	return ($rand ^ $_SESSION["token"]) == $token;
}

// used in compiled version
function lzw_decompress($binary) {
	// convert binary string to codes
	$dictionary_count = 256;
	$bits = 8; // ceil(log($dictionary_count, 2))
	$codes = array();
	$rest = 0;
	$rest_length = 0;
	for ($i=0; $i < strlen($binary); $i++) {
		$rest = ($rest << 8) + ord($binary[$i]);
		$rest_length += 8;
		if ($rest_length >= $bits) {
			$rest_length -= $bits;
			$codes[] = $rest >> $rest_length;
			$rest &= (1 << $rest_length) - 1;
			$dictionary_count++;
			if ($dictionary_count >> $bits) {
				$bits++;
			}
		}
	}
	// decompression
	$dictionary = range("\0", "\xFF");
	$return = "";
	foreach ($codes as $i => $code) {
		$element = $dictionary[$code];
		if (!isset($element)) {
			$element = $word . $word[0];
		}
		$return .= $element;
		if ($i) {
			$dictionary[] = $word . $element[0];
		}
		$word = $element;
	}
	return $return;
}

/** Return events to display help on mouse over
* @param string JS expression
* @param bool JS expression
* @return string
*/
function on_help($command, $side = 0) {
	return script("mixin(qsl('select, input'), {onmouseover: function (event) { helpMouseover.call(this, event, $command, $side) }, onmouseout: helpMouseout});", "");
}

/** Print edit data form
* @param string
* @param array
* @param mixed
* @param bool
* @return null
*/
function edit_form($table, $fields, $row, $update) {
	global $adminer, $jush, $token, $error;
	$table_name = $adminer->tableName(table_status1($table, true));
	page_header(
		($update ? lang('Edit') : lang('Insert')),
		$error,
		array("select" => array($table, $table_name)),
		$table_name
	);
	$adminer->editRowPrint($table, $fields, $row, $update);
	if ($row === false) {
		echo "<p class='error'>" . lang('No rows.') . "\n";
	}
	?>
<form action="" method="post" enctype="multipart/form-data" id="form">
<?php
	if (!$fields) {
		echo "<p class='error'>" . lang('You have no privileges to update this table.') . "\n";
	} else {
		echo "<table cellspacing='0' class='layout'>" . script("qsl('table').onkeydown = editingKeydown;");

		foreach ($fields as $name => $field) {
			echo "<tr><th>" . $adminer->fieldName($field);
			$default = $_GET["set"][bracket_escape($name)];
			if ($default === null) {
				$default = $field["default"];
				if ($field["type"] == "bit" && preg_match("~^b'([01]*)'\$~", $default, $regs)) {
					$default = $regs[1];
				}
			}
			$value = ($row !== null
				? ($row[$name] != "" && $jush == "sql" && preg_match("~enum|set~", $field["type"])
					? (is_array($row[$name]) ? array_sum($row[$name]) : +$row[$name])
					: (is_bool($row[$name]) ? +$row[$name] : $row[$name])
				)
				: (!$update && $field["auto_increment"]
					? ""
					: (isset($_GET["select"]) ? false : $default)
				)
			);
			if (!$_POST["save"] && is_string($value)) {
				$value = $adminer->editVal($value, $field);
			}
			$function = ($_POST["save"]
				? (string) $_POST["function"][$name]
				: ($update && preg_match('~^CURRENT_TIMESTAMP~i', $field["on_update"])
					? "now"
					: ($value === false ? null : ($value !== null ? '' : 'NULL'))
				)
			);
			if (!$_POST && !$update && $value == $field["default"] && preg_match('~^[\w.]+\(~', $value)) {
				$function = "SQL";
			}
			if (preg_match("~time~", $field["type"]) && preg_match('~^CURRENT_TIMESTAMP~i', $value)) {
				$value = "";
				$function = "now";
			}
			input($field, $value, $function);
			echo "\n";
		}
		if (!support("table")) {
			echo "<tr>"
				. "<th><input name='field_keys[]'>"
				. script("qsl('input').oninput = fieldChange;")
				. "<td class='function'>" . html_select("field_funs[]", $adminer->editFunctions(array("null" => isset($_GET["select"]))))
				. "<td><input name='field_vals[]'>"
				. "\n"
			;
		}
		echo "</table>\n";
	}
	echo "<p>\n";
	if ($fields) {
		echo "<input type='submit' value='" . lang('Save') . "'>\n";
		if (!isset($_GET["select"])) {
			echo "<input type='submit' name='insert' value='" . ($update
				? lang('Save and continue edit')
				: lang('Save and insert next')
			) . "' title='Ctrl+Shift+Enter'>\n";
			echo ($update ? script("qsl('input').onclick = function () { return !ajaxForm(this.form, '" . lang('Saving') . "…', this); };") : "");
		}
	}
	echo ($update ? "<input type='submit' name='delete' value='" . lang('Delete') . "'>" . confirm() . "\n"
		: ($_POST || !$fields ? "" : script("focus(qsa('td', qs('#form'))[1].firstChild);"))
	);
	if (isset($_GET["select"])) {
		hidden_fields(array("check" => (array) $_POST["check"], "clone" => $_POST["clone"], "all" => $_POST["all"]));
	}
	?>
<input type="hidden" name="referer" value="<?php echo h(isset($_POST["referer"]) ? $_POST["referer"] : $_SERVER["HTTP_REFERER"]); ?>">
<input type="hidden" name="save" value="1">
<input type="hidden" name="token" value="<?php echo $token; ?>">
</form>
<?php
}

if ($_GET["script"] == "version") {
	$fp = file_open_lock(get_temp_dir() . "/adminer.version");
	if ($fp) {
		file_write_unlock($fp, serialize(array("signature" => $_POST["signature"], "version" => $_POST["version"])));
	}
	exit;
}

global $adminer, $connection, $driver, $drivers, $edit_functions, $enum_length, $error, $functions, $grouping, $HTTPS, $inout, $jush, $LANG, $langs, $on_actions, $permanent, $structured_types, $has_token, $token, $translations, $types, $unsigned, $VERSION; // allows including Adminer inside a function

if (!$_SERVER["REQUEST_URI"]) { // IIS 5 compatibility
	$_SERVER["REQUEST_URI"] = $_SERVER["ORIG_PATH_INFO"];
}
if (!strpos($_SERVER["REQUEST_URI"], '?') && $_SERVER["QUERY_STRING"] != "") { // IIS 7 compatibility
	$_SERVER["REQUEST_URI"] .= "?$_SERVER[QUERY_STRING]";
}
if ($_SERVER["HTTP_X_FORWARDED_PREFIX"]) {
	$_SERVER["REQUEST_URI"] = $_SERVER["HTTP_X_FORWARDED_PREFIX"] . $_SERVER["REQUEST_URI"];
}
$HTTPS = ($_SERVER["HTTPS"] && strcasecmp($_SERVER["HTTPS"], "off")) || ini_bool("session.cookie_secure"); // session.cookie_secure could be set on HTTP if we are behind a reverse proxy

@ini_set("session.use_trans_sid", false); // protect links in export, @ - may be disabled
if (!defined("SID")) {
	session_cache_limiter(""); // to allow restarting session
	session_name("adminer_sid"); // use specific session name to get own namespace
	$params = array(0, preg_replace('~\?.*~', '', $_SERVER["REQUEST_URI"]), "", $HTTPS);
	if (version_compare(PHP_VERSION, '5.2.0') >= 0) {
		$params[] = true; // HttpOnly
	}
	call_user_func_array('session_set_cookie_params', $params); // ini_set() may be disabled
	session_start();
}

// disable magic quotes to be able to use database escaping function
remove_slashes(array(&$_GET, &$_POST, &$_COOKIE), $filter);

if (function_exists("get_magic_quotes_runtime") && get_magic_quotes_runtime()) {
	set_magic_quotes_runtime(false);
}


@set_time_limit(0); // @ - can be disabled
@ini_set("zend.ze1_compatibility_mode", false); // @ - deprecated
@ini_set("precision", 15); // @ - can be disabled, 15 - internal PHP precision

// not used in a single language version

$langs = array(
	'en' => 'English',
	'ru' => 'Русский',
	'zh' => '简体中文',
);
function get_lang() {
	global $LANG;
	return $LANG;
}
function lang($idf, $number = null) {
	global $LANG, $translations;
	$translation = ($translations[$idf] ? $translations[$idf] : $idf);
	if (is_array($translation)) {
		$pos = ($number == 1 ? 0
			: ($LANG == 'cs' || $LANG == 'sk' ? ($number && $number < 5 ? 1 : 2) // different forms for 1, 2-4, other
			: ($LANG == 'fr' ? (!$number ? 0 : 1) // different forms for 0-1, other
			: ($LANG == 'pl' ? ($number % 10 > 1 && $number % 10 < 5 && $number / 10 % 10 != 1 ? 1 : 2) // different forms for 1, 2-4 except 12-14, other
			: ($LANG == 'sl' ? ($number % 100 == 1 ? 0 : ($number % 100 == 2 ? 1 : ($number % 100 == 3 || $number % 100 == 4 ? 2 : 3))) // different forms for 1, 2, 3-4, other
			: ($LANG == 'lt' ? ($number % 10 == 1 && $number % 100 != 11 ? 0 : ($number % 10 > 1 && $number / 10 % 10 != 1 ? 1 : 2)) // different forms for 1, 12-19, other
			: ($LANG == 'bs' || $LANG == 'ru' || $LANG == 'sr' || $LANG == 'uk' ? ($number % 10 == 1 && $number % 100 != 11 ? 0 : ($number % 10 > 1 && $number % 10 < 5 && $number / 10 % 10 != 1 ? 1 : 2)) // different forms for 1 except 11, 2-4 except 12-14, other
			: 1 // different forms for 1, other
		))))))); // http://www.gnu.org/software/gettext/manual/html_node/Plural-forms.html
		$translation = $translation[$pos];
	}
	$args = func_get_args();
	array_shift($args);
	$format = str_replace("%d", "%s", $translation);
	if ($format != $translation) {
		$args[0] = format_number($number);
	}
	return vsprintf($format, $args);
}

function switch_lang() {
	global $LANG, $langs;
	echo "<form action='' method='post'>\n<div id='lang'>";
	echo lang('Language') . ": " . html_select("lang", $langs, $LANG, "this.form.submit();");
	echo " <input type='submit' value='" . lang('Use') . "' class='hidden'>\n";
	echo "<input type='hidden' name='token' value='" . get_token() . "'>\n"; // $token may be empty in auth.inc.php
	echo "</div>\n</form>\n";
}

if (isset($_POST["lang"]) && verify_token()) { // $error not yet available
	cookie("adminer_lang", $_POST["lang"]);
	$_SESSION["lang"] = $_POST["lang"]; // cookies may be disabled
	$_SESSION["translations"] = array(); // used in compiled version
	redirect(remove_from_uri());
}

$LANG = "en";
if (isset($langs[$_COOKIE["adminer_lang"]])) {
	cookie("adminer_lang", $_COOKIE["adminer_lang"]);
	$LANG = $_COOKIE["adminer_lang"];
} elseif (isset($langs[$_SESSION["lang"]])) {
	$LANG = $_SESSION["lang"];
} else {
	$accept_language = array();
	preg_match_all('~([-a-z]+)(;q=([0-9.]+))?~', str_replace("_", "-", strtolower($_SERVER["HTTP_ACCEPT_LANGUAGE"])), $matches, PREG_SET_ORDER);
	foreach ($matches as $match) {
		$accept_language[$match[1]] = (isset($match[3]) ? $match[3] : 1);
	}
	arsort($accept_language);
	foreach ($accept_language as $key => $q) {
		if (isset($langs[$key])) {
			$LANG = $key;
			break;
		}
		$key = preg_replace('~-.*~', '', $key);
		if (!isset($accept_language[$key]) && isset($langs[$key])) {
			$LANG = $key;
			break;
		}
	}
}
if ($LANG == "en") {
	$translations = array(
		'Too many unsuccessful logins, try again in %d minute(s).' => array('Too many unsuccessful logins, try again in %d minute.', 'Too many unsuccessful logins, try again in %d minutes.'),
		'Query executed OK, %d row(s) affected.' => array('Query executed OK, %d row affected.', 'Query executed OK, %d rows affected.'),
		'%d byte(s)' => array('%d byte', '%d bytes'),
		'Routine has been called, %d row(s) affected.' => array('Routine has been called, %d row affected.', 'Routine has been called, %d rows affected.'),
		'%d process(es) have been killed.' => array('%d process has been killed.', '%d processes have been killed.'),
		'%d / ' => '%d / ',
		'%d row(s)' => array('%d row', '%d rows'),
		'%d item(s) have been affected.' => array('%d item has been affected.', '%d items have been affected.'),
		'%d row(s) have been imported.' => array('%d row has been imported.', '%d rows have been imported.'),
		'%d e-mail(s) have been sent.' => array('%d e-mail has been sent.', '%d e-mails have been sent.'),
		'%d in total' => '%d in total',
		'%d query(s) executed OK.' => array('%d query executed OK.', '%d queries executed OK.'),
	);	
}
elseif ($LANG == "ru") {
	$translations = array(
		'Login' => 'Войти',
		'Logout successful.' => 'Вы успешно покинули систему.',
		'Invalid credentials.' => 'Неправильное имя пользователя или пароль.',
		'Server' => 'Сервер',
		'Username' => 'Имя пользователя',
		'Password' => 'Пароль',
		'Select database' => 'Выбрать базу данных',
		'Invalid database.' => 'Неверная база данных.',
		'Table has been dropped.' => 'Таблица была удалена.',
		'Table has been altered.' => 'Таблица была изменена.',
		'Table has been created.' => 'Таблица была создана.',
		'Alter table' => 'Изменить таблицу',
		'Create table' => 'Создать таблицу',
		'Table name' => 'Название таблицы',
		'engine' => 'Тип таблицы',
		'collation' => 'режим сопоставления',
		'Column name' => 'Название поля',
		'Type' => 'Тип',
		'Length' => 'Длина',
		'Auto Increment' => 'Автоматическое приращение',
		'Options' => 'Действие',
		'Save' => 'Сохранить',
		'Drop' => 'Удалить',
		'Database has been dropped.' => 'База данных была удалена.',
		'Database has been created.' => 'База данных была создана.',
		'Database has been renamed.' => 'База данных была переименована.',
		'Database has been altered.' => 'База данных была изменена.',
		'Alter database' => 'Изменить базу данных',
		'Create database' => 'Создать базу данных',
		'SQL command' => 'SQL-запрос',
		'Logout' => 'Выйти',
		'database' => 'база данных',
		'Use' => 'Выбрать',
		'No tables.' => 'В базе данных нет таблиц.',
		'select' => 'выбрать',
		'Item has been deleted.' => 'Запись удалена.',
		'Item has been updated.' => 'Запись обновлена.',
		'Item%s has been inserted.' => 'Запись%s была вставлена.',
		'Edit' => 'Редактировать',
		'Insert' => 'Вставить',
		'Save and insert next' => 'Сохранить и вставить ещё',
		'Delete' => 'Стереть',
		'Database' => 'База данных',
		'Routines' => 'Хранимые процедуры и функции',
		'Indexes have been altered.' => 'Индексы изменены.',
		'Indexes' => 'Индексы',
		'Alter indexes' => 'Изменить индексы',
		'Add next' => 'Добавить ещё',
		'Language' => 'Язык',
		'Select' => 'Выбрать',
		'New item' => 'Новая запись',
		'Search' => 'Поиск',
		'Sort' => 'Сортировать',
		'descending' => 'по убыванию',
		'Limit' => 'Лимит',
		'No rows.' => 'Нет записей.',
		'Action' => 'Действие',
		'edit' => 'редактировать',
		'Page' => 'Страница',
		'Query executed OK, %d row(s) affected.' => array('Запрос завершён, изменена %d запись.', 'Запрос завершён, изменены %d записи.', 'Запрос завершён, изменено %d записей.'),
		'Error in query' => 'Ошибка в запросe',
		'Execute' => 'Выполнить',
		'Table' => 'Таблица',
		'Foreign keys' => 'Внешние ключи',
		'Triggers' => 'Триггеры',
		'View' => 'Представление',
		'Unable to select the table' => 'Не удалось получить данные из таблицы',
		'Invalid CSRF token. Send the form again.' => 'Недействительный CSRF-токен. Отправите форму ещё раз.',
		'Comment' => 'Комментарий',
		'Default values' => 'Значения по умолчанию',
		'%d byte(s)' => array('%d байт', '%d байта', '%d байтов'),
		'No commands to execute.' => 'Нет команд для выполнения.',
		'Unable to upload a file.' => 'Не удалось загрузить файл на сервер.',
		'File upload' => 'Загрузить файл на сервер',
		'File uploads are disabled.' => 'Загрузка файлов на сервер запрещена.',
		'Routine has been called, %d row(s) affected.' => array('Была вызвана процедура, %d запись была изменена.', 'Была вызвана процедура, %d записи было изменено.', 'Была вызвана процедура, %d записей было изменено.'),
		'Call' => 'Вызвать',
		'No extension' => 'Нет расширений',
		'None of the supported PHP extensions (%s) are available.' => 'Недоступно ни одного расширения из поддерживаемых (%s).',
		'Session support must be enabled.' => 'Сессии должны быть включены.',
		'Session expired, please login again.' => 'Срок действия сессии истёк, нужно снова войти в систему.',
		'Text length' => 'Длина текста',
		'Foreign key has been dropped.' => 'Внешний ключ был удалён.',
		'Foreign key has been altered.' => 'Внешний ключ был изменён.',
		'Foreign key has been created.' => 'Внешний ключ был создан.',
		'Foreign key' => 'Внешний ключ',
		'Target table' => 'Результирующая таблица',
		'Change' => 'Изменить',
		'Source' => 'Источник',
		'Target' => 'Цель',
		'Add column' => 'Добавить поле',
		'Alter' => 'Изменить',
		'Add foreign key' => 'Добавить внешний ключ',
		'ON DELETE' => 'При стирании',
		'ON UPDATE' => 'При обновлении',
		'Index Type' => 'Тип индекса',
		'Column (length)' => 'Поле (длина)',
		'View has been dropped.' => 'Представление было удалено.',
		'View has been altered.' => 'Представление было изменено.',
		'View has been created.' => 'Представление было создано.',
		'Alter view' => 'Изменить представление',
		'Create view' => 'Создать представление',
		'Name' => 'Название',
		'Process list' => 'Список процессов',
		'%d process(es) have been killed.' => array('Был завершён %d процесс.', 'Было завершено %d процесса.', 'Было завершено %d процессов.'),
		'Kill' => 'Завершить',
		'Parameter name' => 'Название параметра',
		'Database schema' => 'Схема базы данных',
		'Create procedure' => 'Создать процедуру',
		'Create function' => 'Создать функцию',
		'Routine has been dropped.' => 'Процедура была удалена.',
		'Routine has been altered.' => 'Процедура была изменена.',
		'Routine has been created.' => 'Процедура была создана.',
		'Alter function' => 'Изменить функцию',
		'Alter procedure' => 'Изменить процедуру',
		'Return type' => 'Возвращаемый тип',
		'Add trigger' => 'Добавить триггер',
		'Trigger has been dropped.' => 'Триггер был удалён.',
		'Trigger has been altered.' => 'Триггер был изменён.',
		'Trigger has been created.' => 'Триггер был создан.',
		'Alter trigger' => 'Изменить триггер',
		'Create trigger' => 'Создать триггер',
		'Time' => 'Время',
		'Event' => 'Событие',
		'%s version: %s through PHP extension %s' => 'Версия %s: %s с PHP-расширением %s',
		'%d row(s)' => array('%d строка', '%d строки', '%d строк'),
		'Remove' => 'Удалить',
		'Are you sure?' => 'Вы уверены?',
		'Privileges' => 'Полномочия',
		'Create user' => 'Создать пользователя',
		'User has been dropped.' => 'Пользователь был удалён.',
		'User has been altered.' => 'Пользователь был изменён.',
		'User has been created.' => 'Пользователь был создан.',
		'Hashed' => 'Хешировано',
		'Column' => 'поле',
		'Routine' => 'Процедура',
		'Grant' => 'Позволить',
		'Revoke' => 'Запретить',
		'Too big POST data. Reduce the data or increase the %s configuration directive.' => 'Слишком большой объем POST-данных. Пошлите меньший объём данных или увеличьте параметр конфигурационной директивы %s.',
		'Logged as: %s' => 'Вы вошли как: %s',
		'Move up' => 'Переместить вверх',
		'Move down' => 'Переместить вниз',
		'Functions' => 'Функции',
		'Aggregation' => 'Агрегация',
		'Export' => 'Экспорт',
		'Output' => 'Выходные данные',
		'open' => 'открыть',
		'save' => 'сохранить',
		'Format' => 'Формат',
		'Tables' => 'Таблицы',
		'Data' => 'Данные',
		'Event has been dropped.' => 'Событие было удалено.',
		'Event has been altered.' => 'Событие было изменено.',
		'Event has been created.' => 'Событие было создано.',
		'Alter event' => 'Изменить событие',
		'Create event' => 'Создать событие',
		'At given time' => 'В данное время',
		'Every' => 'Каждые',
		'Events' => 'События',
		'Schedule' => 'Расписание',
		'Start' => 'Начало',
		'End' => 'Конец',
		'Status' => 'Состояние',
		'On completion preserve' => 'После завершения сохранить',
		'Tables and views' => 'Таблицы и представления',
		'Data Length' => 'Объём данных',
		'Index Length' => 'Объём индексов',
		'Data Free' => 'Свободное место',
		'Collation' => 'Режим сопоставления',
		'Analyze' => 'Анализировать',
		'Optimize' => 'Оптимизировать',
		'Check' => 'Проверить',
		'Repair' => 'Исправить',
		'Truncate' => 'Очистить',
		'Tables have been truncated.' => 'Таблицы были очищены.',
		'Rows' => 'Строк',
		',' => ' ',
		'0123456789' => '0123456789',
		'Tables have been moved.' => 'Таблицы были перемещены.',
		'Move to other database' => 'Переместить в другую базу данных',
		'Move' => 'Переместить',
		'Engine' => 'Тип таблиц',
		'Save and continue edit' => 'Сохранить и продолжить редактирование',
		'original' => 'исходный',
		'%d item(s) have been affected.' => array('Была изменена %d запись.', 'Были изменены %d записи.', 'Было изменено %d записей.'),
		'Whole result' => 'Весь результат',
		'Tables have been dropped.' => 'Таблицы были удалены.',
		'Clone' => 'Клонировать',
		'Partition by' => 'Разделить по',
		'Partitions' => 'Разделы',
		'Partition name' => 'Название раздела',
		'Values' => 'Параметры',
		'%d row(s) have been imported.' => array('Импортирована %d строка.', 'Импортировано %d строки.', 'Импортировано %d строк.'),
		'Import' => 'Импорт',
		'Stop on error' => 'Остановить при ошибке',
		'Maximum number of allowed fields exceeded. Please increase %s.' => 'Достигнуто максимальное значение количества доступных полей. Увеличьте %s.',
		'anywhere' => 'в любом месте',
		'%.3f s' => '%.3f s',
		'$1-$3-$5' => '$5.$3.$1',
		'[yyyy]-mm-dd' => 'дд.мм.[гггг]',
		'History' => 'История',
		'Variables' => 'Переменные',
		'Source and target columns must have the same data type, there must be an index on the target columns and referenced data must exist.' => 'Поля должны иметь одинаковые типы данных, в результирующем поле должен быть индекс, данные для импорта должны существовать.',
		'Relations' => 'Отношения',
		'Run file' => 'Запустить файл',
		'Clear' => 'Очистить',
		'Maximum allowed file size is %sB.' => 'Максимальный разрешённый размер файла — %sB.',
		'Numbers' => 'Числа',
		'Date and time' => 'Дата и время',
		'Strings' => 'Строки',
		'Binary' => 'Двоичный тип',
		'Lists' => 'Списки',
		'Editor' => 'Редактор',
		'E-mail' => 'Эл. почта',
		'From' => 'От',
		'Subject' => 'Тема',
		'Send' => 'Послать',
		'%d e-mail(s) have been sent.' => array('Было отправлено %d письмо.', 'Было отправлено %d письма.', 'Было отправлено %d писем.'),
		'Webserver file %s' => 'Файл %s на вебсервере',
		'File does not exist.' => 'Такого файла не существует.',
		'%d in total' => 'Всего %d',
		'Permanent login' => 'Оставаться в системе',
		'Databases have been dropped.' => 'Базы данных удалены.',
		'Search data in tables' => 'Поиск в таблицах',
		'Schema' => 'Схема',
		'Alter schema' => 'Изменить схему',
		'Create schema' => 'Новая схема',
		'Schema has been dropped.' => 'Схема удалена.',
		'Schema has been created.' => 'Создана новая схема.',
		'Schema has been altered.' => 'Схема изменена.',
		'Sequences' => '«Последовательности»',
		'Create sequence' => 'Создать «последовательность»',
		'Alter sequence' => 'Изменить «последовательность»',
		'Sequence has been dropped.' => '«Последовательность» удалена.',
		'Sequence has been created.' => 'Создана новая «последовательность».',
		'Sequence has been altered.' => '«Последовательность» изменена.',
		'User types' => 'Типы пользователей',
		'Create type' => 'Создать тип',
		'Alter type' => 'Изменить тип',
		'Type has been dropped.' => 'Тип удален.',
		'Type has been created.' => 'Создан новый тип.',
		'Ctrl+click on a value to modify it.' => 'Выполните Ctrl+Щелчок мышью по значению, чтобы его изменить.',
		'Use edit link to modify this value.' => 'Изменить это значение можно с помощью ссылки «изменить».',
		'last' => 'последняя',
		'From server' => 'С сервера',
		'System' => 'Движок',
		'Select data' => 'Выбрать',
		'Show structure' => 'Показать структуру',
		'empty' => 'пусто',
		'Network' => 'Сеть',
		'Geometry' => 'Геометрия',
		'File exists.' => 'Файл уже существует.',
		'Attachments' => 'Прикреплённые файлы',
		'%d query(s) executed OK.' => array('%d запрос выполнен успешно.', '%d запроса выполнено успешно.', '%d запросов выполнено успешно.'),
		'Show only errors' => 'Только ошибки',
		'Refresh' => 'Обновить',
		'Invalid schema.' => 'Неправильная схема.',
		'Please use one of the extensions %s.' => 'Используйте одно из этих расширений %s.',
		'now' => 'сейчас',
		'ltr' => 'ltr',
		'Tables have been copied.' => 'Таблицы скопированы.',
		'Copy' => 'Копировать',
		'Permanent link' => 'Постоянная ссылка',
		'Edit all' => 'Редактировать всё',
		'HH:MM:SS' => 'ЧЧ:ММ:СС',
		'Tables have been optimized.' => 'Таблицы оптимизированы.',
		'Materialized view' => 'Материализованное представление',
		'Vacuum' => 'Вакуум',
		'Selected' => 'Выбранные',
		'File must be in UTF-8 encoding.' => 'Файл должен быть в кодировке UTF-8.',
		'Modify' => 'Изменить',
		'Loading' => 'Загрузка',
		'Load more data' => 'Загрузить ещё данные',
		'ATTACH queries are not supported.' => 'ATTACH-запросы не поддерживаются.',
		'%d / ' => '%d / ',
		'Limit rows' => 'Лимит строк',
		'Default value' => 'Значение по умолчанию',
		'Full table scan' => 'Анализ полной таблицы',
		'Too many unsuccessful logins, try again in %d minute(s).' => array('Слишком много неудачных попыток входа. Попробуйте снова через %d минуту.', 'Слишком много неудачных попыток входа. Попробуйте снова через %d минуты.', 'Слишком много неудачных попыток входа. Попробуйте снова через %d минут.'),
		'Master password expired. <a href="https://www.adminer.org/en/extension/"%s>Implement</a> %s method to make it permanent.' => 'Мастер-пароль истёк. <a href="https://www.adminer.org/en/extension/"%s>Реализуйте</a> метод %s, чтобы сделать его постоянным.',
		'If you did not send this request from Adminer then close this page.' => 'Если вы не посылали этот запрос из Adminer, закройте эту страницу.',
		'You can upload a big SQL file via FTP and import it from server.' => 'Вы можете закачать большой SQL-файл по FTP и затем импортировать его с сервера.',
		'Size' => 'Размер',
		'Compute' => 'Вычислить',
		'You are offline.' => 'Вы не выполнили вход.',
		'You have no privileges to update this table.' => 'У вас нет прав на обновление этой таблицы.',
		'Saving' => 'Сохранение',
		'yes' => 'Да',
		'no' => 'Нет',
	);
	
}
elseif ($LANG == "zh") {
	$translations = array(
		// label for database system selection (MySQL, SQLite, ...)
		'System' => '系统',
		'Server' => '服务器',
		'Username' => '用户名',
		'Password' => '密码',
		'Permanent login' => '保持登录',
		'Login' => '登录',
		'Logout' => '登出',
		'Logged as: %s' => '登录用户：%s',
		'Logout successful.' => '成功登出。',
		'Thanks for using Adminer, consider <a href="https://www.adminer.org/en/donation/">donating</a>.' => '感谢使用Adminer，请考虑为我们<a href="https://www.adminer.org/en/donation/">捐款（英文页面）</a>.',
		'Invalid credentials.' => '无效凭据。',
		'There is a space in the input password which might be the cause.' => '您输入的密码中有一个空格，这可能是导致问题的原因。',
		'Adminer does not support accessing a database without a password, <a href="https://www.adminer.org/en/password/"%s>more information</a>.' => 'Adminer默认不支持访问没有密码的数据库，<a href="https://www.adminer.org/en/password/"%s>详情见这里</a>.',
		'Database does not support password.' => '数据库不支持密码。',
		'Too many unsuccessful logins, try again in %d minute(s).' => '登录失败次数过多，请 %d 分钟后重试。',
		'Master password expired. <a href="https://www.adminer.org/en/extension/"%s>Implement</a> %s method to make it permanent.' => '主密码已过期。<a href="https://www.adminer.org/en/extension/"%s>请扩展</a> %s 方法让它永久化。',
		'Language' => '语言',
		'Invalid CSRF token. Send the form again.' => '无效 CSRF 令牌。请重新发送表单。',
		'If you did not send this request from Adminer then close this page.' => '如果您并没有从Adminer发送请求，请关闭此页面。',
		'No extension' => '没有扩展',
		'None of the supported PHP extensions (%s) are available.' => '没有支持的 PHP 扩展可用（%s）。',
		'Connecting to privileged ports is not allowed.' => '不允许连接到特权端口。',
		'Disable %s or enable %s or %s extensions.' => '禁用 %s 或启用 %s 或 %s 扩展。',
		'Session support must be enabled.' => '必须启用会话支持。',
		'Session expired, please login again.' => '会话已过期，请重新登录。',
		'The action will be performed after successful login with the same credentials.' => '此操作将在成功使用相同的凭据登录后执行。',
		'%s version: %s through PHP extension %s' => '%s 版本：%s， 使用PHP扩展 %s',
		'Refresh' => '刷新',
		
		// text direction - 'ltr' or 'rtl'
		'ltr' => 'ltr',
	
		'Privileges' => '权限',
		'Create user' => '创建用户',
		'User has been dropped.' => '已删除用户。',
		'User has been altered.' => '已修改用户。',
		'User has been created.' => '已创建用户。',
		'Hashed' => 'Hashed',
		'Column' => '列',
		'Routine' => '子程序',
		'Grant' => '授权',
		'Revoke' => '废除',
	
		'Process list' => '进程列表',
		'%d process(es) have been killed.' => '%d 个进程被终止',
		'Kill' => '终止',
	
		'Variables' => '变量',
		'Status' => '状态',
	
		'SQL command' => 'SQL命令',
		'%d query(s) executed OK.' => '%d 条查询已成功执行。',
		'Query executed OK, %d row(s) affected.' => '查询执行完毕，%d 行受影响。',
		'No commands to execute.' => '没有命令被执行。',
		'Error in query' => '查询出错',
		'Unknown error.' => '未知错误。',
		'Warnings' => '警告',
		'ATTACH queries are not supported.' => '不支持ATTACH查询。',
		'Execute' => '执行',
		'Stop on error' => '出错时停止',
		'Show only errors' => '仅显示错误',
		// sprintf() format for time of the command
		'%.3f s' => '%.3f 秒',
		'History' => '历史',
		'Clear' => '清除',
		'Edit all' => '编辑全部',
	
		'File upload' => '文件上传',
		'From server' => '来自服务器',
		'Webserver file %s' => 'Web服务器文件 %s',
		'Run file' => '运行文件',
		'File does not exist.' => '文件不存在。',
		'File uploads are disabled.' => '文件上传被禁用。',
		'Unable to upload a file.' => '不能上传文件。',
		'Maximum allowed file size is %sB.' => '最多允许的文件大小为 %sB。',
		'Too big POST data. Reduce the data or increase the %s configuration directive.' => 'POST 数据太大。请减少数据或者增加 %s 配置命令。',
		'You can upload a big SQL file via FTP and import it from server.' => '您可以通过FTP上传大型SQL文件并从服务器导入。',
		'You are offline.' => '您离线了。',
	
		'Export' => '导出',
		'Output' => '输出',
		'open' => '打开',
		'save' => '保存',
		'Saving' => '保存中',
		'Format' => '格式',
		'Data' => '数据',
	
		'Database' => '数据库',
		'database' => '数据库',
		'DB' => '数据库',
		'Use' => '使用',
		'Select database' => '选择数据库',
		'Invalid database.' => '无效数据库。',
		'Database has been dropped.' => '已删除数据库。',
		'Databases have been dropped.' => '已删除数据库。',
		'Database has been created.' => '已创建数据库。',
		'Database has been renamed.' => '已重命名数据库。',
		'Database has been altered.' => '已修改数据库。',
		'Alter database' => '修改数据库',
		'Create database' => '创建数据库',
		'Database schema' => '数据库概要',
	
		// link to current database schema layout
		'Permanent link' => '固定链接',
	
		// thousands separator - must contain single byte
		',' => ',',
		'0123456789' => '0123456789',
		'Engine' => '引擎',
		'Collation' => '校对',
		'Data Length' => '数据长度',
		'Index Length' => '索引长度',
		'Data Free' => '数据空闲',
		'Rows' => '行数',
		'%d in total' => '共计 %d',
		'Analyze' => '分析',
		'Optimize' => '优化',
		'Vacuum' => '整理（Vacuum）',
		'Check' => '检查',
		'Repair' => '修复',
		'Truncate' => '清空',
		'Tables have been truncated.' => '已清空表。',
		'Move to other database' => '转移到其它数据库',
		'Move' => '转移',
		'Tables have been moved.' => '已转移表。',
		'Copy' => '复制',
		'Tables have been copied.' => '已复制表。',
		'overwrite' => '覆盖',
	
		'Routines' => '子程序',
		'Routine has been called, %d row(s) affected.' => '子程序被调用，%d 行被影响。',
		'Call' => '调用',
		'Parameter name' => '参数名',
		'Create procedure' => '创建过程',
		'Create function' => '创建函数',
		'Routine has been dropped.' => '已删除子程序。',
		'Routine has been altered.' => '已修改子程序。',
		'Routine has been created.' => '已创建子程序。',
		'Alter function' => '修改函数',
		'Alter procedure' => '修改过程',
		'Return type' => '返回类型',
	
		'Events' => '事件',
		'Event has been dropped.' => '已删除事件。',
		'Event has been altered.' => '已修改事件。',
		'Event has been created.' => '已创建事件。',
		'Alter event' => '修改事件',
		'Create event' => '创建事件',
		'At given time' => '在指定时间',
		'Every' => '每',
		'Schedule' => '调度',
		'Start' => '开始',
		'End' => '结束',
		'On completion preserve' => '完成后仍保留',
	
		'Tables' => '表',
		'Tables and views' => '表和视图',
		'Table' => '表',
		'No tables.' => '没有表。',
		'Alter table' => '修改表',
		'Create table' => '创建表',
		'Table has been dropped.' => '已删除表。',
		'Tables have been dropped.' => '已删除表。',
		'Tables have been optimized.' => '已优化表。',
		'Table has been altered.' => '已修改表。',
		'Table has been created.' => '已创建表。',
		'Table name' => '表名',
		'Show structure' => '显示结构',
		'engine' => '引擎',
		'collation' => '校对',
		'Column name' => '字段名',
		'Type' => '类型',
		'Length' => '长度',
		'Auto Increment' => '自动增量',
		'Options' => '选项',
		'Comment' => '注释',
		'Default value' => '默认值',
		'Default values' => '默认值',
		'Drop' => '删除',
		'Drop %s?' => '删除 %s?',
		'Are you sure?' => '您确定吗？',
		'Size' => '大小',
		'Compute' => '计算',
		'Move up' => '上移',
		'Move down' => '下移',
		'Remove' => '移除',
		'Maximum number of allowed fields exceeded. Please increase %s.' => '超过最多允许的字段数量。请增加 %s。',
	
		'Partition by' => '分区类型',
		'Partitions' => '分区',
		'Partition name' => '分区名',
		'Values' => '值',
	
		'View' => '视图',
		'Materialized view' => '物化视图',
		'View has been dropped.' => '已删除视图。',
		'View has been altered.' => '已修改视图。',
		'View has been created.' => '已创建视图。',
		'Alter view' => '修改视图',
		'Create view' => '创建视图',
	
		'Indexes' => '索引',
		'Indexes have been altered.' => '已修改索引。',
		'Alter indexes' => '修改索引',
		'Add next' => '下一行插入',
		'Index Type' => '索引类型',
		'Column (length)' => '列（长度）',
	
		'Foreign keys' => '外键',
		'Foreign key' => '外键',
		'Foreign key has been dropped.' => '已删除外键。',
		'Foreign key has been altered.' => '已修改外键。',
		'Foreign key has been created.' => '已创建外键。',
		'Target table' => '目标表',
		'Change' => '修改',
		'Source' => '源',
		'Target' => '目标',
		'Add column' => '增加列',
		'Alter' => '修改',
		'Add foreign key' => '添加外键',
		'ON DELETE' => 'ON DELETE',
		'ON UPDATE' => 'ON UPDATE',
		'Source and target columns must have the same data type, there must be an index on the target columns and referenced data must exist.' => '源列和目标列必须具有相同的数据类型，在目标列上必须有一个索引并且引用的数据必须存在。',
	
		'Triggers' => '触发器',
		'Add trigger' => '创建触发器',
		'Trigger has been dropped.' => '已删除触发器。',
		'Trigger has been altered.' => '已修改触发器。',
		'Trigger has been created.' => '已创建触发器。',
		'Alter trigger' => '修改触发器',
		'Create trigger' => '创建触发器',
		'Time' => '时间',
		'Event' => '事件',
		'Name' => '名称',
	
		'select' => '选择',
		'Select' => '选择',
		'Select data' => '选择数据',
		'Functions' => '函数',
		'Aggregation' => '集合',
		'Search' => '搜索',
		'anywhere' => '任意位置',
		'Search data in tables' => '在表中搜索数据',
		'Sort' => '排序',
		'descending' => '降序',
		'Limit' => '范围',
		'Limit rows' => '限制行数',
		'Text length' => '文本显示限制',
		'Action' => '动作',
		'Full table scan' => '全表扫描',
		'Unable to select the table' => '不能选择该表',
		'No rows.' => '无数据。',
		'%d / ' => '%d / ',
		'%d row(s)' => '%d 行',
		'Page' => '页面',
		'last' => '最后',
		'Load more data' => '加载更多数据',
		'Loading' => '加载中',
		'Whole result' => '所有结果',
		'%d byte(s)' => '%d 字节',
	
		'Import' => '导入',
		'%d row(s) have been imported.' => '%d 行已导入。',
		'File must be in UTF-8 encoding.' => '文件必须使用UTF-8编码。',
	
		// in-place editing in select
		'Modify' => '修改',
		'Ctrl+click on a value to modify it.' => '按住Ctrl并单击某个值进行修改。',
		'Use edit link to modify this value.' => '使用编辑链接修改该值。',
	
		// %s can contain auto-increment value
		'Item%s has been inserted.' => '已插入项目%s。',
		'Item has been deleted.' => '已删除项目。',
		'Item has been updated.' => '已更新项目。',
		'%d item(s) have been affected.' => '%d 个项目受到影响。',
		'New item' => '新建数据',
		'original' => '原始',
		// label for value '' in enum data type
		'empty' => '空',
		'edit' => '编辑',
		'Edit' => '编辑',
		'Insert' => '插入',
		'Save' => '保存',
		'Save and continue edit' => '保存并继续编辑',
		'Save and insert next' => '保存并插入下一个',
		'Selected' => '已选中',
		'Clone' => '复制',
		'Delete' => '删除',
		'You have no privileges to update this table.' => '您没有权限更新这个表。',
	
		'E-mail' => '电子邮件',
		'From' => '来自',
		'Subject' => '主题',
		'Attachments' => '附件',
		'Send' => '发送',
		'%d e-mail(s) have been sent.' => '%d 封邮件已发送。',
	
		// data type descriptions
		'Numbers' => '数字',
		'Date and time' => '日期时间',
		'Strings' => '字符串',
		'Binary' => '二进制',
		'Lists' => '列表',
		'Network' => '网络',
		'Geometry' => '几何图形',
		'Relations' => '关联信息',
	
		'Editor' => '编辑器',
		// date format in Editor: $1 yyyy, $2 yy, $3 mm, $4 m, $5 dd, $6 d
		'$1-$3-$5' => '$1.$3.$5',
		// hint for date format - use language equivalents for day, month and year shortcuts
		'[yyyy]-mm-dd' => '[yyyy].mm.dd',
		// hint for time format - use language equivalents for hour, minute and second shortcuts
		'HH:MM:SS' => 'HH:MM:SS',
		'now' => '现在',
		'yes' => '是',
		'no' => '否',
	
		// general SQLite error in create, drop or rename database
		'File exists.' => '文件已存在。',
		'Please use one of the extensions %s.' => '请使用其中一个扩展：%s。',
	
		// PostgreSQL and MS SQL schema support
		'Alter schema' => '修改模式',
		'Create schema' => '创建模式',
		'Schema has been dropped.' => '已删除模式。',
		'Schema has been created.' => '已创建模式。',
		'Schema has been altered.' => '已修改模式。',
		'Schema' => '模式',
		'Invalid schema.' => '非法模式。',
	
		// PostgreSQL sequences support
		'Sequences' => '序列',
		'Create sequence' => '创建序列',
		'Sequence has been dropped.' => '已删除序列。',
		'Sequence has been created.' => '已创建序列。',
		'Sequence has been altered.' => '已修改序列。',
		'Alter sequence' => '修改序列',
	
		// PostgreSQL user types support
		'User types' => '用户类型',
		'Create type' => '创建类型',
		'Type has been dropped.' => '已删除类型。',
		'Type has been created.' => '已创建类型。',
		'Alter type' => '修改类型',
	);	
}
// PDO can be used in several database drivers
if (extension_loaded('pdo')) {
	/*abstract*/ class Min_PDO {
		var $_result, $server_info, $affected_rows, $errno, $error, $pdo;
		
		function __construct() {
			global $adminer;
			$pos = array_search("SQL", $adminer->operators);
			if ($pos !== false) {
				unset($adminer->operators[$pos]);
			}
		}
		
		function dsn($dsn, $username, $password, $options = array()) {
			$options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_SILENT;
			$options[PDO::ATTR_STATEMENT_CLASS] = array('Min_PDOStatement');
			try {
				$this->pdo = new PDO($dsn, $username, $password, $options);
			} catch (Exception $ex) {
				auth_error(h($ex->getMessage()));
			}
			$this->server_info = @$this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
		}
		
		/*abstract function select_db($database);*/
		
		function quote($string) {
			return $this->pdo->quote($string);
		}
		
		function query($query, $unbuffered = false) {
			$result = $this->pdo->query($query);
			$this->error = "";
			if (!$result) {
				list(, $this->errno, $this->error) = $this->pdo->errorInfo();
				if (!$this->error) {
					$this->error = lang('Unknown error.');
				}
				return false;
			}
			$this->store_result($result);
			return $result;
		}
		
		function multi_query($query) {
			return $this->_result = $this->query($query);
		}
		
		function store_result($result = null) {
			if (!$result) {
				$result = $this->_result;
				if (!$result) {
					return false;
				}
			}
			if ($result->columnCount()) {
				$result->num_rows = $result->rowCount(); // is not guaranteed to work with all drivers
				return $result;
			}
			$this->affected_rows = $result->rowCount();
			return true;
		}
		
		function next_result() {
			if (!$this->_result) {
				return false;
			}
			$this->_result->_offset = 0;
			return @$this->_result->nextRowset(); // @ - PDO_PgSQL doesn't support it
		}
		
		function result($query, $field = 0) {
			$result = $this->query($query);
			if (!$result) {
				return false;
			}
			$row = $result->fetch();
			return $row[$field];
		}
	}
	
	class Min_PDOStatement extends PDOStatement {
		var $_offset = 0, $num_rows;
		
		function fetch_assoc() {
			return $this->fetch(PDO::FETCH_ASSOC);
		}
		
		function fetch_row() {
			return $this->fetch(PDO::FETCH_NUM);
		}
		
		function fetch_field() {
			$row = (object) $this->getColumnMeta($this->_offset++);
			$row->orgtable = $row->table;
			$row->orgname = $row->name;
			$row->charsetnr = (in_array("blob", (array) $row->flags) ? 63 : 0);
			return $row;
		}
	}
}

$drivers = array();

class Min_SQL {
	var $_conn;

	function __construct($connection) {
		$this->_conn = $connection;
	}
	function select($table, $select, $where, $group, $order = array(), $limit = 1, $page = 0, $print = false) {
		global $adminer, $jush;
		$is_group = (count($group) < count($select));
		$query = $adminer->selectQueryBuild($select, $where, $group, $order, $limit, $page);
		if (!$query) {
			$query = "SELECT" . limit(
				($_GET["page"] != "last" && $limit != "" && $group && $is_group && $jush == "sql" ? "SQL_CALC_FOUND_ROWS " : "") . implode(", ", $select) . "\nFROM " . table($table),
				($where ? "\nWHERE " . implode(" AND ", $where) : "") . ($group && $is_group ? "\nGROUP BY " . implode(", ", $group) : "") . ($order ? "\nORDER BY " . implode(", ", $order) : ""),
				($limit != "" ? +$limit : null),
				($page ? $limit * $page : 0),
				"\n"
			);
		}
		$start = microtime(true);
		$return = $this->_conn->query($query);
		if ($print) {
			echo $adminer->selectQuery($query, $start, !$return);
		}
		return $return;
	}
	function delete($table, $queryWhere, $limit = 0) {
		$query = "FROM " . table($table);
		return queries("DELETE" . ($limit ? limit1($table, $query, $queryWhere) : " $query$queryWhere"));
	}
	function update($table, $set, $queryWhere, $limit = 0, $separator = "\n") {
		$values = array();
		foreach ($set as $key => $val) {
			$values[] = "$key = $val";
		}
		$query = table($table) . " SET$separator" . implode(",$separator", $values);
		return queries("UPDATE" . ($limit ? limit1($table, $query, $queryWhere, $separator) : " $query$queryWhere"));
	}
	function insert($table, $set) {
		return queries("INSERT INTO " . table($table) . ($set
			? " (" . implode(", ", array_keys($set)) . ")\nVALUES (" . implode(", ", $set) . ")"
			: " DEFAULT VALUES"
		));
	}
	function insertUpdate($table, $rows, $primary) {
		return false;
	}
	function begin() {
		return queries("BEGIN");
	}
	function commit() {
		return queries("COMMIT");
	}
	function rollback() {
		return queries("ROLLBACK");
	}
	function slowQuery($query, $timeout) {
	}
	function convertSearch($idf, $val, $field) {
		return $idf;
	}
	function value($val, $field) {
		return (method_exists($this->_conn, 'value')
			? $this->_conn->value($val, $field)
			: (is_resource($val) ? stream_get_contents($val) : $val)
		);
	}
	function quoteBinary($s) {
		return q($s);
	}
	function warnings() {
		return '';
	}
	function tableHelp($name) {
	}

}
$drivers["sqlite"] = "SQLite 3";
$drivers["sqlite2"] = "SQLite 2";

if (isset($_GET["sqlite"]) || isset($_GET["sqlite2"])) {
	$possible_drivers = array((isset($_GET["sqlite"]) ? "SQLite3" : "SQLite"), "PDO_SQLite");
	define("DRIVER", (isset($_GET["sqlite"]) ? "sqlite" : "sqlite2"));
	if (class_exists(isset($_GET["sqlite"]) ? "SQLite3" : "SQLiteDatabase")) {
		if (isset($_GET["sqlite"])) {

			class Min_SQLite {
				var $extension = "SQLite3", $server_info, $affected_rows, $errno, $error, $_link;

				function __construct($filename) {
					$this->_link = new SQLite3($filename);
					$version = $this->_link->version();
					$this->server_info = $version["versionString"];
				}

				function query($query) {
					$result = @$this->_link->query($query);
					$this->error = "";
					if (!$result) {
						$this->errno = $this->_link->lastErrorCode();
						$this->error = $this->_link->lastErrorMsg();
						return false;
					} elseif ($result->numColumns()) {
						return new Min_Result($result);
					}
					$this->affected_rows = $this->_link->changes();
					return true;
				}

				function quote($string) {
					return (is_utf8($string)
						? "'" . $this->_link->escapeString($string) . "'"
						: "x'" . reset(unpack('H*', $string)) . "'"
					);
				}

				function store_result() {
					return $this->_result;
				}

				function result($query, $field = 0) {
					$result = $this->query($query);
					if (!is_object($result)) {
						return false;
					}
					$row = $result->_result->fetchArray();
					return $row[$field];
				}
			}

			class Min_Result {
				var $_result, $_offset = 0, $num_rows;

				function __construct($result) {
					$this->_result = $result;
				}

				function fetch_assoc() {
					return $this->_result->fetchArray(SQLITE3_ASSOC);
				}

				function fetch_row() {
					return $this->_result->fetchArray(SQLITE3_NUM);
				}

				function fetch_field() {
					$column = $this->_offset++;
					$type = $this->_result->columnType($column);
					return (object) array(
						"name" => $this->_result->columnName($column),
						"type" => $type,
						"charsetnr" => ($type == SQLITE3_BLOB ? 63 : 0), // 63 - binary
					);
				}

				function __desctruct() {
					return $this->_result->finalize();
				}
			}

		} else {

			class Min_SQLite {
				var $extension = "SQLite", $server_info, $affected_rows, $error, $_link;

				function __construct($filename) {
					$this->server_info = sqlite_libversion();
					$this->_link = new SQLiteDatabase($filename);
				}

				function query($query, $unbuffered = false) {
					$method = ($unbuffered ? "unbufferedQuery" : "query");
					$result = @$this->_link->$method($query, SQLITE_BOTH, $error);
					$this->error = "";
					if (!$result) {
						$this->error = $error;
						return false;
					} elseif ($result === true) {
						$this->affected_rows = $this->changes();
						return true;
					}
					return new Min_Result($result);
				}

				function quote($string) {
					return "'" . sqlite_escape_string($string) . "'";
				}

				function store_result() {
					return $this->_result;
				}

				function result($query, $field = 0) {
					$result = $this->query($query);
					if (!is_object($result)) {
						return false;
					}
					$row = $result->_result->fetch();
					return $row[$field];
				}
			}

			class Min_Result {
				var $_result, $_offset = 0, $num_rows;

				function __construct($result) {
					$this->_result = $result;
					if (method_exists($result, 'numRows')) { // not available in unbuffered query
						$this->num_rows = $result->numRows();
					}
				}

				function fetch_assoc() {
					$row = $this->_result->fetch(SQLITE_ASSOC);
					if (!$row) {
						return false;
					}
					$return = array();
					foreach ($row as $key => $val) {
						$return[($key[0] == '"' ? idf_unescape($key) : $key)] = $val;
					}
					return $return;
				}

				function fetch_row() {
					return $this->_result->fetch(SQLITE_NUM);
				}

				function fetch_field() {
					$name = $this->_result->fieldName($this->_offset++);
					$pattern = '(\[.*]|"(?:[^"]|"")*"|(.+))';
					if (preg_match("~^($pattern\\.)?$pattern\$~", $name, $match)) {
						$table = ($match[3] != "" ? $match[3] : idf_unescape($match[2]));
						$name = ($match[5] != "" ? $match[5] : idf_unescape($match[4]));
					}
					return (object) array(
						"name" => $name,
						"orgname" => $name,
						"orgtable" => $table,
					);
				}

			}

		}

	} elseif (extension_loaded("pdo_sqlite")) {
		class Min_SQLite extends Min_PDO {
			var $extension = "PDO_SQLite";

			function __construct($filename) {
				$this->dsn(DRIVER . ":$filename", "", "");
			}
		}

	}

	if (class_exists("Min_SQLite")) {
		class Min_DB extends Min_SQLite {

			function __construct() {
				parent::__construct(":memory:");
				$this->query("PRAGMA foreign_keys = 1");
			}

			function select_db($filename) {
				if (is_readable($filename) && $this->query("ATTACH " . $this->quote(preg_match("~(^[/\\\\]|:)~", $filename) ? $filename : dirname($_SERVER["SCRIPT_FILENAME"]) . "/$filename") . " AS a")) { // is_readable - SQLite 3
					parent::__construct($filename);
					$this->query("PRAGMA foreign_keys = 1");
					return true;
				}
				return false;
			}

			function multi_query($query) {
				return $this->_result = $this->query($query);
			}

			function next_result() {
				return false;
			}
		}
	}



	class Min_Driver extends Min_SQL {

		function insertUpdate($table, $rows, $primary) {
			$values = array();
			foreach ($rows as $set) {
				$values[] = "(" . implode(", ", $set) . ")";
			}
			return queries("REPLACE INTO " . table($table) . " (" . implode(", ", array_keys(reset($rows))) . ") VALUES\n" . implode(",\n", $values));
		}

		function tableHelp($name) {
			if ($name == "sqlite_sequence") {
				return "fileformat2.html#seqtab";
			}
			if ($name == "sqlite_master") {
				return "fileformat2.html#$name";
			}
		}

	}



	function idf_escape($idf) {
		return '"' . str_replace('"', '""', $idf) . '"';
	}

	function table($idf) {
		return idf_escape($idf);
	}

	function connect() {
		global $adminer;
		list(, , $password) = $adminer->credentials();
		if ($password != "") {
			return lang('Database does not support password.');
		}
		return new Min_DB;
	}

	function get_databases() {
		return array();
	}

	function limit($query, $where, $limit, $offset = 0, $separator = " ") {
		return " $query$where" . ($limit !== null ? $separator . "LIMIT $limit" . ($offset ? " OFFSET $offset" : "") : "");
	}

	function limit1($table, $query, $where, $separator = "\n") {
		global $connection;
		return (preg_match('~^INTO~', $query) || $connection->result("SELECT sqlite_compileoption_used('ENABLE_UPDATE_DELETE_LIMIT')")
			? limit($query, $where, 1, 0, $separator)
			: " $query WHERE rowid = (SELECT rowid FROM " . table($table) . $where . $separator . "LIMIT 1)" //! use primary key in tables with WITHOUT rowid
		);
	}

	function db_collation($db, $collations) {
		global $connection;
		return $connection->result("PRAGMA encoding"); // there is no database list so $db == DB
	}

	function engines() {
		return array();
	}

	function logged_user() {
		return get_current_user(); // should return effective user
	}

	function tables_list() {
		return get_key_vals("SELECT name, type FROM sqlite_master WHERE type IN ('table', 'view') ORDER BY (name = 'sqlite_sequence'), name");
	}

	function count_tables($databases) {
		return array();
	}

	function table_status($name = "") {
		global $connection;
		$return = array();
		foreach (get_rows("SELECT name AS Name, type AS Engine, 'rowid' AS Oid, '' AS Auto_increment FROM sqlite_master WHERE type IN ('table', 'view') " . ($name != "" ? "AND name = " . q($name) : "ORDER BY name")) as $row) {
			$row["Rows"] = $connection->result("SELECT COUNT(*) FROM " . idf_escape($row["Name"]));
			$return[$row["Name"]] = $row;
		}
		foreach (get_rows("SELECT * FROM sqlite_sequence", null, "") as $row) {
			$return[$row["name"]]["Auto_increment"] = $row["seq"];
		}
		return ($name != "" ? $return[$name] : $return);
	}

	function is_view($table_status) {
		return $table_status["Engine"] == "view";
	}

	function fk_support($table_status) {
		global $connection;
		return !$connection->result("SELECT sqlite_compileoption_used('OMIT_FOREIGN_KEY')");
	}

	function fields($table) {
		global $connection;
		$return = array();
		$primary = "";
		foreach (get_rows("PRAGMA table_info(" . table($table) . ")") as $row) {
			$name = $row["name"];
			$type = strtolower($row["type"]);
			$default = $row["dflt_value"];
			$return[$name] = array(
				"field" => $name,
				"type" => (preg_match('~int~i', $type) ? "integer" : (preg_match('~char|clob|text~i', $type) ? "text" : (preg_match('~blob~i', $type) ? "blob" : (preg_match('~real|floa|doub~i', $type) ? "real" : "numeric")))),
				"full_type" => $type,
				"default" => (preg_match("~'(.*)'~", $default, $match) ? str_replace("''", "'", $match[1]) : ($default == "NULL" ? null : $default)),
				"null" => !$row["notnull"],
				"privileges" => array("select" => 1, "insert" => 1, "update" => 1),
				"primary" => $row["pk"],
			);
			if ($row["pk"]) {
				if ($primary != "") {
					$return[$primary]["auto_increment"] = false;
				} elseif (preg_match('~^integer$~i', $type)) {
					$return[$name]["auto_increment"] = true;
				}
				$primary = $name;
			}
		}
		$sql = $connection->result("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = " . q($table));
		preg_match_all('~(("[^"]*+")+|[a-z0-9_]+)\s+text\s+COLLATE\s+(\'[^\']+\'|\S+)~i', $sql, $matches, PREG_SET_ORDER);
		foreach ($matches as $match) {
			$name = str_replace('""', '"', preg_replace('~^"|"$~', '', $match[1]));
			if ($return[$name]) {
				$return[$name]["collation"] = trim($match[3], "'");
			}
		}
		return $return;
	}

	function indexes($table, $connection2 = null) {
		global $connection;
		if (!is_object($connection2)) {
			$connection2 = $connection;
		}
		$return = array();
		$sql = $connection2->result("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = " . q($table));
		if (preg_match('~\bPRIMARY\s+KEY\s*\((([^)"]+|"[^"]*"|`[^`]*`)++)~i', $sql, $match)) {
			$return[""] = array("type" => "PRIMARY", "columns" => array(), "lengths" => array(), "descs" => array());
			preg_match_all('~((("[^"]*+")+|(?:`[^`]*+`)+)|(\S+))(\s+(ASC|DESC))?(,\s*|$)~i', $match[1], $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				$return[""]["columns"][] = idf_unescape($match[2]) . $match[4];
				$return[""]["descs"][] = (preg_match('~DESC~i', $match[5]) ? '1' : null);
			}
		}
		if (!$return) {
			foreach (fields($table) as $name => $field) {
				if ($field["primary"]) {
					$return[""] = array("type" => "PRIMARY", "columns" => array($name), "lengths" => array(), "descs" => array(null));
				}
			}
		}
		$sqls = get_key_vals("SELECT name, sql FROM sqlite_master WHERE type = 'index' AND tbl_name = " . q($table), $connection2);
		foreach (get_rows("PRAGMA index_list(" . table($table) . ")", $connection2) as $row) {
			$name = $row["name"];
			$index = array("type" => ($row["unique"] ? "UNIQUE" : "INDEX"));
			$index["lengths"] = array();
			$index["descs"] = array();
			foreach (get_rows("PRAGMA index_info(" . idf_escape($name) . ")", $connection2) as $row1) {
				$index["columns"][] = $row1["name"];
				$index["descs"][] = null;
			}
			if (preg_match('~^CREATE( UNIQUE)? INDEX ' . preg_quote(idf_escape($name) . ' ON ' . idf_escape($table), '~') . ' \((.*)\)$~i', $sqls[$name], $regs)) {
				preg_match_all('/("[^"]*+")+( DESC)?/', $regs[2], $matches);
				foreach ($matches[2] as $key => $val) {
					if ($val) {
						$index["descs"][$key] = '1';
					}
				}
			}
			if (!$return[""] || $index["type"] != "UNIQUE" || $index["columns"] != $return[""]["columns"] || $index["descs"] != $return[""]["descs"] || !preg_match("~^sqlite_~", $name)) {
				$return[$name] = $index;
			}
		}
		return $return;
	}

	function foreign_keys($table) {
		$return = array();
		foreach (get_rows("PRAGMA foreign_key_list(" . table($table) . ")") as $row) {
			$foreign_key = &$return[$row["id"]];
			//! idf_unescape in SQLite2
			if (!$foreign_key) {
				$foreign_key = $row;
			}
			$foreign_key["source"][] = $row["from"];
			$foreign_key["target"][] = $row["to"];
		}
		return $return;
	}

	function view($name) {
		global $connection;
		return array("select" => preg_replace('~^(?:[^`"[]+|`[^`]*`|"[^"]*")* AS\s+~iU', '', $connection->result("SELECT sql FROM sqlite_master WHERE name = " . q($name)))); //! identifiers may be inside []
	}

	function collations() {
		return (isset($_GET["create"]) ? get_vals("PRAGMA collation_list", 1) : array());
	}

	function information_schema($db) {
		return false;
	}

	function error() {
		global $connection;
		return h($connection->error);
	}

	function check_sqlite_name($name) {
		// avoid creating PHP files on unsecured servers
		global $connection;
		$extensions = "db|sdb|sqlite";
		if (!preg_match("~^[^\\0]*\\.($extensions)\$~", $name)) {
			$connection->error = lang('Please use one of the extensions %s.', str_replace("|", ", ", $extensions));
			return false;
		}
		return true;
	}

	function create_database($db, $collation) {
		global $connection;
		if (file_exists($db)) {
			$connection->error = lang('File exists.');
			return false;
		}
		if (!check_sqlite_name($db)) {
			return false;
		}
		try {
			$link = new Min_SQLite($db);
		} catch (Exception $ex) {
			$connection->error = $ex->getMessage();
			return false;
		}
		$link->query('PRAGMA encoding = "UTF-8"');
		$link->query('CREATE TABLE adminer (i)'); // otherwise creates empty file
		$link->query('DROP TABLE adminer');
		return true;
	}

	function drop_databases($databases) {
		global $connection;
		$connection->__construct(":memory:"); // to unlock file, doesn't work in PDO on Windows
		foreach ($databases as $db) {
			if (!@unlink($db)) {
				$connection->error = lang('File exists.');
				return false;
			}
		}
		return true;
	}

	function rename_database($name, $collation) {
		global $connection;
		if (!check_sqlite_name($name)) {
			return false;
		}
		$connection->__construct(":memory:");
		$connection->error = lang('File exists.');
		return @rename(DB, $name);
	}

	function auto_increment() {
		return " PRIMARY KEY" . (DRIVER == "sqlite" ? " AUTOINCREMENT" : "");
	}

	function alter_table($table, $name, $fields, $foreign, $comment, $engine, $collation, $auto_increment, $partitioning) {
		global $connection;
		$use_all_fields = ($table == "" || $foreign);
		foreach ($fields as $field) {
			if ($field[0] != "" || !$field[1] || $field[2]) {
				$use_all_fields = true;
				break;
			}
		}
		$alter = array();
		$originals = array();
		foreach ($fields as $field) {
			if ($field[1]) {
				$alter[] = ($use_all_fields ? $field[1] : "ADD " . implode($field[1]));
				if ($field[0] != "") {
					$originals[$field[0]] = $field[1][0];
				}
			}
		}
		if (!$use_all_fields) {
			foreach ($alter as $val) {
				if (!queries("ALTER TABLE " . table($table) . " $val")) {
					return false;
				}
			}
			if ($table != $name && !queries("ALTER TABLE " . table($table) . " RENAME TO " . table($name))) {
				return false;
			}
		} elseif (!recreate_table($table, $name, $alter, $originals, $foreign, $auto_increment)) {
			return false;
		}
		if ($auto_increment) {
			queries("BEGIN");
			queries("UPDATE sqlite_sequence SET seq = $auto_increment WHERE name = " . q($name)); // ignores error
			if (!$connection->affected_rows) {
				queries("INSERT INTO sqlite_sequence (name, seq) VALUES (" . q($name) . ", $auto_increment)");
			}
			queries("COMMIT");
		}
		return true;
	}

	function recreate_table($table, $name, $fields, $originals, $foreign, $auto_increment, $indexes = array()) {
		global $connection;
		if ($table != "") {
			if (!$fields) {
				foreach (fields($table) as $key => $field) {
					if ($indexes) {
						$field["auto_increment"] = 0;
					}
					$fields[] = process_field($field, $field);
					$originals[$key] = idf_escape($key);
				}
			}
			$primary_key = false;
			foreach ($fields as $field) {
				if ($field[6]) {
					$primary_key = true;
				}
			}
			$drop_indexes = array();
			foreach ($indexes as $key => $val) {
				if ($val[2] == "DROP") {
					$drop_indexes[$val[1]] = true;
					unset($indexes[$key]);
				}
			}
			foreach (indexes($table) as $key_name => $index) {
				$columns = array();
				foreach ($index["columns"] as $key => $column) {
					if (!$originals[$column]) {
						continue 2;
					}
					$columns[] = $originals[$column] . ($index["descs"][$key] ? " DESC" : "");
				}
				if (!$drop_indexes[$key_name]) {
					if ($index["type"] != "PRIMARY" || !$primary_key) {
						$indexes[] = array($index["type"], $key_name, $columns);
					}
				}
			}
			foreach ($indexes as $key => $val) {
				if ($val[0] == "PRIMARY") {
					unset($indexes[$key]);
					$foreign[] = "  PRIMARY KEY (" . implode(", ", $val[2]) . ")";
				}
			}
			foreach (foreign_keys($table) as $key_name => $foreign_key) {
				foreach ($foreign_key["source"] as $key => $column) {
					if (!$originals[$column]) {
						continue 2;
					}
					$foreign_key["source"][$key] = idf_unescape($originals[$column]);
				}
				if (!isset($foreign[" $key_name"])) {
					$foreign[] = " " . format_foreign_key($foreign_key);
				}
			}
			queries("BEGIN");
		}
		foreach ($fields as $key => $field) {
			$fields[$key] = "  " . implode($field);
		}
		$fields = array_merge($fields, array_filter($foreign));
		$temp_name = ($table == $name ? "adminer_$name" : $name);
		if (!queries("CREATE TABLE " . table($temp_name) . " (\n" . implode(",\n", $fields) . "\n)")) {
			// implicit ROLLBACK to not overwrite $connection->error
			return false;
		}
		if ($table != "") {
			if ($originals && !queries("INSERT INTO " . table($temp_name) . " (" . implode(", ", $originals) . ") SELECT " . implode(", ", array_map('idf_escape', array_keys($originals))) . " FROM " . table($table))) {
				return false;
			}
			$triggers = array();
			foreach (triggers($table) as $trigger_name => $timing_event) {
				$trigger = trigger($trigger_name);
				$triggers[] = "CREATE TRIGGER " . idf_escape($trigger_name) . " " . implode(" ", $timing_event) . " ON " . table($name) . "\n$trigger[Statement]";
			}
			$auto_increment = $auto_increment ? 0 : $connection->result("SELECT seq FROM sqlite_sequence WHERE name = " . q($table)); // if $auto_increment is set then it will be updated later
			if (!queries("DROP TABLE " . table($table)) // drop before creating indexes and triggers to allow using old names
				|| ($table == $name && !queries("ALTER TABLE " . table($temp_name) . " RENAME TO " . table($name)))
				|| !alter_indexes($name, $indexes)
			) {
				return false;
			}
			if ($auto_increment) {
				queries("UPDATE sqlite_sequence SET seq = $auto_increment WHERE name = " . q($name)); // ignores error
			}
			foreach ($triggers as $trigger) {
				if (!queries($trigger)) {
					return false;
				}
			}
			queries("COMMIT");
		}
		return true;
	}

	function index_sql($table, $type, $name, $columns) {
		return "CREATE $type " . ($type != "INDEX" ? "INDEX " : "")
			. idf_escape($name != "" ? $name : uniqid($table . "_"))
			. " ON " . table($table)
			. " $columns"
		;
	}

	function alter_indexes($table, $alter) {
		foreach ($alter as $primary) {
			if ($primary[0] == "PRIMARY") {
				return recreate_table($table, $table, array(), array(), array(), 0, $alter);
			}
		}
		foreach (array_reverse($alter) as $val) {
			if (!queries($val[2] == "DROP"
				? "DROP INDEX " . idf_escape($val[1])
				: index_sql($table, $val[0], $val[1], "(" . implode(", ", $val[2]) . ")")
			)) {
				return false;
			}
		}
		return true;
	}

	function truncate_tables($tables) {
		return apply_queries("DELETE FROM", $tables);
	}

	function drop_views($views) {
		return apply_queries("DROP VIEW", $views);
	}

	function drop_tables($tables) {
		return apply_queries("DROP TABLE", $tables);
	}

	function move_tables($tables, $views, $target) {
		return false;
	}

	function trigger($name) {
		global $connection;
		if ($name == "") {
			return array("Statement" => "BEGIN\n\t;\nEND");
		}
		$idf = '(?:[^`"\s]+|`[^`]*`|"[^"]*")+';
		$trigger_options = trigger_options();
		preg_match(
			"~^CREATE\\s+TRIGGER\\s*$idf\\s*(" . implode("|", $trigger_options["Timing"]) . ")\\s+([a-z]+)(?:\\s+OF\\s+($idf))?\\s+ON\\s*$idf\\s*(?:FOR\\s+EACH\\s+ROW\\s)?(.*)~is",
			$connection->result("SELECT sql FROM sqlite_master WHERE type = 'trigger' AND name = " . q($name)),
			$match
		);
		$of = $match[3];
		return array(
			"Timing" => strtoupper($match[1]),
			"Event" => strtoupper($match[2]) . ($of ? " OF" : ""),
			"Of" => ($of[0] == '`' || $of[0] == '"' ? idf_unescape($of) : $of),
			"Trigger" => $name,
			"Statement" => $match[4],
		);
	}

	function triggers($table) {
		$return = array();
		$trigger_options = trigger_options();
		foreach (get_rows("SELECT * FROM sqlite_master WHERE type = 'trigger' AND tbl_name = " . q($table)) as $row) {
			preg_match('~^CREATE\s+TRIGGER\s*(?:[^`"\s]+|`[^`]*`|"[^"]*")+\s*(' . implode("|", $trigger_options["Timing"]) . ')\s*(.*?)\s+ON\b~i', $row["sql"], $match);
			$return[$row["name"]] = array($match[1], $match[2]);
		}
		return $return;
	}

	function trigger_options() {
		return array(
			"Timing" => array("BEFORE", "AFTER", "INSTEAD OF"),
			"Event" => array("INSERT", "UPDATE", "UPDATE OF", "DELETE"),
			"Type" => array("FOR EACH ROW"),
		);
	}

	function begin() {
		return queries("BEGIN");
	}

	function last_id() {
		global $connection;
		return $connection->result("SELECT LAST_INSERT_ROWID()");
	}

	function explain($connection, $query) {
		return $connection->query("EXPLAIN QUERY PLAN $query");
	}

	function found_rows($table_status, $where) {
	}

	function types() {
		return array();
	}

	function schemas() {
		return array();
	}

	function get_schema() {
		return "";
	}

	function set_schema($scheme) {
		return true;
	}

	function create_sql($table, $auto_increment, $style) {
		global $connection;
		$return = $connection->result("SELECT sql FROM sqlite_master WHERE type IN ('table', 'view') AND name = " . q($table));
		foreach (indexes($table) as $name => $index) {
			if ($name == '') {
				continue;
			}
			$return .= ";\n\n" . index_sql($table, $index['type'], $name, "(" . implode(", ", array_map('idf_escape', $index['columns'])) . ")");
		}
		return $return;
	}

	function truncate_sql($table) {
		return "DELETE FROM " . table($table);
	}

	function use_sql($database) {
	}

	function trigger_sql($table) {
		return implode(get_vals("SELECT sql || ';;\n' FROM sqlite_master WHERE type = 'trigger' AND tbl_name = " . q($table)));
	}

	function show_variables() {
		global $connection;
		$return = array();
		foreach (array("auto_vacuum", "cache_size", "count_changes", "default_cache_size", "empty_result_callbacks", "encoding", "foreign_keys", "full_column_names", "fullfsync", "journal_mode", "journal_size_limit", "legacy_file_format", "locking_mode", "page_size", "max_page_count", "read_uncommitted", "recursive_triggers", "reverse_unordered_selects", "secure_delete", "short_column_names", "synchronous", "temp_store", "temp_store_directory", "schema_version", "integrity_check", "quick_check") as $key) {
			$return[$key] = $connection->result("PRAGMA $key");
		}
		return $return;
	}

	function show_status() {
		$return = array();
		foreach (get_vals("PRAGMA compile_options") as $option) {
			list($key, $val) = explode("=", $option, 2);
			$return[$key] = $val;
		}
		return $return;
	}

	function convert_field($field) {
	}

	function unconvert_field($field, $return) {
		return $return;
	}

	function support($feature) {
		return preg_match('~^(columns|database|drop_col|dump|indexes|descidx|move_col|sql|status|table|trigger|variables|view|view_trigger)$~', $feature);
	}

	$jush = "sqlite";
	$types = array("integer" => 0, "real" => 0, "numeric" => 0, "text" => 0, "blob" => 0);
	$structured_types = array_keys($types);
	$unsigned = array();
	$operators = array("=", "<", ">", "<=", ">=", "!=", "LIKE", "LIKE %%", "IN", "IS NULL", "NOT LIKE", "NOT IN", "IS NOT NULL", "SQL"); // REGEXP can be user defined function
	$functions = array("hex", "length", "lower", "round", "unixepoch", "upper");
	$grouping = array("avg", "count", "count distinct", "group_concat", "max", "min", "sum");
	$edit_functions = array(
		array(
			// "text" => "date('now')/time('now')/datetime('now')",
		), array(
			"integer|real|numeric" => "+/-",
			// "text" => "date/time/datetime",
			"text" => "||",
		)
	);
}
$drivers["pgsql"] = "PostgreSQL";

if (isset($_GET["pgsql"])) {
	$possible_drivers = array("PgSQL", "PDO_PgSQL");
	define("DRIVER", "pgsql");
	if (extension_loaded("pgsql")) {
		class Min_DB {
			var $extension = "PgSQL", $_link, $_result, $_string, $_database = true, $server_info, $affected_rows, $error, $timeout;

			function _error($errno, $error) {
				if (ini_bool("html_errors")) {
					$error = html_entity_decode(strip_tags($error));
				}
				$error = preg_replace('~^[^:]*: ~', '', $error);
				$this->error = $error;
			}

			function connect($server, $username, $password) {
				global $adminer;
				$db = $adminer->database();
				set_error_handler(array($this, '_error'));
				$this->_string = "host='" . str_replace(":", "' port='", addcslashes($server, "'\\")) . "' user='" . addcslashes($username, "'\\") . "' password='" . addcslashes($password, "'\\") . "'";
				$this->_link = @pg_connect("$this->_string dbname='" . ($db != "" ? addcslashes($db, "'\\") : "postgres") . "'", PGSQL_CONNECT_FORCE_NEW);
				if (!$this->_link && $db != "") {
					// try to connect directly with database for performance
					$this->_database = false;
					$this->_link = @pg_connect("$this->_string dbname='postgres'", PGSQL_CONNECT_FORCE_NEW);
				}
				restore_error_handler();
				if ($this->_link) {
					$version = pg_version($this->_link);
					$this->server_info = $version["server"];
					pg_set_client_encoding($this->_link, "UTF8");
				}
				return (bool) $this->_link;
			}

			function quote($string) {
				return "'" . pg_escape_string($this->_link, $string) . "'";
			}

			function value($val, $field) {
				return ($field["type"] == "bytea" ? pg_unescape_bytea($val) : $val);
			}

			function quoteBinary($string) {
				return "'" . pg_escape_bytea($this->_link, $string) . "'";
			}

			function select_db($database) {
				global $adminer;
				if ($database == $adminer->database()) {
					return $this->_database;
				}
				$return = @pg_connect("$this->_string dbname='" . addcslashes($database, "'\\") . "'", PGSQL_CONNECT_FORCE_NEW);
				if ($return) {
					$this->_link = $return;
				}
				return $return;
			}

			function close() {
				$this->_link = @pg_connect("$this->_string dbname='postgres'");
			}

			function query($query, $unbuffered = false) {
				$result = @pg_query($this->_link, $query);
				$this->error = "";
				if (!$result) {
					$this->error = pg_last_error($this->_link);
					$return = false;
				} elseif (!pg_num_fields($result)) {
					$this->affected_rows = pg_affected_rows($result);
					$return = true;
				} else {
					$return = new Min_Result($result);
				}
				if ($this->timeout) {
					$this->timeout = 0;
					$this->query("RESET statement_timeout");
				}
				return $return;
			}

			function multi_query($query) {
				return $this->_result = $this->query($query);
			}

			function store_result() {
				return $this->_result;
			}

			function next_result() {
				// PgSQL extension doesn't support multiple results
				return false;
			}

			function result($query, $field = 0) {
				$result = $this->query($query);
				if (!$result || !$result->num_rows) {
					return false;
				}
				return pg_fetch_result($result->_result, 0, $field);
			}

			function warnings() {
				return h(pg_last_notice($this->_link)); // second parameter is available since PHP 7.1.0
			}
		}

		class Min_Result {
			var $_result, $_offset = 0, $num_rows;

			function __construct($result) {
				$this->_result = $result;
				$this->num_rows = pg_num_rows($result);
			}

			function fetch_assoc() {
				return pg_fetch_assoc($this->_result);
			}

			function fetch_row() {
				return pg_fetch_row($this->_result);
			}

			function fetch_field() {
				$column = $this->_offset++;
				$return = new stdClass;
				if (function_exists('pg_field_table')) {
					$return->orgtable = pg_field_table($this->_result, $column);
				}
				$return->name = pg_field_name($this->_result, $column);
				$return->orgname = $return->name;
				$return->type = pg_field_type($this->_result, $column);
				$return->charsetnr = ($return->type == "bytea" ? 63 : 0); // 63 - binary
				return $return;
			}

			function __destruct() {
				pg_free_result($this->_result);
			}
		}

	} elseif (extension_loaded("pdo_pgsql")) {
		class Min_DB extends Min_PDO {
			var $extension = "PDO_PgSQL", $timeout;

			function connect($server, $username, $password) {
				global $adminer;
				$db = $adminer->database();
				$string = "pgsql:host='" . str_replace(":", "' port='", addcslashes($server, "'\\")) . "' options='-c client_encoding=utf8'";
				$this->dsn("$string dbname='" . ($db != "" ? addcslashes($db, "'\\") : "postgres") . "'", $username, $password);
				//! connect without DB in case of an error
				return true;
			}

			function select_db($database) {
				global $adminer;
				return ($adminer->database() == $database);
			}

			function quoteBinary($s) {
				return q($s);
			}

			function query($query, $unbuffered = false) {
				$return = parent::query($query, $unbuffered);
				if ($this->timeout) {
					$this->timeout = 0;
					parent::query("RESET statement_timeout");
				}
				return $return;
			}

			function warnings() {
				return ''; // not implemented in PDO_PgSQL as of PHP 7.2.1
			}

			function close() {
			}
		}

	}
	class Min_Driver extends Min_SQL {

		function insertUpdate($table, $rows, $primary) {
			global $connection;
			foreach ($rows as $set) {
				$update = array();
				$where = array();
				foreach ($set as $key => $val) {
					$update[] = "$key = $val";
					if (isset($primary[idf_unescape($key)])) {
						$where[] = "$key = $val";
					}
				}
				if (!(($where && queries("UPDATE " . table($table) . " SET " . implode(", ", $update) . " WHERE " . implode(" AND ", $where)) && $connection->affected_rows)
					|| queries("INSERT INTO " . table($table) . " (" . implode(", ", array_keys($set)) . ") VALUES (" . implode(", ", $set) . ")")
				)) {
					return false;
				}
			}
			return true;
		}

		function slowQuery($query, $timeout) {
			$this->_conn->query("SET statement_timeout = " . (1000 * $timeout));
			$this->_conn->timeout = 1000 * $timeout;
			return $query;
		}

		function convertSearch($idf, $val, $field) {
			return (preg_match('~char|text'
					. (!preg_match('~LIKE~', $val["op"]) ? '|date|time(stamp)?|boolean|uuid|' . number_type() : '')
					. '~', $field["type"])
				? $idf
				: "CAST($idf AS text)"
			);
		}

		function quoteBinary($s) {
			return $this->_conn->quoteBinary($s);
		}

		function warnings() {
			return $this->_conn->warnings();
		}

		function tableHelp($name) {
			$links = array(
				"information_schema" => "infoschema",
				"pg_catalog" => "catalog",
			);
			$link = $links[$_GET["ns"]];
			if ($link) {
				return "$link-" . str_replace("_", "-", $name) . ".html";
			}
		}

	}
	function idf_escape($idf) {
		return '"' . str_replace('"', '""', $idf) . '"';
	}
	function table($idf) {
		return idf_escape($idf);
	}
	function connect() {
		global $adminer, $types, $structured_types;
		$connection = new Min_DB;
		$credentials = $adminer->credentials();
		if ($connection->connect($credentials[0], $credentials[1], $credentials[2])) {
			if (min_version(9, 0, $connection)) {
				$connection->query("SET application_name = 'Adminer'");
				if (min_version(9.2, 0, $connection)) {
					$structured_types[lang('Strings')][] = "json";
					$types["json"] = 4294967295;
					if (min_version(9.4, 0, $connection)) {
						$structured_types[lang('Strings')][] = "jsonb";
						$types["jsonb"] = 4294967295;
					}
				}
			}
			return $connection;
		}
		return $connection->error;
	}
	function get_databases() {
		return get_vals("SELECT datname FROM pg_database WHERE has_database_privilege(datname, 'CONNECT') ORDER BY datname");
	}

	function limit($query, $where, $limit, $offset = 0, $separator = " ") {
		return " $query$where" . ($limit !== null ? $separator . "LIMIT $limit" . ($offset ? " OFFSET $offset" : "") : "");
	}

	function limit1($table, $query, $where, $separator = "\n") {
		return (preg_match('~^INTO~', $query)
			? limit($query, $where, 1, 0, $separator)
			: " $query" . (is_view(table_status1($table)) ? $where : " WHERE ctid = (SELECT ctid FROM " . table($table) . $where . $separator . "LIMIT 1)")
		);
	}

	function db_collation($db, $collations) {
		global $connection;
		return $connection->result("SHOW LC_COLLATE"); //! respect $db
	}

	function engines() {
		return array();
	}

	function logged_user() {
		global $connection;
		return $connection->result("SELECT user");
	}

	function tables_list() {
		$query = "SELECT table_name, table_type FROM information_schema.tables WHERE table_schema = current_schema()";
		if (support('materializedview')) {
			$query .= "
UNION ALL
SELECT matviewname, 'MATERIALIZED VIEW'
FROM pg_matviews
WHERE schemaname = current_schema()";
		}
		$query .= "
ORDER BY 1";
		return get_key_vals($query);
	}

	function count_tables($databases) {
		return array(); // would require reconnect
	}

	function table_status($name = "") {
		$return = array();
		foreach (get_rows("SELECT c.relname AS \"Name\", CASE c.relkind WHEN 'r' THEN 'table' WHEN 'm' THEN 'materialized view' ELSE 'view' END AS \"Engine\", pg_relation_size(c.oid) AS \"Data_length\", pg_total_relation_size(c.oid) - pg_relation_size(c.oid) AS \"Index_length\", obj_description(c.oid, 'pg_class') AS \"Comment\", " . (min_version(12) ? "''" : "CASE WHEN c.relhasoids THEN 'oid' ELSE '' END") . " AS \"Oid\", c.reltuples as \"Rows\", n.nspname
FROM pg_class c
JOIN pg_namespace n ON(n.nspname = current_schema() AND n.oid = c.relnamespace)
WHERE relkind IN ('r', 'm', 'v', 'f')
" . ($name != "" ? "AND relname = " . q($name) : "ORDER BY relname")
		) as $row) { //! Index_length, Auto_increment
			$return[$row["Name"]] = $row;
		}
		return ($name != "" ? $return[$name] : $return);
	}

	function is_view($table_status) {
		return in_array($table_status["Engine"], array("view", "materialized view"));
	}

	function fk_support($table_status) {
		return true;
	}

	function fields($table) {
		$return = array();
		$aliases = array(
			'timestamp without time zone' => 'timestamp',
			'timestamp with time zone' => 'timestamptz',
		);

		$identity_column = min_version(10) ? "(a.attidentity = 'd')::int" : '0';

		foreach (get_rows("SELECT a.attname AS field, format_type(a.atttypid, a.atttypmod) AS full_type, pg_get_expr(d.adbin, d.adrelid) AS default, a.attnotnull::int, col_description(c.oid, a.attnum) AS comment, $identity_column AS identity
FROM pg_class c
JOIN pg_namespace n ON c.relnamespace = n.oid
JOIN pg_attribute a ON c.oid = a.attrelid
LEFT JOIN pg_attrdef d ON c.oid = d.adrelid AND a.attnum = d.adnum
WHERE c.relname = " . q($table) . "
AND n.nspname = current_schema()
AND NOT a.attisdropped
AND a.attnum > 0
ORDER BY a.attnum"
		) as $row) {
			//! collation, primary
			preg_match('~([^([]+)(\((.*)\))?([a-z ]+)?((\[[0-9]*])*)$~', $row["full_type"], $match);
			list(, $type, $length, $row["length"], $addon, $array) = $match;
			$row["length"] .= $array;
			$check_type = $type . $addon;
			if (isset($aliases[$check_type])) {
				$row["type"] = $aliases[$check_type];
				$row["full_type"] = $row["type"] . $length . $array;
			} else {
				$row["type"] = $type;
				$row["full_type"] = $row["type"] . $length . $addon . $array;
			}
			if ($row['identity']) {
				$row['default'] = 'GENERATED BY DEFAULT AS IDENTITY';
			}
			$row["null"] = !$row["attnotnull"];
			$row["auto_increment"] = $row['identity'] || preg_match('~^nextval\(~i', $row["default"]);
			$row["privileges"] = array("insert" => 1, "select" => 1, "update" => 1);
			if (preg_match('~(.+)::[^)]+(.*)~', $row["default"], $match)) {
				$row["default"] = ($match[1] == "NULL" ? null : (($match[1][0] == "'" ? idf_unescape($match[1]) : $match[1]) . $match[2]));
			}
			$return[$row["field"]] = $row;
		}
		return $return;
	}

	function indexes($table, $connection2 = null) {
		global $connection;
		if (!is_object($connection2)) {
			$connection2 = $connection;
		}
		$return = array();
		$table_oid = $connection2->result("SELECT oid FROM pg_class WHERE relnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema()) AND relname = " . q($table));
		$columns = get_key_vals("SELECT attnum, attname FROM pg_attribute WHERE attrelid = $table_oid AND attnum > 0", $connection2);
		foreach (get_rows("SELECT relname, indisunique::int, indisprimary::int, indkey, indoption , (indpred IS NOT NULL)::int as indispartial FROM pg_index i, pg_class ci WHERE i.indrelid = $table_oid AND ci.oid = i.indexrelid", $connection2) as $row) {
			$relname = $row["relname"];
			$return[$relname]["type"] = ($row["indispartial"] ? "INDEX" : ($row["indisprimary"] ? "PRIMARY" : ($row["indisunique"] ? "UNIQUE" : "INDEX")));
			$return[$relname]["columns"] = array();
			foreach (explode(" ", $row["indkey"]) as $indkey) {
				$return[$relname]["columns"][] = $columns[$indkey];
			}
			$return[$relname]["descs"] = array();
			foreach (explode(" ", $row["indoption"]) as $indoption) {
				$return[$relname]["descs"][] = ($indoption & 1 ? '1' : null); // 1 - INDOPTION_DESC
			}
			$return[$relname]["lengths"] = array();
		}
		return $return;
	}

	function foreign_keys($table) {
		global $on_actions;
		$return = array();
		foreach (get_rows("SELECT conname, condeferrable::int AS deferrable, pg_get_constraintdef(oid) AS definition
FROM pg_constraint
WHERE conrelid = (SELECT pc.oid FROM pg_class AS pc INNER JOIN pg_namespace AS pn ON (pn.oid = pc.relnamespace) WHERE pc.relname = " . q($table) . " AND pn.nspname = current_schema())
AND contype = 'f'::char
ORDER BY conkey, conname") as $row) {
			if (preg_match('~FOREIGN KEY\s*\((.+)\)\s*REFERENCES (.+)\((.+)\)(.*)$~iA', $row['definition'], $match)) {
				$row['source'] = array_map('trim', explode(',', $match[1]));
				if (preg_match('~^(("([^"]|"")+"|[^"]+)\.)?"?("([^"]|"")+"|[^"]+)$~', $match[2], $match2)) {
					$row['ns'] = str_replace('""', '"', preg_replace('~^"(.+)"$~', '\1', $match2[2]));
					$row['table'] = str_replace('""', '"', preg_replace('~^"(.+)"$~', '\1', $match2[4]));
				}
				$row['target'] = array_map('trim', explode(',', $match[3]));
				$row['on_delete'] = (preg_match("~ON DELETE ($on_actions)~", $match[4], $match2) ? $match2[1] : 'NO ACTION');
				$row['on_update'] = (preg_match("~ON UPDATE ($on_actions)~", $match[4], $match2) ? $match2[1] : 'NO ACTION');
				$return[$row['conname']] = $row;
			}
		}
		return $return;
	}

	function view($name) {
		global $connection;
		return array("select" => trim($connection->result("SELECT pg_get_viewdef(" . $connection->result("SELECT oid FROM pg_class WHERE relname = " . q($name)) . ")")));
	}

	function collations() {
		//! supported in CREATE DATABASE
		return array();
	}

	function information_schema($db) {
		return ($db == "information_schema");
	}

	function error() {
		global $connection;
		$return = h($connection->error);
		if (preg_match('~^(.*\n)?([^\n]*)\n( *)\^(\n.*)?$~s', $return, $match)) {
			$return = $match[1] . preg_replace('~((?:[^&]|&[^;]*;){' . strlen($match[3]) . '})(.*)~', '\1<b>\2</b>', $match[2]) . $match[4];
		}
		return nl_br($return);
	}

	function create_database($db, $collation) {
		return queries("CREATE DATABASE " . idf_escape($db) . ($collation ? " ENCODING " . idf_escape($collation) : ""));
	}

	function drop_databases($databases) {
		global $connection;
		$connection->close();
		return apply_queries("DROP DATABASE", $databases, 'idf_escape');
	}

	function rename_database($name, $collation) {
		//! current database cannot be renamed
		return queries("ALTER DATABASE " . idf_escape(DB) . " RENAME TO " . idf_escape($name));
	}

	function auto_increment() {
		return "";
	}

	function alter_table($table, $name, $fields, $foreign, $comment, $engine, $collation, $auto_increment, $partitioning) {
		$alter = array();
		$queries = array();
		if ($table != "" && $table != $name) {
			$queries[] = "ALTER TABLE " . table($table) . " RENAME TO " . table($name);
		}
		foreach ($fields as $field) {
			$column = idf_escape($field[0]);
			$val = $field[1];
			if (!$val) {
				$alter[] = "DROP $column";
			} else {
				$val5 = $val[5];
				unset($val[5]);
				if (isset($val[6]) && $field[0] == "") { // auto_increment
					$val[1] = ($val[1] == "bigint" ? " big" : " ") . "serial";
				}
				if ($field[0] == "") {
					$alter[] = ($table != "" ? "ADD " : "  ") . implode($val);
				} else {
					if ($column != $val[0]) {
						$queries[] = "ALTER TABLE " . table($name) . " RENAME $column TO $val[0]";
					}
					$alter[] = "ALTER $column TYPE$val[1]";
					if (!$val[6]) {
						$alter[] = "ALTER $column " . ($val[3] ? "SET$val[3]" : "DROP DEFAULT");
						$alter[] = "ALTER $column " . ($val[2] == " NULL" ? "DROP NOT" : "SET") . $val[2];
					}
				}
				if ($field[0] != "" || $val5 != "") {
					$queries[] = "COMMENT ON COLUMN " . table($name) . ".$val[0] IS " . ($val5 != "" ? substr($val5, 9) : "''");
				}
			}
		}
		$alter = array_merge($alter, $foreign);
		if ($table == "") {
			array_unshift($queries, "CREATE TABLE " . table($name) . " (\n" . implode(",\n", $alter) . "\n)");
		} elseif ($alter) {
			array_unshift($queries, "ALTER TABLE " . table($table) . "\n" . implode(",\n", $alter));
		}
		if ($table != "" || $comment != "") {
			$queries[] = "COMMENT ON TABLE " . table($name) . " IS " . q($comment);
		}
		if ($auto_increment != "") {
			//! $queries[] = "SELECT setval(pg_get_serial_sequence(" . q($name) . ", ), $auto_increment)";
		}
		foreach ($queries as $query) {
			if (!queries($query)) {
				return false;
			}
		}
		return true;
	}

	function alter_indexes($table, $alter) {
		$create = array();
		$drop = array();
		$queries = array();
		foreach ($alter as $val) {
			if ($val[0] != "INDEX") {
				//! descending UNIQUE indexes results in syntax error
				$create[] = ($val[2] == "DROP"
					? "\nDROP CONSTRAINT " . idf_escape($val[1])
					: "\nADD" . ($val[1] != "" ? " CONSTRAINT " . idf_escape($val[1]) : "") . " $val[0] " . ($val[0] == "PRIMARY" ? "KEY " : "") . "(" . implode(", ", $val[2]) . ")"
				);
			} elseif ($val[2] == "DROP") {
				$drop[] = idf_escape($val[1]);
			} else {
				$queries[] = "CREATE INDEX " . idf_escape($val[1] != "" ? $val[1] : uniqid($table . "_")) . " ON " . table($table) . " (" . implode(", ", $val[2]) . ")";
			}
		}
		if ($create) {
			array_unshift($queries, "ALTER TABLE " . table($table) . implode(",", $create));
		}
		if ($drop) {
			array_unshift($queries, "DROP INDEX " . implode(", ", $drop));
		}
		foreach ($queries as $query) {
			if (!queries($query)) {
				return false;
			}
		}
		return true;
	}

	function truncate_tables($tables) {
		return queries("TRUNCATE " . implode(", ", array_map('table', $tables)));
		return true;
	}

	function drop_views($views) {
		return drop_tables($views);
	}

	function drop_tables($tables) {
		foreach ($tables as $table) {
				$status = table_status($table);
				if (!queries("DROP " . strtoupper($status["Engine"]) . " " . table($table))) {
					return false;
				}
		}
		return true;
	}

	function move_tables($tables, $views, $target) {
		foreach (array_merge($tables, $views) as $table) {
			$status = table_status($table);
			if (!queries("ALTER " . strtoupper($status["Engine"]) . " " . table($table) . " SET SCHEMA " . idf_escape($target))) {
				return false;
			}
		}
		return true;
	}

	function trigger($name, $table = null) {
		if ($name == "") {
			return array("Statement" => "EXECUTE PROCEDURE ()");
		}
		if ($table === null) {
			$table = $_GET['trigger'];
		}
		$rows = get_rows('SELECT t.trigger_name AS "Trigger", t.action_timing AS "Timing", (SELECT STRING_AGG(event_manipulation, \' OR \') FROM information_schema.triggers WHERE event_object_table = t.event_object_table AND trigger_name = t.trigger_name ) AS "Events", t.event_manipulation AS "Event", \'FOR EACH \' || t.action_orientation AS "Type", t.action_statement AS "Statement" FROM information_schema.triggers t WHERE t.event_object_table = ' . q($table) . ' AND t.trigger_name = ' . q($name));
		return reset($rows);
	}

	function triggers($table) {
		$return = array();
		foreach (get_rows("SELECT * FROM information_schema.triggers WHERE event_object_table = " . q($table)) as $row) {
			$return[$row["trigger_name"]] = array($row["action_timing"], $row["event_manipulation"]);
		}
		return $return;
	}

	function trigger_options() {
		return array(
			"Timing" => array("BEFORE", "AFTER"),
			"Event" => array("INSERT", "UPDATE", "DELETE"),
			"Type" => array("FOR EACH ROW", "FOR EACH STATEMENT"),
		);
	}

	function routine($name, $type) {
		$rows = get_rows('SELECT routine_definition AS definition, LOWER(external_language) AS language, *
FROM information_schema.routines
WHERE routine_schema = current_schema() AND specific_name = ' . q($name));
		$return = $rows[0];
		$return["returns"] = array("type" => $return["type_udt_name"]);
		$return["fields"] = get_rows('SELECT parameter_name AS field, data_type AS type, character_maximum_length AS length, parameter_mode AS inout
FROM information_schema.parameters
WHERE specific_schema = current_schema() AND specific_name = ' . q($name) . '
ORDER BY ordinal_position');
		return $return;
	}

	function routines() {
		return get_rows('SELECT specific_name AS "SPECIFIC_NAME", routine_type AS "ROUTINE_TYPE", routine_name AS "ROUTINE_NAME", type_udt_name AS "DTD_IDENTIFIER"
FROM information_schema.routines
WHERE routine_schema = current_schema()
ORDER BY SPECIFIC_NAME');
	}

	function routine_languages() {
		return get_vals("SELECT LOWER(lanname) FROM pg_catalog.pg_language");
	}

	function routine_id($name, $row) {
		$return = array();
		foreach ($row["fields"] as $field) {
			$return[] = $field["type"];
		}
		return idf_escape($name) . "(" . implode(", ", $return) . ")";
	}

	function last_id() {
		return 0; // there can be several sequences
	}

	function explain($connection, $query) {
		return $connection->query("EXPLAIN $query");
	}

	function found_rows($table_status, $where) {
		global $connection;
		if (preg_match(
			"~ rows=([0-9]+)~",
			$connection->result("EXPLAIN SELECT * FROM " . idf_escape($table_status["Name"]) . ($where ? " WHERE " . implode(" AND ", $where) : "")),
			$regs
		)) {
			return $regs[1];
		}
		return false;
	}

	function types() {
		return get_vals("SELECT typname
FROM pg_type
WHERE typnamespace = (SELECT oid FROM pg_namespace WHERE nspname = current_schema())
AND typtype IN ('b','d','e')
AND typelem = 0"
		);
	}

	function schemas() {
		return get_vals("SELECT nspname FROM pg_namespace ORDER BY nspname");
	}

	function get_schema() {
		global $connection;
		return $connection->result("SELECT current_schema()");
	}

	function set_schema($schema, $connection2 = null) {
		global $connection, $types, $structured_types;
		if (!$connection2) {
			$connection2 = $connection;
		}
		$return = $connection2->query("SET search_path TO " . idf_escape($schema));
		foreach (types() as $type) { //! get types from current_schemas('t')
			if (!isset($types[$type])) {
				$types[$type] = 0;
				$structured_types[lang('User types')][] = $type;
			}
		}
		return $return;
	}

	function create_sql($table, $auto_increment, $style) {
		global $connection;
		$return = '';
		$return_parts = array();
		$sequences = array();

		$status = table_status($table);
		$fields = fields($table);
		$indexes = indexes($table);
		ksort($indexes);
		$fkeys = foreign_keys($table);
		ksort($fkeys);

		if (!$status || empty($fields)) {
			return false;
		}

		$return = "CREATE TABLE " . idf_escape($status['nspname']) . "." . idf_escape($status['Name']) . " (\n    ";

		// fields' definitions
		foreach ($fields as $field_name => $field) {
			$part = idf_escape($field['field']) . ' ' . $field['full_type']
				. default_value($field)
				. ($field['attnotnull'] ? " NOT NULL" : "");
			$return_parts[] = $part;

			// sequences for fields
			if (preg_match('~nextval\(\'([^\']+)\'\)~', $field['default'], $matches)) {
				$sequence_name = $matches[1];
				$sq = reset(get_rows(min_version(10)
					? "SELECT *, cache_size AS cache_value FROM pg_sequences WHERE schemaname = current_schema() AND sequencename = " . q($sequence_name)
					: "SELECT * FROM $sequence_name"
				));
				$sequences[] = ($style == "DROP+CREATE" ? "DROP SEQUENCE IF EXISTS $sequence_name;\n" : "")
					. "CREATE SEQUENCE $sequence_name INCREMENT $sq[increment_by] MINVALUE $sq[min_value] MAXVALUE $sq[max_value] START " . ($auto_increment ? $sq['last_value'] : 1) . " CACHE $sq[cache_value];";
			}
		}

		// adding sequences before table definition
		if (!empty($sequences)) {
			$return = implode("\n\n", $sequences) . "\n\n$return";
		}

		// primary + unique keys
		foreach ($indexes as $index_name => $index) {
			switch($index['type']) {
				case 'UNIQUE': $return_parts[] = "CONSTRAINT " . idf_escape($index_name) . " UNIQUE (" . implode(', ', array_map('idf_escape', $index['columns'])) . ")"; break;
				case 'PRIMARY': $return_parts[] = "CONSTRAINT " . idf_escape($index_name) . " PRIMARY KEY (" . implode(', ', array_map('idf_escape', $index['columns'])) . ")"; break;
			}
		}

		// foreign keys
		foreach ($fkeys as $fkey_name => $fkey) {
			$return_parts[] = "CONSTRAINT " . idf_escape($fkey_name) . " $fkey[definition] " . ($fkey['deferrable'] ? 'DEFERRABLE' : 'NOT DEFERRABLE');
		}

		$return .= implode(",\n    ", $return_parts) . "\n) WITH (oids = " . ($status['Oid'] ? 'true' : 'false') . ");";

		// "basic" indexes after table definition
		foreach ($indexes as $index_name => $index) {
			if ($index['type'] == 'INDEX') {
				$columns = array();
				foreach ($index['columns'] as $key => $val) {
					$columns[] = idf_escape($val) . ($index['descs'][$key] ? " DESC" : "");
				}
				$return .= "\n\nCREATE INDEX " . idf_escape($index_name) . " ON " . idf_escape($status['nspname']) . "." . idf_escape($status['Name']) . " USING btree (" . implode(', ', $columns) . ");";
			}
		}

		// coments for table & fields
		if ($status['Comment']) {
			$return .= "\n\nCOMMENT ON TABLE " . idf_escape($status['nspname']) . "." . idf_escape($status['Name']) . " IS " . q($status['Comment']) . ";";
		}

		foreach ($fields as $field_name => $field) {
			if ($field['comment']) {
				$return .= "\n\nCOMMENT ON COLUMN " . idf_escape($status['nspname']) . "." . idf_escape($status['Name']) . "." . idf_escape($field_name) . " IS " . q($field['comment']) . ";";
			}
		}

		return rtrim($return, ';');
	}

	function truncate_sql($table) {
		return "TRUNCATE " . table($table);
	}

	function trigger_sql($table) {
		$status = table_status($table);
		$return = "";
		foreach (triggers($table) as $trg_id => $trg) {
			$trigger = trigger($trg_id, $status['Name']);
			$return .= "\nCREATE TRIGGER " . idf_escape($trigger['Trigger']) . " $trigger[Timing] $trigger[Events] ON " . idf_escape($status["nspname"]) . "." . idf_escape($status['Name']) . " $trigger[Type] $trigger[Statement];;\n";
		}
		return $return;
	}


	function use_sql($database) {
		return "\connect " . idf_escape($database);
	}

	function show_variables() {
		return get_key_vals("SHOW ALL");
	}

	function process_list() {
		return get_rows("SELECT * FROM pg_stat_activity ORDER BY " . (min_version(9.2) ? "pid" : "procpid"));
	}

	function show_status() {
	}

	function convert_field($field) {
	}

	function unconvert_field($field, $return) {
		return $return;
	}

	function support($feature) {
		return preg_match('~^(database|table|columns|sql|indexes|descidx|comment|view|' . (min_version(9.3) ? 'materializedview|' : '') . 'scheme|routine|processlist|sequence|trigger|type|variables|drop_col|kill|dump)$~', $feature);
	}

	function kill_process($val) {
		return queries("SELECT pg_terminate_backend(" . number($val) . ")");
	}

	function connection_id(){
		return "SELECT pg_backend_pid()";
	}

	function max_connections() {
		global $connection;
		return $connection->result("SHOW max_connections");
	}

	$jush = "pgsql";
	$types = array();
	$structured_types = array();
	foreach (array( //! arrays
		lang('Numbers') => array("smallint" => 5, "integer" => 10, "bigint" => 19, "boolean" => 1, "numeric" => 0, "real" => 7, "double precision" => 16, "money" => 20),
		lang('Date and time') => array("date" => 13, "time" => 17, "timestamp" => 20, "timestamptz" => 21, "interval" => 0),
		lang('Strings') => array("character" => 0, "character varying" => 0, "text" => 0, "tsquery" => 0, "tsvector" => 0, "uuid" => 0, "xml" => 0),
		lang('Binary') => array("bit" => 0, "bit varying" => 0, "bytea" => 0),
		lang('Network') => array("cidr" => 43, "inet" => 43, "macaddr" => 17, "txid_snapshot" => 0),
		lang('Geometry') => array("box" => 0, "circle" => 0, "line" => 0, "lseg" => 0, "path" => 0, "point" => 0, "polygon" => 0),
	) as $key => $val) { //! can be retrieved from pg_type
		$types += $val;
		$structured_types[$key] = array_keys($val);
	}
	$unsigned = array();
	$operators = array("=", "<", ">", "<=", ">=", "!=", "~", "!~", "LIKE", "LIKE %%", "ILIKE", "ILIKE %%", "IN", "IS NULL", "NOT LIKE", "NOT IN", "IS NOT NULL"); // no "SQL" to avoid CSRF
	$functions = array("char_length", "lower", "round", "to_hex", "to_timestamp", "upper");
	$grouping = array("avg", "count", "count distinct", "max", "min", "sum");
	$edit_functions = array(
		array(
			"char" => "md5",
			"date|time" => "now",
		), array(
			number_type() => "+/-",
			"date|time" => "+ interval/- interval", //! escape
			"char|text" => "||",
		)
	);
}
$drivers["mssql"] = "MS SQL (beta)";

if (isset($_GET["mssql"])) {
	$possible_drivers = array("SQLSRV", "MSSQL", "PDO_DBLIB");
	define("DRIVER", "mssql");
	if (extension_loaded("sqlsrv")) {
		class Min_DB {
			var $extension = "sqlsrv", $_link, $_result, $server_info, $affected_rows, $errno, $error;

			function _get_error() {
				$this->error = "";
				foreach (sqlsrv_errors() as $error) {
					$this->errno = $error["code"];
					$this->error .= "$error[message]\n";
				}
				$this->error = rtrim($this->error);
			}

			function connect($server, $username, $password) {
				global $adminer;
				$db = $adminer->database();
				$connection_info = array("UID" => $username, "PWD" => $password, "CharacterSet" => "UTF-8");
				if ($db != "") {
					$connection_info["Database"] = $db;
				}
				$this->_link = @sqlsrv_connect(preg_replace('~:~', ',', $server), $connection_info);
				if ($this->_link) {
					$info = sqlsrv_server_info($this->_link);
					$this->server_info = $info['SQLServerVersion'];
				} else {
					$this->_get_error();
				}
				return (bool) $this->_link;
			}

			function quote($string) {
				return "'" . str_replace("'", "''", $string) . "'";
			}

			function select_db($database) {
				return $this->query("USE " . idf_escape($database));
			}

			function query($query, $unbuffered = false) {
				$result = sqlsrv_query($this->_link, $query); //! , array(), ($unbuffered ? array() : array("Scrollable" => "keyset"))
				$this->error = "";
				if (!$result) {
					$this->_get_error();
					return false;
				}
				return $this->store_result($result);
			}

			function multi_query($query) {
				$this->_result = sqlsrv_query($this->_link, $query);
				$this->error = "";
				if (!$this->_result) {
					$this->_get_error();
					return false;
				}
				return true;
			}

			function store_result($result = null) {
				if (!$result) {
					$result = $this->_result;
				}
				if (!$result) {
					return false;
				}
				if (sqlsrv_field_metadata($result)) {
					return new Min_Result($result);
				}
				$this->affected_rows = sqlsrv_rows_affected($result);
				return true;
			}

			function next_result() {
				return $this->_result ? sqlsrv_next_result($this->_result) : null;
			}

			function result($query, $field = 0) {
				$result = $this->query($query);
				if (!is_object($result)) {
					return false;
				}
				$row = $result->fetch_row();
				return $row[$field];
			}
		}

		class Min_Result {
			var $_result, $_offset = 0, $_fields, $num_rows;

			function __construct($result) {
				$this->_result = $result;
				// $this->num_rows = sqlsrv_num_rows($result); // available only in scrollable results
			}

			function _convert($row) {
				foreach ((array) $row as $key => $val) {
					if (is_a($val, 'DateTime')) {
						$row[$key] = $val->format("Y-m-d H:i:s");
					}
					//! stream
				}
				return $row;
			}

			function fetch_assoc() {
				return $this->_convert(sqlsrv_fetch_array($this->_result, SQLSRV_FETCH_ASSOC));
			}

			function fetch_row() {
				return $this->_convert(sqlsrv_fetch_array($this->_result, SQLSRV_FETCH_NUMERIC));
			}

			function fetch_field() {
				if (!$this->_fields) {
					$this->_fields = sqlsrv_field_metadata($this->_result);
				}
				$field = $this->_fields[$this->_offset++];
				$return = new stdClass;
				$return->name = $field["Name"];
				$return->orgname = $field["Name"];
				$return->type = ($field["Type"] == 1 ? 254 : 0);
				return $return;
			}

			function seek($offset) {
				for ($i=0; $i < $offset; $i++) {
					sqlsrv_fetch($this->_result); // SQLSRV_SCROLL_ABSOLUTE added in sqlsrv 1.1
				}
			}

			function __destruct() {
				sqlsrv_free_stmt($this->_result);
			}
		}

	} elseif (extension_loaded("mssql")) {
		class Min_DB {
			var $extension = "MSSQL", $_link, $_result, $server_info, $affected_rows, $error;

			function connect($server, $username, $password) {
				$this->_link = @mssql_connect($server, $username, $password);
				if ($this->_link) {
					$result = $this->query("SELECT SERVERPROPERTY('ProductLevel'), SERVERPROPERTY('Edition')");
					if ($result) {
						$row = $result->fetch_row();
						$this->server_info = $this->result("sp_server_info 2", 2) . " [$row[0]] $row[1]";
					}
				} else {
					$this->error = mssql_get_last_message();
				}
				return (bool) $this->_link;
			}

			function quote($string) {
				return "'" . str_replace("'", "''", $string) . "'";
			}

			function select_db($database) {
				return mssql_select_db($database);
			}

			function query($query, $unbuffered = false) {
				$result = @mssql_query($query, $this->_link); //! $unbuffered
				$this->error = "";
				if (!$result) {
					$this->error = mssql_get_last_message();
					return false;
				}
				if ($result === true) {
					$this->affected_rows = mssql_rows_affected($this->_link);
					return true;
				}
				return new Min_Result($result);
			}

			function multi_query($query) {
				return $this->_result = $this->query($query);
			}

			function store_result() {
				return $this->_result;
			}

			function next_result() {
				return mssql_next_result($this->_result->_result);
			}

			function result($query, $field = 0) {
				$result = $this->query($query);
				if (!is_object($result)) {
					return false;
				}
				return mssql_result($result->_result, 0, $field);
			}
		}

		class Min_Result {
			var $_result, $_offset = 0, $_fields, $num_rows;

			function __construct($result) {
				$this->_result = $result;
				$this->num_rows = mssql_num_rows($result);
			}

			function fetch_assoc() {
				return mssql_fetch_assoc($this->_result);
			}

			function fetch_row() {
				return mssql_fetch_row($this->_result);
			}

			function num_rows() {
				return mssql_num_rows($this->_result);
			}

			function fetch_field() {
				$return = mssql_fetch_field($this->_result);
				$return->orgtable = $return->table;
				$return->orgname = $return->name;
				return $return;
			}

			function seek($offset) {
				mssql_data_seek($this->_result, $offset);
			}

			function __destruct() {
				mssql_free_result($this->_result);
			}
		}

	} elseif (extension_loaded("pdo_dblib")) {
		class Min_DB extends Min_PDO {
			var $extension = "PDO_DBLIB";

			function connect($server, $username, $password) {
				$this->dsn("dblib:charset=utf8;host=" . str_replace(":", ";unix_socket=", preg_replace('~:(\d)~', ';port=\1', $server)), $username, $password);
				return true;
			}

			function select_db($database) {
				// database selection is separated from the connection so dbname in DSN can't be used
				return $this->query("USE " . idf_escape($database));
			}
		}
	}
	class Min_Driver extends Min_SQL {

		function insertUpdate($table, $rows, $primary) {
			foreach ($rows as $set) {
				$update = array();
				$where = array();
				foreach ($set as $key => $val) {
					$update[] = "$key = $val";
					if (isset($primary[idf_unescape($key)])) {
						$where[] = "$key = $val";
					}
				}
				//! can use only one query for all rows
				if (!queries("MERGE " . table($table) . " USING (VALUES(" . implode(", ", $set) . ")) AS source (c" . implode(", c", range(1, count($set))) . ") ON " . implode(" AND ", $where) //! source, c1 - possible conflict
					. " WHEN MATCHED THEN UPDATE SET " . implode(", ", $update)
					. " WHEN NOT MATCHED THEN INSERT (" . implode(", ", array_keys($set)) . ") VALUES (" . implode(", ", $set) . ");" // ; is mandatory
				)) {
					return false;
				}
			}
			return true;
		}

		function begin() {
			return queries("BEGIN TRANSACTION");
		}

	}
	function idf_escape($idf) {
		return "[" . str_replace("]", "]]", $idf) . "]";
	}
	function table($idf) {
		return ($_GET["ns"] != "" ? idf_escape($_GET["ns"]) . "." : "") . idf_escape($idf);
	}
	function connect() {
		global $adminer;
		$connection = new Min_DB;
		$credentials = $adminer->credentials();
		if ($connection->connect($credentials[0], $credentials[1], $credentials[2])) {
			return $connection;
		}
		return $connection->error;
	}

	function get_databases() {
		return get_vals("SELECT name FROM sys.databases WHERE name NOT IN ('master', 'tempdb', 'model', 'msdb')");
	}

	function limit($query, $where, $limit, $offset = 0, $separator = " ") {
		return ($limit !== null ? " TOP (" . ($limit + $offset) . ")" : "") . " $query$where"; // seek later
	}

	function limit1($table, $query, $where, $separator = "\n") {
		return limit($query, $where, 1, 0, $separator);
	}

	function db_collation($db, $collations) {
		global $connection;
		return $connection->result("SELECT collation_name FROM sys.databases WHERE name = " . q($db));
	}

	function engines() {
		return array();
	}

	function logged_user() {
		global $connection;
		return $connection->result("SELECT SUSER_NAME()");
	}

	function tables_list() {
		return get_key_vals("SELECT name, type_desc FROM sys.all_objects WHERE schema_id = SCHEMA_ID(" . q(get_schema()) . ") AND type IN ('S', 'U', 'V') ORDER BY name");
	}

	function count_tables($databases) {
		global $connection;
		$return = array();
		foreach ($databases as $db) {
			$connection->select_db($db);
			$return[$db] = $connection->result("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES");
		}
		return $return;
	}

	function table_status($name = "") {
		$return = array();
		foreach (get_rows("SELECT ao.name AS Name, ao.type_desc AS Engine, (SELECT value FROM fn_listextendedproperty(default, 'SCHEMA', schema_name(schema_id), 'TABLE', ao.name, null, null)) AS Comment FROM sys.all_objects AS ao WHERE schema_id = SCHEMA_ID(" . q(get_schema()) . ") AND type IN ('S', 'U', 'V') " . ($name != "" ? "AND name = " . q($name) : "ORDER BY name")) as $row) {
			if ($name != "") {
				return $row;
			}
			$return[$row["Name"]] = $row;
		}
		return $return;
	}

	function is_view($table_status) {
		return $table_status["Engine"] == "VIEW";
	}

	function fk_support($table_status) {
		return true;
	}

	function fields($table) {
		$comments = get_key_vals("SELECT objname, cast(value as varchar) FROM fn_listextendedproperty('MS_DESCRIPTION', 'schema', " . q(get_schema()) . ", 'table', " . q($table) . ", 'column', NULL)");
		$return = array();
		foreach (get_rows("SELECT c.max_length, c.precision, c.scale, c.name, c.is_nullable, c.is_identity, c.collation_name, t.name type, CAST(d.definition as text) [default]
FROM sys.all_columns c
JOIN sys.all_objects o ON c.object_id = o.object_id
JOIN sys.types t ON c.user_type_id = t.user_type_id
LEFT JOIN sys.default_constraints d ON c.default_object_id = d.parent_column_id
WHERE o.schema_id = SCHEMA_ID(" . q(get_schema()) . ") AND o.type IN ('S', 'U', 'V') AND o.name = " . q($table)
		) as $row) {
			$type = $row["type"];
			$length = (preg_match("~char|binary~", $type) ? $row["max_length"] : ($type == "decimal" ? "$row[precision],$row[scale]" : ""));
			$return[$row["name"]] = array(
				"field" => $row["name"],
				"full_type" => $type . ($length ? "($length)" : ""),
				"type" => $type,
				"length" => $length,
				"default" => $row["default"],
				"null" => $row["is_nullable"],
				"auto_increment" => $row["is_identity"],
				"collation" => $row["collation_name"],
				"privileges" => array("insert" => 1, "select" => 1, "update" => 1),
				"primary" => $row["is_identity"], //! or indexes.is_primary_key
				"comment" => $comments[$row["name"]],
			);
		}
		return $return;
	}

	function indexes($table, $connection2 = null) {
		$return = array();
		// sp_statistics doesn't return information about primary key
		foreach (get_rows("SELECT i.name, key_ordinal, is_unique, is_primary_key, c.name AS column_name, is_descending_key
FROM sys.indexes i
INNER JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
WHERE OBJECT_NAME(i.object_id) = " . q($table)
		, $connection2) as $row) {
			$name = $row["name"];
			$return[$name]["type"] = ($row["is_primary_key"] ? "PRIMARY" : ($row["is_unique"] ? "UNIQUE" : "INDEX"));
			$return[$name]["lengths"] = array();
			$return[$name]["columns"][$row["key_ordinal"]] = $row["column_name"];
			$return[$name]["descs"][$row["key_ordinal"]] = ($row["is_descending_key"] ? '1' : null);
		}
		return $return;
	}

	function view($name) {
		global $connection;
		return array("select" => preg_replace('~^(?:[^[]|\[[^]]*])*\s+AS\s+~isU', '', $connection->result("SELECT VIEW_DEFINITION FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = SCHEMA_NAME() AND TABLE_NAME = " . q($name))));
	}

	function collations() {
		$return = array();
		foreach (get_vals("SELECT name FROM fn_helpcollations()") as $collation) {
			$return[preg_replace('~_.*~', '', $collation)][] = $collation;
		}
		return $return;
	}

	function information_schema($db) {
		return false;
	}

	function error() {
		global $connection;
		return nl_br(h(preg_replace('~^(\[[^]]*])+~m', '', $connection->error)));
	}

	function create_database($db, $collation) {
		return queries("CREATE DATABASE " . idf_escape($db) . (preg_match('~^[a-z0-9_]+$~i', $collation) ? " COLLATE $collation" : ""));
	}

	function drop_databases($databases) {
		return queries("DROP DATABASE " . implode(", ", array_map('idf_escape', $databases)));
	}

	function rename_database($name, $collation) {
		if (preg_match('~^[a-z0-9_]+$~i', $collation)) {
			queries("ALTER DATABASE " . idf_escape(DB) . " COLLATE $collation");
		}
		queries("ALTER DATABASE " . idf_escape(DB) . " MODIFY NAME = " . idf_escape($name));
		return true; //! false negative "The database name 'test2' has been set."
	}

	function auto_increment() {
		return " IDENTITY" . ($_POST["Auto_increment"] != "" ? "(" . number($_POST["Auto_increment"]) . ",1)" : "") . " PRIMARY KEY";
	}

	function alter_table($table, $name, $fields, $foreign, $comment, $engine, $collation, $auto_increment, $partitioning) {
		$alter = array();
		$comments = array();
		foreach ($fields as $field) {
			$column = idf_escape($field[0]);
			$val = $field[1];
			if (!$val) {
				$alter["DROP"][] = " COLUMN $column";
			} else {
				$val[1] = preg_replace("~( COLLATE )'(\\w+)'~", '\1\2', $val[1]);
				$comments[$field[0]] = $val[5];
				unset($val[5]);
				if ($field[0] == "") {
					$alter["ADD"][] = "\n  " . implode("", $val) . ($table == "" ? substr($foreign[$val[0]], 16 + strlen($val[0])) : ""); // 16 - strlen("  FOREIGN KEY ()")
				} else {
					unset($val[6]); //! identity can't be removed
					if ($column != $val[0]) {
						queries("EXEC sp_rename " . q(table($table) . ".$column") . ", " . q(idf_unescape($val[0])) . ", 'COLUMN'");
					}
					$alter["ALTER COLUMN " . implode("", $val)][] = "";
				}
			}
		}
		if ($table == "") {
			return queries("CREATE TABLE " . table($name) . " (" . implode(",", (array) $alter["ADD"]) . "\n)");
		}
		if ($table != $name) {
			queries("EXEC sp_rename " . q(table($table)) . ", " . q($name));
		}
		if ($foreign) {
			$alter[""] = $foreign;
		}
		foreach ($alter as $key => $val) {
			if (!queries("ALTER TABLE " . idf_escape($name) . " $key" . implode(",", $val))) {
				return false;
			}
		}
		foreach ($comments as $key => $val) {
			$comment = substr($val, 9); // 9 - strlen(" COMMENT ")
			queries("EXEC sp_dropextendedproperty @name = N'MS_Description', @level0type = N'Schema', @level0name = " . q(get_schema()) . ", @level1type = N'Table',  @level1name = " . q($name) . ", @level2type = N'Column', @level2name = " . q($key));
			queries("EXEC sp_addextendedproperty @name = N'MS_Description', @value = " . $comment . ", @level0type = N'Schema', @level0name = " . q(get_schema()) . ", @level1type = N'Table',  @level1name = " . q($name) . ", @level2type = N'Column', @level2name = " . q($key));
		}
		return true;
	}

	function alter_indexes($table, $alter) {
		$index = array();
		$drop = array();
		foreach ($alter as $val) {
			if ($val[2] == "DROP") {
				if ($val[0] == "PRIMARY") { //! sometimes used also for UNIQUE
					$drop[] = idf_escape($val[1]);
				} else {
					$index[] = idf_escape($val[1]) . " ON " . table($table);
				}
			} elseif (!queries(($val[0] != "PRIMARY"
				? "CREATE $val[0] " . ($val[0] != "INDEX" ? "INDEX " : "") . idf_escape($val[1] != "" ? $val[1] : uniqid($table . "_")) . " ON " . table($table)
				: "ALTER TABLE " . table($table) . " ADD PRIMARY KEY"
			) . " (" . implode(", ", $val[2]) . ")")) {
				return false;
			}
		}
		return (!$index || queries("DROP INDEX " . implode(", ", $index)))
			&& (!$drop || queries("ALTER TABLE " . table($table) . " DROP " . implode(", ", $drop)))
		;
	}

	function last_id() {
		global $connection;
		return $connection->result("SELECT SCOPE_IDENTITY()"); // @@IDENTITY can return trigger INSERT
	}

	function explain($connection, $query) {
		$connection->query("SET SHOWPLAN_ALL ON");
		$return = $connection->query($query);
		$connection->query("SET SHOWPLAN_ALL OFF"); // connection is used also for indexes
		return $return;
	}

	function found_rows($table_status, $where) {
	}

	function foreign_keys($table) {
		$return = array();
		foreach (get_rows("EXEC sp_fkeys @fktable_name = " . q($table)) as $row) {
			$foreign_key = &$return[$row["FK_NAME"]];
			$foreign_key["db"] = $row["PKTABLE_QUALIFIER"];
			$foreign_key["table"] = $row["PKTABLE_NAME"];
			$foreign_key["source"][] = $row["FKCOLUMN_NAME"];
			$foreign_key["target"][] = $row["PKCOLUMN_NAME"];
		}
		return $return;
	}

	function truncate_tables($tables) {
		return apply_queries("TRUNCATE TABLE", $tables);
	}

	function drop_views($views) {
		return queries("DROP VIEW " . implode(", ", array_map('table', $views)));
	}

	function drop_tables($tables) {
		return queries("DROP TABLE " . implode(", ", array_map('table', $tables)));
	}

	function move_tables($tables, $views, $target) {
		return apply_queries("ALTER SCHEMA " . idf_escape($target) . " TRANSFER", array_merge($tables, $views));
	}

	function trigger($name) {
		if ($name == "") {
			return array();
		}
		$rows = get_rows("SELECT s.name [Trigger],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(s.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(s.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(s.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing],
c.text
FROM sysobjects s
JOIN syscomments c ON s.id = c.id
WHERE s.xtype = 'TR' AND s.name = " . q($name)
		); // triggers are not schema-scoped
		$return = reset($rows);
		if ($return) {
			$return["Statement"] = preg_replace('~^.+\s+AS\s+~isU', '', $return["text"]); //! identifiers, comments
		}
		return $return;
	}

	function triggers($table) {
		$return = array();
		foreach (get_rows("SELECT sys1.name,
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsertTrigger') = 1 THEN 'INSERT' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsUpdateTrigger') = 1 THEN 'UPDATE' WHEN OBJECTPROPERTY(sys1.id, 'ExecIsDeleteTrigger') = 1 THEN 'DELETE' END [Event],
CASE WHEN OBJECTPROPERTY(sys1.id, 'ExecIsInsteadOfTrigger') = 1 THEN 'INSTEAD OF' ELSE 'AFTER' END [Timing]
FROM sysobjects sys1
JOIN sysobjects sys2 ON sys1.parent_obj = sys2.id
WHERE sys1.xtype = 'TR' AND sys2.name = " . q($table)
		) as $row) { // triggers are not schema-scoped
			$return[$row["name"]] = array($row["Timing"], $row["Event"]);
		}
		return $return;
	}

	function trigger_options() {
		return array(
			"Timing" => array("AFTER", "INSTEAD OF"),
			"Event" => array("INSERT", "UPDATE", "DELETE"),
			"Type" => array("AS"),
		);
	}

	function schemas() {
		return get_vals("SELECT name FROM sys.schemas");
	}

	function get_schema() {
		global $connection;
		if ($_GET["ns"] != "") {
			return $_GET["ns"];
		}
		return $connection->result("SELECT SCHEMA_NAME()");
	}

	function set_schema($schema) {
		return true; // ALTER USER is permanent
	}

	function use_sql($database) {
		return "USE " . idf_escape($database);
	}

	function show_variables() {
		return array();
	}

	function show_status() {
		return array();
	}

	function convert_field($field) {
	}

	function unconvert_field($field, $return) {
		return $return;
	}

	function support($feature) {
		return preg_match('~^(comment|columns|database|drop_col|indexes|descidx|scheme|sql|table|trigger|view|view_trigger)$~', $feature); //! routine|
	}

	$jush = "mssql";
	$types = array();
	$structured_types = array();
	foreach (array( //! use sys.types
		lang('Numbers') => array("tinyint" => 3, "smallint" => 5, "int" => 10, "bigint" => 20, "bit" => 1, "decimal" => 0, "real" => 12, "float" => 53, "smallmoney" => 10, "money" => 20),
		lang('Date and time') => array("date" => 10, "smalldatetime" => 19, "datetime" => 19, "datetime2" => 19, "time" => 8, "datetimeoffset" => 10),
		lang('Strings') => array("char" => 8000, "varchar" => 8000, "text" => 2147483647, "nchar" => 4000, "nvarchar" => 4000, "ntext" => 1073741823),
		lang('Binary') => array("binary" => 8000, "varbinary" => 8000, "image" => 2147483647),
	) as $key => $val) {
		$types += $val;
		$structured_types[$key] = array_keys($val);
	}
	$unsigned = array();
	$operators = array("=", "<", ">", "<=", ">=", "!=", "LIKE", "LIKE %%", "IN", "IS NULL", "NOT LIKE", "NOT IN", "IS NOT NULL");
	$functions = array("len", "lower", "round", "upper");
	$grouping = array("avg", "count", "count distinct", "max", "min", "sum");
	$edit_functions = array(
		array(
			"date|time" => "getdate",
		), array(
			"int|decimal|real|float|money|datetime" => "+/-",
			"char|text" => "+",
		)
	);
}

$drivers = array("server" => "MySQL") + $drivers;

if (!defined("DRIVER")) {
	$possible_drivers = array("MySQLi", "MySQL", "PDO_MySQL");
	define("DRIVER", "server"); // server - backwards compatibility
	// MySQLi supports everything, MySQL doesn't support multiple result sets, PDO_MySQL doesn't support orgtable
	if (extension_loaded("mysqli")) {
		class Min_DB extends MySQLi {
			var $extension = "MySQLi";

			function __construct() {
				parent::init();
			}

			function connect($server = "", $username = "", $password = "", $database = null, $port = null, $socket = null) {
				global $adminer;
				mysqli_report(MYSQLI_REPORT_OFF); // stays between requests, not required since PHP 5.3.4
				list($host, $port) = explode(":", $server, 2); // part after : is used for port or socket
				$ssl = $adminer->connectSsl();
				if ($ssl) {
					$this->ssl_set($ssl['key'], $ssl['cert'], $ssl['ca'], '', '');
				}
				$return = @$this->real_connect(
					($server != "" ? $host : ini_get("mysqli.default_host")),
					($server . $username != "" ? $username : ini_get("mysqli.default_user")),
					($server . $username . $password != "" ? $password : ini_get("mysqli.default_pw")),
					$database,
					(is_numeric($port) ? $port : ini_get("mysqli.default_port")),
					(!is_numeric($port) ? $port : $socket),
					($ssl ? 64 : 0) // 64 - MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT (not available before PHP 5.6.16)
				);
				$this->options(MYSQLI_OPT_LOCAL_INFILE, false);
				return $return;
			}

			function set_charset($charset) {
				if (parent::set_charset($charset)) {
					return true;
				}
				// the client library may not support utf8mb4
				parent::set_charset('utf8');
				return $this->query("SET NAMES $charset");
			}

			function result($query, $field = 0) {
				$result = $this->query($query);
				if (!$result) {
					return false;
				}
				$row = $result->fetch_array();
				return $row[$field];
			}
			
			function quote($string) {
				return "'" . $this->escape_string($string) . "'";
			}
		}

	} elseif (extension_loaded("mysql") && !((ini_bool("sql.safe_mode") || ini_bool("mysql.allow_local_infile")) && extension_loaded("pdo_mysql"))) {
		class Min_DB {
			var
				$extension = "MySQL", ///< @var string extension name
				$server_info, ///< @var string server version
				$affected_rows, ///< @var int number of affected rows
				$errno, ///< @var int last error code
				$error, ///< @var string last error message
				$_link, $_result ///< @access private
			;
			function connect($server, $username, $password) {
				if (ini_bool("mysql.allow_local_infile")) {
					$this->error = lang('Disable %s or enable %s or %s extensions.', "'mysql.allow_local_infile'", "MySQLi", "PDO_MySQL");
					return false;
				}
				$this->_link = @mysql_connect(
					($server != "" ? $server : ini_get("mysql.default_host")),
					("$server$username" != "" ? $username : ini_get("mysql.default_user")),
					("$server$username$password" != "" ? $password : ini_get("mysql.default_password")),
					true,
					131072 // CLIENT_MULTI_RESULTS for CALL
				);
				if ($this->_link) {
					$this->server_info = mysql_get_server_info($this->_link);
				} else {
					$this->error = mysql_error();
				}
				return (bool) $this->_link;
			}
			function set_charset($charset) {
				if (function_exists('mysql_set_charset')) {
					if (mysql_set_charset($charset, $this->_link)) {
						return true;
					}
					// the client library may not support utf8mb4
					mysql_set_charset('utf8', $this->_link);
				}
				return $this->query("SET NAMES $charset");
			}
			function quote($string) {
				return "'" . mysql_real_escape_string($string, $this->_link) . "'";
			}
			function select_db($database) {
				return mysql_select_db($database, $this->_link);
			}
			function query($query, $unbuffered = false) {
				$result = @($unbuffered ? mysql_unbuffered_query($query, $this->_link) : mysql_query($query, $this->_link)); // @ - mute mysql.trace_mode
				$this->error = "";
				if (!$result) {
					$this->errno = mysql_errno($this->_link);
					$this->error = mysql_error($this->_link);
					return false;
				}
				if ($result === true) {
					$this->affected_rows = mysql_affected_rows($this->_link);
					$this->info = mysql_info($this->_link);
					return true;
				}
				return new Min_Result($result);
			}
			function multi_query($query) {
				return $this->_result = $this->query($query);
			}
			function store_result() {
				return $this->_result;
			}
			function next_result() {
				// MySQL extension doesn't support multiple results
				return false;
			}
			function result($query, $field = 0) {
				$result = $this->query($query);
				if (!$result || !$result->num_rows) {
					return false;
				}
				return mysql_result($result->_result, 0, $field);
			}
		}

		class Min_Result {
			var
				$num_rows, ///< @var int number of rows in the result
				$_result, $_offset = 0 ///< @access private
			;

			function __construct($result) {
				$this->_result = $result;
				$this->num_rows = mysql_num_rows($result);
			}
			function fetch_assoc() {
				return mysql_fetch_assoc($this->_result);
			}
			function fetch_row() {
				return mysql_fetch_row($this->_result);
			}
			function fetch_field() {
				$return = mysql_fetch_field($this->_result, $this->_offset++); // offset required under certain conditions
				$return->orgtable = $return->table;
				$return->orgname = $return->name;
				$return->charsetnr = ($return->blob ? 63 : 0);
				return $return;
			}

			/** Free result set
			*/
			function __destruct() {
				mysql_free_result($this->_result);
			}
		}

	} elseif (extension_loaded("pdo_mysql")) {
		class Min_DB extends Min_PDO {
			var $extension = "PDO_MySQL";

			function connect($server, $username, $password) {
				global $adminer;
				$options = array(PDO::MYSQL_ATTR_LOCAL_INFILE => false);
				$ssl = $adminer->connectSsl();
				if ($ssl) {
					if (!empty($ssl['key'])) {
						$options[PDO::MYSQL_ATTR_SSL_KEY] = $ssl['key'];
					}
					if (!empty($ssl['cert'])) {
						$options[PDO::MYSQL_ATTR_SSL_CERT] = $ssl['cert'];
					}
					if (!empty($ssl['ca'])) {
						$options[PDO::MYSQL_ATTR_SSL_CA] = $ssl['ca'];
					}
				}
				$this->dsn(
					"mysql:charset=utf8;host=" . str_replace(":", ";unix_socket=", preg_replace('~:(\d)~', ';port=\1', $server)),
					$username,
					$password,
					$options
				);
				return true;
			}

			function set_charset($charset) {
				$this->query("SET NAMES $charset"); // charset in DSN is ignored before PHP 5.3.6
			}

			function select_db($database) {
				// database selection is separated from the connection so dbname in DSN can't be used
				return $this->query("USE " . idf_escape($database));
			}

			function query($query, $unbuffered = false) {
				$this->setAttribute(1000, !$unbuffered); // 1000 - PDO::MYSQL_ATTR_USE_BUFFERED_QUERY
				return parent::query($query, $unbuffered);
			}
		}

	}
	class Min_Driver extends Min_SQL {

		function insert($table, $set) {
			return ($set ? parent::insert($table, $set) : queries("INSERT INTO " . table($table) . " ()\nVALUES ()"));
		}

		function insertUpdate($table, $rows, $primary) {
			$columns = array_keys(reset($rows));
			$prefix = "INSERT INTO " . table($table) . " (" . implode(", ", $columns) . ") VALUES\n";
			$values = array();
			foreach ($columns as $key) {
				$values[$key] = "$key = VALUES($key)";
			}
			$suffix = "\nON DUPLICATE KEY UPDATE " . implode(", ", $values);
			$values = array();
			$length = 0;
			foreach ($rows as $set) {
				$value = "(" . implode(", ", $set) . ")";
				if ($values && (strlen($prefix) + $length + strlen($value) + strlen($suffix) > 1e6)) { // 1e6 - default max_allowed_packet
					if (!queries($prefix . implode(",\n", $values) . $suffix)) {
						return false;
					}
					$values = array();
					$length = 0;
				}
				$values[] = $value;
				$length += strlen($value) + 2; // 2 - strlen(",\n")
			}
			return queries($prefix . implode(",\n", $values) . $suffix);
		}
		
		function slowQuery($query, $timeout) {
			if (min_version('5.7.8', '10.1.2')) {
				if (preg_match('~MariaDB~', $this->_conn->server_info)) {
					return "SET STATEMENT max_statement_time=$timeout FOR $query";
				} elseif (preg_match('~^(SELECT\b)(.+)~is', $query, $match)) {
					return "$match[1] /*+ MAX_EXECUTION_TIME(" . ($timeout * 1000) . ") */ $match[2]";
				}
			}
		}

		function convertSearch($idf, $val, $field) {
			return (preg_match('~char|text|enum|set~', $field["type"]) && !preg_match("~^utf8~", $field["collation"]) && preg_match('~[\x80-\xFF]~', $val['val'])
				? "CONVERT($idf USING " . charset($this->_conn) . ")"
				: $idf
			);
		}
		
		function warnings() {
			$result = $this->_conn->query("SHOW WARNINGS");
			if ($result && $result->num_rows) {
				ob_start();
				select($result); // select() usually needs to print a big table progressively
				return ob_get_clean();
			}
		}

		function tableHelp($name) {
			$maria = preg_match('~MariaDB~', $this->_conn->server_info);
			if (information_schema(DB)) {
				return strtolower(($maria ? "information-schema-$name-table/" : str_replace("_", "-", $name) . "-table.html"));
			}
			if (DB == "mysql") {
				return ($maria ? "mysql$name-table/" : "system-database.html"); //! more precise link
			}
		}

	}
	function idf_escape($idf) {
		return "`" . str_replace("`", "``", $idf) . "`";
	}
	function table($idf) {
		return idf_escape($idf);
	}
	function connect() {
		global $adminer, $types, $structured_types;
		$connection = new Min_DB;
		$credentials = $adminer->credentials();
		if ($connection->connect($credentials[0], $credentials[1], $credentials[2])) {
			$connection->set_charset(charset($connection)); // available in MySQLi since PHP 5.0.5
			$connection->query("SET sql_quote_show_create = 1, autocommit = 1");
			if (min_version('5.7.8', 10.2, $connection)) {
				$structured_types[lang('Strings')][] = "json";
				$types["json"] = 4294967295;
			}
			return $connection;
		}
		$return = $connection->error;
		if (function_exists('iconv') && !is_utf8($return) && strlen($s = iconv("windows-1250", "utf-8", $return)) > strlen($return)) { // windows-1250 - most common Windows encoding
			$return = $s;
		}
		return $return;
	}
	function get_databases($flush) {
		// SHOW DATABASES can take a very long time so it is cached
		$return = get_session("dbs");
		if ($return === null) {
			$query = (min_version(5)
				? "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA ORDER BY SCHEMA_NAME"
				: "SHOW DATABASES"
			); // SHOW DATABASES can be disabled by skip_show_database
			$return = ($flush ? slow_query($query) : get_vals($query));
			restart_session();
			set_session("dbs", $return);
			stop_session();
		}
		return $return;
	}
	function limit($query, $where, $limit, $offset = 0, $separator = " ") {
		return " $query$where" . ($limit !== null ? $separator . "LIMIT $limit" . ($offset ? " OFFSET $offset" : "") : "");
	}
	function limit1($table, $query, $where, $separator = "\n") {
		return limit($query, $where, 1, 0, $separator);
	}
	function db_collation($db, $collations) {
		global $connection;
		$return = null;
		$create = $connection->result("SHOW CREATE DATABASE " . idf_escape($db), 1);
		if (preg_match('~ COLLATE ([^ ]+)~', $create, $match)) {
			$return = $match[1];
		} elseif (preg_match('~ CHARACTER SET ([^ ]+)~', $create, $match)) {
			// default collation
			$return = $collations[$match[1]][-1];
		}
		return $return;
	}
	function engines() {
		$return = array();
		foreach (get_rows("SHOW ENGINES") as $row) {
			if (preg_match("~YES|DEFAULT~", $row["Support"])) {
				$return[] = $row["Engine"];
			}
		}
		return $return;
	}
	function logged_user() {
		global $connection;
		return $connection->result("SELECT USER()");
	}
	function tables_list() {
		return get_key_vals(min_version(5)
			? "SELECT TABLE_NAME, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME"
			: "SHOW TABLES"
		);
	}
	function count_tables($databases) {
		$return = array();
		foreach ($databases as $db) {
			$return[$db] = count(get_vals("SHOW TABLES IN " . idf_escape($db)));
		}
		return $return;
	}
	function table_status($name = "", $fast = false) {
		$return = array();
		foreach (get_rows($fast && min_version(5)
			? "SELECT TABLE_NAME AS Name, ENGINE AS Engine, TABLE_COMMENT AS Comment FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() " . ($name != "" ? "AND TABLE_NAME = " . q($name) : "ORDER BY Name")
			: "SHOW TABLE STATUS" . ($name != "" ? " LIKE " . q(addcslashes($name, "%_\\")) : "")
		) as $row) {
			if ($row["Engine"] == "InnoDB") {
				// ignore internal comment, unnecessary since MySQL 5.1.21
				$row["Comment"] = preg_replace('~(?:(.+); )?InnoDB free: .*~', '\1', $row["Comment"]);
			}
			if (!isset($row["Engine"])) {
				$row["Comment"] = "";
			}
			if ($name != "") {
				return $row;
			}
			$return[$row["Name"]] = $row;
		}
		return $return;
	}
	function is_view($table_status) {
		return $table_status["Engine"] === null;
	}
	function fk_support($table_status) {
		return preg_match('~InnoDB|IBMDB2I~i', $table_status["Engine"])
			|| (preg_match('~NDB~i', $table_status["Engine"]) && min_version(5.6));
	}
	function fields($table) {
		$return = array();
		foreach (get_rows("SHOW FULL COLUMNS FROM " . table($table)) as $row) {
			preg_match('~^([^( ]+)(?:\((.+)\))?( unsigned)?( zerofill)?$~', $row["Type"], $match);
			$return[$row["Field"]] = array(
				"field" => $row["Field"],
				"full_type" => $row["Type"],
				"type" => $match[1],
				"length" => $match[2],
				"unsigned" => ltrim($match[3] . $match[4]),
				"default" => ($row["Default"] != "" || preg_match("~char|set~", $match[1]) ? $row["Default"] : null),
				"null" => ($row["Null"] == "YES"),
				"auto_increment" => ($row["Extra"] == "auto_increment"),
				"on_update" => (preg_match('~^on update (.+)~i', $row["Extra"], $match) ? $match[1] : ""), //! available since MySQL 5.1.23
				"collation" => $row["Collation"],
				"privileges" => array_flip(preg_split('~, *~', $row["Privileges"])),
				"comment" => $row["Comment"],
				"primary" => ($row["Key"] == "PRI"),
				// https://mariadb.com/kb/en/library/show-columns/, https://github.com/vrana/adminer/pull/359#pullrequestreview-276677186
				"generated" => preg_match('~^(VIRTUAL|PERSISTENT|STORED)~', $row["Extra"]),
			);
		}
		return $return;
	}
	function indexes($table, $connection2 = null) {
		$return = array();
		foreach (get_rows("SHOW INDEX FROM " . table($table), $connection2) as $row) {
			$name = $row["Key_name"];
			$return[$name]["type"] = ($name == "PRIMARY" ? "PRIMARY" : ($row["Index_type"] == "FULLTEXT" ? "FULLTEXT" : ($row["Non_unique"] ? ($row["Index_type"] == "SPATIAL" ? "SPATIAL" : "INDEX") : "UNIQUE")));
			$return[$name]["columns"][] = $row["Column_name"];
			$return[$name]["lengths"][] = ($row["Index_type"] == "SPATIAL" ? null : $row["Sub_part"]);
			$return[$name]["descs"][] = null;
		}
		return $return;
	}
	function foreign_keys($table) {
		global $connection, $on_actions;
		static $pattern = '(?:`(?:[^`]|``)+`|"(?:[^"]|"")+")';
		$return = array();
		$create_table = $connection->result("SHOW CREATE TABLE " . table($table), 1);
		if ($create_table) {
			preg_match_all("~CONSTRAINT ($pattern) FOREIGN KEY ?\\(((?:$pattern,? ?)+)\\) REFERENCES ($pattern)(?:\\.($pattern))? \\(((?:$pattern,? ?)+)\\)(?: ON DELETE ($on_actions))?(?: ON UPDATE ($on_actions))?~", $create_table, $matches, PREG_SET_ORDER);
			foreach ($matches as $match) {
				preg_match_all("~$pattern~", $match[2], $source);
				preg_match_all("~$pattern~", $match[5], $target);
				$return[idf_unescape($match[1])] = array(
					"db" => idf_unescape($match[4] != "" ? $match[3] : $match[4]),
					"table" => idf_unescape($match[4] != "" ? $match[4] : $match[3]),
					"source" => array_map('idf_unescape', $source[0]),
					"target" => array_map('idf_unescape', $target[0]),
					"on_delete" => ($match[6] ? $match[6] : "RESTRICT"),
					"on_update" => ($match[7] ? $match[7] : "RESTRICT"),
				);
			}
		}
		return $return;
	}
	function view($name) {
		global $connection;
		return array("select" => preg_replace('~^(?:[^`]|`[^`]*`)*\s+AS\s+~isU', '', $connection->result("SHOW CREATE VIEW " . table($name), 1)));
	}
	function collations() {
		$return = array();
		foreach (get_rows("SHOW COLLATION") as $row) {
			if ($row["Default"]) {
				$return[$row["Charset"]][-1] = $row["Collation"];
			} else {
				$return[$row["Charset"]][] = $row["Collation"];
			}
		}
		ksort($return);
		foreach ($return as $key => $val) {
			asort($return[$key]);
		}
		return $return;
	}
	function information_schema($db) {
		return (min_version(5) && $db == "information_schema")
			|| (min_version(5.5) && $db == "performance_schema");
	}
	function error() {
		global $connection;
		return h(preg_replace('~^You have an error.*syntax to use~U', "Syntax error", $connection->error));
	}
	function create_database($db, $collation) {
		return queries("CREATE DATABASE " . idf_escape($db) . ($collation ? " COLLATE " . q($collation) : ""));
	}
	function drop_databases($databases) {
		$return = apply_queries("DROP DATABASE", $databases, 'idf_escape');
		restart_session();
		set_session("dbs", null);
		return $return;
	}
	function rename_database($name, $collation) {
		$return = false;
		if (create_database($name, $collation)) {
			//! move triggers
			$rename = array();
			foreach (tables_list() as $table => $type) {
				$rename[] = table($table) . " TO " . idf_escape($name) . "." . table($table);
			}
			$return = (!$rename || queries("RENAME TABLE " . implode(", ", $rename)));
			if ($return) {
				queries("DROP DATABASE " . idf_escape(DB));
			}
			restart_session();
			set_session("dbs", null);
		}
		return $return;
	}
	function auto_increment() {
		$auto_increment_index = " PRIMARY KEY";
		// don't overwrite primary key by auto_increment
		if ($_GET["create"] != "" && $_POST["auto_increment_col"]) {
			foreach (indexes($_GET["create"]) as $index) {
				if (in_array($_POST["fields"][$_POST["auto_increment_col"]]["orig"], $index["columns"], true)) {
					$auto_increment_index = "";
					break;
				}
				if ($index["type"] == "PRIMARY") {
					$auto_increment_index = " UNIQUE";
				}
			}
		}
		return " AUTO_INCREMENT$auto_increment_index";
	}
	function alter_table($table, $name, $fields, $foreign, $comment, $engine, $collation, $auto_increment, $partitioning) {
		$alter = array();
		foreach ($fields as $field) {
			$alter[] = ($field[1]
				? ($table != "" ? ($field[0] != "" ? "CHANGE " . idf_escape($field[0]) : "ADD") : " ") . " " . implode($field[1]) . ($table != "" ? $field[2] : "")
				: "DROP " . idf_escape($field[0])
			);
		}
		$alter = array_merge($alter, $foreign);
		$status = ($comment !== null ? " COMMENT=" . q($comment) : "")
			. ($engine ? " ENGINE=" . q($engine) : "")
			. ($collation ? " COLLATE " . q($collation) : "")
			. ($auto_increment != "" ? " AUTO_INCREMENT=$auto_increment" : "")
		;
		if ($table == "") {
			return queries("CREATE TABLE " . table($name) . " (\n" . implode(",\n", $alter) . "\n)$status$partitioning");
		}
		if ($table != $name) {
			$alter[] = "RENAME TO " . table($name);
		}
		if ($status) {
			$alter[] = ltrim($status);
		}
		return ($alter || $partitioning ? queries("ALTER TABLE " . table($table) . "\n" . implode(",\n", $alter) . $partitioning) : true);
	}
	function alter_indexes($table, $alter) {
		foreach ($alter as $key => $val) {
			$alter[$key] = ($val[2] == "DROP"
				? "\nDROP INDEX " . idf_escape($val[1])
				: "\nADD $val[0] " . ($val[0] == "PRIMARY" ? "KEY " : "") . ($val[1] != "" ? idf_escape($val[1]) . " " : "") . "(" . implode(", ", $val[2]) . ")"
			);
		}
		return queries("ALTER TABLE " . table($table) . implode(",", $alter));
	}
	function truncate_tables($tables) {
		return apply_queries("TRUNCATE TABLE", $tables);
	}
	function drop_views($views) {
		return queries("DROP VIEW " . implode(", ", array_map('table', $views)));
	}
	function drop_tables($tables) {
		return queries("DROP TABLE " . implode(", ", array_map('table', $tables)));
	}
	function move_tables($tables, $views, $target) {
		$rename = array();
		foreach (array_merge($tables, $views) as $table) { // views will report SQL error
			$rename[] = table($table) . " TO " . idf_escape($target) . "." . table($table);
		}
		return queries("RENAME TABLE " . implode(", ", $rename));
		//! move triggers
	}
	function copy_tables($tables, $views, $target) {
		queries("SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");
		foreach ($tables as $table) {
			$name = ($target == DB ? table("copy_$table") : idf_escape($target) . "." . table($table));
			if (($_POST["overwrite"] && !queries("\nDROP TABLE IF EXISTS $name"))
				|| !queries("CREATE TABLE $name LIKE " . table($table))
				|| !queries("INSERT INTO $name SELECT * FROM " . table($table))
			) {
				return false;
			}
			foreach (get_rows("SHOW TRIGGERS LIKE " . q(addcslashes($table, "%_\\"))) as $row) {
				$trigger = $row["Trigger"];
				if (!queries("CREATE TRIGGER " . ($target == DB ? idf_escape("copy_$trigger") : idf_escape($target) . "." . idf_escape($trigger)) . " $row[Timing] $row[Event] ON $name FOR EACH ROW\n$row[Statement];")) {
					return false;
				}
			}
		}
		foreach ($views as $table) {
			$name = ($target == DB ? table("copy_$table") : idf_escape($target) . "." . table($table));
			$view = view($table);
			if (($_POST["overwrite"] && !queries("DROP VIEW IF EXISTS $name"))
				|| !queries("CREATE VIEW $name AS $view[select]")) { //! USE to avoid db.table
				return false;
			}
		}
		return true;
	}
	function trigger($name) {
		if ($name == "") {
			return array();
		}
		$rows = get_rows("SHOW TRIGGERS WHERE `Trigger` = " . q($name));
		return reset($rows);
	}
	function triggers($table) {
		$return = array();
		foreach (get_rows("SHOW TRIGGERS LIKE " . q(addcslashes($table, "%_\\"))) as $row) {
			$return[$row["Trigger"]] = array($row["Timing"], $row["Event"]);
		}
		return $return;
	}
	function trigger_options() {
		return array(
			"Timing" => array("BEFORE", "AFTER"),
			"Event" => array("INSERT", "UPDATE", "DELETE"),
			"Type" => array("FOR EACH ROW"),
		);
	}
	function routine($name, $type) {
		global $connection, $enum_length, $inout, $types;
		$aliases = array("bool", "boolean", "integer", "double precision", "real", "dec", "numeric", "fixed", "national char", "national varchar");
		$space = "(?:\\s|/\\*[\s\S]*?\\*/|(?:#|-- )[^\n]*\n?|--\r?\n)";
		$type_pattern = "((" . implode("|", array_merge(array_keys($types), $aliases)) . ")\\b(?:\\s*\\(((?:[^'\")]|$enum_length)++)\\))?\\s*(zerofill\\s*)?(unsigned(?:\\s+zerofill)?)?)(?:\\s*(?:CHARSET|CHARACTER\\s+SET)\\s*['\"]?([^'\"\\s,]+)['\"]?)?";
		$pattern = "$space*(" . ($type == "FUNCTION" ? "" : $inout) . ")?\\s*(?:`((?:[^`]|``)*)`\\s*|\\b(\\S+)\\s+)$type_pattern";
		$create = $connection->result("SHOW CREATE $type " . idf_escape($name), 2);
		preg_match("~\\(((?:$pattern\\s*,?)*)\\)\\s*" . ($type == "FUNCTION" ? "RETURNS\\s+$type_pattern\\s+" : "") . "(.*)~is", $create, $match);
		$fields = array();
		preg_match_all("~$pattern\\s*,?~is", $match[1], $matches, PREG_SET_ORDER);
		foreach ($matches as $param) {
			$fields[] = array(
				"field" => str_replace("``", "`", $param[2]) . $param[3],
				"type" => strtolower($param[5]),
				"length" => preg_replace_callback("~$enum_length~s", 'normalize_enum', $param[6]),
				"unsigned" => strtolower(preg_replace('~\s+~', ' ', trim("$param[8] $param[7]"))),
				"null" => 1,
				"full_type" => $param[4],
				"inout" => strtoupper($param[1]),
				"collation" => strtolower($param[9]),
			);
		}
		if ($type != "FUNCTION") {
			return array("fields" => $fields, "definition" => $match[11]);
		}
		return array(
			"fields" => $fields,
			"returns" => array("type" => $match[12], "length" => $match[13], "unsigned" => $match[15], "collation" => $match[16]),
			"definition" => $match[17],
			"language" => "SQL", // available in information_schema.ROUTINES.PARAMETER_STYLE
		);
	}
	function routines() {
		return get_rows("SELECT ROUTINE_NAME AS SPECIFIC_NAME, ROUTINE_NAME, ROUTINE_TYPE, DTD_IDENTIFIER FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = " . q(DB));
	}
	function routine_languages() {
		return array(); // "SQL" not required
	}
	function routine_id($name, $row) {
		return idf_escape($name);
	}
	function last_id() {
		global $connection;
		return $connection->result("SELECT LAST_INSERT_ID()"); // mysql_insert_id() truncates bigint
	}
	function explain($connection, $query) {
		return $connection->query("EXPLAIN " . (min_version(5.1) ? "PARTITIONS " : "") . $query);
	}
	function found_rows($table_status, $where) {
		return ($where || $table_status["Engine"] != "InnoDB" ? null : $table_status["Rows"]);
	}
	function types() {
		return array();
	}
	function schemas() {
		return array();
	}
	function get_schema() {
		return "";
	}
	function set_schema($schema, $connection2 = null) {
		return true;
	}
	function create_sql($table, $auto_increment, $style) {
		global $connection;
		$return = $connection->result("SHOW CREATE TABLE " . table($table), 1);
		if (!$auto_increment) {
			$return = preg_replace('~ AUTO_INCREMENT=\d+~', '', $return); //! skip comments
		}
		return $return;
	}
	function truncate_sql($table) {
		return "TRUNCATE " . table($table);
	}
	function use_sql($database) {
		return "USE " . idf_escape($database);
	}
	function trigger_sql($table) {
		$return = "";
		foreach (get_rows("SHOW TRIGGERS LIKE " . q(addcslashes($table, "%_\\")), null, "-- ") as $row) {
			$return .= "\nCREATE TRIGGER " . idf_escape($row["Trigger"]) . " $row[Timing] $row[Event] ON " . table($row["Table"]) . " FOR EACH ROW\n$row[Statement];;\n";
		}
		return $return;
	}
	function show_variables() {
		return get_key_vals("SHOW VARIABLES");
	}
	function process_list() {
		return get_rows("SHOW FULL PROCESSLIST");
	}
	function show_status() {
		return get_key_vals("SHOW STATUS");
	}
	function convert_field($field) {
		if (preg_match("~binary~", $field["type"])) {
			return "HEX(" . idf_escape($field["field"]) . ")";
		}
		if ($field["type"] == "bit") {
			return "BIN(" . idf_escape($field["field"]) . " + 0)"; // + 0 is required outside MySQLnd
		}
		if (preg_match("~geometry|point|linestring|polygon~", $field["type"])) {
			return (min_version(8) ? "ST_" : "") . "AsWKT(" . idf_escape($field["field"]) . ")";
		}
	}
	function unconvert_field($field, $return) {
		if (preg_match("~binary~", $field["type"])) {
			$return = "UNHEX($return)";
		}
		if ($field["type"] == "bit") {
			$return = "CONV($return, 2, 10) + 0";
		}
		if (preg_match("~geometry|point|linestring|polygon~", $field["type"])) {
			$return = (min_version(8) ? "ST_" : "") . "GeomFromText($return, SRID($field[field]))";
		}
		return $return;
	}
	function support($feature) {
		return !preg_match("~scheme|sequence|type|view_trigger|materializedview" . (min_version(8) ? "" : "|descidx" . (min_version(5.1) ? "" : "|event|partitioning" . (min_version(5) ? "" : "|routine|trigger|view"))) . "~", $feature);
	}
	function kill_process($val) {
		return queries("KILL " . number($val));
	}
	function connection_id(){
		return "SELECT CONNECTION_ID()";
	}
	function max_connections() {
		global $connection;
		return $connection->result("SELECT @@max_connections");
	}
	$jush = "sql"; ///< @var string JUSH identifier
	$types = array(); ///< @var array ($type => $maximum_unsigned_length, ...)
	$structured_types = array(); ///< @var array ($description => array($type, ...), ...)
	foreach (array(
		lang('Numbers') => array("tinyint" => 3, "smallint" => 5, "mediumint" => 8, "int" => 10, "bigint" => 20, "decimal" => 66, "float" => 12, "double" => 21),
		lang('Date and time') => array("date" => 10, "datetime" => 19, "timestamp" => 19, "time" => 10, "year" => 4),
		lang('Strings') => array("char" => 255, "varchar" => 65535, "tinytext" => 255, "text" => 65535, "mediumtext" => 16777215, "longtext" => 4294967295),
		lang('Lists') => array("enum" => 65535, "set" => 64),
		lang('Binary') => array("bit" => 20, "binary" => 255, "varbinary" => 65535, "tinyblob" => 255, "blob" => 65535, "mediumblob" => 16777215, "longblob" => 4294967295),
		lang('Geometry') => array("geometry" => 0, "point" => 0, "linestring" => 0, "polygon" => 0, "multipoint" => 0, "multilinestring" => 0, "multipolygon" => 0, "geometrycollection" => 0),
	) as $key => $val) {
		$types += $val;
		$structured_types[$key] = array_keys($val);
	}
	$unsigned = array("unsigned", "zerofill", "unsigned zerofill"); ///< @var array number variants
	$operators = array("=", "<", ">", "<=", ">=", "!=", "LIKE", "LIKE %%", "REGEXP", "IN", "FIND_IN_SET", "IS NULL", "NOT LIKE", "NOT REGEXP", "NOT IN", "IS NOT NULL", "SQL"); ///< @var array operators used in select
	$functions = array("char_length", "date", "from_unixtime", "lower", "round", "floor", "ceil", "sec_to_time", "time_to_sec", "upper"); ///< @var array functions used in select
	$grouping = array("avg", "count", "count distinct", "group_concat", "max", "min", "sum"); ///< @var array grouping functions used in select
	$edit_functions = array( ///< @var array of array("$type|$type2" => "$function/$function2") functions used in editing, [0] - edit and insert, [1] - edit only
		array(
			"char" => "md5/sha1/password/encrypt/uuid",
			"binary" => "md5/sha1",
			"date|time" => "now",
		), array(
			number_type() => "+/-",
			"date" => "+ interval/- interval",
			"time" => "addtime/subtime",
			"char|text" => "concat",
		)
	);
}

define("SERVER", $_GET[DRIVER]); // read from pgsql=localhost
define("DB", $_GET["db"]); // for the sake of speed and size
define("ME", str_replace(":", "%3a", preg_replace('~^[^?]*/([^?]*).*~', '\1', $_SERVER["REQUEST_URI"])) . '?'
	. (sid() ? SID . '&' : '')
	. (SERVER !== null ? DRIVER . "=" . urlencode(SERVER) . '&' : '')
	. (isset($_GET["username"]) ? "username=" . urlencode($_GET["username"]) . '&' : '')
	. (DB != "" ? 'db=' . urlencode(DB) . '&' . (isset($_GET["ns"]) ? "ns=" . urlencode($_GET["ns"]) . "&" : "") : '')
);

$VERSION = "4.7.6";

// any method change in this file should be transferred to editor/include/adminer.inc.php and plugins/plugin.php

class Adminer {
	var $operators;
	function name() {
		return "<a href='https://www.adminer.org/'" . target_blank() . " id='h1'>Adminer</a>";
	}
	function credentials() {
		return array(SERVER, $_GET["username"], get_password());
	}
	function connectSsl() {
	}
	function permanentLogin($create = false) {
		return password_file($create);
	}
	function bruteForceKey() {
		return $_SERVER["REMOTE_ADDR"];
	}
	function serverName($server) {
		return h($server);
	}
	function database() {
		// should be used everywhere instead of DB
		return DB;
	}
	function databases($flush = true) {
		return get_databases($flush);
	}
	function schemas() {
		return schemas();
	}
	function queryTimeout() {
		return 2;
	}
	function headers() {
	}
	function csp() {
		return csp();
	}
	function head() {
		?>
<link rel="stylesheet" type="text/css">
<?php
		return true;
	}
	function css() {
		$return = array();
		$filename = "adminer.css";
		if (file_exists($filename)) {
			$return[] = "$filename?v=" . crc32(file_get_contents($filename));
		}
		return $return;
	}
	function loginForm() {
		global $drivers;
		echo "<table cellspacing='0' class='layout'>\n";
		echo $this->loginFormField('driver', '<tr><th>' . lang('System') . '<td>', html_select("auth[driver]", $drivers, DRIVER, "loginDriver(this);") . "\n");
		echo $this->loginFormField('server', '<tr><th>' . lang('Server') . '<td>', '<input name="auth[server]" value="' . h(SERVER) . '" title="hostname[:port]" placeholder="localhost" autocapitalize="off">' . "\n");
		echo $this->loginFormField('username', '<tr><th>' . lang('Username') . '<td>', '<input name="auth[username]" id="username" value="' . h($_GET["username"]) . '" autocomplete="username" autocapitalize="off">' . script("focus(qs('#username')); qs('#username').form['auth[driver]'].onchange();"));
		echo $this->loginFormField('password', '<tr><th>' . lang('Password') . '<td>', '<input type="password" name="auth[password]" autocomplete="current-password">' . "\n");
		echo $this->loginFormField('db', '<tr><th>' . lang('Database') . '<td>', '<input name="auth[db]" value="' . h($_GET["db"]) . '" autocapitalize="off">' . "\n");
		echo "</table>\n";
		echo "<p><input type='submit' value='" . lang('Login') . "'>\n";
		echo checkbox("auth[permanent]", 1, $_COOKIE["adminer_permanent"], lang('Permanent login')) . "\n";
	}
	function loginFormField($name, $heading, $value) {
		return $heading . $value;
	}
	function login($login, $password) {
		if ($password == "") {
			return lang('Adminer does not support accessing a database without a password, <a href="https://www.adminer.org/en/password/"%s>more information</a>.', target_blank());
		}
		return true;
	}
	function tableName($tableStatus) {
		return h($tableStatus["Name"]);
	}
	function fieldName($field, $order = 0) {
		return '<span title="' . h($field["full_type"]) . '">' . h($field["field"]) . '</span>';
	}
	function selectLinks($tableStatus, $set = "") {
		global $jush, $driver;
		echo '<p class="links">';
		$links = array("select" => lang('Select data'));
		if (support("table") || support("indexes")) {
			$links["table"] = lang('Show structure');
		}
		if (support("table")) {
			if (is_view($tableStatus)) {
				$links["view"] = lang('Alter view');
			} else {
				$links["create"] = lang('Alter table');
			}
		}
		if ($set !== null) {
			$links["edit"] = lang('New item');
		}
		$name = $tableStatus["Name"];
		foreach ($links as $key => $val) {
			echo " <a href='" . h(ME) . "$key=" . urlencode($name) . ($key == "edit" ? $set : "") . "'" . bold(isset($_GET[$key])) . ">$val</a>";
		}
		echo doc_link(array($jush => $driver->tableHelp($name)), "?");
		echo "\n";
	}
	function foreignKeys($table) {
		return foreign_keys($table);
	}
	function backwardKeys($table, $tableName) {
		return array();
	}
	function backwardKeysPrint($backwardKeys, $row) {
	}
	function selectQuery($query, $start, $failed = false) {
		global $jush, $driver;
		$return = "</p>\n"; // required for IE9 inline edit
		if (!$failed && ($warnings = $driver->warnings())) {
			$id = "warnings";
			$return = ", <a href='#$id'>" . lang('Warnings') . "</a>" . script("qsl('a').onclick = partial(toggle, '$id');", "")
				. "$return<div id='$id' class='hidden'>\n$warnings</div>\n"
			;
		}
		return "<p><code class='jush-$jush'>" . h(str_replace("\n", " ", $query)) . "</code> <span class='time'>(" . format_time($start) . ")</span>"
			. (support("sql") ? " <a href='" . h(ME) . "sql=" . urlencode($query) . "'>" . lang('Edit') . "</a>" : "")
			. $return
		;
	}
	function sqlCommandQuery($query)
	{
		return shorten_utf8(trim($query), 1000);
	}
	function rowDescription($table) {
		return "";
	}
	function rowDescriptions($rows, $foreignKeys) {
		return $rows;
	}
	function selectLink($val, $field) {
	}
	function selectVal($val, $link, $field, $original) {
		$return = ($val === null ? "<i>NULL</i>" : (preg_match("~char|binary|boolean~", $field["type"]) && !preg_match("~var~", $field["type"]) ? "<code>$val</code>" : $val));
		if (preg_match('~blob|bytea|raw|file~', $field["type"]) && !is_utf8($val)) {
			$return = "<i>" . lang('%d byte(s)', strlen($original)) . "</i>";
		}
		if (preg_match('~json~', $field["type"])) {
			$return = "<code class='jush-js'>$return</code>";
		}
		return ($link ? "<a href='" . h($link) . "'" . (is_url($link) ? target_blank() : "") . ">$return</a>" : $return);
	}
	function editVal($val, $field) {
		return $val;
	}
	function tableStructurePrint($fields) {
		echo "<div class='scrollable'>\n";
		echo "<table cellspacing='0' class='nowrap'>\n";
		echo "<thead><tr><th>" . lang('Column') . "<td>" . lang('Type') . (support("comment") ? "<td>" . lang('Comment') : "") . "</thead>\n";
		foreach ($fields as $field) {
			echo "<tr" . odd() . "><th>" . h($field["field"]);
			echo "<td><span title='" . h($field["collation"]) . "'>" . h($field["full_type"]) . "</span>";
			echo ($field["null"] ? " <i>NULL</i>" : "");
			echo ($field["auto_increment"] ? " <i>" . lang('Auto Increment') . "</i>" : "");
			echo (isset($field["default"]) ? " <span title='" . lang('Default value') . "'>[<b>" . h($field["default"]) . "</b>]</span>" : "");
			echo (support("comment") ? "<td>" . h($field["comment"]) : "");
			echo "\n";
		}
		echo "</table>\n";
		echo "</div>\n";
	}
	function tableIndexesPrint($indexes) {
		echo "<table cellspacing='0'>\n";
		foreach ($indexes as $name => $index) {
			ksort($index["columns"]); // enforce correct columns order
			$print = array();
			foreach ($index["columns"] as $key => $val) {
				$print[] = "<i>" . h($val) . "</i>"
					. ($index["lengths"][$key] ? "(" . $index["lengths"][$key] . ")" : "")
					. ($index["descs"][$key] ? " DESC" : "")
				;
			}
			echo "<tr title='" . h($name) . "'><th>$index[type]<td>" . implode(", ", $print) . "\n";
		}
		echo "</table>\n";
	}
	function selectColumnsPrint($select, $columns) {
		global $functions, $grouping;
		print_fieldset("select", lang('Select'), $select);
		$i = 0;
		$select[""] = array();
		foreach ($select as $key => $val) {
			$val = $_GET["columns"][$key];
			$column = select_input(
				" name='columns[$i][col]'",
				$columns,
				$val["col"],
				($key !== "" ? "selectFieldChange" : "selectAddRow")
			);
			echo "<div>" . ($functions || $grouping ? "<select name='columns[$i][fun]'>"
				. optionlist(array(-1 => "") + array_filter(array(lang('Functions') => $functions, lang('Aggregation') => $grouping)), $val["fun"]) . "</select>"
				. on_help("getTarget(event).value && getTarget(event).value.replace(/ |\$/, '(') + ')'", 1)
				. script("qsl('select').onchange = function () { helpClose();" . ($key !== "" ? "" : " qsl('select, input', this.parentNode).onchange();") . " };", "")
				. "($column)" : $column) . "</div>\n";
			$i++;
		}
		echo "</div></fieldset>\n";
	}
	function selectSearchPrint($where, $columns, $indexes) {
		print_fieldset("search", lang('Search'), $where);
		foreach ($indexes as $i => $index) {
			if ($index["type"] == "FULLTEXT") {
				echo "<div>(<i>" . implode("</i>, <i>", array_map('h', $index["columns"])) . "</i>) AGAINST";
				echo " <input type='search' name='fulltext[$i]' value='" . h($_GET["fulltext"][$i]) . "'>";
				echo script("qsl('input').oninput = selectFieldChange;", "");
				echo checkbox("boolean[$i]", 1, isset($_GET["boolean"][$i]), "BOOL");
				echo "</div>\n";
			}
		}
		$change_next = "this.parentNode.firstChild.onchange();";
		foreach (array_merge((array) $_GET["where"], array(array())) as $i => $val) {
			if (!$val || ("$val[col]$val[val]" != "" && in_array($val["op"], $this->operators))) {
				echo "<div>" . select_input(
					" name='where[$i][col]'",
					$columns,
					$val["col"],
					($val ? "selectFieldChange" : "selectAddRow"),
					"(" . lang('anywhere') . ")"
				);
				echo html_select("where[$i][op]", $this->operators, $val["op"], $change_next);
				echo "<input type='search' name='where[$i][val]' value='" . h($val["val"]) . "'>";
				echo script("mixin(qsl('input'), {oninput: function () { $change_next }, onkeydown: selectSearchKeydown, onsearch: selectSearchSearch});", "");
				echo "</div>\n";
			}
		}
		echo "</div></fieldset>\n";
	}
	function selectOrderPrint($order, $columns, $indexes) {
		print_fieldset("sort", lang('Sort'), $order);
		$i = 0;
		foreach ((array) $_GET["order"] as $key => $val) {
			if ($val != "") {
				echo "<div>" . select_input(" name='order[$i]'", $columns, $val, "selectFieldChange");
				echo checkbox("desc[$i]", 1, isset($_GET["desc"][$key]), lang('descending')) . "</div>\n";
				$i++;
			}
		}
		echo "<div>" . select_input(" name='order[$i]'", $columns, "", "selectAddRow");
		echo checkbox("desc[$i]", 1, false, lang('descending')) . "</div>\n";
		echo "</div></fieldset>\n";
	}
	function selectLimitPrint($limit) {
		echo "<fieldset><legend>" . lang('Limit') . "</legend><div>"; // <div> for easy styling
		echo "<input type='number' name='limit' class='size' value='" . h($limit) . "'>";
		echo script("qsl('input').oninput = selectFieldChange;", "");
		echo "</div></fieldset>\n";
	}
	function selectLengthPrint($text_length) {
		if ($text_length !== null) {
			echo "<fieldset><legend>" . lang('Text length') . "</legend><div>";
			echo "<input type='number' name='text_length' class='size' value='" . h($text_length) . "'>";
			echo "</div></fieldset>\n";
		}
	}
	function selectActionPrint($indexes) {
		echo "<fieldset><legend>" . lang('Action') . "</legend><div>";
		echo "<input type='submit' value='" . lang('Select') . "'>";
		echo " <span id='noindex' title='" . lang('Full table scan') . "'></span>";
		echo "<script" . nonce() . ">\n";
		echo "var indexColumns = ";
		$columns = array();
		foreach ($indexes as $index) {
			$current_key = reset($index["columns"]);
			if ($index["type"] != "FULLTEXT" && $current_key) {
				$columns[$current_key] = 1;
			}
		}
		$columns[""] = 1;
		foreach ($columns as $key => $val) {
			json_row($key);
		}
		echo ";\n";
		echo "selectFieldChange.call(qs('#form')['select']);\n";
		echo "</script>\n";
		echo "</div></fieldset>\n";
	}
	function selectCommandPrint() {
		return !information_schema(DB);
	}
	function selectImportPrint() {
		return !information_schema(DB);
	}
	function selectEmailPrint($emailFields, $columns) {
	}
	function selectColumnsProcess($columns, $indexes) {
		global $functions, $grouping;
		$select = array(); // select expressions, empty for *
		$group = array(); // expressions without aggregation - will be used for GROUP BY if an aggregation function is used
		foreach ((array) $_GET["columns"] as $key => $val) {
			if ($val["fun"] == "count" || ($val["col"] != "" && (!$val["fun"] || in_array($val["fun"], $functions) || in_array($val["fun"], $grouping)))) {
				$select[$key] = apply_sql_function($val["fun"], ($val["col"] != "" ? idf_escape($val["col"]) : "*"));
				if (!in_array($val["fun"], $grouping)) {
					$group[] = $select[$key];
				}
			}
		}
		return array($select, $group);
	}
	function selectSearchProcess($fields, $indexes) {
		global $connection, $driver;
		$return = array();
		foreach ($indexes as $i => $index) {
			if ($index["type"] == "FULLTEXT" && $_GET["fulltext"][$i] != "") {
				$return[] = "MATCH (" . implode(", ", array_map('idf_escape', $index["columns"])) . ") AGAINST (" . q($_GET["fulltext"][$i]) . (isset($_GET["boolean"][$i]) ? " IN BOOLEAN MODE" : "") . ")";
			}
		}
		foreach ((array) $_GET["where"] as $key => $val) {
			if ("$val[col]$val[val]" != "" && in_array($val["op"], $this->operators)) {
				$prefix = "";
				$cond = " $val[op]";
				if (preg_match('~IN$~', $val["op"])) {
					$in = process_length($val["val"]);
					$cond .= " " . ($in != "" ? $in : "(NULL)");
				} elseif ($val["op"] == "SQL") {
					$cond = " $val[val]"; // SQL injection
				} elseif ($val["op"] == "LIKE %%") {
					$cond = " LIKE " . $this->processInput($fields[$val["col"]], "%$val[val]%");
				} elseif ($val["op"] == "ILIKE %%") {
					$cond = " ILIKE " . $this->processInput($fields[$val["col"]], "%$val[val]%");
				} elseif ($val["op"] == "FIND_IN_SET") {
					$prefix = "$val[op](" . q($val["val"]) . ", ";
					$cond = ")";
				} elseif (!preg_match('~NULL$~', $val["op"])) {
					$cond .= " " . $this->processInput($fields[$val["col"]], $val["val"]);
				}
				if ($val["col"] != "") {
					$return[] = $prefix . $driver->convertSearch(idf_escape($val["col"]), $val, $fields[$val["col"]]) . $cond;
				} else {
					// find anywhere
					$cols = array();
					foreach ($fields as $name => $field) {
						if ((preg_match('~^[-\d.' . (preg_match('~IN$~', $val["op"]) ? ',' : '') . ']+$~', $val["val"]) || !preg_match('~' . number_type() . '|bit~', $field["type"]))
							&& (!preg_match("~[\x80-\xFF]~", $val["val"]) || preg_match('~char|text|enum|set~', $field["type"]))
						) {
							$cols[] = $prefix . $driver->convertSearch(idf_escape($name), $val, $field) . $cond;
						}
					}
					$return[] = ($cols ? "(" . implode(" OR ", $cols) . ")" : "1 = 0");
				}
			}
		}
		return $return;
	}
	function selectOrderProcess($fields, $indexes) {
		$return = array();
		foreach ((array) $_GET["order"] as $key => $val) {
			if ($val != "") {
				$return[] = (preg_match('~^((COUNT\(DISTINCT |[A-Z0-9_]+\()(`(?:[^`]|``)+`|"(?:[^"]|"")+")\)|COUNT\(\*\))$~', $val) ? $val : idf_escape($val)) //! MS SQL uses []
					. (isset($_GET["desc"][$key]) ? " DESC" : "")
				;
			}
		}
		return $return;
	}
	function selectLimitProcess() {
		return (isset($_GET["limit"]) ? $_GET["limit"] : "50");
	}
	function selectLengthProcess() {
		return (isset($_GET["text_length"]) ? $_GET["text_length"] : "100");
	}
	function selectEmailProcess($where, $foreignKeys) {
		return false;
	}
	function selectQueryBuild($select, $where, $group, $order, $limit, $page) {
		return "";
	}
	function messageQuery($query, $time, $failed = false) {
		global $jush, $driver;
		restart_session();
		$history = &get_session("queries");
		if (!$history[$_GET["db"]]) {
			$history[$_GET["db"]] = array();
		}
		if (strlen($query) > 1e6) {
			$query = preg_replace('~[\x80-\xFF]+$~', '', substr($query, 0, 1e6)) . "\n…"; // [\x80-\xFF] - valid UTF-8, \n - can end by one-line comment
		}
		$history[$_GET["db"]][] = array($query, time(), $time); // not DB - $_GET["db"] is changed in database.inc.php //! respect $_GET["ns"]
		$sql_id = "sql-" . count($history[$_GET["db"]]);
		$return = "<a href='#$sql_id' class='toggle'>" . lang('SQL command') . "</a>\n";
		if (!$failed && ($warnings = $driver->warnings())) {
			$id = "warnings-" . count($history[$_GET["db"]]);
			$return = "<a href='#$id' class='toggle'>" . lang('Warnings') . "</a>, $return<div id='$id' class='hidden'>\n$warnings</div>\n";
		}
		return " <span class='time'>" . @date("H:i:s") . "</span>" // @ - time zone may be not set
			. " $return<div id='$sql_id' class='hidden'><pre><code class='jush-$jush'>" . shorten_utf8($query, 1000) . "</code></pre>"
			. ($time ? " <span class='time'>($time)</span>" : '')
			. (support("sql") ? '<p><a href="' . h(str_replace("db=" . urlencode(DB), "db=" . urlencode($_GET["db"]), ME) . 'sql=&history=' . (count($history[$_GET["db"]]) - 1)) . '">' . lang('Edit') . '</a>' : '')
			. '</div>'
		;
	}
	function editFunctions($field) {
		global $edit_functions;
		$return = ($field["null"] ? "NULL/" : "");
		foreach ($edit_functions as $key => $functions) {
			if (!$key || (!isset($_GET["call"]) && (isset($_GET["select"]) || where($_GET)))) { // relative functions
				foreach ($functions as $pattern => $val) {
					if (!$pattern || preg_match("~$pattern~", $field["type"])) {
						$return .= "/$val";
					}
				}
				if ($key && !preg_match('~set|blob|bytea|raw|file~', $field["type"])) {
					$return .= "/SQL";
				}
			}
		}
		if ($field["auto_increment"] && !isset($_GET["select"]) && !where($_GET)) {
			$return = lang('Auto Increment');
		}
		return explode("/", $return);
	}
	function editInput($table, $field, $attrs, $value) {
		if ($field["type"] == "enum") {
			return (isset($_GET["select"]) ? "<label><input type='radio'$attrs value='-1' checked><i>" . lang('original') . "</i></label> " : "")
				. ($field["null"] ? "<label><input type='radio'$attrs value=''" . ($value !== null || isset($_GET["select"]) ? "" : " checked") . "><i>NULL</i></label> " : "")
				. enum_input("radio", $attrs, $field, $value, 0) // 0 - empty
			;
		}
		return "";
	}
	function editHint($table, $field, $value) {
		return "";
	}
	function processInput($field, $value, $function = "") {
		if ($function == "SQL") {
			return $value; // SQL injection
		}
		$name = $field["field"];
		$return = q($value);
		if (preg_match('~^(now|getdate|uuid)$~', $function)) {
			$return = "$function()";
		} elseif (preg_match('~^current_(date|timestamp)$~', $function)) {
			$return = $function;
		} elseif (preg_match('~^([+-]|\|\|)$~', $function)) {
			$return = idf_escape($name) . " $function $return";
		} elseif (preg_match('~^[+-] interval$~', $function)) {
			$return = idf_escape($name) . " $function " . (preg_match("~^(\\d+|'[0-9.: -]') [A-Z_]+\$~i", $value) ? $value : $return);
		} elseif (preg_match('~^(addtime|subtime|concat)$~', $function)) {
			$return = "$function(" . idf_escape($name) . ", $return)";
		} elseif (preg_match('~^(md5|sha1|password|encrypt)$~', $function)) {
			$return = "$function($return)";
		}
		return unconvert_field($field, $return);
	}
	function dumpOutput() {
		$return = array('text' => lang('open'), 'file' => lang('save'));
		if (function_exists('gzencode')) {
			$return['gz'] = 'gzip';
		}
		return $return;
	}
	function dumpFormat() {
		return array('sql' => 'SQL', 'csv' => 'CSV,', 'csv;' => 'CSV;', 'tsv' => 'TSV');
	}
	function dumpDatabase($db) {
	}
	function dumpTable($table, $style, $is_view = 0) {
		if ($_POST["format"] != "sql") {
			echo "\xef\xbb\xbf"; // UTF-8 byte order mark
			if ($style) {
				dump_csv(array_keys(fields($table)));
			}
		} else {
			if ($is_view == 2) {
				$fields = array();
				foreach (fields($table) as $name => $field) {
					$fields[] = idf_escape($name) . " $field[full_type]";
				}
				$create = "CREATE TABLE " . table($table) . " (" . implode(", ", $fields) . ")";
			} else {
				$create = create_sql($table, $_POST["auto_increment"], $style);
			}
			set_utf8mb4($create);
			if ($style && $create) {
				if ($style == "DROP+CREATE" || $is_view == 1) {
					echo "DROP " . ($is_view == 2 ? "VIEW" : "TABLE") . " IF EXISTS " . table($table) . ";\n";
				}
				if ($is_view == 1) {
					$create = remove_definer($create);
				}
				echo "$create;\n\n";
			}
		}
	}
	function dumpData($table, $style, $query) {
		global $connection, $jush;
		$max_packet = ($jush == "sqlite" ? 0 : 1048576); // default, minimum is 1024
		if ($style) {
			if ($_POST["format"] == "sql") {
				if ($style == "TRUNCATE+INSERT") {
					echo truncate_sql($table) . ";\n";
				}
				$fields = fields($table);
			}
			$result = $connection->query($query, 1); // 1 - MYSQLI_USE_RESULT //! enum and set as numbers
			if ($result) {
				$insert = "";
				$buffer = "";
				$keys = array();
				$suffix = "";
				$fetch_function = ($table != '' ? 'fetch_assoc' : 'fetch_row');
				while ($row = $result->$fetch_function()) {
					if (!$keys) {
						$values = array();
						foreach ($row as $val) {
							$field = $result->fetch_field();
							$keys[] = $field->name;
							$key = idf_escape($field->name);
							$values[] = "$key = VALUES($key)";
						}
						$suffix = ($style == "INSERT+UPDATE" ? "\nON DUPLICATE KEY UPDATE " . implode(", ", $values) : "") . ";\n";
					}
					if ($_POST["format"] != "sql") {
						if ($style == "table") {
							dump_csv($keys);
							$style = "INSERT";
						}
						dump_csv($row);
					} else {
						if (!$insert) {
							$insert = "INSERT INTO " . table($table) . " (" . implode(", ", array_map('idf_escape', $keys)) . ") VALUES";
						}
						foreach ($row as $key => $val) {
							$field = $fields[$key];
							$row[$key] = ($val !== null
								? unconvert_field($field, preg_match(number_type(), $field["type"]) && !preg_match('~\[~', $field["full_type"]) && is_numeric($val) ? $val : q(($val === false ? 0 : $val)))
								: "NULL"
							);
						}
						$s = ($max_packet ? "\n" : " ") . "(" . implode(",\t", $row) . ")";
						if (!$buffer) {
							$buffer = $insert . $s;
						} elseif (strlen($buffer) + 4 + strlen($s) + strlen($suffix) < $max_packet) { // 4 - length specification
							$buffer .= ",$s";
						} else {
							echo $buffer . $suffix;
							$buffer = $insert . $s;
						}
					}
				}
				if ($buffer) {
					echo $buffer . $suffix;
				}
			} elseif ($_POST["format"] == "sql") {
				echo "-- " . str_replace("\n", " ", $connection->error) . "\n";
			}
		}
	}
	function dumpFilename($identifier) {
		return friendly_url($identifier != "" ? $identifier : (SERVER != "" ? SERVER : "localhost"));
	}
	function dumpHeaders($identifier, $multi_table = false) {
		$output = $_POST["output"];
		$ext = (preg_match('~sql~', $_POST["format"]) ? "sql" : ($multi_table ? "tar" : "csv")); // multiple CSV packed to TAR
		header("Content-Type: " .
			($output == "gz" ? "application/x-gzip" :
			($ext == "tar" ? "application/x-tar" :
			($ext == "sql" || $output != "file" ? "text/plain" : "text/csv") . "; charset=utf-8"
		)));
		if ($output == "gz") {
			ob_start('ob_gzencode', 1e6);
		}
		return $ext;
	}
	function importServerPath() {
		return "adminer.sql";
	}
	function homepage() {
		echo '<p class="links">' . ($_GET["ns"] == "" && support("database") ? '<a href="' . h(ME) . 'database=">' . lang('Alter database') . "</a>\n" : "");
		echo (support("scheme") ? "<a href='" . h(ME) . "scheme='>" . ($_GET["ns"] != "" ? lang('Alter schema') : lang('Create schema')) . "</a>\n" : "");
		echo ($_GET["ns"] !== "" ? '<a href="' . h(ME) . 'schema=">' . lang('Database schema') . "</a>\n" : "");
		echo (support("privileges") ? "<a href='" . h(ME) . "privileges='>" . lang('Privileges') . "</a>\n" : "");
		return true;
	}
	function navigation($missing) {
		global $VERSION, $jush, $drivers, $connection;
		?>
<?php
		if ($missing == "auth") {
			$output = "";
			foreach ((array) $_SESSION["pwds"] as $vendor => $servers) {
				foreach ($servers as $server => $usernames) {
					foreach ($usernames as $username => $password) {
						if ($password !== null) {
							$dbs = $_SESSION["db"][$vendor][$server][$username];
							foreach (($dbs ? array_keys($dbs) : array("")) as $db) {
								$output .= "<li><a href='" . h(auth_url($vendor, $server, $username, $db)) . "'>($drivers[$vendor]) " . h($username . ($server != "" ? "@" . $this->serverName($server) : "") . ($db != "" ? " - $db" : "")) . "</a>\n";
							}
						}
					}
				}
			}
			if ($output) {
				echo "<ul id='logins'>\n$output</ul>\n" . script("mixin(qs('#logins'), {onmouseover: menuOver, onmouseout: menuOut});");
			}
		} else {
			if ($_GET["ns"] !== "" && !$missing && DB != "") {
				$connection->select_db(DB);
				$tables = table_status('', true);
			}
			if (support("sql")) {
				
				?>
<script<?php echo nonce(); ?>>
<?php
				if ($tables) {
					$links = array();
					foreach ($tables as $table => $type) {
						$links[] = preg_quote($table, '/');
					}
					echo "var jushLinks = { $jush: [ '" . js_escape(ME) . (support("table") ? "table=" : "select=") . "\$&', /\\b(" . implode("|", $links) . ")\\b/g ] };\n";
					foreach (array("bac", "bra", "sqlite_quo", "mssql_bra") as $val) {
						echo "jushLinks.$val = jushLinks.$jush;\n";
					}
				}
				$server_info = $connection->server_info;
				?>
bodyLoad('<?php echo (is_object($connection) ? preg_replace('~^(\d\.?\d).*~s', '\1', $server_info) : ""); ?>'<?php echo (preg_match('~MariaDB~', $server_info) ? ", true" : ""); ?>);
</script>
<?php
			}
			$this->databasesPrint($missing);
			if (DB == "" || !$missing) {
				echo "<p class='links'>" . (support("sql") ? "<a href='" . h(ME) . "sql='" . bold(isset($_GET["sql"]) && !isset($_GET["import"])) . ">" . lang('SQL command') . "</a>\n<a href='" . h(ME) . "import='" . bold(isset($_GET["import"])) . ">" . lang('Import') . "</a>\n" : "") . "";
				if (support("dump")) {
					echo "<a href='" . h(ME) . "dump=" . urlencode(isset($_GET["table"]) ? $_GET["table"] : $_GET["select"]) . "' id='dump'" . bold(isset($_GET["dump"])) . ">" . lang('Export') . "</a>\n";
				}
			}
			if ($_GET["ns"] !== "" && !$missing && DB != "") {
				echo '<a href="' . h(ME) . 'create="' . bold($_GET["create"] === "") . ">" . lang('Create table') . "</a>\n";
				if (!$tables) {
					echo "<p class='message'>" . lang('No tables.') . "\n";
				} else {
					$this->tablesPrint($tables);
				}
			}
		}
	}
	function databasesPrint($missing) {
		global $adminer, $connection;
		$databases = $this->databases();
		if ($databases && !in_array(DB, $databases)) {
			array_unshift($databases, DB);
		}
		?>
<form action="">
<p id="dbs">
<?php
		hidden_fields_get();
		$db_events = script("mixin(qsl('select'), {onmousedown: dbMouseDown, onchange: dbChange});");
		echo "<span title='" . lang('database') . "'>" . lang('DB') . "</span>: " . ($databases
			? "<select name='db'>" . optionlist(array("" => "") + $databases, DB) . "</select>$db_events"
			: "<input name='db' value='" . h(DB) . "' autocapitalize='off'>\n"
		);
		echo "<input type='submit' value='" . lang('Use') . "'" . ($databases ? " class='hidden'" : "") . ">\n";
		if ($missing != "db" && DB != "" && $connection->select_db(DB)) {
			if (support("scheme")) {
				echo "<br>" . lang('Schema') . ": <select name='ns'>" . optionlist(array("" => "") + $adminer->schemas(), $_GET["ns"]) . "</select>$db_events";
				if ($_GET["ns"] != "") {
					set_schema($_GET["ns"]);
				}
			}
		}
		foreach (array("import", "sql", "schema", "dump", "privileges") as $val) {
			if (isset($_GET[$val])) {
				echo "<input type='hidden' name='$val' value=''>";
				break;
			}
		}
		echo "</p></form>\n";
	}
	function tablesPrint($tables) {
		echo "<ul id='tables'>" . script("mixin(qs('#tables'), {onmouseover: menuOver, onmouseout: menuOut});");
		foreach ($tables as $table => $status) {
			$name = $this->tableName($status);
			if ($name != "") {
				echo '<li><a href="' . h(ME) . 'select=' . urlencode($table) . '"' . bold($_GET["select"] == $table || $_GET["edit"] == $table, "select") . ">" . lang('select') . "</a> ";
				echo (support("table") || support("indexes")
					? '<a href="' . h(ME) . 'table=' . urlencode($table) . '"'
						. bold(in_array($table, array($_GET["table"], $_GET["create"], $_GET["indexes"], $_GET["foreign"], $_GET["trigger"])), (is_view($status) ? "view" : "structure"))
						. " title='" . lang('Show structure') . "'>$name</a>"
					: "<span>$name</span>"
				) . "\n";
			}
		}
		echo "</ul>\n";
	}

}

$adminer = (function_exists('adminer_object') ? adminer_object() : new Adminer);
if ($adminer->operators === null) {
	$adminer->operators = $operators;
}
function page_header($title, $error = "", $breadcrumb = array(), $title2 = "") {
	global $LANG, $VERSION, $adminer, $drivers, $jush;
	page_headers();
	if (is_ajax() && $error) {
		page_messages($error);
		exit;
	}
	$title_all = $title . ($title2 != "" ? ": $title2" : "");
	$title_page = strip_tags($title_all . (SERVER != "" && SERVER != "localhost" ? h(" - " . SERVER) : "") . " - " . $adminer->name());
	?>
<!DOCTYPE html>
<html lang="<?php echo $LANG; ?>" dir="<?php echo lang('ltr'); ?>">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="robots" content="noindex">
<title><?php echo $title_page; ?></title>
<style>
	body { color: #fff; background: #060a10; font: 90%/1.25 Verdana, Arial, Helvetica, sans-serif; margin: 0; width: -moz-fit-content; width: fit-content; }
	a { color: #fff; text-decoration: none; }
	a:visited { color: #b6b6bd; }
	a:link:hover, a:visited:hover { color: #fff; text-decoration: underline; }
	a.text:hover { text-decoration: none; }
	a.jush-help:hover { color: inherit; }
	h1 { font-size: 150%; margin: 0; padding: .8em 1em; font-weight: normal; color: #fff; background: #12151d; }
	h2 { font-size: 150%; margin: 0 0 20px -18px; padding: .8em 1em; color: #fff; font-weight: normal; background: #12151d; }
	h3 { font-weight: normal; font-size: 130%; margin: 1em 0 0; }
	form { margin: 0; }
	td table { width: 100%; margin: 0; }
	table { margin: 1em 20px 0 0; border-collapse: collapse; font-size: 90%; }
	td, th { border: none; padding: .2em .3em; }
	th { background: #060a10; text-align: left; }
	thead th { text-align: center; padding: .2em .5em; }
	thead td, thead th { background: #060a10; } /* position: sticky; causes Firefox to lose borders */
	fieldset { display: inline; vertical-align: top; padding: .5em .8em; margin: .8em .5em 0 0; border: 1px solid #2e6e9c; }
	p { margin: .8em 20px 0 0; }
	img { vertical-align: middle; border: 0; }
	td img { max-width: 200px; max-height: 200px; }
	code { background: #12151d; }
	.checkable tbody tr:hover td, .checkable tbody tr:hover th { background: #202832; }
	pre { margin: 1em 0 0;}
	pre, textarea { font: 100%/1.25 monospace; }
	input { vertical-align: middle; }
	input, textarea, select {color: #fff; border:none; background-color: #12151d; outline: none;}
	input:hover, textarea:hover, select:hover {background-color: #202832;}
	input:focus, textarea:focus, select:focus {background-color: #202832;}
	input[type='submit'] { background-color: #2E6E9C;}
	input[type='submit']:hover { background-color: #56AD15;}
	input.wayoff { left: -1000px; position: absolute; }
	input:-webkit-autofill {-webkit-box-shadow: inset 0 0 0 50px #12151d !important;-webkit-text-fill-color: #fff !important;color: #fff !important;}
	.block { display: block; }
	.version { color: #777; font-size: 67%; }
	.js .hidden, .nojs .jsonly { display: none; }
	.js .column { position: absolute; background: #202832; padding: .27em 1ex .3em 0; margin-top: -.27em; }
	.nowrap td, .nowrap th, td.nowrap, p.nowrap { white-space: pre; }
	.wrap td { white-space: normal; }
	.error { color: #fff; background: #ffb424; }
	.error b { background: #ffb424; font-weight: normal; }
	.message { color: #ffb424; background: #12151d; }
	.message table { color: #fff; background: #12151d; }
	.error, .message { padding: .5em .8em; margin: 1em 20px 0 0; }
	.char { color: tan; }
	.date { color: #ffb424; }
	.enum { color: #007F7F; }
	.binary { color: red; }
	.odd td { background: #12151d; }
	.js .checkable .checked td, .js .checkable .checked th { background: #202832; }
	.time { color: silver; font-size: 70%; }
	.function { text-align: right; }
	.number { text-align: right; }
	.datetime { text-align: right; }
	.type { width: 15ex; width: auto\9; }
	.options select { width: 20ex; width: auto\9; }
	.view { font-style: italic; }
	.active { font-weight: bold; }
	.sqlarea { width: 98%; }
	.icon { width: 18px; height: 18px; }
	.icon:hover {}
	.size { width: 6ex; }
	.help { cursor: help; }
	.footer { position: sticky; bottom: 0; margin-right: -20px; border-top: 20px solid rgba(4, 4, 4, 0.7); border-image: linear-gradient(rgba(6, 6, 6, 0.2), #0c0f16) 100% 0; }
	.footer > div { background: #060a10; padding: 0 0 .5em; }
	.footer fieldset { margin-top: 0; }
	.links a { white-space: nowrap; margin-right: 20px; }
	.logout { margin-top: .5em; position: absolute; top: 0; right: 0; }
	.loadmore { margin-left: 1ex; }
	/* .edit used in designs */
	#menu { position: absolute; margin: 10px 0 0; padding: 0 0 30px 0; top: 2em; left: 0; width: 19em; }
	#menu p, #logins, #tables { padding: .8em 1em; margin: 0; }
	#logins li, #tables li { list-style: none; }
	#dbs { overflow: hidden; }
	#logins, #tables { white-space: nowrap; overflow: auto; }
	#logins a, #tables a, #tables span { background: #060A10; }
	#content { margin: 2em 0 0 21em; padding: 10px 20px 20px 0; }
	#lang { position: absolute; top: 0; left: 0; line-height: 1.8em; padding: .3em 1em; }
	#breadcrumb { white-space: nowrap; position: absolute; top: 0; left: 21em; background: #060a10; height: 2em; line-height: 1.8em; padding: 0 1em; margin: 0 0 0 -18px; }
	#h1 { color: #777; text-decoration: none; font-style: italic; }
	#version { font-size: 67%; color: red; }
	#schema { margin-left: 60px; position: relative; -moz-user-select: none; -webkit-user-select: none; }
	#schema .table { padding: 0 2px; cursor: move; position: absolute; }
	#schema .references { position: absolute; }
	#help { position: absolute; padding: 5px; font-family: monospace; z-index: 1; }

	.rtl h2 { margin: 0 -18px 20px 0; }
	.rtl p, .rtl table, .rtl .error, .rtl .message { margin: 1em 0 0 20px; }
	.rtl .logout { left: 0; right: auto; }
	.rtl #content { margin: 2em 21em 0 0; padding: 10px 0 20px 20px; }
	.rtl #breadcrumb { left: auto; right: 21em; margin: 0 -18px 0 0; }
	.rtl .pages { left: auto; right: 21em; }
	.rtl input.wayoff { left: auto; right: -1000px; }
	.rtl #lang, .rtl #menu { left: auto; right: 0; }

	@media all and (max-device-width: 880px) {
		.pages { left: auto; }
		#menu { position: static; width: auto; }
		#content { margin-left: 10px; }
		#lang { position: static; border-top: 1px solid #999; }
		#breadcrumb { left: auto; }
		.rtl .pages { right: auto; }
		.rtl #content { margin-right: 10px; }
		.rtl #breadcrumb { right: auto; }
	}

	@media print {
		#lang, #menu { display: none; }
		#content { margin-left: 1em; }
		#breadcrumb { left: 1em; }
		.nowrap td, .nowrap th, td.nowrap { white-space: normal; }
	}
</style>
<?php 
echo script_src("functions.js");
echo script_src("editing.js");
if ($adminer->head()) {
foreach ($adminer->css() as $css) { ?>
<link rel="stylesheet" type="text/css" href="<?php echo h($css); ?>">
<?php }
} ?>

<body class="<?php echo lang('ltr'); ?> nojs">
<?php
	$filename = get_temp_dir() . "/adminer.version";
	if (!$_COOKIE["adminer_version"] && function_exists('openssl_verify') && file_exists($filename) && filemtime($filename) + 86400 > time()) { // 86400 - 1 day in seconds
		$version = unserialize(file_get_contents($filename));
		$public = "-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAwqWOVuF5uw7/+Z70djoK
RlHIZFZPO0uYRezq90+7Amk+FDNd7KkL5eDve+vHRJBLAszF/7XKXe11xwliIsFs
DFWQlsABVZB3oisKCBEuI71J4kPH8dKGEWR9jDHFw3cWmoH3PmqImX6FISWbG3B8
h7FIx3jEaw5ckVPVTeo5JRm/1DZzJxjyDenXvBQ/6o9DgZKeNDgxwKzH+sw9/YCO
jHnq1cFpOIISzARlrHMa/43YfeNRAm/tsBXjSxembBPo7aQZLAWHmaj5+K19H10B
nCpz9Y++cipkVEiKRGih4ZEvjoFysEOdRLj6WiD/uUNky4xGeA6LaJqh5XpkFkcQ
fQIDAQAB
-----END PUBLIC KEY-----
";
		if (openssl_verify($version["version"], base64_decode($version["signature"]), $public) == 1) {
			$_COOKIE["adminer_version"] = $version["version"]; // doesn't need to send to the browser
		}
	}
	?>
<script<?php echo nonce(); ?>>
mixin(document.body, {onkeydown: bodyKeydown, onclick: bodyClick<?php
	echo (isset($_COOKIE["adminer_version"]) ? "" : ", onload: partial(verifyVersion, '$VERSION', '" . js_escape(ME) . "', '" . get_token() . "')"); // $token may be empty in auth.inc.php
	?>});
document.body.className = document.body.className.replace(/ nojs/, ' js');
var offlineMessage = '<?php echo js_escape(lang('You are offline.')); ?>';
var thousandsSeparator = '<?php echo js_escape(lang(',')); ?>';
</script>

<div id="help" class="jush-<?php echo $jush; ?> jsonly hidden"></div>
<?php echo script("mixin(qs('#help'), {onmouseover: function () { helpOpen = 1; }, onmouseout: helpMouseout});"); ?>

<div id="content">
<?php
	if ($breadcrumb !== null) {
		$link = substr(preg_replace('~\b(username|db|ns)=[^&]*&~', '', ME), 0, -1);
		echo '<p id="breadcrumb"><a href="' . h($link ? $link : ".") . '">' . $drivers[DRIVER] . '</a> &raquo; ';
		$link = substr(preg_replace('~\b(db|ns)=[^&]*&~', '', ME), 0, -1);
		$server = $adminer->serverName(SERVER);
		$server = ($server != "" ? $server : lang('Server'));
		if ($breadcrumb === false) {
			echo "$server\n";
		} else {
			echo "<a href='" . ($link ? h($link) : ".") . "' accesskey='1' title='Alt+Shift+1'>$server</a> &raquo; ";
			if ($_GET["ns"] != "" || (DB != "" && is_array($breadcrumb))) {
				echo '<a href="' . h($link . "&db=" . urlencode(DB) . (support("scheme") ? "&ns=" : "")) . '">' . h(DB) . '</a> &raquo; ';
			}
			if (is_array($breadcrumb)) {
				if ($_GET["ns"] != "") {
					echo '<a href="' . h(substr(ME, 0, -1)) . '">' . h($_GET["ns"]) . '</a> &raquo; ';
				}
				foreach ($breadcrumb as $key => $val) {
					$desc = (is_array($val) ? $val[1] : h($val));
					if ($desc != "") {
						echo "<a href='" . h(ME . "$key=") . urlencode(is_array($val) ? $val[0] : $val) . "'>$desc</a> &raquo; ";
					}
				}
			}
			echo "$title\n";
		}
	}
	echo "<h2>$title_all</h2>\n";
	echo "<div id='ajaxstatus' class='jsonly hidden'></div>\n";
	restart_session();
	page_messages($error);
	$databases = &get_session("dbs");
	if (DB != "" && $databases && !in_array(DB, $databases, true)) {
		$databases = null;
	}
	stop_session();
	define("PAGE_HEADER", 1);
}
function page_headers() {
	global $adminer;
	header("Content-Type: text/html; charset=utf-8");
	header("Cache-Control: no-cache");
	header("X-XSS-Protection: 0"); // prevents introducing XSS in IE8 by removing safe parts of the page
	header("X-Content-Type-Options: nosniff");
	header("Referrer-Policy: origin-when-cross-origin");
	foreach ($adminer->csp() as $csp) {
		$header = array();
		foreach ($csp as $key => $val) {
			$header[] = "$key $val";
		}
		header("Content-Security-Policy: " . implode("; ", $header));
	}
	$adminer->headers();
}
function csp() {
	return array(
		array(
			"script-src" => "'self' 'unsafe-inline' 'nonce-" . get_nonce() . "' 'strict-dynamic'", // 'self' is a fallback for browsers not supporting 'strict-dynamic', 'unsafe-inline' is a fallback for browsers not supporting 'nonce-'
			"connect-src" => "'self'",
			"frame-src" => "https://www.adminer.org",
			"object-src" => "'none'",
			"base-uri" => "'none'",
			"form-action" => "'self'",
		),
	);
}
function get_nonce() {
	static $nonce;
	if (!$nonce) {
		$nonce = base64_encode(rand_string());
	}
	return $nonce;
}
function page_messages($error) {
	$uri = preg_replace('~^[^?]*~', '', $_SERVER["REQUEST_URI"]);
	$messages = $_SESSION["messages"][$uri];
	if ($messages) {
		echo "<div class='message'>" . implode("</div>\n<div class='message'>", $messages) . "</div>" . script("messagesPrint();");
		unset($_SESSION["messages"][$uri]);
	}
	if ($error) {
		echo "<div class='error'>$error</div>\n";
	}
}
function page_footer($missing = "") {
	global $adminer, $token;
	?>
</div>

<?php switch_lang(); ?>
<?php if ($missing != "auth") { ?>
<form action="" method="post">
<p class="logout">
<input type="submit" name="logout" value="<?php echo lang('Logout'); ?>" id="logout">
<input type="hidden" name="token" value="<?php echo $token; ?>">
</p>
</form>
<?php } ?>
<div id="menu">
<?php $adminer->navigation($missing); ?>
</div>
<?php
	echo script("setupSubmitHighlight(document);");
}

function int32($n) {
	while ($n >= 2147483648) {
		$n -= 4294967296;
	}
	while ($n <= -2147483649) {
		$n += 4294967296;
	}
	return (int) $n;
}

function long2str($v, $w) {
	$s = '';
	foreach ($v as $val) {
		$s .= pack('V', $val);
	}
	if ($w) {
		return substr($s, 0, end($v));
	}
	return $s;
}

function str2long($s, $w) {
	$v = array_values(unpack('V*', str_pad($s, 4 * ceil(strlen($s) / 4), "\0")));
	if ($w) {
		$v[] = strlen($s);
	}
	return $v;
}

function xxtea_mx($z, $y, $sum, $k) {
	return int32((($z >> 5 & 0x7FFFFFF) ^ $y << 2) + (($y >> 3 & 0x1FFFFFFF) ^ $z << 4)) ^ int32(($sum ^ $y) + ($k ^ $z));
}
function encrypt_string($str, $key) {
	if ($str == "") {
		return "";
	}
	$key = array_values(unpack("V*", pack("H*", md5($key))));
	$v = str2long($str, true);
	$n = count($v) - 1;
	$z = $v[$n];
	$y = $v[0];
	$q = floor(6 + 52 / ($n + 1));
	$sum = 0;
	while ($q-- > 0) {
		$sum = int32($sum + 0x9E3779B9);
		$e = $sum >> 2 & 3;
		for ($p=0; $p < $n; $p++) {
			$y = $v[$p + 1];
			$mx = xxtea_mx($z, $y, $sum, $key[$p & 3 ^ $e]);
			$z = int32($v[$p] + $mx);
			$v[$p] = $z;
		}
		$y = $v[0];
		$mx = xxtea_mx($z, $y, $sum, $key[$p & 3 ^ $e]);
		$z = int32($v[$n] + $mx);
		$v[$n] = $z;
	}
	return long2str($v, false);
}
function decrypt_string($str, $key) {
	if ($str == "") {
		return "";
	}
	if (!$key) {
		return false;
	}
	$key = array_values(unpack("V*", pack("H*", md5($key))));
	$v = str2long($str, false);
	$n = count($v) - 1;
	$z = $v[$n];
	$y = $v[0];
	$q = floor(6 + 52 / ($n + 1));
	$sum = int32($q * 0x9E3779B9);
	while ($sum) {
		$e = $sum >> 2 & 3;
		for ($p=$n; $p > 0; $p--) {
			$z = $v[$p - 1];
			$mx = xxtea_mx($z, $y, $sum, $key[$p & 3 ^ $e]);
			$y = int32($v[$p] - $mx);
			$v[$p] = $y;
		}
		$z = $v[$n];
		$mx = xxtea_mx($z, $y, $sum, $key[$p & 3 ^ $e]);
		$y = int32($v[0] - $mx);
		$v[0] = $y;
		$sum = int32($sum - 0x9E3779B9);
	}
	return long2str($v, true);
}
$connection = '';

$has_token = $_SESSION["token"];
if (!$has_token) {
	$_SESSION["token"] = rand(1, 1e6); // defense against cross-site request forgery
}
$token = get_token(); ///< @var string CSRF protection

$permanent = array();
if ($_COOKIE["adminer_permanent"]) {
	foreach (explode(" ", $_COOKIE["adminer_permanent"]) as $val) {
		list($key) = explode(":", $val);
		$permanent[$key] = $val;
	}
}

function add_invalid_login() {
	global $adminer;
	$fp = file_open_lock(get_temp_dir() . "/adminer.invalid");
	if (!$fp) {
		return;
	}
	$invalids = unserialize(stream_get_contents($fp));
	$time = time();
	if ($invalids) {
		foreach ($invalids as $ip => $val) {
			if ($val[0] < $time) {
				unset($invalids[$ip]);
			}
		}
	}
	$invalid = &$invalids[$adminer->bruteForceKey()];
	if (!$invalid) {
		$invalid = array($time + 30*60, 0); // active for 30 minutes
	}
	$invalid[1]++;
	file_write_unlock($fp, serialize($invalids));
}

function check_invalid_login() {
	global $adminer;
	$invalids = unserialize(@file_get_contents(get_temp_dir() . "/adminer.invalid")); // @ - may not exist
	$invalid = $invalids[$adminer->bruteForceKey()];
	$next_attempt = ($invalid[1] > 29 ? $invalid[0] - time() : 0); // allow 30 invalid attempts
	if ($next_attempt > 0) { //! do the same with permanent login
		auth_error(lang('Too many unsuccessful logins, try again in %d minute(s).', ceil($next_attempt / 60)));
	}
}

$auth = $_POST["auth"];
if ($auth) {
	session_regenerate_id(); // defense against session fixation
	$vendor = $auth["driver"];
	$server = $auth["server"];
	$username = $auth["username"];
	$password = (string) $auth["password"];
	$db = $auth["db"];
	set_password($vendor, $server, $username, $password);
	$_SESSION["db"][$vendor][$server][$username][$db] = true;
	if ($auth["permanent"]) {
		$key = base64_encode($vendor) . "-" . base64_encode($server) . "-" . base64_encode($username) . "-" . base64_encode($db);
		$private = $adminer->permanentLogin(true);
		$permanent[$key] = "$key:" . base64_encode($private ? encrypt_string($password, $private) : "");
		cookie("adminer_permanent", implode(" ", $permanent));
	}
	if (count($_POST) == 1 // 1 - auth
		|| DRIVER != $vendor
		|| SERVER != $server
		|| $_GET["username"] !== $username // "0" == "00"
		|| DB != $db
	) {
		redirect(auth_url($vendor, $server, $username, $db));
	}
	
} elseif ($_POST["logout"]) {
	if ($has_token && !verify_token()) {
		page_header(lang('Logout'), lang('Invalid CSRF token. Send the form again.'));
		page_footer("db");
		exit;
	} else {
		foreach (array("pwds", "db", "dbs", "queries") as $key) {
			set_session($key, null);
		}
		unset_permanent();
		redirect(substr(preg_replace('~\b(username|db|ns)=[^&]*&~', '', ME), 0, -1), lang('Logout successful.') . ' ' . lang('bye!'));
	}
	
} elseif ($permanent && !$_SESSION["pwds"]) {
	session_regenerate_id();
	$private = $adminer->permanentLogin();
	foreach ($permanent as $key => $val) {
		list(, $cipher) = explode(":", $val);
		list($vendor, $server, $username, $db) = array_map('base64_decode', explode("-", $key));
		set_password($vendor, $server, $username, decrypt_string(base64_decode($cipher), $private));
		$_SESSION["db"][$vendor][$server][$username][$db] = true;
	}
}

function unset_permanent() {
	global $permanent;
	foreach ($permanent as $key => $val) {
		list($vendor, $server, $username, $db) = array_map('base64_decode', explode("-", $key));
		if ($vendor == DRIVER && $server == SERVER && $username == $_GET["username"] && $db == DB) {
			unset($permanent[$key]);
		}
	}
	cookie("adminer_permanent", implode(" ", $permanent));
}
function auth_error($error) {
	global $adminer, $has_token;
	$session_name = session_name();
	if (isset($_GET["username"])) {
		header("HTTP/1.1 403 Forbidden"); // 401 requires sending WWW-Authenticate header
		if (($_COOKIE[$session_name] || $_GET[$session_name]) && !$has_token) {
			$error = lang('Session expired, please login again.');
		} else {
			restart_session();
			add_invalid_login();
			$password = get_password();
			if ($password !== null) {
				if ($password === false) {
					$error .= '<br>' . lang('Master password expired. <a href="https://www.adminer.org/en/extension/"%s>Implement</a> %s method to make it permanent.', target_blank(), '<code>permanentLogin()</code>');
				}
				set_password(DRIVER, SERVER, $_GET["username"], null);
			}
			unset_permanent();
		}
	}
	if (!$_COOKIE[$session_name] && $_GET[$session_name] && ini_bool("session.use_only_cookies")) {
		$error = lang('Session support must be enabled.');
	}
	$params = session_get_cookie_params();
	cookie("adminer_key", ($_COOKIE["adminer_key"] ? $_COOKIE["adminer_key"] : rand_string()), $params["lifetime"]);
	page_header(lang('Login'), $error, null);
	echo "<form action='' method='post'>\n";
	echo "<div>";
	if (hidden_fields($_POST, array("auth"))) { // expired session
		echo "<p class='message'>" . lang('The action will be performed after successful login with the same credentials.') . "\n";
	}
	echo "</div>\n";
	$adminer->loginForm();
	echo "</form>\n";
	page_footer("auth");
	exit;
}

if (isset($_GET["username"]) && !class_exists("Min_DB")) {
	unset($_SESSION["pwds"][DRIVER]);
	unset_permanent();
	page_header(lang('No extension'), lang('None of the supported PHP extensions (%s) are available.', implode(", ", $possible_drivers)), false);
	page_footer("auth");
	exit;
}

stop_session(true);

if (isset($_GET["username"]) && is_string(get_password())) {
	list($host, $port) = explode(":", SERVER, 2);
	if (is_numeric($port) && $port < 1024) {
		auth_error(lang('Connecting to privileged ports is not allowed.'));
	}
	check_invalid_login();
	$connection = connect();
	$driver = new Min_Driver($connection);
}

$login = null;
if (!is_object($connection) || ($login = $adminer->login($_GET["username"], get_password())) !== true) {
	$error = (is_string($connection) ? h($connection) : (is_string($login) ? $login : lang('Invalid credentials.')));
	auth_error($error . (preg_match('~^ | $~', get_password()) ? '<br>' . lang('There is a space in the input password which might be the cause.') : ''));
}

if ($auth && $_POST["token"]) {
	$_POST["token"] = $token; // reset token after explicit login
}

$error = ''; ///< @var string
if ($_POST) {
	if (!verify_token()) {
		$ini = "max_input_vars";
		$max_vars = ini_get($ini);
		if (extension_loaded("suhosin")) {
			foreach (array("suhosin.request.max_vars", "suhosin.post.max_vars") as $key) {
				$val = ini_get($key);
				if ($val && (!$max_vars || $val < $max_vars)) {
					$ini = $key;
					$max_vars = $val;
				}
			}
		}
		$error = (!$_POST["token"] && $max_vars
			? lang('Maximum number of allowed fields exceeded. Please increase %s.', "'$ini'")
			: lang('Invalid CSRF token. Send the form again.') . ' ' . lang('If you did not send this request from Adminer then close this page.')
		);
	}
	
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
	// posted form with no data means that post_max_size exceeded because Adminer always sends token at least
	$error = lang('Too big POST data. Reduce the data or increase the %s configuration directive.', "'post_max_size'");
	if (isset($_GET["sql"])) {
		$error .= ' ' . lang('You can upload a big SQL file via FTP and import it from server.');
	}
}
function select($result, $connection2 = null, $orgtables = array(), $limit = 0) {
	global $jush;
	$links = array(); // colno => orgtable - create links from these columns
	$indexes = array(); // orgtable => array(column => colno) - primary keys
	$columns = array(); // orgtable => array(column => ) - not selected columns in primary key
	$blobs = array(); // colno => bool - display bytes for blobs
	$types = array(); // colno => type - display char in <code>
	$return = array(); // table => orgtable - mapping to use in EXPLAIN
	odd(''); // reset odd for each result
	for ($i=0; (!$limit || $i < $limit) && ($row = $result->fetch_row()); $i++) {
		if (!$i) {
			echo "<div class='scrollable'>\n";
			echo "<table cellspacing='0' class='nowrap'>\n";
			echo "<thead><tr>";
			for ($j=0; $j < count($row); $j++) {
				$field = $result->fetch_field();
				$name = $field->name;
				$orgtable = $field->orgtable;
				$orgname = $field->orgname;
				$return[$field->table] = $orgtable;
				if ($orgtables && $jush == "sql") { // MySQL EXPLAIN
					$links[$j] = ($name == "table" ? "table=" : ($name == "possible_keys" ? "indexes=" : null));
				} elseif ($orgtable != "") {
					if (!isset($indexes[$orgtable])) {
						// find primary key in each table
						$indexes[$orgtable] = array();
						foreach (indexes($orgtable, $connection2) as $index) {
							if ($index["type"] == "PRIMARY") {
								$indexes[$orgtable] = array_flip($index["columns"]);
								break;
							}
						}
						$columns[$orgtable] = $indexes[$orgtable];
					}
					if (isset($columns[$orgtable][$orgname])) {
						unset($columns[$orgtable][$orgname]);
						$indexes[$orgtable][$orgname] = $j;
						$links[$j] = $orgtable;
					}
				}
				if ($field->charsetnr == 63) { // 63 - binary
					$blobs[$j] = true;
				}
				$types[$j] = $field->type;
				echo "<th" . ($orgtable != "" || $field->name != $orgname ? " title='" . h(($orgtable != "" ? "$orgtable." : "") . $orgname) . "'" : "") . ">" . h($name)
					. ($orgtables ? doc_link(array(
						'sql' => "explain-output.html#explain_" . strtolower($name),
						'mariadb' => "explain/#the-columns-in-explain-select",
					)) : "")
				;
			}
			echo "</thead>\n";
		}
		echo "<tr" . odd() . ">";
		foreach ($row as $key => $val) {
			if ($val === null) {
				$val = "<i>NULL</i>";
			} elseif ($blobs[$key] && !is_utf8($val)) {
				$val = "<i>" . lang('%d byte(s)', strlen($val)) . "</i>"; //! link to download
			} else {
				$val = h($val);
				if ($types[$key] == 254) { // 254 - char
					$val = "<code>$val</code>";
				}
			}
			if (isset($links[$key]) && !$columns[$links[$key]]) {
				if ($orgtables && $jush == "sql") { // MySQL EXPLAIN
					$table = $row[array_search("table=", $links)];
					$link = $links[$key] . urlencode($orgtables[$table] != "" ? $orgtables[$table] : $table);
				} else {
					$link = "edit=" . urlencode($links[$key]);
					foreach ($indexes[$links[$key]] as $col => $j) {
						$link .= "&where" . urlencode("[" . bracket_escape($col) . "]") . "=" . urlencode($row[$j]);
					}
				}
				$val = "<a href='" . h(ME . $link) . "'>$val</a>";
			}
			echo "<td>$val";
		}
	}
	echo ($i ? "</table>\n</div>" : "<p class='message'>" . lang('No rows.')) . "\n";
	return $return;
}
function referencable_primary($self) {
	$return = array(); // table_name => field
	foreach (table_status('', true) as $table_name => $table) {
		if ($table_name != $self && fk_support($table)) {
			foreach (fields($table_name) as $field) {
				if ($field["primary"]) {
					if ($return[$table_name]) { // multi column primary key
						unset($return[$table_name]);
						break;
					}
					$return[$table_name] = $field;
				}
			}
		}
	}
	return $return;
}
function adminer_settings() {
	parse_str($_COOKIE["adminer_settings"], $settings);
	return $settings;
}
function adminer_setting($key) {
	$settings = adminer_settings();
	return $settings[$key];
}
function set_adminer_settings($settings) {
	return cookie("adminer_settings", http_build_query($settings + adminer_settings()));
}
function textarea($name, $value, $rows = 10, $cols = 80) {
	global $jush;
	echo "<textarea name='$name' rows='$rows' cols='$cols' class='sqlarea jush-$jush' spellcheck='false' wrap='off'>";
	if (is_array($value)) {
		foreach ($value as $val) { // not implode() to save memory
			echo h($val[0]) . "\n\n\n"; // $val == array($query, $time, $elapsed)
		}
	} else {
		echo h($value);
	}
	echo "</textarea>";
}
function edit_type($key, $field, $collations, $foreign_keys = array(), $extra_types = array()) {
	global $structured_types, $types, $unsigned, $on_actions;
	$type = $field["type"];
	?>
<td><select name="<?php echo h($key); ?>[type]" class="type" aria-labelledby="label-type"><?php
if ($type && !isset($types[$type]) && !isset($foreign_keys[$type]) && !in_array($type, $extra_types)) {
	$extra_types[] = $type;
}
if ($foreign_keys) {
	$structured_types[lang('Foreign keys')] = $foreign_keys;
}
echo optionlist(array_merge($extra_types, $structured_types), $type);
?></select><?php echo on_help("getTarget(event).value", 1); ?>
<?php echo script("mixin(qsl('select'), {onfocus: function () { lastType = selectValue(this); }, onchange: editingTypeChange});", ""); ?>
<td><input name="<?php echo h($key); ?>[length]" value="<?php echo h($field["length"]); ?>" size="3"<?php echo (!$field["length"] && preg_match('~var(char|binary)$~', $type) ? " class='required'" : ""); //! type="number" with enabled JavaScript ?> aria-labelledby="label-length"><?php echo script("mixin(qsl('input'), {onfocus: editingLengthFocus, oninput: editingLengthChange});", ""); ?><td class="options"><?php
	echo "<select name='" . h($key) . "[collation]'" . (preg_match('~(char|text|enum|set)$~', $type) ? "" : " class='hidden'") . '><option value="">(' . lang('collation') . ')' . optionlist($collations, $field["collation"]) . '</select>';
	echo ($unsigned ? "<select name='" . h($key) . "[unsigned]'" . (!$type || preg_match(number_type(), $type) ? "" : " class='hidden'") . '><option>' . optionlist($unsigned, $field["unsigned"]) . '</select>' : '');
	echo (isset($field['on_update']) ? "<select name='" . h($key) . "[on_update]'" . (preg_match('~timestamp|datetime~', $type) ? "" : " class='hidden'") . '>' . optionlist(array("" => "(" . lang('ON UPDATE') . ")", "CURRENT_TIMESTAMP"), (preg_match('~^CURRENT_TIMESTAMP~i', $field["on_update"]) ? "CURRENT_TIMESTAMP" : $field["on_update"])) . '</select>' : '');
	echo ($foreign_keys ? "<select name='" . h($key) . "[on_delete]'" . (preg_match("~`~", $type) ? "" : " class='hidden'") . "><option value=''>(" . lang('ON DELETE') . ")" . optionlist(explode("|", $on_actions), $field["on_delete"]) . "</select> " : " "); // space for IE
}
function process_length($length) {
	global $enum_length;
	return (preg_match("~^\\s*\\(?\\s*$enum_length(?:\\s*,\\s*$enum_length)*+\\s*\\)?\\s*\$~", $length) && preg_match_all("~$enum_length~", $length, $matches)
		? "(" . implode(",", $matches[0]) . ")"
		: preg_replace('~^[0-9].*~', '(\0)', preg_replace('~[^-0-9,+()[\]]~', '', $length))
	);
}
function process_type($field, $collate = "COLLATE") {
	global $unsigned;
	return " $field[type]"
		. process_length($field["length"])
		. (preg_match(number_type(), $field["type"]) && in_array($field["unsigned"], $unsigned) ? " $field[unsigned]" : "")
		. (preg_match('~char|text|enum|set~', $field["type"]) && $field["collation"] ? " $collate " . q($field["collation"]) : "")
	;
}
function process_field($field, $type_field) {
	return array(
		idf_escape(trim($field["field"])),
		process_type($type_field),
		($field["null"] ? " NULL" : " NOT NULL"), // NULL for timestamp
		default_value($field),
		(preg_match('~timestamp|datetime~', $field["type"]) && $field["on_update"] ? " ON UPDATE $field[on_update]" : ""),
		(support("comment") && $field["comment"] != "" ? " COMMENT " . q($field["comment"]) : ""),
		($field["auto_increment"] ? auto_increment() : null),
	);
}
function default_value($field) {
	$default = $field["default"];
	return ($default === null ? "" : " DEFAULT " . (preg_match('~char|binary|text|enum|set~', $field["type"]) || preg_match('~^(?![a-z])~i', $default) ? q($default) : $default));
}
function type_class($type) {
	foreach (array(
		'char' => 'text',
		'date' => 'time|year',
		'binary' => 'blob',
		'enum' => 'set',
	) as $key => $val) {
		if (preg_match("~$key|$val~", $type)) {
			return " class='$key'";
		}
	}
}

function edit_fields($fields, $collations, $type = "TABLE", $foreign_keys = array()) {
	global $inout;
	$fields = array_values($fields);
	?>
<thead><tr>
<?php if ($type == "PROCEDURE") { ?><td><?php } ?>
<th id="label-name"><?php echo ($type == "TABLE" ? lang('Column name') : lang('Parameter name')); ?>
<td id="label-type"><?php echo lang('Type'); ?><textarea id="enum-edit" rows="4" cols="12" wrap="off" style="display: none;"></textarea><?php echo script("qs('#enum-edit').onblur = editingLengthBlur;"); ?>
<td id="label-length"><?php echo lang('Length'); ?>
<td><?php echo lang('Options'); /* no label required, options have their own label */ ?>
<?php if ($type == "TABLE") { ?>
<td id="label-null">NULL
<td><input type="radio" name="auto_increment_col" value=""><acronym id="label-ai" title="<?php echo lang('Auto Increment'); ?>">AI</acronym><?php echo doc_link(array(
	'sql' => "example-auto-increment.html",
	'mariadb' => "auto_increment/",
	'sqlite' => "autoinc.html",
	'pgsql' => "datatype.html#DATATYPE-SERIAL",
	'mssql' => "ms186775.aspx",
)); ?>
<td id="label-default"><?php echo lang('Default value'); ?>
<?php echo (support("comment") ? "<td id='label-comment'>" . lang('Comment') : ""); ?>
<?php } ?>
<td><?php echo "<input type='submit' class='icon' name='add[" . (support("move_col") ? 0 : count($fields)) . "]' value='+' title='" . lang('Add next') . "'>" . script("row_count = " . count($fields) . ";"); ?>
</thead>
<tbody>
<?php
	echo script("mixin(qsl('tbody'), {onclick: editingClick, onkeydown: editingKeydown, oninput: editingInput});");
	foreach ($fields as $i => $field) {
		$i++;
		$orig = $field[($_POST ? "orig" : "field")];
		$display = (isset($_POST["add"][$i-1]) || (isset($field["field"]) && !$_POST["drop_col"][$i])) && (support("drop_col") || $orig == "");
		?>
<tr<?php echo ($display ? "" : " style='display: none;'"); ?>>
<?php echo ($type == "PROCEDURE" ? "<td>" . html_select("fields[$i][inout]", explode("|", $inout), $field["inout"]) : ""); ?>
<th><?php if ($display) { ?><input name="fields[<?php echo $i; ?>][field]" value="<?php echo h($field["field"]); ?>" data-maxlength="64" autocapitalize="off" aria-labelledby="label-name"><?php echo script("qsl('input').oninput = function () { editingNameChange.call(this);" . ($field["field"] != "" || count($fields) > 1 ? "" : " editingAddRow.call(this);") . " };", ""); ?><?php } ?>
<input type="hidden" name="fields[<?php echo $i; ?>][orig]" value="<?php echo h($orig); ?>"><?php edit_type("fields[$i]", $field, $collations, $foreign_keys); ?>
<?php if ($type == "TABLE") { ?>
<td><?php echo checkbox("fields[$i][null]", 1, $field["null"], "", "", "block", "label-null"); ?>
<td><label class="block"><input type="radio" name="auto_increment_col" value="<?php echo $i; ?>"<?php if ($field["auto_increment"]) { ?> checked<?php } ?> aria-labelledby="label-ai"></label><td><?php
			echo checkbox("fields[$i][has_default]", 1, $field["has_default"], "", "", "", "label-default"); ?><input name="fields[<?php echo $i; ?>][default]" value="<?php echo h($field["default"]); ?>" aria-labelledby="label-default"><?php
			echo (support("comment") ? "<td><input name='fields[$i][comment]' value='" . h($field["comment"]) . "' data-maxlength='" . (min_version(5.5) ? 1024 : 255) . "' aria-labelledby='label-comment'>" : "");
		}
		echo "<td>";
		echo (support("move_col") ?
			"<input type='submit' class='icon' name='add[$i]' value='+' title='" . lang('Add next') . "'> "
			. "<input type='submit' class='icon' name='up[$i]' value='↑' title='" . lang('Move up') . "'> "
			. "<input type='submit' class='icon' name='down[$i]' value='↓' title='" . lang('Move down') . "'> "
		: "");
		echo ($orig == "" || support("drop_col") ? "<input type='submit' class='icon' name='drop_col[$i]' value='x' title='" . lang('Remove') . "'>" : "");
	}
}
function process_fields(&$fields) {
	$offset = 0;
	if ($_POST["up"]) {
		$last = 0;
		foreach ($fields as $key => $field) {
			if (key($_POST["up"]) == $key) {
				unset($fields[$key]);
				array_splice($fields, $last, 0, array($field));
				break;
			}
			if (isset($field["field"])) {
				$last = $offset;
			}
			$offset++;
		}
	} elseif ($_POST["down"]) {
		$found = false;
		foreach ($fields as $key => $field) {
			if (isset($field["field"]) && $found) {
				unset($fields[key($_POST["down"])]);
				array_splice($fields, $offset, 0, array($found));
				break;
			}
			if (key($_POST["down"]) == $key) {
				$found = $field;
			}
			$offset++;
		}
	} elseif ($_POST["add"]) {
		$fields = array_values($fields);
		array_splice($fields, key($_POST["add"]), 0, array(array()));
	} elseif (!$_POST["drop_col"]) {
		return false;
	}
	return true;
}
function normalize_enum($match) {
	return "'" . str_replace("'", "''", addcslashes(stripcslashes(str_replace($match[0][0] . $match[0][0], $match[0][0], substr($match[0], 1, -1))), '\\')) . "'";
}
function grant($grant, $privileges, $columns, $on) {
	if (!$privileges) {
		return true;
	}
	if ($privileges == array("ALL PRIVILEGES", "GRANT OPTION")) {
		// can't be granted or revoked together
		return ($grant == "GRANT"
			? queries("$grant ALL PRIVILEGES$on WITH GRANT OPTION")
			: queries("$grant ALL PRIVILEGES$on") && queries("$grant GRANT OPTION$on")
		);
	}
	return queries("$grant " . preg_replace('~(GRANT OPTION)\([^)]*\)~', '\1', implode("$columns, ", $privileges) . $columns) . $on);
}
function drop_create($drop, $create, $drop_created, $test, $drop_test, $location, $message_drop, $message_alter, $message_create, $old_name, $new_name) {
	if ($_POST["drop"]) {
		query_redirect($drop, $location, $message_drop);
	} elseif ($old_name == "") {
		query_redirect($create, $location, $message_create);
	} elseif ($old_name != $new_name) {
		$created = queries($create);
		queries_redirect($location, $message_alter, $created && queries($drop));
		if ($created) {
			queries($drop_created);
		}
	} else {
		queries_redirect(
			$location,
			$message_alter,
			queries($test) && queries($drop_test) && queries($drop) && queries($create)
		);
	}
}
function create_trigger($on, $row) {
	global $jush;
	$timing_event = " $row[Timing] $row[Event]" . ($row["Event"] == "UPDATE OF" ? " " . idf_escape($row["Of"]) : "");
	return "CREATE TRIGGER "
		. idf_escape($row["Trigger"])
		. ($jush == "mssql" ? $on . $timing_event : $timing_event . $on)
		. rtrim(" $row[Type]\n$row[Statement]", ";")
		. ";"
	;
}
function create_routine($routine, $row) {
	global $inout, $jush;
	$set = array();
	$fields = (array) $row["fields"];
	ksort($fields); // enforce fields order
	foreach ($fields as $field) {
		if ($field["field"] != "") {
			$set[] = (preg_match("~^($inout)\$~", $field["inout"]) ? "$field[inout] " : "") . idf_escape($field["field"]) . process_type($field, "CHARACTER SET");
		}
	}
	$definition = rtrim("\n$row[definition]", ";");
	return "CREATE $routine "
		. idf_escape(trim($row["name"]))
		. " (" . implode(", ", $set) . ")"
		. (isset($_GET["function"]) ? " RETURNS" . process_type($row["returns"], "CHARACTER SET") : "")
		. ($row["language"] ? " LANGUAGE $row[language]" : "")
		. ($jush == "pgsql" ? " AS " . q($definition) : "$definition;")
	;
}
function remove_definer($query) {
	return preg_replace('~^([A-Z =]+) DEFINER=`' . preg_replace('~@(.*)~', '`@`(%|\1)', logged_user()) . '`~', '\1', $query); //! proper escaping of user
}
function format_foreign_key($foreign_key) {
	global $on_actions;
	$db = $foreign_key["db"];
	$ns = $foreign_key["ns"];
	return " FOREIGN KEY (" . implode(", ", array_map('idf_escape', $foreign_key["source"])) . ") REFERENCES "
		. ($db != "" && $db != $_GET["db"] ? idf_escape($db) . "." : "")
		. ($ns != "" && $ns != $_GET["ns"] ? idf_escape($ns) . "." : "")
		. table($foreign_key["table"])
		. " (" . implode(", ", array_map('idf_escape', $foreign_key["target"])) . ")" //! reuse $name - check in older MySQL versions
		. (preg_match("~^($on_actions)\$~", $foreign_key["on_delete"]) ? " ON DELETE $foreign_key[on_delete]" : "")
		. (preg_match("~^($on_actions)\$~", $foreign_key["on_update"]) ? " ON UPDATE $foreign_key[on_update]" : "")
	;
}
function tar_file($filename, $tmp_file) {
	$return = pack("a100a8a8a8a12a12", $filename, 644, 0, 0, decoct($tmp_file->size), decoct(time()));
	$checksum = 8*32; // space for checksum itself
	for ($i=0; $i < strlen($return); $i++) {
		$checksum += ord($return[$i]);
	}
	$return .= sprintf("%06o", $checksum) . "\0 ";
	echo $return;
	echo str_repeat("\0", 512 - strlen($return));
	$tmp_file->send();
	echo str_repeat("\0", 511 - ($tmp_file->size + 511) % 512);
}
function ini_bytes($ini) {
	$val = ini_get($ini);
	switch (strtolower(substr($val, -1))) {
		case 'g': $val *= 1024; // no break
		case 'm': $val *= 1024; // no break
		case 'k': $val *= 1024;
	}
	return $val;
}
function doc_link($paths, $text = "<sup>?</sup>") {
	global $jush, $connection;
	$server_info = $connection->server_info;
	$version = preg_replace('~^(\d\.?\d).*~s', '\1', $server_info); // two most significant digits
	$urls = array(
		'sql' => "https://dev.mysql.com/doc/refman/$version/en/",
		'sqlite' => "https://www.sqlite.org/",
		'pgsql' => "https://www.postgresql.org/docs/$version/",
		'mssql' => "https://msdn.microsoft.com/library/",
		'oracle' => "https://www.oracle.com/pls/topic/lookup?ctx=db" . preg_replace('~^.* (\d+)\.(\d+)\.\d+\.\d+\.\d+.*~s', '\1\2', $server_info) . "&id=",
	);
	if (preg_match('~MariaDB~', $server_info)) {
		$urls['sql'] = "https://mariadb.com/kb/en/library/";
		$paths['sql'] = (isset($paths['mariadb']) ? $paths['mariadb'] : str_replace(".html", "/", $paths['sql']));
	}
	return ($paths[$jush] ? "<a href='$urls[$jush]$paths[$jush]'" . target_blank() . ">$text</a>" : "");
}
function ob_gzencode($string) {
	// ob_start() callback recieves an optional parameter $phase but gzencode() accepts optional parameter $level
	return gzencode($string);
}
function db_size($db) {
	global $connection;
	if (!$connection->select_db($db)) {
		return "?";
	}
	$return = 0;
	foreach (table_status() as $table_status) {
		$return += $table_status["Data_length"] + $table_status["Index_length"];
	}
	return format_number($return);
}
function set_utf8mb4($create) {
	global $connection;
	static $set = false;
	if (!$set && preg_match('~\butf8mb4~i', $create)) { // possible false positive
		$set = true;
		echo "SET NAMES " . charset($connection) . ";\n\n";
	}
}

function connect_error() {
	global $adminer, $connection, $token, $error, $drivers;
	if (DB != "") {
		header("HTTP/1.1 404 Not Found");
		page_header(lang('Database') . ": " . h(DB), lang('Invalid database.'), true);
	} else {
		if ($_POST["db"] && !$error) {
			queries_redirect(substr(ME, 0, -1), lang('Databases have been dropped.'), drop_databases($_POST["db"]));
		}
		
		page_header(lang('Select database'), $error, false);
		echo "<p class='links'>\n";
		foreach (array(
			'database' => lang('Create database'),
			'privileges' => lang('Privileges'),
			'processlist' => lang('Process list'),
			'variables' => lang('Variables'),
			'status' => lang('Status'),
		) as $key => $val) {
			if (support($key)) {
				echo "<a href='" . h(ME) . "$key='>$val</a>\n";
			}
		}
		echo "<p>" . lang('%s version: %s through PHP extension %s', $drivers[DRIVER], "<b>" . h($connection->server_info) . "</b>", "<b>$connection->extension</b>") . "\n";
		echo "<p>" . lang('Logged as: %s', "<b>" . h(logged_user()) . "</b>") . "\n";
		$databases = $adminer->databases();
		if ($databases) {
			$scheme = support("scheme");
			$collations = collations();
			echo "<form action='' method='post'>\n";
			echo "<table cellspacing='0' class='checkable'>\n";
			echo script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});");
			echo "<thead><tr>"
				. (support("database") ? "<td>" : "")
				. "<th>" . lang('Database') . " - <a href='" . h(ME) . "refresh=1'>" . lang('Refresh') . "</a>"
				. "<td>" . lang('Collation')
				. "<td>" . lang('Tables')
				. "<td>" . lang('Size') . " - <a href='" . h(ME) . "dbsize=1'>" . lang('Compute') . "</a>" . script("qsl('a').onclick = partial(ajaxSetHtml, '" . js_escape(ME) . "script=connect');", "")
				. "</thead>\n"
			;
			
			$databases = ($_GET["dbsize"] ? count_tables($databases) : array_flip($databases));
			
			foreach ($databases as $db => $tables) {
				$root = h(ME) . "db=" . urlencode($db);
				$id = h("Db-" . $db);
				echo "<tr" . odd() . ">" . (support("database") ? "<td>" . checkbox("db[]", $db, in_array($db, (array) $_POST["db"]), "", "", "", $id) : "");
				echo "<th><a href='$root' id='$id'>" . h($db) . "</a>";
				$collation = h(db_collation($db, $collations));
				echo "<td>" . (support("database") ? "<a href='$root" . ($scheme ? "&amp;ns=" : "") . "&amp;database=' title='" . lang('Alter database') . "'>$collation</a>" : $collation);
				echo "<td align='right'><a href='$root&amp;schema=' id='tables-" . h($db) . "' title='" . lang('Database schema') . "'>" . ($_GET["dbsize"] ? $tables : "?") . "</a>";
				echo "<td align='right' id='size-" . h($db) . "'>" . ($_GET["dbsize"] ? db_size($db) : "?");
				echo "\n";
			}
			
			echo "</table>\n";
			echo (support("database")
				? "<div class='footer'><div>\n"
					. "<fieldset><legend>" . lang('Selected') . " <span id='selected'></span></legend><div>\n"
					. "<input type='hidden' name='all' value=''>" . script("qsl('input').onclick = function () { selectCount('selected', formChecked(this, /^db/)); };") // used by trCheck()
					. "<input type='submit' name='drop' value='" . lang('Drop') . "'>" . confirm() . "\n"
					. "</div></fieldset>\n"
					. "</div></div>\n"
				: ""
			);
			echo "<input type='hidden' name='token' value='$token'>\n";
			echo "</form>\n";
			echo script("tableCheck();");
		}
	}
	
	page_footer("db");
}

if (isset($_GET["status"])) {
	$_GET["variables"] = $_GET["status"];
}
if (isset($_GET["import"])) {
	$_GET["sql"] = $_GET["import"];
}

if (!(DB != "" ? $connection->select_db(DB) : isset($_GET["sql"]) || isset($_GET["dump"]) || isset($_GET["database"]) || isset($_GET["processlist"]) || isset($_GET["privileges"]) || isset($_GET["user"]) || isset($_GET["variables"]) || $_GET["script"] == "connect" || $_GET["script"] == "kill")) {
	if (DB != "" || $_GET["refresh"]) {
		restart_session();
		set_session("dbs", null);
	}
	connect_error(); // separate function to catch SQLite error
	exit;
}

if (support("scheme") && DB != "" && $_GET["ns"] !== "") {
	if (!isset($_GET["ns"])) {
		redirect(preg_replace('~ns=[^&]*&~', '', ME) . "ns=" . get_schema());
	}
	if (!set_schema($_GET["ns"])) {
		header("HTTP/1.1 404 Not Found");
		page_header(lang('Schema') . ": " . h($_GET["ns"]), lang('Invalid schema.'), true);
		page_footer("ns");
		exit;
	}
}

$on_actions = "RESTRICT|NO ACTION|CASCADE|SET NULL|SET DEFAULT"; ///< @var string used in foreign_keys()

class TmpFile {
	var $handler;
	var $size;
	
	function __construct() {
		$this->handler = tmpfile();
	}
	
	function write($contents) {
		$this->size += strlen($contents);
		fwrite($this->handler, $contents);
	}
	
	function send() {
		fseek($this->handler, 0);
		fpassthru($this->handler);
		fclose($this->handler);
	}
	
}

$enum_length = "'(?:''|[^'\\\\]|\\\\.)*'";
$inout = "IN|OUT|INOUT";

if (isset($_GET["select"]) && ($_POST["edit"] || $_POST["clone"]) && !$_POST["save"]) {
	$_GET["edit"] = $_GET["select"];
}
if (isset($_GET["callf"])) {
	$_GET["call"] = $_GET["callf"];
}
if (isset($_GET["function"])) {
	$_GET["procedure"] = $_GET["function"];
}

if (isset($_GET["download"])) {
	$TABLE = $_GET["download"];
	$fields = fields($TABLE);
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=" . friendly_url("$TABLE-" . implode("_", $_GET["where"])) . "." . friendly_url($_GET["field"]));
	$select = array(idf_escape($_GET["field"]));
	$result = $driver->select($TABLE, $select, array(where($_GET, $fields)), $select);
	$row = ($result ? $result->fetch_row() : array());
	echo $driver->value($row[0], $fields[$_GET["field"]]);
	exit; // don't output footer
	
} elseif (isset($_GET["table"])) {
	$TABLE = $_GET["table"];
	$fields = fields($TABLE);
	if (!$fields) {
		$error = error();
	}
	$table_status = table_status1($TABLE, true);
	$name = $adminer->tableName($table_status);

	page_header(($fields && is_view($table_status) ? $table_status['Engine'] == 'materialized view' ? lang('Materialized view') : lang('View') : lang('Table')) . ": " . ($name != "" ? $name : h($TABLE)), $error);

	$adminer->selectLinks($table_status);
	$comment = $table_status["Comment"];
	if ($comment != "") {
		echo "<p class='nowrap'>" . lang('Comment') . ": " . h($comment) . "\n";
	}

	if ($fields) {
		$adminer->tableStructurePrint($fields);
	}

	if (!is_view($table_status)) {
		if (support("indexes")) {
			echo "<h3 id='indexes'>" . lang('Indexes') . "</h3>\n";
			$indexes = indexes($TABLE);
			if ($indexes) {
				$adminer->tableIndexesPrint($indexes);
			}
			echo '<p class="links"><a href="' . h(ME) . 'indexes=' . urlencode($TABLE) . '">' . lang('Alter indexes') . "</a>\n";
		}
		
		if (fk_support($table_status)) {
			echo "<h3 id='foreign-keys'>" . lang('Foreign keys') . "</h3>\n";
			$foreign_keys = foreign_keys($TABLE);
			if ($foreign_keys) {
				echo "<table cellspacing='0'>\n";
				echo "<thead><tr><th>" . lang('Source') . "<td>" . lang('Target') . "<td>" . lang('ON DELETE') . "<td>" . lang('ON UPDATE') . "<td></thead>\n";
				foreach ($foreign_keys as $name => $foreign_key) {
					echo "<tr title='" . h($name) . "'>";
					echo "<th><i>" . implode("</i>, <i>", array_map('h', $foreign_key["source"])) . "</i>";
					echo "<td><a href='" . h($foreign_key["db"] != "" ? preg_replace('~db=[^&]*~', "db=" . urlencode($foreign_key["db"]), ME) : ($foreign_key["ns"] != "" ? preg_replace('~ns=[^&]*~', "ns=" . urlencode($foreign_key["ns"]), ME) : ME)) . "table=" . urlencode($foreign_key["table"]) . "'>"
						. ($foreign_key["db"] != "" ? "<b>" . h($foreign_key["db"]) . "</b>." : "") . ($foreign_key["ns"] != "" ? "<b>" . h($foreign_key["ns"]) . "</b>." : "") . h($foreign_key["table"])
						. "</a>"
					;
					echo "(<i>" . implode("</i>, <i>", array_map('h', $foreign_key["target"])) . "</i>)";
					echo "<td>" . h($foreign_key["on_delete"]) . "\n";
					echo "<td>" . h($foreign_key["on_update"]) . "\n";
					echo '<td><a href="' . h(ME . 'foreign=' . urlencode($TABLE) . '&name=' . urlencode($name)) . '">' . lang('Alter') . '</a>';
				}
				echo "</table>\n";
			}
			echo '<p class="links"><a href="' . h(ME) . 'foreign=' . urlencode($TABLE) . '">' . lang('Add foreign key') . "</a>\n";
		}
	}

	if (support(is_view($table_status) ? "view_trigger" : "trigger")) {
		echo "<h3 id='triggers'>" . lang('Triggers') . "</h3>\n";
		$triggers = triggers($TABLE);
		if ($triggers) {
			echo "<table cellspacing='0'>\n";
			foreach ($triggers as $key => $val) {
				echo "<tr valign='top'><td>" . h($val[0]) . "<td>" . h($val[1]) . "<th>" . h($key) . "<td><a href='" . h(ME . 'trigger=' . urlencode($TABLE) . '&name=' . urlencode($key)) . "'>" . lang('Alter') . "</a>\n";
			}
			echo "</table>\n";
		}
		echo '<p class="links"><a href="' . h(ME) . 'trigger=' . urlencode($TABLE) . '">' . lang('Add trigger') . "</a>\n";
}

} elseif (isset($_GET["schema"])) {

	page_header(lang('Database schema'), "", array(), h(DB . ($_GET["ns"] ? ".$_GET[ns]" : "")));

	$table_pos = array();
	$table_pos_js = array();
	$SCHEMA = ($_GET["schema"] ? $_GET["schema"] : $_COOKIE["adminer_schema-" . str_replace(".", "_", DB)]); // $_COOKIE["adminer_schema"] was used before 3.2.0 //! ':' in table name
	preg_match_all('~([^:]+):([-0-9.]+)x([-0-9.]+)(_|$)~', $SCHEMA, $matches, PREG_SET_ORDER);
	foreach ($matches as $i => $match) {
		$table_pos[$match[1]] = array($match[2], $match[3]);
		$table_pos_js[] = "\n\t'" . js_escape($match[1]) . "': [ $match[2], $match[3] ]";
	}

	$top = 0;
	$base_left = -1;
	$schema = array(); // table => array("fields" => array(name => field), "pos" => array(top, left), "references" => array(table => array(left => array(source, target))))
	$referenced = array(); // target_table => array(table => array(left => target_column))
	$lefts = array(); // float => bool
	foreach (table_status('', true) as $table => $table_status) {
		if (is_view($table_status)) {
			continue;
		}
		$pos = 0;
		$schema[$table]["fields"] = array();
		foreach (fields($table) as $name => $field) {
			$pos += 1.25;
			$field["pos"] = $pos;
			$schema[$table]["fields"][$name] = $field;
		}
		$schema[$table]["pos"] = ($table_pos[$table] ? $table_pos[$table] : array($top, 0));
		foreach ($adminer->foreignKeys($table) as $val) {
			if (!$val["db"]) {
				$left = $base_left;
				if ($table_pos[$table][1] || $table_pos[$val["table"]][1]) {
					$left = min(floatval($table_pos[$table][1]), floatval($table_pos[$val["table"]][1])) - 1;
				} else {
					$base_left -= .1;
				}
				while ($lefts[(string) $left]) {
					// find free $left
					$left -= .0001;
				}
				$schema[$table]["references"][$val["table"]][(string) $left] = array($val["source"], $val["target"]);
				$referenced[$val["table"]][$table][(string) $left] = $val["target"];
				$lefts[(string) $left] = true;
			}
		}
		$top = max($top, $schema[$table]["pos"][0] + 2.5 + $pos);
	}

	?>
	<div id="schema" style="height: <?php echo $top; ?>em;">
	<script<?php echo nonce(); ?>>
	qs('#schema').onselectstart = function () { return false; };
	var tablePos = {<?php echo implode(",", $table_pos_js) . "\n"; ?>};
	var em = qs('#schema').offsetHeight / <?php echo $top; ?>;
	document.onmousemove = schemaMousemove;
	document.onmouseup = partialArg(schemaMouseup, '<?php echo js_escape(DB); ?>');
	</script>
	<?php
	foreach ($schema as $name => $table) {
		echo "<div class='table' style='top: " . $table["pos"][0] . "em; left: " . $table["pos"][1] . "em;'>";
		echo '<a href="' . h(ME) . 'table=' . urlencode($name) . '"><b>' . h($name) . "</b></a>";
		echo script("qsl('div').onmousedown = schemaMousedown;");
		
		foreach ($table["fields"] as $field) {
			$val = '<span' . type_class($field["type"]) . ' title="' . h($field["full_type"] . ($field["null"] ? " NULL" : '')) . '">' . h($field["field"]) . '</span>';
			echo "<br>" . ($field["primary"] ? "<i>$val</i>" : $val);
		}
		
		foreach ((array) $table["references"] as $target_name => $refs) {
			foreach ($refs as $left => $ref) {
				$left1 = $left - $table_pos[$name][1];
				$i = 0;
				foreach ($ref[0] as $source) {
					echo "\n<div class='references' title='" . h($target_name) . "' id='refs$left-" . ($i++) . "' style='left: $left1" . "em; top: " . $table["fields"][$source]["pos"] . "em; padding-top: .5em;'><div style='border-top: 1px solid Gray; width: " . (-$left1) . "em;'></div></div>";
				}
			}
		}
		
		foreach ((array) $referenced[$name] as $target_name => $refs) {
			foreach ($refs as $left => $columns) {
				$left1 = $left - $table_pos[$name][1];
				$i = 0;
				foreach ($columns as $target) {
					echo "\n<div class='references' title='" . h($target_name) . "' id='refd$left-" . ($i++) . "' style='left: $left1" . "em; top: " . $table["fields"][$target]["pos"] . "em; height: 1.25em;'><div style='height: .5em; border-bottom: 1px solid Gray; width: " . (-$left1) . "em;'></div></div>";
				}
			}
		}
		
		echo "\n</div>\n";
	}

	foreach ($schema as $name => $table) {
		foreach ((array) $table["references"] as $target_name => $refs) {
			foreach ($refs as $left => $ref) {
				$min_pos = $top;
				$max_pos = -10;
				foreach ($ref[0] as $key => $source) {
					$pos1 = $table["pos"][0] + $table["fields"][$source]["pos"];
					$pos2 = $schema[$target_name]["pos"][0] + $schema[$target_name]["fields"][$ref[1][$key]]["pos"];
					$min_pos = min($min_pos, $pos1, $pos2);
					$max_pos = max($max_pos, $pos1, $pos2);
				}
				echo "<div class='references' id='refl$left' style='left: $left" . "em; top: $min_pos" . "em; padding: .5em 0;'><div style='border-right: 1px solid Gray; margin-top: 1px; height: " . ($max_pos - $min_pos) . "em;'></div></div>\n";
			}
		}
	}
	?>
	</div>
	<p class="links"><a href="<?php echo h(ME . "schema=" . urlencode($SCHEMA)); ?>" id="schema-link"><?php echo lang('Permanent link'); ?></a>
	<?php

} elseif (isset($_GET["dump"])) {
	$TABLE = $_GET["dump"];

	if ($_POST && !$error) {
		$cookie = "";
		foreach (array("output", "format", "db_style", "routines", "events", "table_style", "auto_increment", "triggers", "data_style") as $key) {
			$cookie .= "&$key=" . urlencode($_POST[$key]);
		}
		cookie("adminer_export", substr($cookie, 1));
		$tables = array_flip((array) $_POST["tables"]) + array_flip((array) $_POST["data"]);
		$ext = dump_headers(
			(count($tables) == 1 ? key($tables) : DB),
			(DB == "" || count($tables) > 1));
		$is_sql = preg_match('~sql~', $_POST["format"]);

		if ($is_sql) {
			echo "-- Adminer $VERSION " . $drivers[DRIVER] . " dump\n\n";
			if ($jush == "sql") {
				echo "SET NAMES utf8;
	SET time_zone = '+00:00';
	" . ($_POST["data_style"] ? "SET foreign_key_checks = 0;
	SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';
	" : "") . "
	";
				$connection->query("SET time_zone = '+00:00';");
			}
		}

		$style = $_POST["db_style"];
		$databases = array(DB);
		if (DB == "") {
			$databases = $_POST["databases"];
			if (is_string($databases)) {
				$databases = explode("\n", rtrim(str_replace("\r", "", $databases), "\n"));
			}
		}

		foreach ((array) $databases as $db) {
			$adminer->dumpDatabase($db);
			if ($connection->select_db($db)) {
				if ($is_sql && preg_match('~CREATE~', $style) && ($create = $connection->result("SHOW CREATE DATABASE " . idf_escape($db), 1))) {
					set_utf8mb4($create);
					if ($style == "DROP+CREATE") {
						echo "DROP DATABASE IF EXISTS " . idf_escape($db) . ";\n";
					}
					echo "$create;\n";
				}
				if ($is_sql) {
					if ($style) {
						echo use_sql($db) . ";\n\n";
					}
					$out = "";

					if ($_POST["routines"]) {
						foreach (array("FUNCTION", "PROCEDURE") as $routine) {
							foreach (get_rows("SHOW $routine STATUS WHERE Db = " . q($db), null, "-- ") as $row) {
								$create = remove_definer($connection->result("SHOW CREATE $routine " . idf_escape($row["Name"]), 2));
								set_utf8mb4($create);
								$out .= ($style != 'DROP+CREATE' ? "DROP $routine IF EXISTS " . idf_escape($row["Name"]) . ";;\n" : "") . "$create;;\n\n";
							}
						}
					}

					if ($_POST["events"]) {
						foreach (get_rows("SHOW EVENTS", null, "-- ") as $row) {
							$create = remove_definer($connection->result("SHOW CREATE EVENT " . idf_escape($row["Name"]), 3));
							set_utf8mb4($create);
							$out .= ($style != 'DROP+CREATE' ? "DROP EVENT IF EXISTS " . idf_escape($row["Name"]) . ";;\n" : "") . "$create;;\n\n";
						}
					}

					if ($out) {
						echo "DELIMITER ;;\n\n$out" . "DELIMITER ;\n\n";
					}
				}

				if ($_POST["table_style"] || $_POST["data_style"]) {
					$views = array();
					foreach (table_status('', true) as $name => $table_status) {
						$table = (DB == "" || in_array($name, (array) $_POST["tables"]));
						$data = (DB == "" || in_array($name, (array) $_POST["data"]));
						if ($table || $data) {
							if ($ext == "tar") {
								$tmp_file = new TmpFile;
								ob_start(array($tmp_file, 'write'), 1e5);
							}

							$adminer->dumpTable($name, ($table ? $_POST["table_style"] : ""), (is_view($table_status) ? 2 : 0));
							if (is_view($table_status)) {
								$views[] = $name;
							} elseif ($data) {
								$fields = fields($name);
								$adminer->dumpData($name, $_POST["data_style"], "SELECT *" . convert_fields($fields, $fields) . " FROM " . table($name));
							}
							if ($is_sql && $_POST["triggers"] && $table && ($triggers = trigger_sql($name))) {
								echo "\nDELIMITER ;;\n$triggers\nDELIMITER ;\n";
							}

							if ($ext == "tar") {
								ob_end_flush();
								tar_file((DB != "" ? "" : "$db/") . "$name.csv", $tmp_file);
							} elseif ($is_sql) {
								echo "\n";
							}
						}
					}

					foreach ($views as $view) {
						$adminer->dumpTable($view, $_POST["table_style"], 1);
					}

					if ($ext == "tar") {
						echo pack("x512");
					}
				}
			}
		}

		if ($is_sql) {
			echo "-- " . $connection->result("SELECT NOW()") . "\n";
		}
		exit;
	}

	page_header(lang('Export'), $error, ($_GET["export"] != "" ? array("table" => $_GET["export"]) : array()), h(DB));
	?>

	<form action="" method="post">
	<table cellspacing="0" class="layout">
	<?php
	$db_style = array('', 'USE', 'DROP+CREATE', 'CREATE');
	$table_style = array('', 'DROP+CREATE', 'CREATE');
	$data_style = array('', 'TRUNCATE+INSERT', 'INSERT');
	if ($jush == "sql") { //! use insertUpdate() in all drivers
		$data_style[] = 'INSERT+UPDATE';
	}
	parse_str($_COOKIE["adminer_export"], $row);
	if (!$row) {
		$row = array("output" => "text", "format" => "sql", "db_style" => (DB != "" ? "" : "CREATE"), "table_style" => "DROP+CREATE", "data_style" => "INSERT");
	}
	if (!isset($row["events"])) { // backwards compatibility
		$row["routines"] = $row["events"] = ($_GET["dump"] == "");
		$row["triggers"] = $row["table_style"];
	}

	echo "<tr><th>" . lang('Output') . "<td>" . html_select("output", $adminer->dumpOutput(), $row["output"], 0) . "\n"; // 0 - radio

	echo "<tr><th>" . lang('Format') . "<td>" . html_select("format", $adminer->dumpFormat(), $row["format"], 0) . "\n"; // 0 - radio

	echo ($jush == "sqlite" ? "" : "<tr><th>" . lang('Database') . "<td>" . html_select('db_style', $db_style, $row["db_style"])
		. (support("routine") ? checkbox("routines", 1, $row["routines"], lang('Routines')) : "")
		. (support("event") ? checkbox("events", 1, $row["events"], lang('Events')) : "")
	);

	echo "<tr><th>" . lang('Tables') . "<td>" . html_select('table_style', $table_style, $row["table_style"])
		. checkbox("auto_increment", 1, $row["auto_increment"], lang('Auto Increment'))
		. (support("trigger") ? checkbox("triggers", 1, $row["triggers"], lang('Triggers')) : "")
	;

	echo "<tr><th>" . lang('Data') . "<td>" . html_select('data_style', $data_style, $row["data_style"]);
	?>
	</table>
	<p><input type="submit" value="<?php echo lang('Export'); ?>">
	<input type="hidden" name="token" value="<?php echo $token; ?>">

	<table cellspacing="0">
	<?php
	echo script("qsl('table').onclick = dumpClick;");
	$prefixes = array();
	if (DB != "") {
		$checked = ($TABLE != "" ? "" : " checked");
		echo "<thead><tr>";
		echo "<th style='text-align: left;'><label class='block'><input type='checkbox' id='check-tables'$checked>" . lang('Tables') . "</label>" . script("qs('#check-tables').onclick = partial(formCheck, /^tables\\[/);", "");
		echo "<th style='text-align: right;'><label class='block'>" . lang('Data') . "<input type='checkbox' id='check-data'$checked></label>" . script("qs('#check-data').onclick = partial(formCheck, /^data\\[/);", "");
		echo "</thead>\n";

		$views = "";
		$tables_list = tables_list();
		foreach ($tables_list as $name => $type) {
			$prefix = preg_replace('~_.*~', '', $name);
			$checked = ($TABLE == "" || $TABLE == (substr($TABLE, -1) == "%" ? "$prefix%" : $name)); //! % may be part of table name
			$print = "<tr><td>" . checkbox("tables[]", $name, $checked, $name, "", "block");
			if ($type !== null && !preg_match('~table~i', $type)) {
				$views .= "$print\n";
			} else {
				echo "$print<td align='right'><label class='block'><span id='Rows-" . h($name) . "'></span>" . checkbox("data[]", $name, $checked) . "</label>\n";
			}
			$prefixes[$prefix]++;
		}
		echo $views;

		if ($tables_list) {
			echo script("ajaxSetHtml('" . js_escape(ME) . "script=db');");
		}

	} else {
		echo "<thead><tr><th style='text-align: left;'>";
		echo "<label class='block'><input type='checkbox' id='check-databases'" . ($TABLE == "" ? " checked" : "") . ">" . lang('Database') . "</label>";
		echo script("qs('#check-databases').onclick = partial(formCheck, /^databases\\[/);", "");
		echo "</thead>\n";
		$databases = $adminer->databases();
		if ($databases) {
			foreach ($databases as $db) {
				if (!information_schema($db)) {
					$prefix = preg_replace('~_.*~', '', $db);
					echo "<tr><td>" . checkbox("databases[]", $db, $TABLE == "" || $TABLE == "$prefix%", $db, "", "block") . "\n";
					$prefixes[$prefix]++;
				}
			}
		} else {
			echo "<tr><td><textarea name='databases' rows='10' cols='20'></textarea>";
		}
	}
	?>
	</table>
	</form>
	<?php
	$first = true;
	foreach ($prefixes as $key => $val) {
		if ($key != "" && $val > 1) {
			echo ($first ? "<p>" : " ") . "<a href='" . h(ME) . "dump=" . urlencode("$key%") . "'>" . h($key) . "</a>";
			$first = false;
		}
	}


} elseif (isset($_GET["privileges"])) {

	page_header(lang('Privileges'));

	echo '<p class="links"><a href="' . h(ME) . 'user=">' . lang('Create user') . "</a>";

	$result = $connection->query("SELECT User, Host FROM mysql." . (DB == "" ? "user" : "db WHERE " . q(DB) . " LIKE Db") . " ORDER BY Host, User");
	$grant = $result;
	if (!$result) {
		// list logged user, information_schema.USER_PRIVILEGES lists just the current user too
		$result = $connection->query("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', 1) AS User, SUBSTRING_INDEX(CURRENT_USER, '@', -1) AS Host");
	}

	echo "<form action=''><p>\n";
	hidden_fields_get();
	echo "<input type='hidden' name='db' value='" . h(DB) . "'>\n";
	echo ($grant ? "" : "<input type='hidden' name='grant' value=''>\n");
	echo "<table cellspacing='0'>\n";
	echo "<thead><tr><th>" . lang('Username') . "<th>" . lang('Server') . "<th></thead>\n";

	while ($row = $result->fetch_assoc()) {
		echo '<tr' . odd() . '><td>' . h($row["User"]) . "<td>" . h($row["Host"]) . '<td><a href="' . h(ME . 'user=' . urlencode($row["User"]) . '&host=' . urlencode($row["Host"])) . '">' . lang('Edit') . "</a>\n";
	}

	if (!$grant || DB != "") {
		echo "<tr" . odd() . "><td><input name='user' autocapitalize='off'><td><input name='host' value='localhost' autocapitalize='off'><td><input type='submit' value='" . lang('Edit') . "'>\n";
	}

	echo "</table>\n";
	echo "</form>\n";

} elseif (isset($_GET["sql"])) {
	
	if (!$error && $_POST["export"]) {
		dump_headers("sql");
		$adminer->dumpTable("", "");
		$adminer->dumpData("", "table", $_POST["query"]);
		exit;
	}
	
	restart_session();
	$history_all = &get_session("queries");
	$history = &$history_all[DB];
	if (!$error && $_POST["clear"]) {
		$history = array();
		redirect(remove_from_uri("history"));
	}
	
	page_header((isset($_GET["import"]) ? lang('Import') : lang('SQL command')), $error);
	
	if (!$error && $_POST) {
		$fp = false;
		if (!isset($_GET["import"])) {
			$query = $_POST["query"];
		} elseif ($_POST["webfile"]) {
			$sql_file_path = $adminer->importServerPath();
			$fp = @fopen((file_exists($sql_file_path)
				? $sql_file_path
				: "compress.zlib://$sql_file_path.gz"
			), "rb");
			$query = ($fp ? fread($fp, 1e6) : false);
		} else {
			$query = get_file("sql_file", true);
		}
	
		if (is_string($query)) { // get_file() returns error as number, fread() as false
			if (function_exists('memory_get_usage')) {
				@ini_set("memory_limit", max(ini_bytes("memory_limit"), 2 * strlen($query) + memory_get_usage() + 8e6)); // @ - may be disabled, 2 - substr and trim, 8e6 - other variables
			}
	
			if ($query != "" && strlen($query) < 1e6) { // don't add big queries
				$q = $query . (preg_match("~;[ \t\r\n]*\$~", $query) ? "" : ";"); //! doesn't work with DELIMITER |
				if (!$history || reset(end($history)) != $q) { // no repeated queries
					restart_session();
					$history[] = array($q, time()); //! add elapsed time
					set_session("queries", $history_all); // required because reference is unlinked by stop_session()
					stop_session();
				}
			}
	
			$space = "(?:\\s|/\\*[\s\S]*?\\*/|(?:#|-- )[^\n]*\n?|--\r?\n)";
			$delimiter = ";";
			$offset = 0;
			$empty = true;
			$connection2 = connect(); // connection for exploring indexes and EXPLAIN (to not replace FOUND_ROWS()) //! PDO - silent error
			if (is_object($connection2) && DB != "") {
				$connection2->select_db(DB);
				if ($_GET["ns"] != "") {
					set_schema($_GET["ns"], $connection2);
				}
			}
			$commands = 0;
			$errors = array();
			$parse = '[\'"' . ($jush == "sql" ? '`#' : ($jush == "sqlite" ? '`[' : ($jush == "mssql" ? '[' : ''))) . ']|/\*|-- |$' . ($jush == "pgsql" ? '|\$[^$]*\$' : '');
			$total_start = microtime(true);
			parse_str($_COOKIE["adminer_export"], $adminer_export);
			$dump_format = $adminer->dumpFormat();
			unset($dump_format["sql"]);
	
			while ($query != "") {
				if (!$offset && preg_match("~^$space*+DELIMITER\\s+(\\S+)~i", $query, $match)) {
					$delimiter = $match[1];
					$query = substr($query, strlen($match[0]));
				} else {
					preg_match('(' . preg_quote($delimiter) . "\\s*|$parse)", $query, $match, PREG_OFFSET_CAPTURE, $offset); // should always match
					list($found, $pos) = $match[0];
					if (!$found && $fp && !feof($fp)) {
						$query .= fread($fp, 1e5);
					} else {
						if (!$found && rtrim($query) == "") {
							break;
						}
						$offset = $pos + strlen($found);
	
						if ($found && rtrim($found) != $delimiter) { // find matching quote or comment end
							while (preg_match('(' . ($found == '/*' ? '\*/' : ($found == '[' ? ']' : (preg_match('~^-- |^#~', $found) ? "\n" : preg_quote($found) . "|\\\\."))) . '|$)s', $query, $match, PREG_OFFSET_CAPTURE, $offset)) { //! respect sql_mode NO_BACKSLASH_ESCAPES
								$s = $match[0][0];
								if (!$s && $fp && !feof($fp)) {
									$query .= fread($fp, 1e5);
								} else {
									$offset = $match[0][1] + strlen($s);
									if ($s[0] != "\\") {
										break;
									}
								}
							}
	
						} else { // end of a query
							$empty = false;
							$q = substr($query, 0, $pos);
							$commands++;
							$print = "<pre id='sql-$commands'><code class='jush-$jush'>" . $adminer->sqlCommandQuery($q) . "</code></pre>\n";
							if ($jush == "sqlite" && preg_match("~^$space*+ATTACH\\b~i", $q, $match)) {
								// PHP doesn't support setting SQLITE_LIMIT_ATTACHED
								echo $print;
								echo "<p class='error'>" . lang('ATTACH queries are not supported.') . "\n";
								$errors[] = " <a href='#sql-$commands'>$commands</a>";
								if ($_POST["error_stops"]) {
									break;
								}
							} else {
								if (!$_POST["only_errors"]) {
									echo $print;
									ob_flush();
									flush(); // can take a long time - show the running query
								}
								$start = microtime(true);
								//! don't allow changing of character_set_results, convert encoding of displayed query
								if ($connection->multi_query($q) && is_object($connection2) && preg_match("~^$space*+USE\\b~i", $q)) {
									$connection2->query($q);
								}
	
								do {
									$result = $connection->store_result();
	
									if ($connection->error) {
										echo ($_POST["only_errors"] ? $print : "");
										echo "<p class='error'>" . lang('Error in query') . ($connection->errno ? " ($connection->errno)" : "") . ": " . error() . "\n";
										$errors[] = " <a href='#sql-$commands'>$commands</a>";
										if ($_POST["error_stops"]) {
											break 2;
										}
	
									} else {
										$time = " <span class='time'>(" . format_time($start) . ")</span>"
											. (strlen($q) < 1000 ? " <a href='" . h(ME) . "sql=" . urlencode(trim($q)) . "'>" . lang('Edit') . "</a>" : "") // 1000 - maximum length of encoded URL in IE is 2083 characters
										;
										$affected = $connection->affected_rows; // getting warnigns overwrites this
										$warnings = ($_POST["only_errors"] ? "" : $driver->warnings());
										$warnings_id = "warnings-$commands";
										if ($warnings) {
											$time .= ", <a href='#$warnings_id'>" . lang('Warnings') . "</a>" . script("qsl('a').onclick = partial(toggle, '$warnings_id');", "");
										}
										$explain = null;
										$explain_id = "explain-$commands";
										if (is_object($result)) {
											$limit = $_POST["limit"];
											$orgtables = select($result, $connection2, array(), $limit);
											if (!$_POST["only_errors"]) {
												echo "<form action='' method='post'>\n";
												$num_rows = $result->num_rows;
												echo "<p>" . ($num_rows ? ($limit && $num_rows > $limit ? lang('%d / ', $limit) : "") . lang('%d row(s)', $num_rows) : "");
												echo $time;
												if ($connection2 && preg_match("~^($space|\\()*+SELECT\\b~i", $q) && ($explain = explain($connection2, $q))) {
													echo ", <a href='#$explain_id'>Explain</a>" . script("qsl('a').onclick = partial(toggle, '$explain_id');", "");
												}
												$id = "export-$commands";
												echo ", <a href='#$id'>" . lang('Export') . "</a>" . script("qsl('a').onclick = partial(toggle, '$id');", "") . "<span id='$id' class='hidden'>: "
													. html_select("output", $adminer->dumpOutput(), $adminer_export["output"]) . " "
													. html_select("format", $dump_format, $adminer_export["format"])
													. "<input type='hidden' name='query' value='" . h($q) . "'>"
													. " <input type='submit' name='export' value='" . lang('Export') . "'><input type='hidden' name='token' value='$token'></span>\n"
													. "</form>\n"
												;
											}
	
										} else {
											if (preg_match("~^$space*+(CREATE|DROP|ALTER)$space++(DATABASE|SCHEMA)\\b~i", $q)) {
												restart_session();
												set_session("dbs", null); // clear cache
												stop_session();
											}
											if (!$_POST["only_errors"]) {
												echo "<p class='message' title='" . h($connection->info) . "'>" . lang('Query executed OK, %d row(s) affected.', $affected) . "$time\n";
											}
										}
										echo ($warnings ? "<div id='$warnings_id' class='hidden'>\n$warnings</div>\n" : "");
										if ($explain) {
											echo "<div id='$explain_id' class='hidden'>\n";
											select($explain, $connection2, $orgtables);
											echo "</div>\n";
										}
									}
	
									$start = microtime(true);
								} while ($connection->next_result());
							}
	
							$query = substr($query, $offset);
							$offset = 0;
						}
	
					}
				}
			}
	
			if ($empty) {
				echo "<p class='message'>" . lang('No commands to execute.') . "\n";
			} elseif ($_POST["only_errors"]) {
				echo "<p class='message'>" . lang('%d query(s) executed OK.', $commands - count($errors));
				echo " <span class='time'>(" . format_time($total_start) . ")</span>\n";
			} elseif ($errors && $commands > 1) {
				echo "<p class='error'>" . lang('Error in query') . ": " . implode("", $errors) . "\n";
			}
			//! MS SQL - SET SHOWPLAN_ALL OFF
	
		} else {
			echo "<p class='error'>" . upload_error($query) . "\n";
		}
	}
	?>
	
	<form action="" method="post" enctype="multipart/form-data" id="form">
	<?php
	$execute = "<input type='submit' value='" . lang('Execute') . "' title='Ctrl+Enter'>";
	if (!isset($_GET["import"])) {
		$q = $_GET["sql"]; // overwrite $q from if ($_POST) to save memory
		if ($_POST) {
			$q = $_POST["query"];
		} elseif ($_GET["history"] == "all") {
			$q = $history;
		} elseif ($_GET["history"] != "") {
			$q = $history[$_GET["history"]][0];
		}
		echo "<p>";
		textarea("query", $q, 20);
		echo script(($_POST ? "" : "qs('textarea').focus();\n") . "qs('#form').onsubmit = partial(sqlSubmit, qs('#form'), '" . remove_from_uri("sql|limit|error_stops|only_errors") . "');");
		echo "<p>$execute\n";
		echo lang('Limit rows') . ": <input type='number' name='limit' class='size' value='" . h($_POST ? $_POST["limit"] : $_GET["limit"]) . "'>\n";
		
	} else {
		echo "<fieldset><legend>" . lang('File upload') . "</legend><div>";
		$gz = (extension_loaded("zlib") ? "[.gz]" : "");
		echo (ini_bool("file_uploads")
			? "SQL$gz (&lt; " . ini_get("upload_max_filesize") . "B): <input type='file' name='sql_file[]' multiple>\n$execute" // ignore post_max_size because it is for all form fields together and bytes computing would be necessary
			: lang('File uploads are disabled.')
		);
		echo "</div></fieldset>\n";
		$importServerPath = $adminer->importServerPath();
		if ($importServerPath) {
			echo "<fieldset><legend>" . lang('From server') . "</legend><div>";
			echo lang('Webserver file %s', "<code>" . h($importServerPath) . "$gz</code>");
			echo ' <input type="submit" name="webfile" value="' . lang('Run file') . '">';
			echo "</div></fieldset>\n";
		}
		echo "<p>";
	}
	
	echo checkbox("error_stops", 1, ($_POST ? $_POST["error_stops"] : isset($_GET["import"])), lang('Stop on error')) . "\n";
	echo checkbox("only_errors", 1, ($_POST ? $_POST["only_errors"] : isset($_GET["import"])), lang('Show only errors')) . "\n";
	echo "<input type='hidden' name='token' value='$token'>\n";
	
	if (!isset($_GET["import"]) && $history) {
		print_fieldset("history", lang('History'), $_GET["history"] != "");
		for ($val = end($history); $val; $val = prev($history)) { // not array_reverse() to save memory
			$key = key($history);
			list($q, $time, $elapsed) = $val;
			echo '<a href="' . h(ME . "sql=&history=$key") . '">' . lang('Edit') . "</a>"
				. " <span class='time' title='" . @date('Y-m-d', $time) . "'>" . @date("H:i:s", $time) . "</span>" // @ - time zone may be not set
				. " <code class='jush-$jush'>" . shorten_utf8(ltrim(str_replace("\n", " ", str_replace("\r", "", preg_replace('~^(#|-- ).*~m', '', $q)))), 80, "</code>")
				. ($elapsed ? " <span class='time'>($elapsed)</span>" : "")
				. "<br>\n"
			;
		}
		echo "<input type='submit' name='clear' value='" . lang('Clear') . "'>\n";
		echo "<a href='" . h(ME . "sql=&history=all") . "'>" . lang('Edit all') . "</a>\n";
		echo "</div></fieldset>\n";
	}
	?>
	</form>
	<?php

} elseif (isset($_GET["edit"])) {
	$TABLE = $_GET["edit"];
	$fields = fields($TABLE);
	$where = (isset($_GET["select"]) ? ($_POST["check"] && count($_POST["check"]) == 1 ? where_check($_POST["check"][0], $fields) : "") : where($_GET, $fields));
	$update = (isset($_GET["select"]) ? $_POST["edit"] : $where);
	foreach ($fields as $name => $field) {
		if (!isset($field["privileges"][$update ? "update" : "insert"]) || $adminer->fieldName($field) == "" || $field["generated"]) {
			unset($fields[$name]);
		}
	}

	if ($_POST && !$error && !isset($_GET["select"])) {
		$location = $_POST["referer"];
		if ($_POST["insert"]) { // continue edit or insert
			$location = ($update ? null : $_SERVER["REQUEST_URI"]);
		} elseif (!preg_match('~^.+&select=.+$~', $location)) {
			$location = ME . "select=" . urlencode($TABLE);
		}

		$indexes = indexes($TABLE);
		$unique_array = unique_array($_GET["where"], $indexes);
		$query_where = "\nWHERE $where";

		if (isset($_POST["delete"])) {
			queries_redirect(
				$location,
				lang('Item has been deleted.'),
				$driver->delete($TABLE, $query_where, !$unique_array)
			);

		} else {
			$set = array();
			foreach ($fields as $name => $field) {
				$val = process_input($field);
				if ($val !== false && $val !== null) {
					$set[idf_escape($name)] = $val;
				}
			}

			if ($update) {
				if (!$set) {
					redirect($location);
				}
				queries_redirect(
					$location,
					lang('Item has been updated.'),
					$driver->update($TABLE, $set, $query_where, !$unique_array)
				);
				if (is_ajax()) {
					page_headers();
					page_messages($error);
					exit;
				}
			} else {
				$result = $driver->insert($TABLE, $set);
				$last_id = ($result ? last_id() : 0);
				queries_redirect($location, lang('Item%s has been inserted.', ($last_id ? " $last_id" : "")), $result); //! link
			}
		}
	}

	$row = null;
	if ($_POST["save"]) {
		$row = (array) $_POST["fields"];
	} elseif ($where) {
		$select = array();
		foreach ($fields as $name => $field) {
			if (isset($field["privileges"]["select"])) {
				$as = convert_field($field);
				if ($_POST["clone"] && $field["auto_increment"]) {
					$as = "''";
				}
				if ($jush == "sql" && preg_match("~enum|set~", $field["type"])) {
					$as = "1*" . idf_escape($name);
				}
				$select[] = ($as ? "$as AS " : "") . idf_escape($name);
			}
		}
		$row = array();
		if (!support("table")) {
			$select = array("*");
		}
		if ($select) {
			$result = $driver->select($TABLE, $select, array($where), $select, array(), (isset($_GET["select"]) ? 2 : 1));
			if (!$result) {
				$error = error();
			} else {
				$row = $result->fetch_assoc();
				if (!$row) { // MySQLi returns null
					$row = false;
				}
			}
			if (isset($_GET["select"]) && (!$row || $result->fetch_assoc())) { // $result->num_rows != 1 isn't available in all drivers
				$row = null;
			}
		}
	}

	if (!support("table") && !$fields) {
		if (!$where) { // insert
			$result = $driver->select($TABLE, array("*"), $where, array("*"));
			$row = ($result ? $result->fetch_assoc() : false);
			if (!$row) {
				$row = array($driver->primary => "");
			}
		}
		if ($row) {
			foreach ($row as $key => $val) {
				if (!$where) {
					$row[$key] = null;
				}
				$fields[$key] = array("field" => $key, "null" => ($key != $driver->primary), "auto_increment" => ($key == $driver->primary));
			}
		}
	}

	edit_form($TABLE, $fields, $row, $update);

} elseif (isset($_GET["create"])) {
	$TABLE = $_GET["create"];
	$partition_by = array();
	foreach (array('HASH', 'LINEAR HASH', 'KEY', 'LINEAR KEY', 'RANGE', 'LIST') as $key) {
		$partition_by[$key] = $key;
	}

	$referencable_primary = referencable_primary($TABLE);
	$foreign_keys = array();
	foreach ($referencable_primary as $table_name => $field) {
		$foreign_keys[str_replace("`", "``", $table_name) . "`" . str_replace("`", "``", $field["field"])] = $table_name; // not idf_escape() - used in JS
	}

	$orig_fields = array();
	$table_status = array();
	if ($TABLE != "") {
		$orig_fields = fields($TABLE);
		$table_status = table_status($TABLE);
		if (!$table_status) {
			$error = lang('No tables.');
		}
	}

	$row = $_POST;
	$row["fields"] = (array) $row["fields"];
	if ($row["auto_increment_col"]) {
		$row["fields"][$row["auto_increment_col"]]["auto_increment"] = true;
	}

	if ($_POST) {
		set_adminer_settings(array("comments" => $_POST["comments"], "defaults" => $_POST["defaults"]));
	}

	if ($_POST && !process_fields($row["fields"]) && !$error) {
		if ($_POST["drop"]) {
			queries_redirect(substr(ME, 0, -1), lang('Table has been dropped.'), drop_tables(array($TABLE)));
		} else {
			$fields = array();
			$all_fields = array();
			$use_all_fields = false;
			$foreign = array();
			$orig_field = reset($orig_fields);
			$after = " FIRST";

			foreach ($row["fields"] as $key => $field) {
				$foreign_key = $foreign_keys[$field["type"]];
				$type_field = ($foreign_key !== null ? $referencable_primary[$foreign_key] : $field); //! can collide with user defined type
				if ($field["field"] != "") {
					if (!$field["has_default"]) {
						$field["default"] = null;
					}
					if ($key == $row["auto_increment_col"]) {
						$field["auto_increment"] = true;
					}
					$process_field = process_field($field, $type_field);
					$all_fields[] = array($field["orig"], $process_field, $after);
					if ($process_field != process_field($orig_field, $orig_field)) {
						$fields[] = array($field["orig"], $process_field, $after);
						if ($field["orig"] != "" || $after) {
							$use_all_fields = true;
						}
					}
					if ($foreign_key !== null) {
						$foreign[idf_escape($field["field"])] = ($TABLE != "" && $jush != "sqlite" ? "ADD" : " ") . format_foreign_key(array(
							'table' => $foreign_keys[$field["type"]],
							'source' => array($field["field"]),
							'target' => array($type_field["field"]),
							'on_delete' => $field["on_delete"],
						));
					}
					$after = " AFTER " . idf_escape($field["field"]);
				} elseif ($field["orig"] != "") {
					$use_all_fields = true;
					$fields[] = array($field["orig"]);
				}
				if ($field["orig"] != "") {
					$orig_field = next($orig_fields);
					if (!$orig_field) {
						$after = "";
					}
				}
			}

			$partitioning = "";
			if ($partition_by[$row["partition_by"]]) {
				$partitions = array();
				if ($row["partition_by"] == 'RANGE' || $row["partition_by"] == 'LIST') {
					foreach (array_filter($row["partition_names"]) as $key => $val) {
						$value = $row["partition_values"][$key];
						$partitions[] = "\n  PARTITION " . idf_escape($val) . " VALUES " . ($row["partition_by"] == 'RANGE' ? "LESS THAN" : "IN") . ($value != "" ? " ($value)" : " MAXVALUE"); //! SQL injection
					}
				}
				$partitioning .= "\nPARTITION BY $row[partition_by]($row[partition])" . ($partitions // $row["partition"] can be expression, not only column
					? " (" . implode(",", $partitions) . "\n)"
					: ($row["partitions"] ? " PARTITIONS " . (+$row["partitions"]) : "")
				);
			} elseif (support("partitioning") && preg_match("~partitioned~", $table_status["Create_options"])) {
				$partitioning .= "\nREMOVE PARTITIONING";
			}

			$message = lang('Table has been altered.');
			if ($TABLE == "") {
				cookie("adminer_engine", $row["Engine"]);
				$message = lang('Table has been created.');
			}
			$name = trim($row["name"]);

			queries_redirect(ME . (support("table") ? "table=" : "select=") . urlencode($name), $message, alter_table(
				$TABLE,
				$name,
				($jush == "sqlite" && ($use_all_fields || $foreign) ? $all_fields : $fields),
				$foreign,
				($row["Comment"] != $table_status["Comment"] ? $row["Comment"] : null),
				($row["Engine"] && $row["Engine"] != $table_status["Engine"] ? $row["Engine"] : ""),
				($row["Collation"] && $row["Collation"] != $table_status["Collation"] ? $row["Collation"] : ""),
				($row["Auto_increment"] != "" ? number($row["Auto_increment"]) : ""),
				$partitioning
			));
		}
	}

	page_header(($TABLE != "" ? lang('Alter table') : lang('Create table')), $error, array("table" => $TABLE), h($TABLE));

	if (!$_POST) {
		$row = array(
			"Engine" => $_COOKIE["adminer_engine"],
			"fields" => array(array("field" => "", "type" => (isset($types["int"]) ? "int" : (isset($types["integer"]) ? "integer" : "")), "on_update" => "")),
			"partition_names" => array(""),
		);

		if ($TABLE != "") {
			$row = $table_status;
			$row["name"] = $TABLE;
			$row["fields"] = array();
			if (!$_GET["auto_increment"]) { // don't prefill by original Auto_increment for the sake of performance and not reusing deleted ids
				$row["Auto_increment"] = "";
			}
			foreach ($orig_fields as $field) {
				$field["has_default"] = isset($field["default"]);
				$row["fields"][] = $field;
			}

			if (support("partitioning")) {
				$from = "FROM information_schema.PARTITIONS WHERE TABLE_SCHEMA = " . q(DB) . " AND TABLE_NAME = " . q($TABLE);
				$result = $connection->query("SELECT PARTITION_METHOD, PARTITION_ORDINAL_POSITION, PARTITION_EXPRESSION $from ORDER BY PARTITION_ORDINAL_POSITION DESC LIMIT 1");
				list($row["partition_by"], $row["partitions"], $row["partition"]) = $result->fetch_row();
				$partitions = get_key_vals("SELECT PARTITION_NAME, PARTITION_DESCRIPTION $from AND PARTITION_NAME != '' ORDER BY PARTITION_ORDINAL_POSITION");
				$partitions[""] = "";
				$row["partition_names"] = array_keys($partitions);
				$row["partition_values"] = array_values($partitions);
			}
		}
	}

	$collations = collations();
	$engines = engines();
	// case of engine may differ
	foreach ($engines as $engine) {
		if (!strcasecmp($engine, $row["Engine"])) {
			$row["Engine"] = $engine;
			break;
		}
	}
	?>

	<form action="" method="post" id="form">
	<p>
	<?php if (support("columns") || $TABLE == "") { ?>
	<?php echo lang('Table name'); ?>: <input name="name" data-maxlength="64" value="<?php echo h($row["name"]); ?>" autocapitalize="off">
	<?php if ($TABLE == "" && !$_POST) { echo script("focus(qs('#form')['name']);"); } ?>
	<?php echo ($engines ? "<select name='Engine'>" . optionlist(array("" => "(" . lang('engine') . ")") + $engines, $row["Engine"]) . "</select>" . on_help("getTarget(event).value", 1) . script("qsl('select').onchange = helpClose;") : ""); ?>
	<?php echo ($collations && !preg_match("~sqlite|mssql~", $jush) ? html_select("Collation", array("" => "(" . lang('collation') . ")") + $collations, $row["Collation"]) : ""); ?>
	<input type="submit" value="<?php echo lang('Save'); ?>">
	<?php } ?>

	<?php if (support("columns")) { ?>
	<div class="scrollable">
	<table cellspacing="0" id="edit-fields" class="nowrap">
	<?php
	edit_fields($row["fields"], $collations, "TABLE", $foreign_keys);
	?>
	</table>
	</div>
	<p>
	<?php echo lang('Auto Increment'); ?>: <input type="number" name="Auto_increment" size="6" value="<?php echo h($row["Auto_increment"]); ?>">
	<?php echo checkbox("defaults", 1, ($_POST ? $_POST["defaults"] : adminer_setting("defaults")), lang('Default values'), "columnShow(this.checked, 5)", "jsonly"); ?>
	<?php echo (support("comment")
		? checkbox("comments", 1, ($_POST ? $_POST["comments"] : adminer_setting("comments")), lang('Comment'), "editingCommentsClick(this, true);", "jsonly")
			. ' <input name="Comment" value="' . h($row["Comment"]) . '" data-maxlength="' . (min_version(5.5) ? 2048 : 60) . '">'
		: '')
	; ?>
	<p>
	<input type="submit" value="<?php echo lang('Save'); ?>">
	<?php } ?>

	<?php if ($TABLE != "") { ?><input type="submit" name="drop" value="<?php echo lang('Drop'); ?>"><?php echo confirm(lang('Drop %s?', $TABLE)); ?><?php } ?>
	<?php
	if (support("partitioning")) {
		$partition_table = preg_match('~RANGE|LIST~', $row["partition_by"]);
		print_fieldset("partition", lang('Partition by'), $row["partition_by"]);
		?>
	<p>
	<?php echo "<select name='partition_by'>" . optionlist(array("" => "") + $partition_by, $row["partition_by"]) . "</select>" . on_help("getTarget(event).value.replace(/./, 'PARTITION BY \$&')", 1) . script("qsl('select').onchange = partitionByChange;"); ?>
	(<input name="partition" value="<?php echo h($row["partition"]); ?>">)
	<?php echo lang('Partitions'); ?>: <input type="number" name="partitions" class="size<?php echo ($partition_table || !$row["partition_by"] ? " hidden" : ""); ?>" value="<?php echo h($row["partitions"]); ?>">
	<table cellspacing="0" id="partition-table"<?php echo ($partition_table ? "" : " class='hidden'"); ?>>
	<thead><tr><th><?php echo lang('Partition name'); ?><th><?php echo lang('Values'); ?></thead>
	<?php
	foreach ($row["partition_names"] as $key => $val) {
		echo '<tr>';
		echo '<td><input name="partition_names[]" value="' . h($val) . '" autocapitalize="off">';
		echo ($key == count($row["partition_names"]) - 1 ? script("qsl('input').oninput = partitionNameChange;") : '');
		echo '<td><input name="partition_values[]" value="' . h($row["partition_values"][$key]) . '">';
	}
	?>
	</table>
	</div></fieldset>
	<?php
	}
	?>
	<input type="hidden" name="token" value="<?php echo $token; ?>">
	</form>
	<?php echo script("qs('#form')['defaults'].onclick();" . (support("comment") ? " editingCommentsClick(qs('#form')['comments']);" : ""));

} elseif (isset($_GET["indexes"])) {
	$TABLE = $_GET["indexes"];
	$index_types = array("PRIMARY", "UNIQUE", "INDEX");
	$table_status = table_status($TABLE, true);
	if (preg_match('~MyISAM|M?aria' . (min_version(5.6, '10.0.5') ? '|InnoDB' : '') . '~i', $table_status["Engine"])) {
		$index_types[] = "FULLTEXT";
	}
	if (preg_match('~MyISAM|M?aria' . (min_version(5.7, '10.2.2') ? '|InnoDB' : '') . '~i', $table_status["Engine"])) {
		$index_types[] = "SPATIAL";
	}
	$indexes = indexes($TABLE);
	$primary = array();
	if ($jush == "mongo") { // doesn't support primary key
		$primary = $indexes["_id_"];
		unset($index_types[0]);
		unset($indexes["_id_"]);
	}
	$row = $_POST;

	if ($_POST && !$error && !$_POST["add"] && !$_POST["drop_col"]) {
		$alter = array();
		foreach ($row["indexes"] as $index) {
			$name = $index["name"];
			if (in_array($index["type"], $index_types)) {
				$columns = array();
				$lengths = array();
				$descs = array();
				$set = array();
				ksort($index["columns"]);
				foreach ($index["columns"] as $key => $column) {
					if ($column != "") {
						$length = $index["lengths"][$key];
						$desc = $index["descs"][$key];
						$set[] = idf_escape($column) . ($length ? "(" . (+$length) . ")" : "") . ($desc ? " DESC" : "");
						$columns[] = $column;
						$lengths[] = ($length ? $length : null);
						$descs[] = $desc;
					}
				}

				if ($columns) {
					$existing = $indexes[$name];
					if ($existing) {
						ksort($existing["columns"]);
						ksort($existing["lengths"]);
						ksort($existing["descs"]);
						if ($index["type"] == $existing["type"]
							&& array_values($existing["columns"]) === $columns
							&& (!$existing["lengths"] || array_values($existing["lengths"]) === $lengths)
							&& array_values($existing["descs"]) === $descs
						) {
							// skip existing index
							unset($indexes[$name]);
							continue;
						}
					}
					$alter[] = array($index["type"], $name, $set);
				}
			}
		}

		// drop removed indexes
		foreach ($indexes as $name => $existing) {
			$alter[] = array($existing["type"], $name, "DROP");
		}
		if (!$alter) {
			redirect(ME . "table=" . urlencode($TABLE));
		}
		queries_redirect(ME . "table=" . urlencode($TABLE), lang('Indexes have been altered.'), alter_indexes($TABLE, $alter));
	}

	page_header(lang('Indexes'), $error, array("table" => $TABLE), h($TABLE));

	$fields = array_keys(fields($TABLE));
	if ($_POST["add"]) {
		foreach ($row["indexes"] as $key => $index) {
			if ($index["columns"][count($index["columns"])] != "") {
				$row["indexes"][$key]["columns"][] = "";
			}
		}
		$index = end($row["indexes"]);
		if ($index["type"] || array_filter($index["columns"], 'strlen')) {
			$row["indexes"][] = array("columns" => array(1 => ""));
		}
	}
	if (!$row) {
		foreach ($indexes as $key => $index) {
			$indexes[$key]["name"] = $key;
			$indexes[$key]["columns"][] = "";
		}
		$indexes[] = array("columns" => array(1 => ""));
		$row["indexes"] = $indexes;
	}
	?>

	<form action="" method="post">
	<div class="scrollable">
	<table cellspacing="0" class="nowrap">
	<thead><tr>
	<th id="label-type"><?php echo lang('Index Type'); ?>
	<th><input type="submit" class="wayoff"><?php echo lang('Column (length)'); ?>
	<th id="label-name"><?php echo lang('Name'); ?>
	<th><noscript><?php echo "<input type='submit' class='icon' name='add[0]' value='+' title='" . lang('Add next') . "'>"; ?></noscript>
	</thead>
	<?php
	if ($primary) {
		echo "<tr><td>PRIMARY<td>";
		foreach ($primary["columns"] as $key => $column) {
			echo select_input(" disabled", $fields, $column);
			echo "<label><input disabled type='checkbox'>" . lang('descending') . "</label> ";
		}
		echo "<td><td>\n";
	}
	$j = 1;
	foreach ($row["indexes"] as $index) {
		if (!$_POST["drop_col"] || $j != key($_POST["drop_col"])) {
			echo "<tr><td>" . html_select("indexes[$j][type]", array(-1 => "") + $index_types, $index["type"], ($j == count($row["indexes"]) ? "indexesAddRow.call(this);" : 1), "label-type");

			echo "<td>";
			ksort($index["columns"]);
			$i = 1;
			foreach ($index["columns"] as $key => $column) {
				echo "<span>" . select_input(
					" name='indexes[$j][columns][$i]' title='" . lang('Column') . "'",
					($fields ? array_combine($fields, $fields) : $fields),
					$column,
					"partial(" . ($i == count($index["columns"]) ? "indexesAddColumn" : "indexesChangeColumn") . ", '" . js_escape($jush == "sql" ? "" : $_GET["indexes"] . "_") . "')"
				);
				echo ($jush == "sql" || $jush == "mssql" ? "<input type='number' name='indexes[$j][lengths][$i]' class='size' value='" . h($index["lengths"][$key]) . "' title='" . lang('Length') . "'>" : "");
				echo (support("descidx") ? checkbox("indexes[$j][descs][$i]", 1, $index["descs"][$key], lang('descending')) : "");
				echo " </span>";
				$i++;
			}

			echo "<td><input name='indexes[$j][name]' value='" . h($index["name"]) . "' autocapitalize='off' aria-labelledby='label-name'>\n";
			echo "<td><input type='submit' class='icon' name='drop_col[$j]' value='x' title='" . lang('Remove') . "'>" . script("qsl('input').onclick = partial(editingRemoveRow, 'indexes\$1[type]');");
		}
		$j++;
	}
	?>
	</table>
	</div>
	<p>
	<input type="submit" value="<?php echo lang('Save'); ?>">
	<input type="hidden" name="token" value="<?php echo $token; ?>">
	</form>
	<?php

} elseif (isset($_GET["database"])) {
	$row = $_POST;

	if ($_POST && !$error && !isset($_POST["add_x"])) { // add is an image and PHP changes add.x to add_x
		$name = trim($row["name"]);
		if ($_POST["drop"]) {
			$_GET["db"] = ""; // to save in global history
			queries_redirect(remove_from_uri("db|database"), lang('Database has been dropped.'), drop_databases(array(DB)));
		} elseif (DB !== $name) {
			// create or rename database
			if (DB != "") {
				$_GET["db"] = $name;
				queries_redirect(preg_replace('~\bdb=[^&]*&~', '', ME) . "db=" . urlencode($name), lang('Database has been renamed.'), rename_database($name, $row["collation"]));
			} else {
				$databases = explode("\n", str_replace("\r", "", $name));
				$success = true;
				$last = "";
				foreach ($databases as $db) {
					if (count($databases) == 1 || $db != "") { // ignore empty lines but always try to create single database
						if (!create_database($db, $row["collation"])) {
							$success = false;
						}
						$last = $db;
					}
				}
				restart_session();
				set_session("dbs", null);
				queries_redirect(ME . "db=" . urlencode($last), lang('Database has been created.'), $success);
			}
		} else {
			// alter database
			if (!$row["collation"]) {
				redirect(substr(ME, 0, -1));
			}
			query_redirect("ALTER DATABASE " . idf_escape($name) . (preg_match('~^[a-z0-9_]+$~i', $row["collation"]) ? " COLLATE $row[collation]" : ""), substr(ME, 0, -1), lang('Database has been altered.'));
		}
	}

	page_header(DB != "" ? lang('Alter database') : lang('Create database'), $error, array(), h(DB));

	$collations = collations();
	$name = DB;
	if ($_POST) {
		$name = $row["name"];
	} elseif (DB != "") {
		$row["collation"] = db_collation(DB, $collations);
	} elseif ($jush == "sql") {
		// propose database name with limited privileges
		foreach (get_vals("SHOW GRANTS") as $grant) {
			if (preg_match('~ ON (`(([^\\\\`]|``|\\\\.)*)%`\.\*)?~', $grant, $match) && $match[1]) {
				$name = stripcslashes(idf_unescape("`$match[2]`"));
				break;
			}
		}
	}
	?>

	<form action="" method="post">
	<p>
	<?php
	echo ($_POST["add_x"] || strpos($name, "\n")
		? '<textarea id="name" name="name" rows="10" cols="40">' . h($name) . '</textarea><br>'
		: '<input name="name" id="name" value="' . h($name) . '" data-maxlength="64" autocapitalize="off">'
	) . "\n" . ($collations ? html_select("collation", array("" => "(" . lang('collation') . ")") + $collations, $row["collation"]) . doc_link(array(
		'sql' => "charset-charsets.html",
		'mariadb' => "supported-character-sets-and-collations/",
		'mssql' => "ms187963.aspx",
	)) : "");
	echo script("focus(qs('#name'));");
	?>
	<input type="submit" value="<?php echo lang('Save'); ?>">
	<?php
	if (DB != "") {
		echo "<input type='submit' name='drop' value='" . lang('Drop') . "'>" . confirm(lang('Drop %s?', DB)) . "\n";
	} elseif (!$_POST["add_x"] && $_GET["db"] == "") {
		echo "<input type='submit' class='icon' name='add' value='+' title='" . lang('Add next') . "'>\n";
	}
	?>
	<input type="hidden" name="token" value="<?php echo $token; ?>">
	</form>
	<?php
	
} elseif (isset($_GET["scheme"])) {
	$row = $_POST;

	if ($_POST && !$error) {
		$link = preg_replace('~ns=[^&]*&~', '', ME) . "ns=";
		if ($_POST["drop"]) {
			query_redirect("DROP SCHEMA " . idf_escape($_GET["ns"]), $link, lang('Schema has been dropped.'));
		} else {
			$name = trim($row["name"]);
			$link .= urlencode($name);
			if ($_GET["ns"] == "") {
				query_redirect("CREATE SCHEMA " . idf_escape($name), $link, lang('Schema has been created.'));
			} elseif ($_GET["ns"] != $name) {
				query_redirect("ALTER SCHEMA " . idf_escape($_GET["ns"]) . " RENAME TO " . idf_escape($name), $link, lang('Schema has been altered.')); //! sp_rename in MS SQL
			} else {
				redirect($link);
			}
		}
	}

	page_header($_GET["ns"] != "" ? lang('Alter schema') : lang('Create schema'), $error);

	if (!$row) {
		$row["name"] = $_GET["ns"];
	}
	?>

	<form action="" method="post">
	<p><input name="name" id="name" value="<?php echo h($row["name"]); ?>" autocapitalize="off">
	<?php echo script("focus(qs('#name'));"); ?>
	<input type="submit" value="<?php echo lang('Save'); ?>">
	<?php
	if ($_GET["ns"] != "") {
		echo "<input type='submit' name='drop' value='" . lang('Drop') . "'>" . confirm(lang('Drop %s?', $_GET["ns"])) . "\n";
	}
	?>
	<input type="hidden" name="token" value="<?php echo $token; ?>">
	</form>
	<?php

} elseif (isset($_GET["call"])) {
	$PROCEDURE = ($_GET["name"] ? $_GET["name"] : $_GET["call"]);
	page_header(lang('Call') . ": " . h($PROCEDURE), $error);

	$routine = routine($_GET["call"], (isset($_GET["callf"]) ? "FUNCTION" : "PROCEDURE"));
	$in = array();
	$out = array();
	foreach ($routine["fields"] as $i => $field) {
		if (substr($field["inout"], -3) == "OUT") {
			$out[$i] = "@" . idf_escape($field["field"]) . " AS " . idf_escape($field["field"]);
		}
		if (!$field["inout"] || substr($field["inout"], 0, 2) == "IN") {
			$in[] = $i;
		}
	}

	if (!$error && $_POST) {
		$call = array();
		foreach ($routine["fields"] as $key => $field) {
			if (in_array($key, $in)) {
				$val = process_input($field);
				if ($val === false) {
					$val = "''";
				}
				if (isset($out[$key])) {
					$connection->query("SET @" . idf_escape($field["field"]) . " = $val");
				}
			}
			$call[] = (isset($out[$key]) ? "@" . idf_escape($field["field"]) : $val);
		}
		
		$query = (isset($_GET["callf"]) ? "SELECT" : "CALL") . " " . table($PROCEDURE) . "(" . implode(", ", $call) . ")";
		$start = microtime(true);
		$result = $connection->multi_query($query);
		$affected = $connection->affected_rows; // getting warnigns overwrites this
		echo $adminer->selectQuery($query, $start, !$result);
		
		if (!$result) {
			echo "<p class='error'>" . error() . "\n";
		} else {
			$connection2 = connect();
			if (is_object($connection2)) {
				$connection2->select_db(DB);
			}
			
			do {
				$result = $connection->store_result();
				if (is_object($result)) {
					select($result, $connection2);
				} else {
					echo "<p class='message'>" . lang('Routine has been called, %d row(s) affected.', $affected) . "\n";
				}
			} while ($connection->next_result());
			
			if ($out) {
				select($connection->query("SELECT " . implode(", ", $out)));
			}
		}
	}
	?>

	<form action="" method="post">
	<?php
	if ($in) {
		echo "<table cellspacing='0' class='layout'>\n";
		foreach ($in as $key) {
			$field = $routine["fields"][$key];
			$name = $field["field"];
			echo "<tr><th>" . $adminer->fieldName($field);
			$value = $_POST["fields"][$name];
			if ($value != "") {
				if ($field["type"] == "enum") {
					$value = +$value;
				}
				if ($field["type"] == "set") {
					$value = array_sum($value);
				}
			}
			input($field, $value, (string) $_POST["function"][$name]); // param name can be empty
			echo "\n";
		}
		echo "</table>\n";
	}
	?>
	<p>
	<input type="submit" value="<?php echo lang('Call'); ?>">
	<input type="hidden" name="token" value="<?php echo $token; ?>">
	</form>
	<?php
} elseif (isset($_GET["foreign"])) {
	$TABLE = $_GET["foreign"];
	$name = $_GET["name"];
	$row = $_POST;

	if ($_POST && !$error && !$_POST["add"] && !$_POST["change"] && !$_POST["change-js"]) {
		$message = ($_POST["drop"] ? lang('Foreign key has been dropped.') : ($name != "" ? lang('Foreign key has been altered.') : lang('Foreign key has been created.')));
		$location = ME . "table=" . urlencode($TABLE);
		
		if (!$_POST["drop"]) {
			$row["source"] = array_filter($row["source"], 'strlen');
			ksort($row["source"]); // enforce input order
			$target = array();
			foreach ($row["source"] as $key => $val) {
				$target[$key] = $row["target"][$key];
			}
			$row["target"] = $target;
		}
		
		if ($jush == "sqlite") {
			queries_redirect($location, $message, recreate_table($TABLE, $TABLE, array(), array(), array(" $name" => ($_POST["drop"] ? "" : " " . format_foreign_key($row)))));
		} else {
			$alter = "ALTER TABLE " . table($TABLE);
			$drop = "\nDROP " . ($jush == "sql" ? "FOREIGN KEY " : "CONSTRAINT ") . idf_escape($name);
			if ($_POST["drop"]) {
				query_redirect($alter . $drop, $location, $message);
			} else {
				query_redirect($alter . ($name != "" ? "$drop," : "") . "\nADD" . format_foreign_key($row), $location, $message);
				$error = lang('Source and target columns must have the same data type, there must be an index on the target columns and referenced data must exist.') . "<br>$error"; //! no partitioning
			}
		}
	}

	page_header(lang('Foreign key'), $error, array("table" => $TABLE), h($TABLE));

	if ($_POST) {
		ksort($row["source"]);
		if ($_POST["add"]) {
			$row["source"][] = "";
		} elseif ($_POST["change"] || $_POST["change-js"]) {
			$row["target"] = array();
		}
	} elseif ($name != "") {
		$foreign_keys = foreign_keys($TABLE);
		$row = $foreign_keys[$name];
		$row["source"][] = "";
	} else {
		$row["table"] = $TABLE;
		$row["source"] = array("");
	}
	?>

	<form action="" method="post">
	<?php
	$source = array_keys(fields($TABLE)); //! no text and blob
	if ($row["db"] != "") {
		$connection->select_db($row["db"]);
	}
	if ($row["ns"] != "") {
		set_schema($row["ns"]);
	}
	$referencable = array_keys(array_filter(table_status('', true), 'fk_support'));
	$target = ($TABLE === $row["table"] ? $source : array_keys(fields(in_array($row["table"], $referencable) ? $row["table"] : reset($referencable))));
	$onchange = "this.form['change-js'].value = '1'; this.form.submit();";
	echo "<p>" . lang('Target table') . ": " . html_select("table", $referencable, $row["table"], $onchange) . "\n";
	if ($jush == "pgsql") {
		echo lang('Schema') . ": " . html_select("ns", $adminer->schemas(), $row["ns"] != "" ? $row["ns"] : $_GET["ns"], $onchange);
	} elseif ($jush != "sqlite") {
		$dbs = array();
		foreach ($adminer->databases() as $db) {
			if (!information_schema($db)) {
				$dbs[] = $db;
			}
		}
		echo lang('DB') . ": " . html_select("db", $dbs, $row["db"] != "" ? $row["db"] : $_GET["db"], $onchange);
	}
	?>
	<input type="hidden" name="change-js" value="">
	<noscript><p><input type="submit" name="change" value="<?php echo lang('Change'); ?>"></noscript>
	<table cellspacing="0">
	<thead><tr><th id="label-source"><?php echo lang('Source'); ?><th id="label-target"><?php echo lang('Target'); ?></thead>
	<?php
	$j = 0;
	foreach ($row["source"] as $key => $val) {
		echo "<tr>";
		echo "<td>" . html_select("source[" . (+$key) . "]", array(-1 => "") + $source, $val, ($j == count($row["source"]) - 1 ? "foreignAddRow.call(this);" : 1), "label-source");
		echo "<td>" . html_select("target[" . (+$key) . "]", $target, $row["target"][$key], 1, "label-target");
		$j++;
	}
	?>
	</table>
	<p>
	<?php echo lang('ON DELETE'); ?>: <?php echo html_select("on_delete", array(-1 => "") + explode("|", $on_actions), $row["on_delete"]); ?>
	<?php echo lang('ON UPDATE'); ?>: <?php echo html_select("on_update", array(-1 => "") + explode("|", $on_actions), $row["on_update"]); ?>
	<?php echo doc_link(array(
		'sql' => "innodb-foreign-key-constraints.html",
		'mariadb' => "foreign-keys/",
		'pgsql' => "sql-createtable.html#SQL-CREATETABLE-REFERENCES",
		'mssql' => "ms174979.aspx",
		'oracle' => "https://docs.oracle.com/cd/B19306_01/server.102/b14200/clauses002.htm#sthref2903",
	)); ?>
	<p>
	<input type="submit" value="<?php echo lang('Save'); ?>">
	<noscript><p><input type="submit" name="add" value="<?php echo lang('Add column'); ?>"></noscript>
	<?php if ($name != "") { ?><input type="submit" name="drop" value="<?php echo lang('Drop'); ?>"><?php echo confirm(lang('Drop %s?', $name)); ?><?php } ?>
	<input type="hidden" name="token" value="<?php echo $token; ?>">
	</form>
	<?php

} elseif (isset($_GET["view"])) {
	$TABLE = $_GET["view"];
	$row = $_POST;
	$orig_type = "VIEW";
	if ($jush == "pgsql" && $TABLE != "") {
		$status = table_status($TABLE);
		$orig_type = strtoupper($status["Engine"]);
	}

	if ($_POST && !$error) {
		$name = trim($row["name"]);
		$as = " AS\n$row[select]";
		$location = ME . "table=" . urlencode($name);
		$message = lang('View has been altered.');

		$type = ($_POST["materialized"] ? "MATERIALIZED VIEW" : "VIEW");

		if (!$_POST["drop"] && $TABLE == $name && $jush != "sqlite" && $type == "VIEW" && $orig_type == "VIEW") {
			query_redirect(($jush == "mssql" ? "ALTER" : "CREATE OR REPLACE") . " VIEW " . table($name) . $as, $location, $message);
		} else {
			$temp_name = $name . "_adminer_" . uniqid();
			drop_create(
				"DROP $orig_type " . table($TABLE),
				"CREATE $type " . table($name) . $as,
				"DROP $type " . table($name),
				"CREATE $type " . table($temp_name) . $as,
				"DROP $type " . table($temp_name),
				($_POST["drop"] ? substr(ME, 0, -1) : $location),
				lang('View has been dropped.'),
				$message,
				lang('View has been created.'),
				$TABLE,
				$name
			);
		}
	}

	if (!$_POST && $TABLE != "") {
		$row = view($TABLE);
		$row["name"] = $TABLE;
		$row["materialized"] = ($orig_type != "VIEW");
		if (!$error) {
			$error = error();
		}
	}

	page_header(($TABLE != "" ? lang('Alter view') : lang('Create view')), $error, array("table" => $TABLE), h($TABLE));
	?>

	<form action="" method="post">
	<p><?php echo lang('Name'); ?>: <input name="name" value="<?php echo h($row["name"]); ?>" data-maxlength="64" autocapitalize="off">
	<?php echo (support("materializedview") ? " " . checkbox("materialized", 1, $row["materialized"], lang('Materialized view')) : ""); ?>
	<p><?php textarea("select", $row["select"]); ?>
	<p>
	<input type="submit" value="<?php echo lang('Save'); ?>">
	<?php if ($TABLE != "") { ?><input type="submit" name="drop" value="<?php echo lang('Drop'); ?>"><?php echo confirm(lang('Drop %s?', $TABLE)); ?><?php } ?>
	<input type="hidden" name="token" value="<?php echo $token; ?>">
	</form>
	<?php

} elseif (isset($_GET["event"])) {
	$EVENT = $_GET["event"];
	$intervals = array("YEAR", "QUARTER", "MONTH", "DAY", "HOUR", "MINUTE", "WEEK", "SECOND", "YEAR_MONTH", "DAY_HOUR", "DAY_MINUTE", "DAY_SECOND", "HOUR_MINUTE", "HOUR_SECOND", "MINUTE_SECOND");
	$statuses = array("ENABLED" => "ENABLE", "DISABLED" => "DISABLE", "SLAVESIDE_DISABLED" => "DISABLE ON SLAVE");
	$row = $_POST;

	if ($_POST && !$error) {
		if ($_POST["drop"]) {
			query_redirect("DROP EVENT " . idf_escape($EVENT), substr(ME, 0, -1), lang('Event has been dropped.'));
		} elseif (in_array($row["INTERVAL_FIELD"], $intervals) && isset($statuses[$row["STATUS"]])) {
			$schedule = "\nON SCHEDULE " . ($row["INTERVAL_VALUE"]
				? "EVERY " . q($row["INTERVAL_VALUE"]) . " $row[INTERVAL_FIELD]"
				. ($row["STARTS"] ? " STARTS " . q($row["STARTS"]) : "")
				. ($row["ENDS"] ? " ENDS " . q($row["ENDS"]) : "") //! ALTER EVENT doesn't drop ENDS - MySQL bug #39173
				: "AT " . q($row["STARTS"])
				) . " ON COMPLETION" . ($row["ON_COMPLETION"] ? "" : " NOT") . " PRESERVE"
			;
			
			queries_redirect(substr(ME, 0, -1), ($EVENT != "" ? lang('Event has been altered.') : lang('Event has been created.')), queries(($EVENT != ""
				? "ALTER EVENT " . idf_escape($EVENT) . $schedule
				. ($EVENT != $row["EVENT_NAME"] ? "\nRENAME TO " . idf_escape($row["EVENT_NAME"]) : "")
				: "CREATE EVENT " . idf_escape($row["EVENT_NAME"]) . $schedule
				) . "\n" . $statuses[$row["STATUS"]] . " COMMENT " . q($row["EVENT_COMMENT"])
				. rtrim(" DO\n$row[EVENT_DEFINITION]", ";") . ";"
			));
		}
	}

	page_header(($EVENT != "" ? lang('Alter event') . ": " . h($EVENT) : lang('Create event')), $error);

	if (!$row && $EVENT != "") {
		$rows = get_rows("SELECT * FROM information_schema.EVENTS WHERE EVENT_SCHEMA = " . q(DB) . " AND EVENT_NAME = " . q($EVENT));
		$row = reset($rows);
	}
	?>

	<form action="" method="post">
	<table cellspacing="0" class="layout">
	<tr><th><?php echo lang('Name'); ?><td><input name="EVENT_NAME" value="<?php echo h($row["EVENT_NAME"]); ?>" data-maxlength="64" autocapitalize="off">
	<tr><th title="datetime"><?php echo lang('Start'); ?><td><input name="STARTS" value="<?php echo h("$row[EXECUTE_AT]$row[STARTS]"); ?>">
	<tr><th title="datetime"><?php echo lang('End'); ?><td><input name="ENDS" value="<?php echo h($row["ENDS"]); ?>">
	<tr><th><?php echo lang('Every'); ?><td><input type="number" name="INTERVAL_VALUE" value="<?php echo h($row["INTERVAL_VALUE"]); ?>" class="size"> <?php echo html_select("INTERVAL_FIELD", $intervals, $row["INTERVAL_FIELD"]); ?>
	<tr><th><?php echo lang('Status'); ?><td><?php echo html_select("STATUS", $statuses, $row["STATUS"]); ?>
	<tr><th><?php echo lang('Comment'); ?><td><input name="EVENT_COMMENT" value="<?php echo h($row["EVENT_COMMENT"]); ?>" data-maxlength="64">
	<tr><th><td><?php echo checkbox("ON_COMPLETION", "PRESERVE", $row["ON_COMPLETION"] == "PRESERVE", lang('On completion preserve')); ?>
	</table>
	<p><?php textarea("EVENT_DEFINITION", $row["EVENT_DEFINITION"]); ?>
	<p>
	<input type="submit" value="<?php echo lang('Save'); ?>">
	<?php if ($EVENT != "") { ?><input type="submit" name="drop" value="<?php echo lang('Drop'); ?>"><?php echo confirm(lang('Drop %s?', $EVENT)); ?><?php } ?>
	<input type="hidden" name="token" value="<?php echo $token; ?>">
	</form>
	<?php

} elseif (isset($_GET["procedure"])) {
	$PROCEDURE = ($_GET["name"] ? $_GET["name"] : $_GET["procedure"]);
	$routine = (isset($_GET["function"]) ? "FUNCTION" : "PROCEDURE");
	$row = $_POST;
	$row["fields"] = (array) $row["fields"];

	if ($_POST && !process_fields($row["fields"]) && !$error) {
		$orig = routine($_GET["procedure"], $routine);
		$temp_name = "$row[name]_adminer_" . uniqid();
		drop_create(
			"DROP $routine " . routine_id($PROCEDURE, $orig),
			create_routine($routine, $row),
			"DROP $routine " . routine_id($row["name"], $row),
			create_routine($routine, array("name" => $temp_name) + $row),
			"DROP $routine " . routine_id($temp_name, $row),
			substr(ME, 0, -1),
			lang('Routine has been dropped.'),
			lang('Routine has been altered.'),
			lang('Routine has been created.'),
			$PROCEDURE,
			$row["name"]
		);
	}

	page_header(($PROCEDURE != "" ? (isset($_GET["function"]) ? lang('Alter function') : lang('Alter procedure')) . ": " . h($PROCEDURE) : (isset($_GET["function"]) ? lang('Create function') : lang('Create procedure'))), $error);

	if (!$_POST && $PROCEDURE != "") {
		$row = routine($_GET["procedure"], $routine);
		$row["name"] = $PROCEDURE;
	}

	$collations = get_vals("SHOW CHARACTER SET");
	sort($collations);
	$routine_languages = routine_languages();
	?>

	<form action="" method="post" id="form">
	<p><?php echo lang('Name'); ?>: <input name="name" value="<?php echo h($row["name"]); ?>" data-maxlength="64" autocapitalize="off">
	<?php echo ($routine_languages ? lang('Language') . ": " . html_select("language", $routine_languages, $row["language"]) . "\n" : ""); ?>
	<input type="submit" value="<?php echo lang('Save'); ?>">
	<div class="scrollable">
	<table cellspacing="0" class="nowrap">
	<?php
	edit_fields($row["fields"], $collations, $routine);
	if (isset($_GET["function"])) {
		echo "<tr><td>" . lang('Return type');
		edit_type("returns", $row["returns"], $collations, array(), ($jush == "pgsql" ? array("void", "trigger") : array()));
	}
	?>
	</table>
	</div>
	<p><?php textarea("definition", $row["definition"]); ?>
	<p>
	<input type="submit" value="<?php echo lang('Save'); ?>">
	<?php if ($PROCEDURE != "") { ?><input type="submit" name="drop" value="<?php echo lang('Drop'); ?>"><?php echo confirm(lang('Drop %s?', $PROCEDURE)); ?><?php } ?>
	<input type="hidden" name="token" value="<?php echo $token; ?>">
	</form>
	<?php

} elseif (isset($_GET["sequence"])) {
	$SEQUENCE = $_GET["sequence"];
	$row = $_POST;

	if ($_POST && !$error) {
		$link = substr(ME, 0, -1);
		$name = trim($row["name"]);
		if ($_POST["drop"]) {
			query_redirect("DROP SEQUENCE " . idf_escape($SEQUENCE), $link, lang('Sequence has been dropped.'));
		} elseif ($SEQUENCE == "") {
			query_redirect("CREATE SEQUENCE " . idf_escape($name), $link, lang('Sequence has been created.'));
		} elseif ($SEQUENCE != $name) {
			query_redirect("ALTER SEQUENCE " . idf_escape($SEQUENCE) . " RENAME TO " . idf_escape($name), $link, lang('Sequence has been altered.'));
		} else {
			redirect($link);
		}
	}

	page_header($SEQUENCE != "" ? lang('Alter sequence') . ": " . h($SEQUENCE) : lang('Create sequence'), $error);

	if (!$row) {
		$row["name"] = $SEQUENCE;
	}
	?>

	<form action="" method="post">
	<p><input name="name" value="<?php echo h($row["name"]); ?>" autocapitalize="off">
	<input type="submit" value="<?php echo lang('Save'); ?>">
	<?php
	if ($SEQUENCE != "") {
		echo "<input type='submit' name='drop' value='" . lang('Drop') . "'>" . confirm(lang('Drop %s?', $SEQUENCE)) . "\n";
	}
	?>
	<input type="hidden" name="token" value="<?php echo $token; ?>">
	</form>
	<?php

} elseif (isset($_GET["type"])) {
	$TYPE = $_GET["type"];
	$row = $_POST;

	if ($_POST && !$error) {
		$link = substr(ME, 0, -1);
		if ($_POST["drop"]) {
			query_redirect("DROP TYPE " . idf_escape($TYPE), $link, lang('Type has been dropped.'));
		} else {
			query_redirect("CREATE TYPE " . idf_escape(trim($row["name"])) . " $row[as]", $link, lang('Type has been created.'));
		}
	}

	page_header($TYPE != "" ? lang('Alter type') . ": " . h($TYPE) : lang('Create type'), $error);

	if (!$row) {
		$row["as"] = "AS ";
	}
	?>

	<form action="" method="post">
	<p>
	<?php
	if ($TYPE != "") {
		echo "<input type='submit' name='drop' value='" . lang('Drop') . "'>" . confirm(lang('Drop %s?', $TYPE)) . "\n";
	} else {
		echo "<input name='name' value='" . h($row['name']) . "' autocapitalize='off'>\n";
		textarea("as", $row["as"]);
		echo "<p><input type='submit' value='" . lang('Save') . "'>\n";
	}
	?>
	<input type="hidden" name="token" value="<?php echo $token; ?>">
	</form>
	<?php

} elseif (isset($_GET["trigger"])) {
	$TABLE = $_GET["trigger"];
	$name = $_GET["name"];
	$trigger_options = trigger_options();
	$row = (array) trigger($name) + array("Trigger" => $TABLE . "_bi");

	if ($_POST) {
		if (!$error && in_array($_POST["Timing"], $trigger_options["Timing"]) && in_array($_POST["Event"], $trigger_options["Event"]) && in_array($_POST["Type"], $trigger_options["Type"])) {
			// don't use drop_create() because there may not be more triggers for the same action
			$on = " ON " . table($TABLE);
			$drop = "DROP TRIGGER " . idf_escape($name) . ($jush == "pgsql" ? $on : "");
			$location = ME . "table=" . urlencode($TABLE);
			if ($_POST["drop"]) {
				query_redirect($drop, $location, lang('Trigger has been dropped.'));
			} else {
				if ($name != "") {
					queries($drop);
				}
				queries_redirect(
					$location,
					($name != "" ? lang('Trigger has been altered.') : lang('Trigger has been created.')),
					queries(create_trigger($on, $_POST))
				);
				if ($name != "") {
					queries(create_trigger($on, $row + array("Type" => reset($trigger_options["Type"]))));
				}
			}
		}
		$row = $_POST;
	}

	page_header(($name != "" ? lang('Alter trigger') . ": " . h($name) : lang('Create trigger')), $error, array("table" => $TABLE));
	?>

	<form action="" method="post" id="form">
	<table cellspacing="0" class="layout">
	<tr><th><?php echo lang('Time'); ?><td><?php echo html_select("Timing", $trigger_options["Timing"], $row["Timing"], "triggerChange(/^" . preg_quote($TABLE, "/") . "_[ba][iud]$/, '" . js_escape($TABLE) . "', this.form);"); ?>
	<tr><th><?php echo lang('Event'); ?><td><?php echo html_select("Event", $trigger_options["Event"], $row["Event"], "this.form['Timing'].onchange();"); ?>
	<?php echo (in_array("UPDATE OF", $trigger_options["Event"]) ? " <input name='Of' value='" . h($row["Of"]) . "' class='hidden'>": ""); ?>
	<tr><th><?php echo lang('Type'); ?><td><?php echo html_select("Type", $trigger_options["Type"], $row["Type"]); ?>
	</table>
	<p><?php echo lang('Name'); ?>: <input name="Trigger" value="<?php echo h($row["Trigger"]); ?>" data-maxlength="64" autocapitalize="off">
	<?php echo script("qs('#form')['Timing'].onchange();"); ?>
	<p><?php textarea("Statement", $row["Statement"]); ?>
	<p>
	<input type="submit" value="<?php echo lang('Save'); ?>">
	<?php if ($name != "") { ?><input type="submit" name="drop" value="<?php echo lang('Drop'); ?>"><?php echo confirm(lang('Drop %s?', $name)); ?><?php } ?>
	<input type="hidden" name="token" value="<?php echo $token; ?>">
	</form>
	<?php

} elseif (isset($_GET["user"])) {
	$USER = $_GET["user"];
	$privileges = array("" => array("All privileges" => ""));
	foreach (get_rows("SHOW PRIVILEGES") as $row) {
		foreach (explode(",", ($row["Privilege"] == "Grant option" ? "" : $row["Context"])) as $context) {
			$privileges[$context][$row["Privilege"]] = $row["Comment"];
		}
	}
	$privileges["Server Admin"] += $privileges["File access on server"];
	$privileges["Databases"]["Create routine"] = $privileges["Procedures"]["Create routine"]; // MySQL bug #30305
	unset($privileges["Procedures"]["Create routine"]);
	$privileges["Columns"] = array();
	foreach (array("Select", "Insert", "Update", "References") as $val) {
		$privileges["Columns"][$val] = $privileges["Tables"][$val];
	}
	unset($privileges["Server Admin"]["Usage"]);
	foreach ($privileges["Tables"] as $key => $val) {
		unset($privileges["Databases"][$key]);
	}

	$new_grants = array();
	if ($_POST) {
		foreach ($_POST["objects"] as $key => $val) {
			$new_grants[$val] = (array) $new_grants[$val] + (array) $_POST["grants"][$key];
		}
	}
	$grants = array();
	$old_pass = "";

	if (isset($_GET["host"]) && ($result = $connection->query("SHOW GRANTS FOR " . q($USER) . "@" . q($_GET["host"])))) { //! use information_schema for MySQL 5 - column names in column privileges are not escaped
		while ($row = $result->fetch_row()) {
			if (preg_match('~GRANT (.*) ON (.*) TO ~', $row[0], $match) && preg_match_all('~ *([^(,]*[^ ,(])( *\([^)]+\))?~', $match[1], $matches, PREG_SET_ORDER)) { //! escape the part between ON and TO
				foreach ($matches as $val) {
					if ($val[1] != "USAGE") {
						$grants["$match[2]$val[2]"][$val[1]] = true;
					}
					if (preg_match('~ WITH GRANT OPTION~', $row[0])) { //! don't check inside strings and identifiers
						$grants["$match[2]$val[2]"]["GRANT OPTION"] = true;
					}
				}
			}
			if (preg_match("~ IDENTIFIED BY PASSWORD '([^']+)~", $row[0], $match)) {
				$old_pass = $match[1];
			}
		}
	}

	if ($_POST && !$error) {
		$old_user = (isset($_GET["host"]) ? q($USER) . "@" . q($_GET["host"]) : "''");
		if ($_POST["drop"]) {
			query_redirect("DROP USER $old_user", ME . "privileges=", lang('User has been dropped.'));
		} else {
			$new_user = q($_POST["user"]) . "@" . q($_POST["host"]); // if $_GET["host"] is not set then $new_user is always different
			$pass = $_POST["pass"];
			if ($pass != '' && !$_POST["hashed"] && !min_version(8)) {
				// compute hash in a separate query so that plain text password is not saved to history
				$pass = $connection->result("SELECT PASSWORD(" . q($pass) . ")");
				$error = !$pass;
			}

			$created = false;
			if (!$error) {
				if ($old_user != $new_user) {
					$created = queries((min_version(5) ? "CREATE USER" : "GRANT USAGE ON *.* TO") . " $new_user IDENTIFIED BY " . (min_version(8) ? "" : "PASSWORD ") . q($pass));
					$error = !$created;
				} elseif ($pass != $old_pass) {
					queries("SET PASSWORD FOR $new_user = " . q($pass));
				}
			}

			if (!$error) {
				$revoke = array();
				foreach ($new_grants as $object => $grant) {
					if (isset($_GET["grant"])) {
						$grant = array_filter($grant);
					}
					$grant = array_keys($grant);
					if (isset($_GET["grant"])) {
						// no rights to mysql.user table
						$revoke = array_diff(array_keys(array_filter($new_grants[$object], 'strlen')), $grant);
					} elseif ($old_user == $new_user) {
						$old_grant = array_keys((array) $grants[$object]);
						$revoke = array_diff($old_grant, $grant);
						$grant = array_diff($grant, $old_grant);
						unset($grants[$object]);
					}
					if (preg_match('~^(.+)\s*(\(.*\))?$~U', $object, $match) && (
						!grant("REVOKE", $revoke, $match[2], " ON $match[1] FROM $new_user") //! SQL injection
						|| !grant("GRANT", $grant, $match[2], " ON $match[1] TO $new_user")
					)) {
						$error = true;
						break;
					}
				}
			}

			if (!$error && isset($_GET["host"])) {
				if ($old_user != $new_user) {
					queries("DROP USER $old_user");
				} elseif (!isset($_GET["grant"])) {
					foreach ($grants as $object => $revoke) {
						if (preg_match('~^(.+)(\(.*\))?$~U', $object, $match)) {
							grant("REVOKE", array_keys($revoke), $match[2], " ON $match[1] FROM $new_user");
						}
					}
				}
			}

			queries_redirect(ME . "privileges=", (isset($_GET["host"]) ? lang('User has been altered.') : lang('User has been created.')), !$error);

			if ($created) {
				// delete new user in case of an error
				$connection->query("DROP USER $new_user");
			}
		}
	}

	page_header((isset($_GET["host"]) ? lang('Username') . ": " . h("$USER@$_GET[host]") : lang('Create user')), $error, array("privileges" => array('', lang('Privileges'))));

	if ($_POST) {
		$row = $_POST;
		$grants = $new_grants;
	} else {
		$row = $_GET + array("host" => $connection->result("SELECT SUBSTRING_INDEX(CURRENT_USER, '@', -1)")); // create user on the same domain by default
		$row["pass"] = $old_pass;
		if ($old_pass != "") {
			$row["hashed"] = true;
		}
		$grants[(DB == "" || $grants ? "" : idf_escape(addcslashes(DB, "%_\\"))) . ".*"] = array();
	}

	?>
	<form action="" method="post">
	<table cellspacing="0" class="layout">
	<tr><th><?php echo lang('Server'); ?><td><input name="host" data-maxlength="60" value="<?php echo h($row["host"]); ?>" autocapitalize="off">
	<tr><th><?php echo lang('Username'); ?><td><input name="user" data-maxlength="80" value="<?php echo h($row["user"]); ?>" autocapitalize="off">
	<tr><th><?php echo lang('Password'); ?><td><input name="pass" id="pass" value="<?php echo h($row["pass"]); ?>" autocomplete="new-password">
	<?php if (!$row["hashed"]) { echo script("typePassword(qs('#pass'));"); } ?>
	<?php echo (min_version(8) ? "" : checkbox("hashed", 1, $row["hashed"], lang('Hashed'), "typePassword(this.form['pass'], this.checked);")); ?>
	</table>

	<?php
	//! MAX_* limits, REQUIRE
	echo "<table cellspacing='0'>\n";
	echo "<thead><tr><th colspan='2'>" . lang('Privileges') . doc_link(array('sql' => "grant.html#priv_level"));
	$i = 0;
	foreach ($grants as $object => $grant) {
		echo '<th>' . ($object != "*.*" ? "<input name='objects[$i]' value='" . h($object) . "' size='10' autocapitalize='off'>" : "<input type='hidden' name='objects[$i]' value='*.*' size='10'>*.*"); //! separate db, table, columns, PROCEDURE|FUNCTION, routine
		$i++;
	}
	echo "</thead>\n";

	foreach (array(
		"" => "",
		"Server Admin" => lang('Server'),
		"Databases" => lang('Database'),
		"Tables" => lang('Table'),
		"Columns" => lang('Column'),
		"Procedures" => lang('Routine'),
	) as $context => $desc) {
		foreach ((array) $privileges[$context] as $privilege => $comment) {
			echo "<tr" . odd() . "><td" . ($desc ? ">$desc<td" : " colspan='2'") . ' lang="en" title="' . h($comment) . '">' . h($privilege);
			$i = 0;
			foreach ($grants as $object => $grant) {
				$name = "'grants[$i][" . h(strtoupper($privilege)) . "]'";
				$value = $grant[strtoupper($privilege)];
				if ($context == "Server Admin" && $object != (isset($grants["*.*"]) ? "*.*" : ".*")) {
					echo "<td>";
				} elseif (isset($_GET["grant"])) {
					echo "<td><select name=$name><option><option value='1'" . ($value ? " selected" : "") . ">" . lang('Grant') . "<option value='0'" . ($value == "0" ? " selected" : "") . ">" . lang('Revoke') . "</select>";
				} else {
					echo "<td align='center'><label class='block'>";
					echo "<input type='checkbox' name=$name value='1'" . ($value ? " checked" : "") . ($privilege == "All privileges"
						? " id='grants-$i-all'>" //! uncheck all except grant if all is checked
						: ">" . ($privilege == "Grant option" ? "" : script("qsl('input').onclick = function () { if (this.checked) formUncheck('grants-$i-all'); };")));
					echo "</label>";
				}
				$i++;
			}
		}
	}

	echo "</table>\n";
	?>
	<p>
	<input type="submit" value="<?php echo lang('Save'); ?>">
	<?php if (isset($_GET["host"])) { ?><input type="submit" name="drop" value="<?php echo lang('Drop'); ?>"><?php echo confirm(lang('Drop %s?', "$USER@$_GET[host]")); ?><?php } ?>
	<input type="hidden" name="token" value="<?php echo $token; ?>">
	</form>
	<?php

} elseif (isset($_GET["processlist"])) {
	if (support("kill") && $_POST && !$error) {
		$killed = 0;
		foreach ((array) $_POST["kill"] as $val) {
			if (kill_process($val)) {
				$killed++;
			}
		}
		queries_redirect(ME . "processlist=", lang('%d process(es) have been killed.', $killed), $killed || !$_POST["kill"]);
	}
	
	page_header(lang('Process list'), $error);
	?>
	
	<form action="" method="post">
	<div class="scrollable">
	<table cellspacing="0" class="nowrap checkable">
	<?php
	echo script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});");
	// HTML valid because there is always at least one process
	$i = -1;
	foreach (process_list() as $i => $row) {
	
		if (!$i) {
			echo "<thead><tr lang='en'>" . (support("kill") ? "<th>" : "");
			foreach ($row as $key => $val) {
				echo "<th>$key" . doc_link(array(
					'sql' => "show-processlist.html#processlist_" . strtolower($key),
					'pgsql' => "monitoring-stats.html#PG-STAT-ACTIVITY-VIEW",
					'oracle' => "REFRN30223",
				));
			}
			echo "</thead>\n";
		}
		echo "<tr" . odd() . ">" . (support("kill") ? "<td>" . checkbox("kill[]", $row[$jush == "sql" ? "Id" : "pid"], 0) : "");
		foreach ($row as $key => $val) {
			echo "<td>" . (
				($jush == "sql" && $key == "Info" && preg_match("~Query|Killed~", $row["Command"]) && $val != "") ||
				($jush == "pgsql" && $key == "current_query" && $val != "<IDLE>") ||
				($jush == "oracle" && $key == "sql_text" && $val != "")
				? "<code class='jush-$jush'>" . shorten_utf8($val, 100, "</code>") . ' <a href="' . h(ME . ($row["db"] != "" ? "db=" . urlencode($row["db"]) . "&" : "") . "sql=" . urlencode($val)) . '">' . lang('Clone') . '</a>'
				: h($val)
			);
		}
		echo "\n";
	}
	?>
	</table>
	</div>
	<p>
	<?php
	if (support("kill")) {
		echo ($i + 1) . "/" . lang('%d in total', max_connections());
		echo "<p><input type='submit' value='" . lang('Kill') . "'>\n";
	}
	?>
	<input type="hidden" name="token" value="<?php echo $token; ?>">
	</form>
	<?php echo script("tableCheck();");
		
} elseif (isset($_GET["select"])) {
	$TABLE = $_GET["select"];
	$table_status = table_status1($TABLE);
	$indexes = indexes($TABLE);
	$fields = fields($TABLE);
	$foreign_keys = column_foreign_keys($TABLE);
	$oid = $table_status["Oid"];
	parse_str($_COOKIE["adminer_import"], $adminer_import);

	$rights = array(); // privilege => 0
	$columns = array(); // selectable columns
	$text_length = null;
	foreach ($fields as $key => $field) {
		$name = $adminer->fieldName($field);
		if (isset($field["privileges"]["select"]) && $name != "") {
			$columns[$key] = html_entity_decode(strip_tags($name), ENT_QUOTES);
			if (is_shortable($field)) {
				$text_length = $adminer->selectLengthProcess();
			}
		}
		$rights += $field["privileges"];
	}

	list($select, $group) = $adminer->selectColumnsProcess($columns, $indexes);
	$is_group = count($group) < count($select);
	$where = $adminer->selectSearchProcess($fields, $indexes);
	$order = $adminer->selectOrderProcess($fields, $indexes);
	$limit = $adminer->selectLimitProcess();

	if ($_GET["val"] && is_ajax()) {
		header("Content-Type: text/plain; charset=utf-8");
		foreach ($_GET["val"] as $unique_idf => $row) {
			$as = convert_field($fields[key($row)]);
			$select = array($as ? $as : idf_escape(key($row)));
			$where[] = where_check($unique_idf, $fields);
			$return = $driver->select($TABLE, $select, $where, $select);
			if ($return) {
				echo reset($return->fetch_row());
			}
		}
		exit;
	}

	$primary = $unselected = null;
	foreach ($indexes as $index) {
		if ($index["type"] == "PRIMARY") {
			$primary = array_flip($index["columns"]);
			$unselected = ($select ? $primary : array());
			foreach ($unselected as $key => $val) {
				if (in_array(idf_escape($key), $select)) {
					unset($unselected[$key]);
				}
			}
			break;
		}
	}
	if ($oid && !$primary) {
		$primary = $unselected = array($oid => 0);
		$indexes[] = array("type" => "PRIMARY", "columns" => array($oid));
	}

	if ($_POST && !$error) {
		$where_check = $where;
		if (!$_POST["all"] && is_array($_POST["check"])) {
			$checks = array();
			foreach ($_POST["check"] as $check) {
				$checks[] = where_check($check, $fields);
			}
			$where_check[] = "((" . implode(") OR (", $checks) . "))";
		}
		$where_check = ($where_check ? "\nWHERE " . implode(" AND ", $where_check) : "");
		if ($_POST["export"]) {
			cookie("adminer_import", "output=" . urlencode($_POST["output"]) . "&format=" . urlencode($_POST["format"]));
			dump_headers($TABLE);
			$adminer->dumpTable($TABLE, "");
			$from = ($select ? implode(", ", $select) : "*")
				. convert_fields($columns, $fields, $select)
				. "\nFROM " . table($TABLE);
			$group_by = ($group && $is_group ? "\nGROUP BY " . implode(", ", $group) : "") . ($order ? "\nORDER BY " . implode(", ", $order) : "");
			if (!is_array($_POST["check"]) || $primary) {
				$query = "SELECT $from$where_check$group_by";
			} else {
				$union = array();
				foreach ($_POST["check"] as $val) {
					// where is not unique so OR can't be used
					$union[] = "(SELECT" . limit($from, "\nWHERE " . ($where ? implode(" AND ", $where) . " AND " : "") . where_check($val, $fields) . $group_by, 1) . ")";
				}
				$query = implode(" UNION ALL ", $union);
			}
			$adminer->dumpData($TABLE, "table", $query);
			exit;
		}

		if (!$adminer->selectEmailProcess($where, $foreign_keys)) {
			if ($_POST["save"] || $_POST["delete"]) { // edit
				$result = true;
				$affected = 0;
				$set = array();
				if (!$_POST["delete"]) {
					foreach ($columns as $name => $val) { //! should check also for edit or insert privileges
						$val = process_input($fields[$name]);
						if ($val !== null && ($_POST["clone"] || $val !== false)) {
							$set[idf_escape($name)] = ($val !== false ? $val : idf_escape($name));
						}
					}
				}
				if ($_POST["delete"] || $set) {
					if ($_POST["clone"]) {
						$query = "INTO " . table($TABLE) . " (" . implode(", ", array_keys($set)) . ")\nSELECT " . implode(", ", $set) . "\nFROM " . table($TABLE);
					}
					if ($_POST["all"] || ($primary && is_array($_POST["check"])) || $is_group) {
						$result = ($_POST["delete"]
							? $driver->delete($TABLE, $where_check)
							: ($_POST["clone"]
								? queries("INSERT $query$where_check")
								: $driver->update($TABLE, $set, $where_check)
							)
						);
						$affected = $connection->affected_rows;
					} else {
						foreach ((array) $_POST["check"] as $val) {
							// where is not unique so OR can't be used
							$where2 = "\nWHERE " . ($where ? implode(" AND ", $where) . " AND " : "") . where_check($val, $fields);
							$result = ($_POST["delete"]
								? $driver->delete($TABLE, $where2, 1)
								: ($_POST["clone"]
									? queries("INSERT" . limit1($TABLE, $query, $where2))
									: $driver->update($TABLE, $set, $where2, 1)
								)
							);
							if (!$result) {
								break;
							}
							$affected += $connection->affected_rows;
						}
					}
				}
				$message = lang('%d item(s) have been affected.', $affected);
				if ($_POST["clone"] && $result && $affected == 1) {
					$last_id = last_id();
					if ($last_id) {
						$message = lang('Item%s has been inserted.', " $last_id");
					}
				}
				queries_redirect(remove_from_uri($_POST["all"] && $_POST["delete"] ? "page" : ""), $message, $result);
				if (!$_POST["delete"]) {
					edit_form($TABLE, $fields, (array) $_POST["fields"], !$_POST["clone"]);
					page_footer();
					exit;
				}

			} elseif (!$_POST["import"]) { // modify
				if (!$_POST["val"]) {
					$error = lang('Ctrl+click on a value to modify it.');
				} else {
					$result = true;
					$affected = 0;
					foreach ($_POST["val"] as $unique_idf => $row) {
						$set = array();
						foreach ($row as $key => $val) {
							$key = bracket_escape($key, 1); // 1 - back
							$set[idf_escape($key)] = (preg_match('~char|text~', $fields[$key]["type"]) || $val != "" ? $adminer->processInput($fields[$key], $val) : "NULL");
						}
						$result = $driver->update(
							$TABLE,
							$set,
							" WHERE " . ($where ? implode(" AND ", $where) . " AND " : "") . where_check($unique_idf, $fields),
							!$is_group && !$primary,
							" "
						);
						if (!$result) {
							break;
						}
						$affected += $connection->affected_rows;
					}
					queries_redirect(remove_from_uri(), lang('%d item(s) have been affected.', $affected), $result);
				}

			} elseif (!is_string($file = get_file("csv_file", true))) {
				$error = upload_error($file);
			} elseif (!preg_match('~~u', $file)) {
				$error = lang('File must be in UTF-8 encoding.');
			} else {
				cookie("adminer_import", "output=" . urlencode($adminer_import["output"]) . "&format=" . urlencode($_POST["separator"]));
				$result = true;
				$cols = array_keys($fields);
				preg_match_all('~(?>"[^"]*"|[^"\r\n]+)+~', $file, $matches);
				$affected = count($matches[0]);
				$driver->begin();
				$separator = ($_POST["separator"] == "csv" ? "," : ($_POST["separator"] == "tsv" ? "\t" : ";"));
				$rows = array();
				foreach ($matches[0] as $key => $val) {
					preg_match_all("~((?>\"[^\"]*\")+|[^$separator]*)$separator~", $val . $separator, $matches2);
					if (!$key && !array_diff($matches2[1], $cols)) { //! doesn't work with column names containing ",\n
						// first row corresponds to column names - use it for table structure
						$cols = $matches2[1];
						$affected--;
					} else {
						$set = array();
						foreach ($matches2[1] as $i => $col) {
							$set[idf_escape($cols[$i])] = ($col == "" && $fields[$cols[$i]]["null"] ? "NULL" : q(str_replace('""', '"', preg_replace('~^"|"$~', '', $col))));
						}
						$rows[] = $set;
					}
				}
				$result = (!$rows || $driver->insertUpdate($TABLE, $rows, $primary));
				if ($result) {
					$result = $driver->commit();
				}
				queries_redirect(remove_from_uri("page"), lang('%d row(s) have been imported.', $affected), $result);
				$driver->rollback(); // after queries_redirect() to not overwrite error

			}
		}
	}

	$table_name = $adminer->tableName($table_status);
	if (is_ajax()) {
		page_headers();
		ob_start();
	} else {
		page_header(lang('Select') . ": $table_name", $error);
	}

	$set = null;
	if (isset($rights["insert"]) || !support("table")) {
		$set = "";
		foreach ((array) $_GET["where"] as $val) {
			if ($foreign_keys[$val["col"]] && count($foreign_keys[$val["col"]]) == 1 && ($val["op"] == "="
				|| (!$val["op"] && !preg_match('~[_%]~', $val["val"])) // LIKE in Editor
			)) {
				$set .= "&set" . urlencode("[" . bracket_escape($val["col"]) . "]") . "=" . urlencode($val["val"]);
			}
		}
	}
	$adminer->selectLinks($table_status, $set);

	if (!$columns && support("table")) {
		echo "<p class='error'>" . lang('Unable to select the table') . ($fields ? "." : ": " . error()) . "\n";
	} else {
		echo "<form action='' id='form'>\n";
		echo "<div style='display: none;'>";
		hidden_fields_get();
		echo (DB != "" ? '<input type="hidden" name="db" value="' . h(DB) . '">' . (isset($_GET["ns"]) ? '<input type="hidden" name="ns" value="' . h($_GET["ns"]) . '">' : "") : ""); // not used in Editor
		echo '<input type="hidden" name="select" value="' . h($TABLE) . '">';
		echo "</div>\n";
		$adminer->selectColumnsPrint($select, $columns);
		$adminer->selectSearchPrint($where, $columns, $indexes);
		$adminer->selectOrderPrint($order, $columns, $indexes);
		$adminer->selectLimitPrint($limit);
		$adminer->selectLengthPrint($text_length);
		$adminer->selectActionPrint($indexes);
		echo "</form>\n";

		$page = $_GET["page"];
		if ($page == "last") {
			$found_rows = $connection->result(count_rows($TABLE, $where, $is_group, $group));
			$page = floor(max(0, $found_rows - 1) / $limit);
		}

		$select2 = $select;
		$group2 = $group;
		if (!$select2) {
			$select2[] = "*";
			$convert_fields = convert_fields($columns, $fields, $select);
			if ($convert_fields) {
				$select2[] = substr($convert_fields, 2);
			}
		}
		foreach ($select as $key => $val) {
			$field = $fields[idf_unescape($val)];
			if ($field && ($as = convert_field($field))) {
				$select2[$key] = "$as AS $val";
			}
		}
		if (!$is_group && $unselected) {
			foreach ($unselected as $key => $val) {
				$select2[] = idf_escape($key);
				if ($group2) {
					$group2[] = idf_escape($key);
				}
			}
		}
		$result = $driver->select($TABLE, $select2, $where, $group2, $order, $limit, $page, true);

		if (!$result) {
			echo "<p class='error'>" . error() . "\n";
		} else {
			if ($jush == "mssql" && $page) {
				$result->seek($limit * $page);
			}
			$email_fields = array();
			echo "<form action='' method='post' enctype='multipart/form-data'>\n";
			$rows = array();
			while ($row = $result->fetch_assoc()) {
				if ($page && $jush == "oracle") {
					unset($row["RNUM"]);
				}
				$rows[] = $row;
			}

			// use count($rows) without LIMIT, COUNT(*) without grouping, FOUND_ROWS otherwise (slowest)
			if ($_GET["page"] != "last" && $limit != "" && $group && $is_group && $jush == "sql") {
				$found_rows = $connection->result(" SELECT FOUND_ROWS()"); // space to allow mysql.trace_mode
			}

			if (!$rows) {
				echo "<p class='message'>" . lang('No rows.') . "\n";
			} else {
				$backward_keys = $adminer->backwardKeys($TABLE, $table_name);

				echo "<div class='scrollable'>";
				echo "<table id='table' cellspacing='0' class='nowrap checkable'>";
				echo script("mixin(qs('#table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true), onkeydown: editingKeydown});");
				echo "<thead><tr>" . (!$group && $select
					? ""
					: "<td><input type='checkbox' id='all-page' class='jsonly'>" . script("qs('#all-page').onclick = partial(formCheck, /check/);", "")
						. " <a href='" . h($_GET["modify"] ? remove_from_uri("modify") : $_SERVER["REQUEST_URI"] . "&modify=1") . "'>" . lang('Modify') . "</a>");
				$names = array();
				$functions = array();
				reset($select);
				$rank = 1;
				foreach ($rows[0] as $key => $val) {
					if (!isset($unselected[$key])) {
						$val = $_GET["columns"][key($select)];
						$field = $fields[$select ? ($val ? $val["col"] : current($select)) : $key];
						$name = ($field ? $adminer->fieldName($field, $rank) : ($val["fun"] ? "*" : $key));
						if ($name != "") {
							$rank++;
							$names[$key] = $name;
							$column = idf_escape($key);
							$href = remove_from_uri('(order|desc)[^=]*|page') . '&order%5B0%5D=' . urlencode($key);
							$desc = "&desc%5B0%5D=1";
							echo "<th>" . script("mixin(qsl('th'), {onmouseover: partial(columnMouse), onmouseout: partial(columnMouse, ' hidden')});", "");
							echo '<a href="' . h($href . ($order[0] == $column || $order[0] == $key || (!$order && $is_group && $group[0] == $column) ? $desc : '')) . '">'; // $order[0] == $key - COUNT(*)
							echo apply_sql_function($val["fun"], $name) . "</a>"; //! columns looking like functions
							echo "<span class='column hidden'>";
							echo "<a href='" . h($href . $desc) . "' title='" . lang('descending') . "' class='text'> ↓</a>";
							if (!$val["fun"]) {
								echo '<a href="#fieldset-search" title="' . lang('Search') . '" class="text jsonly"> =</a>';
								echo script("qsl('a').onclick = partial(selectSearch, '" . js_escape($key) . "');");
							}
							echo "</span>";
						}
						$functions[$key] = $val["fun"];
						next($select);
					}
				}

				$lengths = array();
				if ($_GET["modify"]) {
					foreach ($rows as $row) {
						foreach ($row as $key => $val) {
							$lengths[$key] = max($lengths[$key], min(40, strlen(utf8_decode($val))));
						}
					}
				}

				echo ($backward_keys ? "<th>" . lang('Relations') : "") . "</thead>\n";

				if (is_ajax()) {
					if ($limit % 2 == 1 && $page % 2 == 1) {
						odd();
					}
					ob_end_clean();
				}

				foreach ($adminer->rowDescriptions($rows, $foreign_keys) as $n => $row) {
					$unique_array = unique_array($rows[$n], $indexes);
					if (!$unique_array) {
						$unique_array = array();
						foreach ($rows[$n] as $key => $val) {
							if (!preg_match('~^(COUNT\((\*|(DISTINCT )?`(?:[^`]|``)+`)\)|(AVG|GROUP_CONCAT|MAX|MIN|SUM)\(`(?:[^`]|``)+`\))$~', $key)) { //! columns looking like functions
								$unique_array[$key] = $val;
							}
						}
					}
					$unique_idf = "";
					foreach ($unique_array as $key => $val) {
						if (($jush == "sql" || $jush == "pgsql") && preg_match('~char|text|enum|set~', $fields[$key]["type"]) && strlen($val) > 64) {
							$key = (strpos($key, '(') ? $key : idf_escape($key)); //! columns looking like functions
							$key = "MD5(" . ($jush != 'sql' || preg_match("~^utf8~", $fields[$key]["collation"]) ? $key : "CONVERT($key USING " . charset($connection) . ")") . ")";
							$val = md5($val);
						}
						$unique_idf .= "&" . ($val !== null ? urlencode("where[" . bracket_escape($key) . "]") . "=" . urlencode($val) : "null%5B%5D=" . urlencode($key));
					}
					echo "<tr" . odd() . ">" . (!$group && $select ? "" : "<td>"
						. checkbox("check[]", substr($unique_idf, 1), in_array(substr($unique_idf, 1), (array) $_POST["check"]))
						. ($is_group || information_schema(DB) ? "" : " <a href='" . h(ME . "edit=" . urlencode($TABLE) . $unique_idf) . "' class='edit'>" . lang('edit') . "</a>")
					);

					foreach ($row as $key => $val) {
						if (isset($names[$key])) {
							$field = $fields[$key];
							$val = $driver->value($val, $field);
							if ($val != "" && (!isset($email_fields[$key]) || $email_fields[$key] != "")) {
								$email_fields[$key] = (is_mail($val) ? $names[$key] : ""); //! filled e-mails can be contained on other pages
							}

							$link = "";
							if (preg_match('~blob|bytea|raw|file~', $field["type"]) && $val != "") {
								$link = ME . 'download=' . urlencode($TABLE) . '&field=' . urlencode($key) . $unique_idf;
							}
							if (!$link && $val !== null) { // link related items
								foreach ((array) $foreign_keys[$key] as $foreign_key) {
									if (count($foreign_keys[$key]) == 1 || end($foreign_key["source"]) == $key) {
										$link = "";
										foreach ($foreign_key["source"] as $i => $source) {
											$link .= where_link($i, $foreign_key["target"][$i], $rows[$n][$source]);
										}
										$link = ($foreign_key["db"] != "" ? preg_replace('~([?&]db=)[^&]+~', '\1' . urlencode($foreign_key["db"]), ME) : ME) . 'select=' . urlencode($foreign_key["table"]) . $link; // InnoDB supports non-UNIQUE keys
										if ($foreign_key["ns"]) {
											$link = preg_replace('~([?&]ns=)[^&]+~', '\1' . urlencode($foreign_key["ns"]), $link);
										}
										if (count($foreign_key["source"]) == 1) {
											break;
										}
									}
								}
							}
							if ($key == "COUNT(*)") { //! columns looking like functions
								$link = ME . "select=" . urlencode($TABLE);
								$i = 0;
								foreach ((array) $_GET["where"] as $v) {
									if (!array_key_exists($v["col"], $unique_array)) {
										$link .= where_link($i++, $v["col"], $v["val"], $v["op"]);
									}
								}
								foreach ($unique_array as $k => $v) {
									$link .= where_link($i++, $k, $v);
								}
							}
							
							$val = select_value($val, $link, $field, $text_length);
							$id = h("val[$unique_idf][" . bracket_escape($key) . "]");
							$value = $_POST["val"][$unique_idf][bracket_escape($key)];
							$editable = !is_array($row[$key]) && is_utf8($val) && $rows[$n][$key] == $row[$key] && !$functions[$key];
							$text = preg_match('~text|lob~', $field["type"]);
							echo "<td id='$id'";
							if (($_GET["modify"] && $editable) || $value !== null) {
								$h_value = h($value !== null ? $value : $row[$key]);
								echo ">" . ($text ? "<textarea name='$id' cols='30' rows='" . (substr_count($row[$key], "\n") + 1) . "'>$h_value</textarea>" : "<input name='$id' value='$h_value' size='$lengths[$key]'>");
							} else {
								$long = strpos($val, "<i>…</i>");
								echo " data-text='" . ($long ? 2 : ($text ? 1 : 0)) . "'"
									. ($editable ? "" : " data-warning='" . h(lang('Use edit link to modify this value.')) . "'")
									. ">$val</td>"
								;
							}
						}
					}

					if ($backward_keys) {
						echo "<td>";
					}
					$adminer->backwardKeysPrint($backward_keys, $rows[$n]);
					echo "</tr>\n"; // close to allow white-space: pre
				}

				if (is_ajax()) {
					exit;
				}
				echo "</table>\n";
				echo "</div>\n";
			}

			if (!is_ajax()) {
				if ($rows || $page) {
					$exact_count = true;
					if ($_GET["page"] != "last") {
						if ($limit == "" || (count($rows) < $limit && ($rows || !$page))) {
							$found_rows = ($page ? $page * $limit : 0) + count($rows);
						} elseif ($jush != "sql" || !$is_group) {
							$found_rows = ($is_group ? false : found_rows($table_status, $where));
							if ($found_rows < max(1e4, 2 * ($page + 1) * $limit)) {
								// slow with big tables
								$found_rows = reset(slow_query(count_rows($TABLE, $where, $is_group, $group)));
							} else {
								$exact_count = false;
							}
						}
					}

					$pagination = ($limit != "" && ($found_rows === false || $found_rows > $limit || $page));
					if ($pagination) {
						echo (($found_rows === false ? count($rows) + 1 : $found_rows - $page * $limit) > $limit
							? '<p><a href="' . h(remove_from_uri("page") . "&page=" . ($page + 1)) . '" class="loadmore">' . lang('Load more data') . '</a>'
								. script("qsl('a').onclick = partial(selectLoadMore, " . (+$limit) . ", '" . lang('Loading') . "…');", "")
							: ''
						);
						echo "\n";
					}
				}
				
				echo "<div class='footer'><div>\n";
				if ($rows || $page) {
					if ($pagination) {
						// display first, previous 4, next 4 and last page
						$max_page = ($found_rows === false
							? $page + (count($rows) >= $limit ? 2 : 1)
							: floor(($found_rows - 1) / $limit)
						);
						echo "<fieldset>";
						if ($jush != "simpledb") {
							echo "<legend><a href='" . h(remove_from_uri("page")) . "'>" . lang('Page') . "</a></legend>";
							echo script("qsl('a').onclick = function () { pageClick(this.href, +prompt('" . lang('Page') . "', '" . ($page + 1) . "')); return false; };");
							echo pagination(0, $page) . ($page > 5 ? " …" : "");
							for ($i = max(1, $page - 4); $i < min($max_page, $page + 5); $i++) {
								echo pagination($i, $page);
							}
							if ($max_page > 0) {
								echo ($page + 5 < $max_page ? " …" : "");
								echo ($exact_count && $found_rows !== false
									? pagination($max_page, $page)
									: " <a href='" . h(remove_from_uri("page") . "&page=last") . "' title='~$max_page'>" . lang('last') . "</a>"
								);
							}
						} else {
							echo "<legend>" . lang('Page') . "</legend>";
							echo pagination(0, $page) . ($page > 1 ? " …" : "");
							echo ($page ? pagination($page, $page) : "");
							echo ($max_page > $page ? pagination($page + 1, $page) . ($max_page > $page + 1 ? " …" : "") : "");
						}
						echo "</fieldset>\n";
					}
					
					echo "<fieldset>";
					echo "<legend>" . lang('Whole result') . "</legend>";
					$display_rows = ($exact_count ? "" : "~ ") . $found_rows;
					echo checkbox("all", 1, 0, ($found_rows !== false ? ($exact_count ? "" : "~ ") . lang('%d row(s)', $found_rows) : ""), "var checked = formChecked(this, /check/); selectCount('selected', this.checked ? '$display_rows' : checked); selectCount('selected2', this.checked || !checked ? '$display_rows' : checked);") . "\n";
					echo "</fieldset>\n";

					if ($adminer->selectCommandPrint()) {
						?>
	<fieldset<?php echo ($_GET["modify"] ? '' : ' class="jsonly"'); ?>><legend><?php echo lang('Modify'); ?></legend><div>
	<input type="submit" value="<?php echo lang('Save'); ?>"<?php echo ($_GET["modify"] ? '' : ' title="' . lang('Ctrl+click on a value to modify it.') . '"'); ?>>
	</div></fieldset>
	<fieldset><legend><?php echo lang('Selected'); ?> <span id="selected"></span></legend><div>
	<input type="submit" name="edit" value="<?php echo lang('Edit'); ?>">
	<input type="submit" name="clone" value="<?php echo lang('Clone'); ?>">
	<input type="submit" name="delete" value="<?php echo lang('Delete'); ?>"><?php echo confirm(); ?>
	</div></fieldset>
	<?php
					}

					$format = $adminer->dumpFormat();
					foreach ((array) $_GET["columns"] as $column) {
						if ($column["fun"]) {
							unset($format['sql']);
							break;
						}
					}
					if ($format) {
						print_fieldset("export", lang('Export') . " <span id='selected2'></span>");
						$output = $adminer->dumpOutput();
						echo ($output ? html_select("output", $output, $adminer_import["output"]) . " " : "");
						echo html_select("format", $format, $adminer_import["format"]);
						echo " <input type='submit' name='export' value='" . lang('Export') . "'>\n";
						echo "</div></fieldset>\n";
					}

					$adminer->selectEmailPrint(array_filter($email_fields, 'strlen'), $columns);
				}

				echo "</div></div>\n";

				if ($adminer->selectImportPrint()) {
					echo "<div>";
					echo "<a href='#import'>" . lang('Import') . "</a>";
					echo script("qsl('a').onclick = partial(toggle, 'import');", "");
					echo "<span id='import' class='hidden'>: ";
					echo "<input type='file' name='csv_file'> ";
					echo html_select("separator", array("csv" => "CSV,", "csv;" => "CSV;", "tsv" => "TSV"), $adminer_import["format"], 1); // 1 - select
					echo " <input type='submit' name='import' value='" . lang('Import') . "'>";
					echo "</span>";
					echo "</div>";
				}

				echo "<input type='hidden' name='token' value='$token'>\n";
				echo "</form>\n";
				echo (!$group && $select ? "" : script("tableCheck();"));
			}
		}
	}

	if (is_ajax()) {
		ob_end_clean();
		exit;
	}

} elseif (isset($_GET["variables"])) {
	$status = isset($_GET["status"]);
	page_header($status ? lang('Status') : lang('Variables'));

	$variables = ($status ? show_status() : show_variables());
	if (!$variables) {
		echo "<p class='message'>" . lang('No rows.') . "\n";
	} else {
		echo "<table cellspacing='0'>\n";
		foreach ($variables as $key => $val) {
			echo "<tr>";
			echo "<th><code class='jush-" . $jush . ($status ? "status" : "set") . "'>" . h($key) . "</code>";
			echo "<td>" . h($val);
		}
		echo "</table>\n";
	}

} elseif (isset($_GET["script"])) {

	header("Content-Type: text/javascript; charset=utf-8");

	if ($_GET["script"] == "db") {
		$sums = array("Data_length" => 0, "Index_length" => 0, "Data_free" => 0);
		foreach (table_status() as $name => $table_status) {
			json_row("Comment-$name", h($table_status["Comment"]));
			if (!is_view($table_status)) {
				foreach (array("Engine", "Collation") as $key) {
					json_row("$key-$name", h($table_status[$key]));
				}
				foreach ($sums + array("Auto_increment" => 0, "Rows" => 0) as $key => $val) {
					if ($table_status[$key] != "") {
						$val = format_number($table_status[$key]);
						json_row("$key-$name", ($key == "Rows" && $val && $table_status["Engine"] == ($sql == "pgsql" ? "table" : "InnoDB")
							? "~ $val"
							: $val
						));
						if (isset($sums[$key])) {
							// ignore innodb_file_per_table because it is not active for tables created before it was enabled
							$sums[$key] += ($table_status["Engine"] != "InnoDB" || $key != "Data_free" ? $table_status[$key] : 0);
						}
					} elseif (array_key_exists($key, $table_status)) {
						json_row("$key-$name");
					}
				}
			}
		}
		foreach ($sums as $key => $val) {
			json_row("sum-$key", format_number($val));
		}
		json_row("");

	} elseif ($_GET["script"] == "kill") {
		$connection->query("KILL " . number($_POST["kill"]));

	} else { // connect
		foreach (count_tables($adminer->databases()) as $db => $val) {
			json_row("tables-$db", $val);
			json_row("size-$db", db_size($db));
		}
		json_row("");
	}

	exit; // don't print footer

} else {
	$tables_views = array_merge((array) $_POST["tables"], (array) $_POST["views"]);

	if ($tables_views && !$error && !$_POST["search"]) {
		$result = true;
		$message = "";
		if ($jush == "sql" && $_POST["tables"] && count($_POST["tables"]) > 1 && ($_POST["drop"] || $_POST["truncate"] || $_POST["copy"])) {
			queries("SET foreign_key_checks = 0"); // allows to truncate or drop several tables at once
		}

		if ($_POST["truncate"]) {
			if ($_POST["tables"]) {
				$result = truncate_tables($_POST["tables"]);
			}
			$message = lang('Tables have been truncated.');
		} elseif ($_POST["move"]) {
			$result = move_tables((array) $_POST["tables"], (array) $_POST["views"], $_POST["target"]);
			$message = lang('Tables have been moved.');
		} elseif ($_POST["copy"]) {
			$result = copy_tables((array) $_POST["tables"], (array) $_POST["views"], $_POST["target"]);
			$message = lang('Tables have been copied.');
		} elseif ($_POST["drop"]) {
			if ($_POST["views"]) {
				$result = drop_views($_POST["views"]);
			}
			if ($result && $_POST["tables"]) {
				$result = drop_tables($_POST["tables"]);
			}
			$message = lang('Tables have been dropped.');
		} elseif ($jush != "sql") {
			$result = ($jush == "sqlite"
				? queries("VACUUM")
				: apply_queries("VACUUM" . ($_POST["optimize"] ? "" : " ANALYZE"), $_POST["tables"])
			);
			$message = lang('Tables have been optimized.');
		} elseif (!$_POST["tables"]) {
			$message = lang('No tables.');
		} elseif ($result = queries(($_POST["optimize"] ? "OPTIMIZE" : ($_POST["check"] ? "CHECK" : ($_POST["repair"] ? "REPAIR" : "ANALYZE"))) . " TABLE " . implode(", ", array_map('idf_escape', $_POST["tables"])))) {
			while ($row = $result->fetch_assoc()) {
				$message .= "<b>" . h($row["Table"]) . "</b>: " . h($row["Msg_text"]) . "<br>";
			}
		}

		queries_redirect(substr(ME, 0, -1), $message, $result);
	}

	page_header(($_GET["ns"] == "" ? lang('Database') . ": " . h(DB) : lang('Schema') . ": " . h($_GET["ns"])), $error, true);

	if ($adminer->homepage()) {
		if ($_GET["ns"] !== "") {
			echo "<h3 id='tables-views'>" . lang('Tables and views') . "</h3>\n";
			$tables_list = tables_list();
			if (!$tables_list) {
				echo "<p class='message'>" . lang('No tables.') . "\n";
			} else {
				echo "<form action='' method='post'>\n";
				if (support("table")) {
					echo "<fieldset><legend>" . lang('Search data in tables') . " <span id='selected2'></span></legend><div>";
					echo "<input type='search' name='query' value='" . h($_POST["query"]) . "'>";
					echo script("qsl('input').onkeydown = partialArg(bodyKeydown, 'search');", "");
					echo " <input type='submit' name='search' value='" . lang('Search') . "'>\n";
					echo "</div></fieldset>\n";
					if ($_POST["search"] && $_POST["query"] != "") {
						$_GET["where"][0]["op"] = "LIKE %%";
						search_tables();
					}
				}
				echo "<div class='scrollable'>\n";
				echo "<table cellspacing='0' class='nowrap checkable'>\n";
				echo script("mixin(qsl('table'), {onclick: tableClick, ondblclick: partialArg(tableClick, true)});");
				echo '<thead><tr class="wrap">';
				echo '<td><input id="check-all" type="checkbox" class="jsonly">' . script("qs('#check-all').onclick = partial(formCheck, /^(tables|views)\[/);", "");
				echo '<th>' . lang('Table');
				echo '<td>' . lang('Engine') . doc_link(array('sql' => 'storage-engines.html'));
				echo '<td>' . lang('Collation') . doc_link(array('sql' => 'charset-charsets.html', 'mariadb' => 'supported-character-sets-and-collations/'));
				echo '<td>' . lang('Data Length') . doc_link(array('sql' => 'show-table-status.html', 'pgsql' => 'functions-admin.html#FUNCTIONS-ADMIN-DBOBJECT', 'oracle' => 'REFRN20286'));
				echo '<td>' . lang('Index Length') . doc_link(array('sql' => 'show-table-status.html', 'pgsql' => 'functions-admin.html#FUNCTIONS-ADMIN-DBOBJECT'));
				echo '<td>' . lang('Data Free') . doc_link(array('sql' => 'show-table-status.html'));
				echo '<td>' . lang('Auto Increment') . doc_link(array('sql' => 'example-auto-increment.html', 'mariadb' => 'auto_increment/'));
				echo '<td>' . lang('Rows') . doc_link(array('sql' => 'show-table-status.html', 'pgsql' => 'catalog-pg-class.html#CATALOG-PG-CLASS', 'oracle' => 'REFRN20286'));
				echo (support("comment") ? '<td>' . lang('Comment') . doc_link(array('sql' => 'show-table-status.html', 'pgsql' => 'functions-info.html#FUNCTIONS-INFO-COMMENT-TABLE')) : '');
				echo "</thead>\n";

				$tables = 0;
				foreach ($tables_list as $name => $type) {
					$view = ($type !== null && !preg_match('~table~i', $type));
					$id = h("Table-" . $name);
					echo '<tr' . odd() . '><td>' . checkbox(($view ? "views[]" : "tables[]"), $name, in_array($name, $tables_views, true), "", "", "", $id);
					echo '<th>' . (support("table") || support("indexes") ? "<a href='" . h(ME) . "table=" . urlencode($name) . "' title='" . lang('Show structure') . "' id='$id'>" . h($name) . '</a>' : h($name));
					if ($view) {
						echo '<td colspan="6"><a href="' . h(ME) . "view=" . urlencode($name) . '" title="' . lang('Alter view') . '">' . (preg_match('~materialized~i', $type) ? lang('Materialized view') : lang('View')) . '</a>';
						echo '<td align="right"><a href="' . h(ME) . "select=" . urlencode($name) . '" title="' . lang('Select data') . '">?</a>';
					} else {
						foreach (array(
							"Engine" => array(),
							"Collation" => array(),
							"Data_length" => array("create", lang('Alter table')),
							"Index_length" => array("indexes", lang('Alter indexes')),
							"Data_free" => array("edit", lang('New item')),
							"Auto_increment" => array("auto_increment=1&create", lang('Alter table')),
							"Rows" => array("select", lang('Select data')),
						) as $key => $link) {
							$id = " id='$key-" . h($name) . "'";
							echo ($link ? "<td align='right'>" . (support("table") || $key == "Rows" || (support("indexes") && $key != "Data_length")
								? "<a href='" . h(ME . "$link[0]=") . urlencode($name) . "'$id title='$link[1]'>?</a>"
								: "<span$id>?</span>"
							) : "<td id='$key-" . h($name) . "'>");
						}
						$tables++;
					}
					echo (support("comment") ? "<td id='Comment-" . h($name) . "'>" : "");
				}

				echo "<tr><td><th>" . lang('%d in total', count($tables_list));
				echo "<td>" . h($jush == "sql" ? $connection->result("SELECT @@storage_engine") : "");
				echo "<td>" . h(db_collation(DB, collations()));
				foreach (array("Data_length", "Index_length", "Data_free") as $key) {
					echo "<td align='right' id='sum-$key'>";
				}

				echo "</table>\n";
				echo "</div>\n";
				if (!information_schema(DB)) {
					echo "<div class='footer'><div>\n";
					$vacuum = "<input type='submit' value='" . lang('Vacuum') . "'> " . on_help("'VACUUM'");
					$optimize = "<input type='submit' name='optimize' value='" . lang('Optimize') . "'> " . on_help($jush == "sql" ? "'OPTIMIZE TABLE'" : "'VACUUM OPTIMIZE'");
					echo "<fieldset><legend>" . lang('Selected') . " <span id='selected'></span></legend><div>"
					. ($jush == "sqlite" ? $vacuum
					: ($jush == "pgsql" ? $vacuum . $optimize
					: ($jush == "sql" ? "<input type='submit' value='" . lang('Analyze') . "'> " . on_help("'ANALYZE TABLE'") . $optimize
						. "<input type='submit' name='check' value='" . lang('Check') . "'> " . on_help("'CHECK TABLE'")
						. "<input type='submit' name='repair' value='" . lang('Repair') . "'> " . on_help("'REPAIR TABLE'")
					: "")))
					. "<input type='submit' name='truncate' value='" . lang('Truncate') . "'> " . on_help($jush == "sqlite" ? "'DELETE'" : "'TRUNCATE" . ($jush == "pgsql" ? "'" : " TABLE'")) . confirm()
					. "<input type='submit' name='drop' value='" . lang('Drop') . "'>" . on_help("'DROP TABLE'") . confirm() . "\n";
					$databases = (support("scheme") ? $adminer->schemas() : $adminer->databases());
					if (count($databases) != 1 && $jush != "sqlite") {
						$db = (isset($_POST["target"]) ? $_POST["target"] : (support("scheme") ? $_GET["ns"] : DB));
						echo "<p>" . lang('Move to other database') . ": ";
						echo ($databases ? html_select("target", $databases, $db) : '<input name="target" value="' . h($db) . '" autocapitalize="off">');
						echo " <input type='submit' name='move' value='" . lang('Move') . "'>";
						echo (support("copy") ? " <input type='submit' name='copy' value='" . lang('Copy') . "'> " . checkbox("overwrite", 1, $_POST["overwrite"], lang('overwrite')) : "");
						echo "\n";
					}
					echo "<input type='hidden' name='all' value=''>"; // used by trCheck()
					echo script("qsl('input').onclick = function () { selectCount('selected', formChecked(this, /^(tables|views)\[/));" . (support("table") ? " selectCount('selected2', formChecked(this, /^tables\[/) || $tables);" : "") . " }");
					echo "<input type='hidden' name='token' value='$token'>\n";
					echo "</div></fieldset>\n";
					echo "</div></div>\n";
				}
				echo "</form>\n";
				echo script("tableCheck();");
			}

			echo '<p class="links"><a href="' . h(ME) . 'create=">' . lang('Create table') . "</a>\n";
			echo (support("view") ? '<a href="' . h(ME) . 'view=">' . lang('Create view') . "</a>\n" : "");

			if (support("routine")) {
				echo "<h3 id='routines'>" . lang('Routines') . "</h3>\n";
				$routines = routines();
				if ($routines) {
					echo "<table cellspacing='0'>\n";
					echo '<thead><tr><th>' . lang('Name') . '<td>' . lang('Type') . '<td>' . lang('Return type') . "<td></thead>\n";
					odd('');
					foreach ($routines as $row) {
						$name = ($row["SPECIFIC_NAME"] == $row["ROUTINE_NAME"] ? "" : "&name=" . urlencode($row["ROUTINE_NAME"])); // not computed on the pages to be able to print the header first
						echo '<tr' . odd() . '>';
						echo '<th><a href="' . h(ME . ($row["ROUTINE_TYPE"] != "PROCEDURE" ? 'callf=' : 'call=') . urlencode($row["SPECIFIC_NAME"]) . $name) . '">' . h($row["ROUTINE_NAME"]) . '</a>';
						echo '<td>' . h($row["ROUTINE_TYPE"]);
						echo '<td>' . h($row["DTD_IDENTIFIER"]);
						echo '<td><a href="' . h(ME . ($row["ROUTINE_TYPE"] != "PROCEDURE" ? 'function=' : 'procedure=') . urlencode($row["SPECIFIC_NAME"]) . $name) . '">' . lang('Alter') . "</a>";
					}
					echo "</table>\n";
				}
				echo '<p class="links">'
					. (support("procedure") ? '<a href="' . h(ME) . 'procedure=">' . lang('Create procedure') . '</a>' : '')
					. '<a href="' . h(ME) . 'function=">' . lang('Create function') . "</a>\n"
				;
			}

			if (support("sequence")) {
				echo "<h3 id='sequences'>" . lang('Sequences') . "</h3>\n";
				$sequences = get_vals("SELECT sequence_name FROM information_schema.sequences WHERE sequence_schema = current_schema() ORDER BY sequence_name");
				if ($sequences) {
					echo "<table cellspacing='0'>\n";
					echo "<thead><tr><th>" . lang('Name') . "</thead>\n";
					odd('');
					foreach ($sequences as $val) {
						echo "<tr" . odd() . "><th><a href='" . h(ME) . "sequence=" . urlencode($val) . "'>" . h($val) . "</a>\n";
					}
					echo "</table>\n";
				}
				echo "<p class='links'><a href='" . h(ME) . "sequence='>" . lang('Create sequence') . "</a>\n";
			}

			if (support("type")) {
				echo "<h3 id='user-types'>" . lang('User types') . "</h3>\n";
				$user_types = types();
				if ($user_types) {
					echo "<table cellspacing='0'>\n";
					echo "<thead><tr><th>" . lang('Name') . "</thead>\n";
					odd('');
					foreach ($user_types as $val) {
						echo "<tr" . odd() . "><th><a href='" . h(ME) . "type=" . urlencode($val) . "'>" . h($val) . "</a>\n";
					}
					echo "</table>\n";
				}
				echo "<p class='links'><a href='" . h(ME) . "type='>" . lang('Create type') . "</a>\n";
			}

			if (support("event")) {
				echo "<h3 id='events'>" . lang('Events') . "</h3>\n";
				$rows = get_rows("SHOW EVENTS");
				if ($rows) {
					echo "<table cellspacing='0'>\n";
					echo "<thead><tr><th>" . lang('Name') . "<td>" . lang('Schedule') . "<td>" . lang('Start') . "<td>" . lang('End') . "<td></thead>\n";
					foreach ($rows as $row) {
						echo "<tr>";
						echo "<th>" . h($row["Name"]);
						echo "<td>" . ($row["Execute at"] ? lang('At given time') . "<td>" . $row["Execute at"] : lang('Every') . " " . $row["Interval value"] . " " . $row["Interval field"] . "<td>$row[Starts]");
						echo "<td>$row[Ends]";
						echo '<td><a href="' . h(ME) . 'event=' . urlencode($row["Name"]) . '">' . lang('Alter') . '</a>';
					}
					echo "</table>\n";
					$event_scheduler = $connection->result("SELECT @@event_scheduler");
					if ($event_scheduler && $event_scheduler != "ON") {
						echo "<p class='error'><code class='jush-sqlset'>event_scheduler</code>: " . h($event_scheduler) . "\n";
					}
				}
				echo '<p class="links"><a href="' . h(ME) . 'event=">' . lang('Create event') . "</a>\n";
			}

			if ($tables_list) {
				echo script("ajaxSetHtml('" . js_escape(ME) . "script=db');");
			}
		}
	}

}

// each page calls its own page_header(), if the footer should not be called then the page exits
page_footer();
