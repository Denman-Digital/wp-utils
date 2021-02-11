<?php

/**
 * Utility functions
 *
 * @package Denman_WP_Utils
 */

namespace Denman_Utils;

use ArrayObject;
use DateTime;
use InvalidArgumentException;
use WP_Query;

defined('ABSPATH') || exit; // Exit if accessed directly.

/**
 * Check if variable is not null.
 *
 * Since isset() is a PHP language construct, this wrapper allows us to call it using variable functions
 *
 * @param mixed $var
 * @return bool
 */
function is_not_null($var): bool
{
	return isset($var);
}

function not_empty($var): bool
{
	return !empty($var);
}

/**
 * Check whether string starts with substring.
 *
 * @param string $haystack String to search within.
 * @param string $needle String to search for.
 * @return boolean
 */
function str_starts_with($haystack, $needle): bool
{
	$length = strlen($needle);
	if ($length == 0) {
		return true;
	}
	return (substr($haystack, 0, $length) === $needle);
}

/**
 * Check whether string ends with substring.
 *
 * @param string $haystack String to search within.
 * @param string $needle String to search for.
 * @return boolean
 */
function str_ends_with($haystack, $needle)
{
	$length = strlen($needle);
	if ($length == 0) {
		return true;
	}
	return (substr($haystack, -$length) === $needle);
}

/**
 * Ensure that a string starts with a prefix.
 *
 * @uses str_starts_with
 *
 * @param string $str Subject.
 * @param string $prefix Substring to look for/prepend.
 * @return string
 */
function str_prefix($str, $prefix)
{
	if (!str_starts_with($str, $prefix)) {
		$str = $prefix . $str;
	}
	return $str;
}

/**
 * Ensure that a string ends with a postfix
 *
 * @uses str_ends_with
 *
 * @param string $str Subject
 * @param string $postfix Substring to look for/append
 * @return string
 */
function str_postfix($str, $postfix)
{
	if (!str_ends_with($str, $postfix)) {
		$str .= $postfix;
	}
	return $str;
}

/**
 * Ensure that a string begins and ends with another string
 *
 * @uses str_prefix
 * @uses str_postfix
 *
 * @param string $str Subject.
 * @param string $bookend Pre/postfix.
 * @return string
 */
function str_bookend($str, $bookend)
{
	$bookend = (string) $bookend;
	return str_prefix(str_postfix($str, $bookend), $bookend);
}

/**
 * Ensure that a string doesn't start with a prefix
 *
 * @uses str_starts_with
 *
 * @param string $str Subject.
 * @param string $prefix Substring to look for/remove from start.
 * @param int $max Optional. Max number of times to unperfix, <0 means no limit. Default -1.
 * @return string
 */
function str_unprefix($str, $prefix, $max = -1)
{
	$max = $max >= 0 ? $max : -1;
	$count = 0;
	while ($prefix && ($max === -1 || $count < $max) && str_starts_with($str, $prefix)) {
		$str = substr($str, strlen($prefix));
	}
	return $str;
}

/**
 * Ensure that a string doesn't end with a postfix
 *
 * @uses str_ends_with
 *
 * @param string $str Subject.
 * @param string $prefix Substring to look for/remove from end.
 * @param int $max Optional. Max number of times to unpostfix, <0 means no limit. Default -1.
 * @return string
 */
function str_unpostfix($str, $postfix, $max = -1)
{
	$max = $max >= 0 ? $max : -1;
	$count = 0;
	while ($postfix && ($max === -1 || $count < $max) && str_ends_with($str, $postfix)) {
		$str = substr($str, 0, -strlen($postfix));
	}
	return $str;
}


/**
 * Check for existance of substring within a string
 *
 * @param string $haystack String to search within.
 * @param string $needle String to search for.
 * @param boolean $case_insensitive Optional. Whether to ignore case when checking. Default false.
 * @return boolean
 */
function str_contains($haystack, $needle, $case_insensitive = false)
{
	if ($case_insensitive) {
		$haystack = strtolower($haystack);
		$needle = strtolower($needle);
	}
	return (strpos($haystack, $needle) !== false);
}

/**
 * Truncate a string and append an indicator of truncation
 *
 * @param string $str String to truncate.
 * @param int $length Max length for $str before truncation occurs.
 * @param int|null $tolerance Optional. Tolerance for triggering truncation. Default 0.
 * @param string|null $after_truncate Optional. String to append after truncation. Default '...'.
 * @return string
 */
function str_truncate($str, $length, $tolerance = 0, $after_truncate = '...')
{
	if ($length && is_int($length) && $length < strlen($str) - abs($tolerance)) {
		$str = trim(substr($str, 0, $length)) . $after_truncate;
	}
	return $str;
}

/**
 * Sprintf with named placeholders in `%name%` format
 *
 * @uses str_bookend
 *
 * @param string $format Format string.
 * @param string[]|object Array of search/replace pairs, or object with public non-static properties
 * @return string
 */
function sprintf_keys($format, $pairs)
{
	$pairs = is_object($pairs) ? get_object_vars($pairs) : $pairs;
	if (!$pairs || !is_array($pairs)) {
		return $format;
	}
	$placeholders = array_map(function ($key) {
		return str_bookend($key, '%');
	}, array_keys($pairs));
	return str_replace($placeholders, array_values($pairs), $format);
}

/**
 * Re-key an array with a callback that returns new keys for each value
 *
 * @param callable $new_key_cb Callback function.
 * @param array $array Source array.
 * @param bool $key_first Optional. Whether the key should be the first parameter for the callback. Default true.
 * @param bool $value_scoped_cb Optional. Whether the callback provided is a non-static method of each value. Default false.
 */
function array_map_keys($new_key_cb, $array, $key_first = true, $value_scoped_cb = false)
{
	$output = [];
	foreach ($array as $key => $value) {
		if (!is_callable($new_key_cb)) {
			continue;
		}
		$cb = $value_scoped_cb ? [$value, $new_key_cb] : $new_key_cb;
		$cb_args = $key_first ? [$key, $value, $array] : [$value, $key, $array];
		$key = call_user_func_array($cb, $cb_args);
		$output[$key] = $value;
	}
	return $output;
}

/**
 * Remove empty values from array
 *
 * @param array $array The array to act upon.
 * @param boolean $null_only Optional. Whether to strictly compare to null. Default false.
 * @return array
 */
function array_clear_empty($array, $null_only = false)
{
	return array_values(array_filter($array, function ($value) use ($null_only) {
		return $null_only && !is_null($value) || !empty($value);
	}));
}

/**
 * Assert a value as an array. If not an array or ArrayObject, will create new array with $value as contents
 *
 * @param mixed $value Value to assert.
 * @param bool $wrap_null Optional. Whether to wrap null values in an array. Default false.
 * @return array
 */
function assert_array($value, $wrap_null = false)
{
	if (is_array($value)) {
		return $value;
	} else if ($value instanceof ArrayObject) {
		return $value->getArrayCopy();
	} else if (isset($value) || !!$wrap_null) {
		return [$value];
	} else {
		return [];
	}
}

/**
 * Remove an item from an array, returning the item
 *
 * @param array $array Source array.
 * @param string|int $key Key to remove and return value.
 * @return mixed|void
 */
function array_pluck(&$array, $key)
{
	if (!is_array($array) || !array_key_exists($key, $array)) {
		return;
	}
	$plucked = $array[$key];
	unset($array[$key]);
	return $plucked;
}

/**
 * Returns only array entries whose keys are listed in an inclusion list.
 *
 * @uses resolve_arglist
 * @uses array_flatten
 *
 * @param array $array Original array to operate on.
 * @param array ...$included_keys Keys or arrays of keys you want to keep.
 * @return array
 */
function array_include_keys($array, ...$included_keys)
{
	$included_keys = array_flatten(resolve_arglist($included_keys));
	return array_intersect_key($array, array_flip($included_keys));
}

/**
 * Returns only array entries whose keys are not listed in an exclusion list.
 *
 * @uses resolve_arglist
 * @uses array_flatten
 *
 * @param array $array Original array to operate on.
 * @param array ...$excluded_keys Keys or arrays of keys you want to remove.
 * @return array
 */
function array_exclude_keys($array, ...$excluded_keys)
{
	$excluded_keys = array_flatten(resolve_arglist($excluded_keys));
	return array_diff_key($array, array_flip($excluded_keys));
}

/**
 * Flatten nested arrays.
 *
 * * Returns only array values, keys are lost
 *
 * @param array[]|mixed[] $array Array containing arrays
 * @return mixed[]
 */
function array_flatten($array)
{
	$array = array_values($array);
	$output = [];
	foreach (array_values($array) as $value) {
		if (is_array($value)) {
			$output = array_merge($output, array_flatten($value));
		} else {
			$output[] = $value;
		}
	}
	return $output;
}

/**
 * Get the nth value in an array. Returns nothing if $array is empty or not an array.
 *
 * @uses min_max
 *
 * @param mixed[] $array
 * @param int $n Position to retrieve value from. If negative, counts back from end of array. WIll not overflow array bounds.
 * @return mixed|void
 */
function array_nth($array, $n)
{
	if (!is_array($array)) {
		return;
	}
	$length = count($array);
	if (!$length) {
		return;
	}
	$n = min_max($n, -$length, $length - 1); // don't overflow array bounds
	return array_values($array)[$n >= 0 ? $n : $length + $n];
}

if (!function_exists('array_some')) {
	/**
	 * Check if any entry in an array satisfies the callback.
	 *
	 * @param array $array
	 * @param callable $callback Validation callback.
	 * @param int $callback_args_count Optional. Number of arguments to pass to $callback. Default and maximum is 3.
	 * @return bool
	 */
	function array_some($array, $callback, $callback_args_count = 3)
	{
		foreach ($array as $key => $value) {
			if (call_user_func_array($callback, array_slice([$value, $key, $array], 0, $callback_args_count))) {
				return true;
			}
		}
		return false;
	}
}

if (!function_exists('array_find')) {
	/**
	 * Get the first entry in an array that satisfies the callback.
	 *
	 * @param array $array
	 * @param callable $callback Validation callback. Is passed the value, key, and full array for each entry checked.
	 * @param int $callback_args_count Optional. Number of arguments to pass to $callback. Default and maximum is 3.
	 * @return mixed|void
	 */
	function array_find($array, $callback, $callback_args_count = 3)
	{
		foreach ($array as $key => $value) {
			if (call_user_func_array($callback, array_slice([$value, $key, $array], 0, $callback_args_count))) {
				return ["key" => $key, "value" => $value];
			}
		}
	}
}

/**
 * Resolves an array with only one value that is a non empty array
 *
 * @param array[] $arglist
 * @return array
 */
function resolve_arglist($arglist)
{
	if ($arglist[0] && count($arglist) == 1 && is_array($arglist[0])) {
		$arglist = array_values($arglist[0]);
	}
	return $arglist;
}

/**
 * Compare 2 values exactly
 *
 * @param mixed $a
 * @param mixed $b
 * @return int
 */
function compare_exact($a, $b)
{
	if ($a === $b) {
		return 0;
	} elseif ($a > $b) {
		return 1;
	}
	return -1;
}

/**
 * Get the value of a numeric string.
 *
 * @param string $numeric_str
 * @return float|int
 */
function numval($numeric_str)
{
	if (!defined('LOCALE_DECIMAL_POINT') && ($dec = localeconv()['decimal_point'])) {
		define('LOCALE_DECIMAL_POINT', $dec);
	}
	return strpos($numeric_str, LOCALE_DECIMAL_POINT) === false ? intval($numeric_str) : floatval($numeric_str);
}

/**
 * Clamp a number to between min and max values
 *
 * @param float $num Value to be clamped.
 * @param float $min Minimum value.
 * @param float $max Maximum value.
 * @return float
 */
function min_max($num, $min, $max)
{
	return max(min($num, $max), $min);
}

// function constrain($num, $min, $max)
// {
//     return $num < $max && $num > $min;
// }

/**
 * Get contents of current output buffer and clear without turning buffer off.
 *
 * @return string|boolean
 */
function ob_get_refresh()
{
	$contents = ob_get_contents();
	ob_clean();
	return $contents;
}

/**
 * Get the buffered output of a callback
 *
 * @param callable $output_fn Function to buffer.
 * @param array $args Optional. Arguments for output function.
 * @param boolean $output_only Optional. Return only buffered output. Default true.
 * @return string|mixed[]
 */
function ob_return($output_fn, $args = [], $output_only = true)
{
	if (!is_callable($output_fn)) {
		return '';
	}
	ob_start();
	$returned = call_user_func_array($output_fn, (array) $args);
	$output = ob_get_clean();
	return $output_only ? $output : [$output, $returned, "output" => $output, "return" => $returned];
}

/**
 * Like get_template_part() but lets you pass args to the template file.
 * Args are available in the template as $template_args array
 * Based on Humanmade's hm_get_template_part()
 *
 * @global $post
 *
 * @uses join_path_segments
 *
 * @param string[]|string $file Path to template file.
 * @param mixed[]|object|string $template_args Optional. wp_args style argument list, with some special keys. Default empty array.
 * @param bool $template_args["set_post_data"] Setup post data for the template
 * @param bool $template_args["return"] If truthy, buffer template output and return as string, if `false` return `false`
 * @param mixed[]|object|string $cache_args Optional. Default empty array.
 * @return void|string
 */
function get_template_part_with($file, $template_args = [], $cache_args = [])
{
	global $post;

	if (is_array($file)) {
		$file = join_path_segments($file);
	} else if (!is_string($file)) {
		throw new InvalidArgumentException("\$file must be a string or array of strings");
	}

	$template_args = wp_parse_args($template_args);
	$cache_args = wp_parse_args($cache_args);
	if ($cache_args) {
		foreach ($template_args as $key => $value) {
			if (is_scalar($value) || is_array($value)) {
				$cache_args[$key] = $value;
			} else if (is_object($value) && method_exists($value, 'get_id')) {
				$cache_args[$key] = $value->get_id();
			}
		}
		if (($cache = wp_cache_get($file, serialize($cache_args))) !== false) {
			if (!empty($template_args['return'])) {
				return $cache;
			}
			echo $cache;
			return;
		}
	}
	if (!empty($template_args['set_post_data'])) {
		setup_postdata($post);
	}
	if (file_exists(get_stylesheet_directory() . '/' . $file . '.php')) {
		$file = get_stylesheet_directory() . '/' . $file . '.php';
	} elseif (file_exists(get_template_directory() . '/' . $file . '.php')) {
		$file = get_template_directory() . '/' . $file . '.php';
	}
	ob_start();
	$return = require $file;
	$data = ob_get_clean();
	if (!empty($template_args['set_post_data'])) {
		wp_reset_postdata();
	}
	if ($cache_args) {
		wp_cache_set($file, $data, serialize($cache_args), 3600);
	}
	if (!empty($template_args['return'])) {
		return ($return === false ? false : $data);
	}
	echo $data;
}

// /**
//  * Try to determine if site is being served from localhost
//  *
//  * @return bool
//  */
// function is_local()
// {
//     return $_SERVER['SERVER_NAME'] == 'localhost' || in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '0.0.0.0', '::1']);
// }

// /**
//  * Try to determine if site is being served from a dev environment by checking for:
//  * - single segment domain (i.e. 'localhost')
//  * - IP address domain
//  * - dev-related subdomain
//  * - dev-related TLD
//  *
//  * @uses is_local
//  *
//  * @return bool
//  */
// function is_dev_mode()
// {
//     if (is_local()) {
//         return true;
//     }
//     $url = 'http://' . $_SERVER['SERVER_NAME'];
//     $domain_segments = explode(".", parse_url($url, PHP_URL_HOST));
//     $dev_subdomains = ['test', 'testing', 'sandbox', 'local', 'stage', 'staging'];
//     $dev_tlds = ['test', 'local', 'localhost', 'dev', 'invalid', 'example', 'app'];
//     return count($domain_segments) == 1 || preg_match('/(\d{0,3}\.?){4}/', $_SERVER['SERVER_NAME']) || in_array($domain_segments[0], $dev_subdomains) || in_array(end($domain_segments), $dev_tlds);
// }

/**
 * Write to the debug log when unable to use var_dump()
 *
 * @param mixed ...$values - a series of values
 */
function log_val(...$values)
{
	error_log(print_r($values, true));
}

/**
 * Returns the first truthy argument, or the last argument.
 * * If passed a single non-empty Array, will return the first truthy value, or the last entry.
 *
 * @uses resolve_arglist
 *
 * @param mixed[]|mixed ...$values - a series or array of values
 * @return mixed
 */
function fallback(...$values)
{
	$values = resolve_arglist($values);
	foreach ($values as $result) {
		if (!empty($result)) {
			return $result;
		}
	}
	return $result;
}

/**
 * Returns the first argument that passes a validation callback, or the last argument.
 * * If passed a single non-empty Array, will return the first truthy value, or the last entry.
 *
 * @throws InvalidArgumentException if $validation_callback is not callable
 *
 * @param callable $validation_callback
 * @param mixed[]|mixed ...$values
 * @return mixed
 */
function fallback_until($validation_callback, ...$values)
{
	if (!is_callable($validation_callback)) {
		throw new InvalidArgumentException("First argument in fallback_until() was not callable");
	}
	$values = resolve_arglist($values);
	foreach ($values as $result) {
		if (call_user_func($validation_callback, $result)) {
			return $result;
		}
	}
	return $result;
}

/**
 * Pass a variable to a validation callback, assigning the first passing fallback value if it fails.
 * * If passed a single non-empty Array, will return the first truthy value, or the last entry.
 *
 * @param mixed $subject Variable to test/override. Passed by reference.
 * @param callable $validation_callback Validates $subject and $values by the truthiness of the return value.
 * @param mixed[]|mixed ...$values Fallback variables. If none pass the validation callback, the last will be used.
 */
function fallback_assign(&$subject, $validation_callback, ...$values)
{
	if (!is_callable($validation_callback)) {
		trigger_error("First argument in validate() was not callable");
		return;
	}
	$values = resolve_arglist($values);
	array_unshift($values, $subject);
	foreach ($values as $value) {
		$subject = $value;
		if (call_user_func($validation_callback, $subject)) {
			return;
		}
	}
}

/**
 * Resolve a variable to a post if possible.
 *
 * @uses get_post_by_slug
 *
 * @param WP_Post|int|string $post Optional. Variable to be resolved to a post, by ID or slug.
 * @return WP_Post|null
 */
function resolve_post($post = null)
{
	if (empty($post)) {
		return $GLOBALS['post'];
	}
	switch (gettype($post)) {
		case 'integer': // Find by ID
			$post = get_post($post);
			break;
		case 'string': // Find by slug
			$post = get_post_by_slug($post);
			break;
	}
	if (is_a($post, 'WP_Post')) {
		return $post;
	}
}

/**
 * Resolve a variable to a taxonomy if possible.
 *
 * @param WP_Taxonomy|string $taxonomy Variable to be resolved to a taxonomy.
 * @return WP_Taxonomy|null
 */
function resolve_taxonomy($taxonomy)
{
	if (is_string($taxonomy)) {
		$taxonomy = get_taxonomy($taxonomy);
	}
	if (is_a($taxonomy, "WP_Taxonomy")) {
		return $taxonomy;
	}
}

/**
 * Retrieve from an array only (a) string keys for truthy values and (b) numerically indexed strings
 *
 * @param string[] $classes
 * @return string[]
 */
function class_names($classes)
{
	$class_names = [];
	foreach ((array) $classes as $key => $value) {
		if (is_array($value)) {
			$class_names = array_merge($class_names, class_names($value));
		} else if (is_string($key)) {
			if (!!$value) {
				$class_names[] = $key;
			}
		} else if (is_string($value)) {
			$class_names[] = $value;
		}
	}
	return $class_names;
}


/**
 * Resolve to a string of space-separated class names.
 *
 * Implodes arrays, replaces dots with spaces, escapes string for use as HTML attribute.
 *
 * @param string[]|string $class_list List of class names.
 * @return string
 */
function resolve_class_list(...$class_list)
{
	$class_list = implode(' ', array_filter(array_flatten($class_list)));
	return esc_attr(str_replace('.', ' ', $class_list));
}

/**
 * Turn an assoc. array into HTML attributes as `key="value"`.
 *
 * * Assumes values are already suitable escaped.
 * * Outputs numerically indexed values as boolean attributes.
 * * Keys with null values will be excluded from output.
 * * Non-string values will be passed through var_export
 *
 * @param mixed[] $attrs
 * @return string
 */
function html_attrs($attrs)
{
	$attrs = array_map(function ($value) {
		return fallback_until("is_string", $value, esc_attr(var_export($value, true)));
	}, array_filter($attrs, "Utils\is_not_null"));
	$output = "";
	foreach ($attrs as $name => $value) {
		if (is_numeric($name)) {
			$output .= " $value";
		} else {
			$output .= " $name=\"$value\"";
		}
	}
	return trim($output);
}

/**
 * Get the attachment id of an image from its url.
 *
 * @param string $image_url
 * @return int|null
 */
function get_image_id($image_url)
{
	global $wpdb;
	$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url));
	return $attachment[0];
}

/**
 * Get a post from its slug.
 *
 * @param string $slug The post slug.
 * @param string $post_type The post-type slug, default is 'any'.
 * @return WP_Post|null
 */
function get_post_by_slug($slug, $post_type = 'any')
{
	$posts = get_posts([
		'name' => $slug,
		'post_type' => $post_type,
		'numberposts' => 1,
	]);
	return $posts[0] ?? null;
}

/**
 * Join path segments with a slash
 * * strips extra slashes on segments
 *
 * @uses str_starts_with
 * @uses str_ends_with
 *
 * @param string[] $segments Array of path segments.
 * @return string
 */
function join_path_segments($segments)
{
	$path = implode('/', array_map(function ($segment) {
		return trim($segment, '/');
	}, $segments));
	if (str_starts_with($segments[0], '/')) {
		$path = '/' . $path;
	}
	if (str_ends_with(array_nth($segments, -1), '/')) {
		$path .= '/';
	}
	return $path;
}

/**
 * Get the URI for a theme asset.
 *
 * @uses str_prefix
 * @uses resolve_arglist
 * @uses join_path_segments
 *
 * @param string[]|string ...$segments A series or array of path segments.
 * @return string
 */
function get_asset_uri(...$segments)
{
	$path = join_path_segments(resolve_arglist($segments));
	return get_template_directory_uri() . str_prefix($path, '/');
}

/**
 * Get the path for a theme asset file.
 *
 * @uses str_prefix
 * @uses resolve_arglist
 * @uses join_path_segments
 *
 * @param string[]|string ...$segments A series or array of path segments.
 * @return string
 */
function get_asset_path(...$segments)
{
	$path = join_path_segments(resolve_arglist($segments));
	return get_template_directory() . str_prefix($path, '/');
}

/**
 * Get the contents of a theme asset file.
 *
 * @uses get_asset_path
 *
 * @param string[]|string ...$segments A series or array of path segments.
 * @return string
 */
function get_asset_contents(...$path_segments)
{
	$path = get_asset_path(...$path_segments);
	$contents = "";
	if ($path && file_exists($path)) {
		try {
			$contents = file_get_contents($path);
		} catch (\Throwable $th) {
			$contents = "";
		}
	}
	return (string) $contents;
}

/**
 * Parse small subset of markdown to html.
 *
 * Includes: em & en dashes, bold, italic, inline code, links, paragraphs, and line-breaks.
 *
 * @param string $md Markdown content.
 * @return string Parsed HTML.
 */
function mini_markdown_parse($md)
{
	return preg_replace(
		[
			// '/^-|*|={3,}$/m', // hr
			'/-{3}/', // em
			'/-{2}/', // en
			'/(?:\*{2}((?:[^*]|(?:\\\*))+)\*{2})|(?:_{2}((?:[^_]|(?:\\_))+)_{2})/', // bold
			'/(?:\*((?:[^*]|(?:\\\*))+)\*)|(?:_((?:[^_]|(?:\\_))+)_)/', // italic
			'/`([^`]+)`/', // code
			'/\[([^\]]+)\]\(([^)]+)\)/', // link with text
			'/\[\]\(([^)]+)\)/', // link
			"/(.+)(?:(?: {2,}\R*)|\R{2,}|(?:\s*$))/", // paragraphs
			"/(.+)\R{}/", // line breaks
		],
		[
			// '<hr>',
			'&mdash;',
			'&ndash;',
			'<strong>$1$2</strong>',
			'<em>$1$2</em>',
			'<code>$1</code>',
			'<a href="$2">$1</a>',
			'<a href="$1">$1</a>',
			'<p>$1</p>',
			'$1<br>',
		],
		trim($md)
	);
}

/**
 * Strip the case of a string: no capitals, words separated by a single space.
 *
 * @param string $str
 * @return string
 */
function strip_case($str)
{
	return strtolower(trim(preg_replace(
		[
			'/([A-Z]+)/',
			'/[_-]+/',
			'/\s+/'
		],
		[
			' \1',
			' ',
			' ',
		],
		$str
	)));
}

/**
 * Title case a string, i.e. This Text Is Titled.
 *
 * @uses strip_case
 *
 * @param string $str
 * @return string
 */
function title_case($str)
{
	return ucwords(strip_case($str));
}

/**
 * Sentence case a string, i.e. This text is sentenced.
 *
 * @uses strip_case
 *
 * @param string $str
 * @return string
 */
function sentence_case($str)
{
	return ucfirst(strip_case($str));
}

/**
 * Pascal case a string, i.e. ThisTextIsPacaled.
 *
 * @uses strip_case
 *
 * @param string $str
 * @return string
 */
function pascal_case($str)
{
	return str_replace(' ', '', ucwords(strip_case($str)));
}

/**
 * Snake case a string, i.e. this_text_is_snaked.
 *
 * @uses strip_case
 *
 * @param string $str
 * @return string
 */
function snake_case($str)
{
	return str_replace(' ', '_', strip_case($str));
}

/**
 * Kebab case a string, i.e. this-text-is-kebabed.
 *
 * @uses strip_case
 *
 * @param string $str
 * @return string
 */
function kebab_case($str)
{
	return str_replace(' ', '-', strip_case($str));
}

/**
 * Camel case a string, i.e. thisTextIsCameled.
 *
 * @uses pascal_case
 *
 * @param string $str
 * @return string
 */
function camel_case($str)
{
	return lcfirst(pascal_case($str));
}

/**
 * Dummy function that returns first argument.
 *
 * @param mixed $var
 * @return mixed
 */
function pass_through($var)
{
	return $var;
}

/**
 * Map an array of objects to just the required properties.
 *
 * @see unwrap
 *
 * @param object[] $objects Array of objects.
 * @param string[]|string $props Object properties to keep.
 * @param string $key_var Optional. Property to use as key in the final array.
 * @return array[]
 */
function array_object_vars($objects, $props, $key_var = null)
{
	$objects = (array) $objects;
	$props = unwrap($props);
	$output = [];
	foreach ($objects as $key => $object) {
		if (!empty($key_var) && property_exists($object, $key_var)) {
			$key = $object->$key_var;
		}
		if (is_array($props)) {
			$vars = [];
			foreach ($props as $prop) {
				if (property_exists($object, $prop)) {
					$vars[$prop] = $object->$prop;
				}
			}
		} else {
			if (property_exists($object, $props)) {
				$vars = $object->$props;
			}
		}
		$output[$key] = $vars;
	}
	return $output;
}

/**
 * Unwrap single value arrays.
 *
 * Will recursively unwrap single-value arrays until left with either a single
 * non-array value, or an array with 0 or 2+ values.
 *
 * @param mixed[]|mixed $array Array to potentially unwrap.
 * @param int $limit Optional. Max number of layers to unwrap. Default -1 (no limit).
 * @return mixed
 */
function unwrap($array, $limit = -1)
{
	$limit = max($limit, -1);
	$count = 0;
	while ($count != $limit && is_array($array) && count($array) === 1) {
		$array = $array[0];
		$count += 1;
	}
	return $array;
}

/** Get an array of registered public custom taxonomies.
 *
 * * Be sure to only call AFTER your custom taxonomies have been registered
 * * Taxonomies MUST have been registered with ['public' => true]
 *
 * @param string[]|string $exclude Optional. Taxonomy slug(s) to exclude.
 * @return WP_Taxonomy[]
 */
function get_custom_taxonomies($exclude = null)
{
	return array_exclude_keys(
		get_taxonomies(
			["public" => true, "_builtin" => false],
			"objects"
		),
		$exclude ?: ''
	);
}

/**
 * Get an array of registered public custom post types.
 *
 * * Be sure to only call AFTER your custom post types have been registered
 * * Post types MUST have been registered with ['public' => true]
 *
 * @param string[]|string $exclude Optional. Post type slug(s) to exclude.
 * @return WP_Post_Type[]
 */
function get_custom_post_types($exclude = null)
{
	return array_exclude_keys(
		get_post_types(
			[
				"public" => true,
				"_builtin" => false
			],
			"objects"
		),
		$exclude ? $exclude : ''
	);
}

// /**
//  * Get the primary term of a taxonomy for a post
//  *
//  * @uses resolve_post
//  * @uses resolve_taxonomy
//  *
//  * @param WP_Taxonomy|string $taxonomy
//  * @param WP_Post|int|string|null $post
//  * @param WP_Term|void
//  */
// function get_primary_term($taxonomy, $post = null)
// {
// 	$post = resolve_post($post);
// 	$taxonomy = resolve_taxonomy($taxonomy);
// 	if (!$taxonomy || !$post) {
// 		return;
// 	}
// 	$term_list = wp_get_post_terms($post->ID, $taxonomy->name, ['fields' => 'all']);
// 	foreach ($term_list as $term) {
// 		if (get_post_meta($post->ID, "_yoast_wpseo_primary_$taxonomy->name", true) == $term->term_id) {
// 			return $term;
// 		}
// 	}
// }

/**
 * Get a string representation of elapsed time.
 *
 * @param int $datetime Unix timestamp.
 * @param bool $full Optional. Whether to output all non-zero time divisions or just the largest. Default false.
 * @return string
 */
function time_elapsed_string($datetime, $full = false)
{
	$now = new DateTime;
	$ago = new DateTime($datetime);
	$diff = $now->diff($ago);

	$diff->w = floor($diff->d / 7);
	$diff->d %= 7;

	$string = array(
		'y' => 'year',
		'm' => 'month',
		'w' => 'week',
		'd' => 'day',
		'h' => 'hour',
		'i' => 'minute',
		's' => 'second',
	);
	foreach ($string as $k => &$v) {
		if ($diff->$k) {
			$v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
		} else {
			unset($string[$k]);
		}
	}

	if (!$full) {
		$string = array_slice($string, 0, 1);
	}
	return $string ? implode(', ', $string) . ' ago' : 'just now';
}

function preserve_newlines($str)
{
	return preg_replace("/\R/", "<br>", $str);
}

if (!function_exists('esc_regex')) {
	function esc_regex($str)
	{
		return preg_replace('/([^\w])/', '\\\$0', $str);
	}
}

/**
 * Wrap a string with localized quotemarks
 *
 * @param string $str
 * @return string
 */
function str_quote($str)
{
	return _x("“", "opening quotemark") . $str . _x("”", "closing quotemark");
}

/**
 * Return a function that only runs if a global flag is falsey.
 *
 * @param string $flag_name - Name of flag variable in the global scope.
 * @param callable $callable - Callable to execute if flag is falsey.
 * @param bool $is_filter - Optional. If true, and flag prevents execution, return the first supplied argument when the returned function is called. Default false.
 * @return function
 */
function flag_block($flag_name, $callable, $is_filter = false)
{
	return function (...$args) use ($callable, $flag_name, $is_filter) {
		if (!empty($GLOBALS[$flag_name])) {
			return $is_filter && $args ? $args[0] : null;
		}
		$GLOBALS[$flag_name] = true;
		$returned = call_user_func_array($callable, $args);
		$GLOBALS[$flag_name] = false;
		return $returned;
	};
}

/**
 * Return a function that only runs if a global flag is truthy.
 *
 * @param string $flag_name - Name of flag variable in the global scope.
 * @param callable $callable - Callable to execute if flag is falsey.
 * @param bool $is_filter - Optional. If true, and flag prevents execution, return the first supplied argument when the returned function is called. Default false.
 * @return function
 */
function flag_pass($flag_name, $callable, $is_filter = false)
{
	return function (...$args) use ($callable, $flag_name, $is_filter) {
		if (empty($GLOBALS[$flag_name])) {
			return $is_filter && $args ? $args[0] : null;
		}
		$GLOBALS[$flag_name] = true;
		$returned = call_user_func_array($callable, $args);
		$GLOBALS[$flag_name] = false;
		return $returned;
	};
}

/**
 * Use array_merge_recursive to concatenate arrays
 * Arguments will be cast to arrays
 *
 * @return array
 */
function array_concat($array, ...$additions)
{
	$arrays = array_map(function ($value) {
		return (array) $value;
	}, $additions);
	array_unshift($arrays, (array) $array);
	return call_user_func_array("array_merge_recursive", $arrays);
}


function fallback_progression($validation_callback, ...$progressive_callbacks)
{
	if (!is_callable($validation_callback)) {
		trigger_error("First argument in validate() was not callable");
		return;
	}
	$progressive_callbacks = array_filter(resolve_arglist($progressive_callbacks), "is_callable");
	foreach ($progressive_callbacks as $callback) {
		$result = call_user_func($callback);
		if (call_user_func($validation_callback, $result)) {
			return $result;
		}
	}
}

/**
 * Get the primary key value of a WordPress object
 *
 * @param Object $obj
 * @return int
 */
function resolve_object_id($obj)
{
	$id_props = ["ID", "term_id"];
	foreach ($id_props as $prop) {
		if (property_exists($obj, $prop)) {
			return $obj->{$prop};
		}
	}
	return 0;
}

/**
 * Return a copy of a function with some arguments prefilled.
 *
 * @param callable $callable - The function/method to prefill.
 * @param mixed $args - Arguments to prefill in the order supplied.
 * @return function
 */
function prefill($callable, ...$args)
{
	return function () use ($callable, $args) {
		return call_user_func_array($callable, array_merge($args, func_get_args()));
	};
}

/**
 * Try to make sure you have a desired amount of posts.
 *
 * @throws InvalidArgumentException if $posts is not an array or WP_Query.
 *
 * @param WP_Post[]|WP_Query $posts - The posts you have already.
 * @param int $desired_post_count - The desired number of posts.
 * @param mixed[] $wp_query_args - Optional. The query args to look for posts to pad out your results.
 * @param boolean|null $return_as_query - Optional. Whether to return the posts as a WP_Query. If a WP_Query is supplied as $posts, defaults to true, otherwise defaults to false.
 * @param boolean $exclude_global_post - Optional. Whether to exclude the current global $post from results. Default true.
 * @return WP_Post[]|WP_Query
 */
function pad_posts($posts, $desired_post_count, $wp_query_args = [], $return_as_query = null, $exclude_global_post = true)
{
	if (is_a($posts, "WP_Query")) {
		$posts = $posts->posts;
		// If $return_as_query is not set, assume true
		$return_as_query = $return_as_query ?? true;
	} elseif (is_array($posts)) {
		$posts = array_filter($posts, function ($obj) {
			return is_object($obj) && is_a($obj, "WP_Post");
		});
	} else {
		throw new InvalidArgumentException("First argument must be an array or WP_Query");
	}
	if (count($posts) < $desired_post_count) {
		if (!is_array($wp_query_args)) {
			$wp_query_args = [];
		}
		$wp_query_args["posts_per_page"] = $desired_post_count - count($posts);
		if (isset($wp_query_args["post__not_in"])) {
			$preset_exclude = array_map("intval", (array) $wp_query_args["post__not_in"]);
		}
		$wp_query_args["post__not_in"] = array_unique(array_merge(
			$preset_exclude ?? [], // exclude preset post ids
			array_map("resolve_object_id", $posts), // exclude any posts already in $posts
			[$exclude_global_post ? get_the_ID() : 0] // exclude global $post (optional)
		));
		$posts = array_merge(
			$posts,
			get_posts($wp_query_args)
		);
	}
	if (!empty($return_as_query)) {
		$return_query_args = [
			"post_type" => "any",
			"posts_per_page" => $desired_post_count,
		];
		if ($posts) {
			$return_query_args["post__in"] = array_map("resolve_object_id", $posts);
			$return_query_args["orderby"] = "post__in";
		} else {
			$return_query_args["post__in"] = [0];
		}
		$posts = new WP_Query($return_query_args);
	}
	return $posts;
}

function link_unless_singular($content_str, $extra_attr_str = "", $post = null)
{
	$post = resolve_post($post);
	$extra_attr_str = $extra_attr_str ? " $extra_attr_str" : "";
	return is_singular($post->post_type) ? $content_str : sprintf("<a href=\"%s\"%s>%s</a>", get_the_permalink($post), $extra_attr_str, $content_str);
}

/**
 * Pass a value through any number of filter hooks sequentially.
 * Basically, short for apply_filters("hook2", apply_filters("hook1", $value))...
 *
 * @param string[] $hooks - Filter hooks.
 * @param mixed ...$args - Arguments for filters.
 * @return mixed
 */
function apply_filters_sequence($hooks, ...$args)
{
	$hooks = array_filter($hooks, "has_filter");
	$value = array_pop($args);
	return array_reduce($hooks, function ($filtered, $next_hook) use ($args) {
		$filter_args = array_concat([$filtered], $args);
		return apply_filters_ref_array($next_hook, $filter_args);
	}, $value);
}

/**
 * Pass a value through any number of filter hooks sequentially.
 *
 * @param string[] $hooks - Filter hooks.
 * @param mixed ...$args - Arguments for filters.
 */
function do_actions_sequence($hooks, ...$args)
{
	$hooks = array_filter($hooks, "has_action");
	foreach ($hooks as $hook) {
		do_action_ref_array($hook, $args);
	}
}

function array_parallel($array)
{
	return array_combine($array, $array);
}

function array_force_assoc($array)
{
	$values = array_values($array);
	$keys = array_map(function ($key) use ($array) {
		return is_numeric($key) ? $array[$key] : $key;
	}, array_keys($array));
	return array_combine($keys, $values);
}

function reverse_wpautop($str)
{
	//remove any new lines already in there
	$str = str_replace("\n", "", $str);
	//remove all <p>
	$str = str_replace("<p>", "", $str);
	//replace <br /> with \n
	$str = str_replace(array("<br />", "<br>", "<br/>"), "\n", $str);
	//replace </p> with \n\n
	$str = str_replace("</p>", "\n\n", $str);
	return $str;
}
