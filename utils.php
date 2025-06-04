<?php

/**
 * Utility functions
 *
 * @package Denman_Utils
 */

namespace Denman_Utils\v2;

use ArrayObject;
use DateTime;
use InvalidArgumentException;
use WP_Post;
use WP_Query;
use WP_Term;
use WP_Taxonomy;

defined('ABSPATH') || exit; // Exit if accessed directly.

/**
 * Dummy function that returns first argument.
 * @since 1.0.0
 * @param mixed $var
 * @return mixed
 */
function pass_through($var)
{
	return $var;
}

/**
 * Check if variable is not null.
 *
 * Since isset() is a PHP language construct, this wrapper allows us to call it using variable functions
 * @since 1.0.0
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
 * @deprecated 1.2.0
 * @since 1.0.0
 * @param string $haystack String to search within.
 * @param string $needle String to search for.
 * @return boolean
 */
function str_starts_with(string $haystack, string $needle): bool
{
	_deprecated_function("Denman_Utils\\v2\\str_starts_with", "6.0", "str_starts_with");
	$length = strlen($needle);
	if ($length == 0) {
		return true;
	}
	return (substr($haystack, 0, $length) === $needle);
}


/**
 * Check whether string ends with substring.
 * @deprecated 1.2.0
 * @since 1.0.0
 * @param string $haystack String to search within.
 * @param string $needle String to search for.
 * @return boolean
 */
function str_ends_with(string $haystack, string $needle): bool
{
	_deprecated_function("Denman_Utils\\v2\\str_ends_with", "6.0", "str_ends_with");
	$length = strlen($needle);
	if ($length == 0) {
		return true;
	}
	return (substr($haystack, -$length) === $needle);
}

/**
 * Ensure that a string starts with a prefix.
 *
 * @since 1.0.0
 * @param string $str Subject.
 * @param string $prefix Substring to look for/prepend.
 * @return string
 */
function str_prefix(string $str, string $prefix): string
{
	if (!\str_starts_with($str, $prefix)) {
		$str = $prefix . $str;
	}
	return $str;
}

/**
 * Ensure that a string ends with a postfix
 *
 * @since 1.0.0
 * @param string $str Subject
 * @param string $postfix Substring to look for/append
 * @return string
 */
function str_postfix(string $str, string $postfix): string
{
	if (!\str_ends_with($str, $postfix)) {
		$str .= $postfix;
	}
	return $str;
}

/**
 * Ensure that a string begins and ends with another string
 *
 * @uses str_prefix
 * @uses str_postfix
 * @since 1.0.0
 * @param string $str Subject.
 * @param string $bookend Pre/postfix.
 * @return string
 */
function str_bookend(string $str, string $bookend): string
{
	$bookend = (string) $bookend;
	return str_prefix(str_postfix($str, $bookend), $bookend);
}

/**
 * Ensure that a string doesn't start with a prefix
 *
 * @since 1.0.0
 * @param string $str Subject.
 * @param string $prefix Substring to look for/remove from start.
 * @param int $max Optional. Max number of times to unperfix, <0 means no limit. Default -1.
 * @return string
 */
function str_unprefix(string $str, string $prefix, int $max = -1): string
{
	$max = $max >= 0 ? $max : -1;
	$count = 0;
	while ($prefix && ($max === -1 || $count < $max) && \str_starts_with($str, $prefix)) {
		$str = substr($str, strlen($prefix));
	}
	return $str;
}

/**
 * Ensure that a string doesn't end with a postfix
 *
 * @since 1.0.0
 * @param string $str Subject.
 * @param string $prefix Substring to look for/remove from end.
 * @param int $max Optional. Max number of times to unpostfix, <0 means no limit. Default -1.
 * @return string
 */
function str_unpostfix(string $str, string $postfix, int $max = -1): string
{
	$max = $max >= 0 ? $max : -1;
	$count = 0;
	while ($postfix && ($max === -1 || $count < $max) && \str_ends_with($str, $postfix)) {
		$str = substr($str, 0, -strlen($postfix));
	}
	return $str;
}


/**
 * Check for existance of substring within a string
 * @deprecated 1.2.0
 * @since 1.0.0
 * @param string $haystack String to search within.
 * @param string $needle String to search for.
 * @param bool $case_insensitive Optional. Whether to ignore case when checking. Default false.
 * @return bool
 */
function str_contains(string $haystack, string $needle, bool $case_insensitive = false): bool
{
	_deprecated_function("Denman_Utils\\v2\\str_contains", "6.0", "str_contains");
	if ($case_insensitive) {
		$haystack = strtolower($haystack);
		$needle = strtolower($needle);
	}
	return (strpos($haystack, $needle) !== false);
}

/**
 * Truncate a string and append an indicator of truncation
 * @since 1.0.0
 * @param string $str String to truncate.
 * @param int $length Max length for $str before truncation occurs.
 * @param int $tolerance Optional. Tolerance for triggering truncation. Default 0.
 * @param string $after_truncate Optional. String to append after truncation. Default '…'.
 * @return string
 */
function str_truncate(string $str, int $length, int $tolerance = 0, string $after_truncate = '…')
{
	if ($length && is_int($length) && $length < strlen($str) - abs($tolerance)) {
		$str = trim(substr($str, 0, $length)) . $after_truncate;
	}
	return $str;
}

/**
 * Wrap a string with localized quotemarks
 * @since 1.0.0
 * @param string $str
 * @return string
 */
function str_quote(string $str): string
{
	return _x("“", "opening quotemark") . $str . _x("”", "closing quotemark");
}

/**
 * Sprintf with named placeholders in `%name%` format
 *
 * @uses str_bookend
 * @since 1.0.0
 * @param string $format Format string.
 * @param string[]|object Array of search/replace pairs, or object with public non-static properties
 * @return string
 */
function sprintf_keys(string $format, $pairs): string
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
 * @since 1.0.0
 * @param callable $new_key_cb Callback function.
 * @param array $array Source array.
 * @param bool $key_first Optional. Whether the key should be the first parameter for the callback. Default true.
 * @param bool $value_scoped_cb Optional. Whether the callback provided is a non-static method of each value. Default false.
 * @return array
 */
function array_map_keys(callable $new_key_cb, array $array, bool $key_first = true, bool $value_scoped_cb = false): array
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
 * @since 1.0.0
 * @param array $array The array to act upon.
 * @param boolean $null_only Optional. Whether to strictly compare to null. Default false.
 * @return array
 */
function array_clear_empty(array $array, bool $null_only = false): array
{
	return array_values(array_filter($array, function ($value) use ($null_only) {
		return $null_only && !is_null($value) || !empty($value);
	}));
}

/**
 * Assert a value as an array. If not an array or ArrayObject, will create new array with $value as contents
 * @since 1.0.0
 * @param mixed $value Value to assert.
 * @param bool $wrap_null Optional. Whether to wrap null values in an array. Default false.
 * @return array
 */
function assert_array(mixed $value, bool $wrap_null = false): array
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
 * @since 1.0.0
 * @param array $array Source array.
 * @param string|int $key Key to remove and return value.
 * @return mixed
 */
function array_pluck(array &$array, string|int $key): mixed
{
	if (!is_array($array) || !array_key_exists($key, $array)) {
		return null;
	}
	$plucked = $array[$key];
	unset($array[$key]);
	return $plucked;
}

/**
 * Returns only array entries whose keys are listed in an inclusion list.
 *
 * @since 1.0.0
 * @uses resolve_arglist
 * @uses array_flatten
 * @param array $array Original array to operate on.
 * @param array ...$included_keys Keys or arrays of keys you want to keep.
 * @return array
 */
function array_include_keys(array $array, ...$included_keys): array
{
	$included_keys = array_flatten(resolve_arglist($included_keys));
	return array_intersect_key($array, array_flip($included_keys));
}

/**
 * Returns only array entries whose keys are not listed in an exclusion list.
 *
 * @since 1.0.0
 * @uses resolve_arglist
 * @uses array_flatten
 * @param array $array Original array to operate on.
 * @param array ...$excluded_keys Keys or arrays of keys you want to remove.
 * @return array
 */
function array_exclude_keys(array $array, ...$excluded_keys): array
{
	$excluded_keys = array_flatten(resolve_arglist($excluded_keys));
	return array_diff_key($array, array_flip($excluded_keys));
}

/**
 * Flatten nested arrays.
 *
 * * Returns only array values, keys are lost
 * @since 1.0.0
 * @param array[]|mixed[] $array Array containing arrays
 * @return mixed[]
 */
function array_flatten(array $array): array
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
 * @since 1.0.0
 * @uses min_max
 * @param mixed[] $array
 * @param int $n Position to retrieve value from. If negative, counts back from end of array. WIll not overflow array bounds.
 * @return mixed
 */
function array_nth(array $array, int $n): mixed
{
	if (!is_array($array)) {
		return null;
	}
	$length = count($array);
	if (!$length) {
		return null;
	}
	$n = min_max($n, -$length, $length - 1); // don't overflow array bounds
	return array_values($array)[$n >= 0 ? $n : $length + $n];
}

/**
 * Check if any entry in an array satisfies the callback.
 * @since 1.0.0
 * @param array $array
 * @param callable $callback Validation callback.
 * @param int $callback_args_count Optional. Number of arguments to pass to $callback. Default and maximum is 3.
 * @return bool
 */
function array_some(array $array, callable $callback, int $callback_args_count = 3): bool
{
	foreach ($array as $key => $value) {
		if (call_user_func_array($callback, array_slice([$value, $key, $array], 0, $callback_args_count))) {
			return true;
		}
	}
	return false;
}

/**
 * Get the first entry in an array that satisfies the callback.
 * @since 1.2.0 Returns empty array instead of null if no entry satisfies callback
 * @since 1.0.0
 * @param array $array
 * @param callable $callback Validation callback. Is passed the value, key, and full array for each entry checked.
 * @param int $callback_args_count Optional. Number of arguments to pass to $callback. Default and maximum is 3.
 * @return array
 */
function array_find(array $array, callable $callback, int $callback_args_count = 3): array
{
	foreach ($array as $key => $value) {
		if (call_user_func_array($callback, array_slice([$value, $key, $array], 0, $callback_args_count))) {
			return ["key" => $key, "value" => $value];
		}
	}
	return [];
}

/**
 * Use array_merge_recursive to concatenate arrays
 * Arguments will be cast to arrays
 * @since 1.0.0
 * @return array
 */
function array_concat(array $array, array ...$additions): array
{
	$arrays = array_map(function ($value) {
		return (array) $value;
	}, $additions);
	array_unshift($arrays, (array) $array);
	return call_user_func_array("array_merge_recursive", $arrays);
}

/**
 * Create an array where keys & values are parallel.
 * @since 1.0.0
 * @param array $array
 * @return array
 */
function array_parallel(array $array): array
{
	return array_combine($array, $array);
}

/**
 * Force an array to be associative, replacing numerical indices with the associated value.
 * @since 1.0.0
 * @param array $array
 * @return array
 */
function array_force_assoc(array $array): array
{
	$values = array_values($array);
	$keys = array_map(function ($key) use ($array) {
		return is_numeric($key) ? $array[$key] : $key;
	}, array_keys($array));
	return array_combine($keys, $values);
}

/**
 * Check whether an array has string keys
 * @deprecated 1.2.0
 * @since 1.1.0
 * @param array $array
 * @return bool
 */
function array_has_string_keys(array $array): bool
{
	_deprecated_function("Denman_Utils\\v2\\array_has_string_keys", "6.0", "array_is_list");
	return count(array_filter(array_keys($array), 'is_string')) > 0;
}

/**
 * Get the an adjacent key in an array. Returns null if $array is empty, not an array, or the key is not set.
 *
 * @since 1.1.0
 * @throws LengthException
 * @param mixed[] $array
 * @param string|int $key Key to look adjacent to.
 * @param int $adjacence Desired position relative to the position of $key.
 * @return mixed|void
 */
function array_key_adjacent(array $array, $key, int $adjacence)
{
	if (!is_array($array)) {
		return;
	}
	$length = count($array);
	if (!$length) {
		return;
	}
	$keys = array_keys($array);
	$start_n = array_search($key, $keys, true);
	if ($start_n === false) {
		return;
	}
	return $keys[$start_n + $adjacence] ?? null;
}

/**
 * Map an array of objects to just the required properties.
 *
 * @see unwrap
 * @since 1.0.0
 * @param object[] $objects Array of objects.
 * @param string[]|string $props Object properties to keep.
 * @param string $key_var Optional. Property to use as key in the final array.
 * @return array[]
 */
function array_object_vars(array $objects, $props, string $key_var = ""): array
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
 * Get the opposite of an array intersection.
 * @since 1.2.0
 * @param array $arr1
 * @param array $arr2
 * @return array
 */
function array_divergence(array $arr1, array $arr2): array
{
	return array_merge(
		array_diff($arr1, $arr2),
		array_diff($arr2, $arr1),
	);
}

/**
 * Unwrap single value arrays.
 *
 * Will recursively unwrap single-value arrays until left with either a single
 * non-array value, or an array with 0 or 2+ values.
 * @since 1.0.0
 * @param mixed[]|mixed $array Array to potentially unwrap.
 * @param int $limit Optional. Max number of layers to unwrap. Default -1 (no limit).
 * @return mixed
 */
function unwrap($array, int $limit = -1)
{
	$limit = max($limit, -1);
	$count = 0;
	while ($count != $limit && is_array($array) && count($array) === 1) {
		$array = $array[0];
		$count += 1;
	}
	return $array;
}

/**
 * Resolves an array with only one value that is a non empty array
 * @since 1.0.0
 * @param array[] $arglist
 * @return array
 */
function resolve_arglist(array $arglist): array
{
	if ($arglist[0] && count($arglist) == 1 && is_array($arglist[0])) {
		$arglist = array_values($arglist[0]);
	}
	return $arglist;
}

/**
 * Like wp_parse_args, but limits results to keys defined in 2nd parameter.
 * @since 1.0.5
 * @param string|array|object $args Arguments to parse.
 * @param array $defaults_and_allowed_keys Default values and allowed keys.
 * @return array
 */
function parse_args(string|array|object $args, array $defaults_and_allowed_keys = []): array
{
	$defaults = array_filter($defaults_and_allowed_keys, "is_string", ARRAY_FILTER_USE_KEY);
	// Get allowed keys
	$allowed_keys = [];
	foreach ($defaults_and_allowed_keys as $key => $value) {
		if ($key && is_string($key)) {
			$allowed_keys[] = $key;
		} else if ($value && is_string($value)) {
			$allowed_keys[] = $value;
		}
	}
	// Assign defaults
	$parsed_args = wp_parse_args($args, $defaults);
	// Limit to allowed keys
	$allowed_args = array_include_keys($parsed_args, $allowed_keys);
	return $allowed_args;
}

/**
 * Compare 2 values exactly
 * @since 1.0.0
 * @param mixed $a
 * @param mixed $b
 * @return int
 */
function compare_exact($a, $b): int
{
	if ($a === $b) {
		return 0;
	}
	return ($a < $b) ? -1 : 1;
}

/**
 * Generate a function to compare 2 objects or associative arrays by the value of a key
 * @since 1.2.0
 * @param int|string $key
 * @param null|callable $callback
 * @return callable
 */
function generate_compare_by_key(int|string $key, ?callable $callback = null): callable
{
	return (function ($a, $b) use ($key, $callback) {
		$a_val = $a[$key];
		$b_val = $b[$key];
		if ($callback) {
			return $callback($a_val, $b_val);
		} else {
			if ($a_val == $b_val) {
				return 0;
			}
			return ($a_val < $b_val) ? -1 : 1;
		}
	});
}


/**
 * Get the value of a numeric string.
 * @since 1.0.0
 * @param string $numeric_str
 * @return float|int
 */
function numval(string $numeric_str)
{
	if (!defined('LOCALE_DECIMAL_POINT') && ($dec = localeconv()['decimal_point'])) {
		define('LOCALE_DECIMAL_POINT', $dec);
	}
	return strpos($numeric_str, LOCALE_DECIMAL_POINT) === false ? intval($numeric_str) : floatval($numeric_str);
}

/**
 * Clamp a number to between min and max values
 * @since 1.0.0
 * @param float|int $num Value to be clamped.
 * @param float|int $min Minimum value.
 * @param float|int $max Maximum value.
 * @return float|int
 */
function min_max($num, $min, $max)
{
	return max(min($num, $max), $min);
}

/**
 * Get contents of current output buffer and clear without turning buffer off.
 * @since 1.0.0
 * @return string
 */
function ob_get_refresh(): string
{
	$contents = ob_get_contents();
	if ($contents !== false) ob_clean();
	return $contents ?: "";
}

/**
 * Get the buffered output of a callback
 * @since 1.0.0
 * @param callable $output_fn Function to buffer.
 * @param array $args Optional. Arguments for output function.
 * @param boolean $output_only Optional. Return only buffered output. Default true.
 * @return string|mixed[]
 */
function ob_return(callable $output_fn, array $args = [], bool $output_only = true)
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
 * Args are available in the template as $template_args array.
 * Based on Humanmade's hm_get_template_part().
 *
 * @since 1.0.0
 * @uses resolve_post() to resolve $template_args["post"].
 * @global $post
 * @param string[]|string $path Path(s) to template file. If passed an array of strings, it will treat attempt to join them with hyphens into a single path. If no such file can be found, it will try again iteratively, dropping the last piece until a valid file can be found.
 * @param mixed[]|object|string $template_args Optional. wp_args style argument list, with some special keys. Default empty array.
 * @param bool $template_args["set_post_data"] Setup post data for the template
 * @param bool $template_args["return"] If truthy, buffer template output and return as string, if `false` return `false`.
 * @param WP_Post|int|string $template_args["post"] If seting-up post data, use this post, falling back to the global $post.
 * @param mixed[]|object|string $cache_args Optional. Default empty array.
 * @return void|string
 */
function get_template_part_with($path, $template_args = [], $cache_args = [])
{
	global $post;

	if (is_array($path)) {
		$path = array_filter($path, "is_string");
	} else if (!is_string($path)) {
		throw new InvalidArgumentException("\$path must be a string or array of strings");
	}
	// Iterate over possible file paths
	$path = assert_array($path);
	$file = null;
	$stylesheet_dir = get_stylesheet_directory();
	$template_dir = get_template_directory();
	while (empty($file) && $path) {
		$test_path = str_prefix(join("-", $path), "/") . ".php";
		if (file_exists($stylesheet_dir . $test_path)) {
			$file = $stylesheet_dir . $test_path;
		} elseif (file_exists($template_dir . $test_path)) {
			$file = $template_dir . $test_path;
		}
		array_pop($path);
	}

	$file = $file ?? "";

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
		$post = resolve_post($template_args['post'] ?? 0) ?? $post;
		setup_postdata($post);
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

/**
 * Write to the debug log when unable to use var_dump()
 * @since 1.0.0
 * @param mixed ...$values A series of values
 */
function log_val(...$values)
{
	error_log(print_r($values, true));
}

/**
 * Returns the first truthy argument, or the last argument.
 * * If passed a single non-empty Array, will return the first truthy value, or the last entry.
 *
 * @since 1.0.0
 * @uses resolve_arglist
 * @param mixed[]|mixed ...$values A series or array of values
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
 * * If passed a single non-empty Array, will return the first valid value, or the last entry.
 *
 * @since 1.0.0
 * @throws InvalidArgumentException if $validation_callback is not callable
 * @param callable $validation_callback
 * @param mixed[]|mixed ...$values
 * @return mixed
 */
function fallback_until(callable $validation_callback, ...$values)
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
 * * If passed a single non-empty Array, will return the first valid value, or the last entry.
 *
 * @since 1.0.0
 * @param mixed $subject Variable to test/override. Passed by reference.
 * @param callable $validation_callback Validates $subject and $values by the truthiness of the return value.
 * @param mixed[]|mixed ...$values Fallback variables. If none pass the validation callback, the last will be used.
 */
function fallback_assign(&$subject, callable $validation_callback, ...$values): void
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
 * Run a series of callbacks until one returns a value that satisfies the validation callback, returning that value.
 * * Returns either the first valid result, or the result of the last callback.
 * @since 1.0.0
 * @param callable $validation_callback
 * @param callable[]|callable $progressive_callbacks
 * @return mixed
 */
function fallback_progression(callable $validation_callback, ...$progressive_callbacks)
{
	if (!is_callable($validation_callback)) {
		trigger_error("First argument in validate() was not callable");
		return;
	}
	$progressive_callbacks = array_filter(resolve_arglist($progressive_callbacks), "is_callable");
	$result = null;
	foreach ($progressive_callbacks as $callback) {
		$result = call_user_func($callback);
		if (call_user_func($validation_callback, $result)) {
			return $result;
		}
	}
	return $result;
}

/**
 * Resolve a variable to a post if possible.
 *
 * @uses get_post_by_slug
 * @since 1.0.0
 * @param WP_Post|int|string $post Optional. Variable to be resolved to a post, by ID or slug.
 * @param ?string $post_type Optional. Desired post type, or "any".
 * @return WP_Post|null
 */
function resolve_post($post = null, string $post_type = "")
{
	if (empty($post)) {
		return $GLOBALS['post'] ?? null;
	}
	switch (gettype($post)) {
		case 'integer': // Find by ID
			$post = get_post($post);
			break;
		case 'string': // Find by slug
			$post = get_post_by_slug($post, $post_type ?: "any");
			break;
	}
	if (is_a($post, 'WP_Post') && (!$post_type || in_array($post_type, [$post->post_type, "any", ""]))) {
		return $post;
	}
}

/**
 * Resolve a variable to a taxonomy if possible.
 * @since 1.0.0
 * @param WP_Taxonomy|string $taxonomy Variable to be resolved to a taxonomy.
 * @return ?WP_Taxonomy
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
 * Get the primary key value of a WordPress object
 * @since 1.0.0
 * @param WP_Post|WP_Term $obj
 * @return int
 */
function resolve_object_id(Object $obj): int
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
 * Get a flat array of all descendants for a given post.
 * @since 1.1.0
 * @param WP_Post|int|string|null $post
 * @param int $depth
 * @param bool $check_post_type
 * @return WP_Post[]
 */
function get_post_descendants($post = null, int $depth = -1, bool $check_post_type = true): array
{
	$post = resolve_post($post);
	if ($check_post_type && !is_post_type_hierarchical($post->post_type)) {
		return [];
	}
	$descendants = [];
	$children = get_posts([
		"post_type" => $post->post_type,
		"post_parent" => $post->ID,
		"posts_per_page" => -1
	]);
	foreach ($children as $child) {
		$descendants[] = $child;
		if ($depth !== 0) {
			$descendants = array_merge($descendants, get_post_descendants($child, $depth - 1, false));
		}
	}
	return $descendants;
}

/**
 * Collapse an array to only (a) string keys for truthy values and (b) numerically indexed strings
 *
 * modeled after [classnames](https://www.npmjs.com/package/classnames) on NPM
 *
 * @since 1.2.0 Returns string instead of string array
 * @since 1.0.0
 * @param mixed[] $classes
 * @return string
 */
function class_names(array $classes): string
{
	$class_names = [];
	foreach ((array) $classes as $key => $value) {
		if (is_array($value)) {
			$class_names[] = class_names($value);
		} else if (is_string($key)) {
			if (!!$value) {
				$class_names[] = $key;
			}
		} else if (is_string($value)) {
			$class_names[] = $value;
		}
	}
	return implode(' ', $class_names);
}

/**
 * Retrieve from an array only (a) string keys for truthy values and (b) numerically indexed strings
 *
 * Similar to [classnames](https://www.npmjs.com/package/classnames) on NPM
 *
 * @since 2.0.1
 * @param mixed[] $classes
 * @return string[]
 */
function class_names_array(array $classes): array
{
	$class_names = [];
	foreach ((array) $classes as $key => $value) {
		if (is_array($value)) {
			$class_names[] = class_names($value);
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
 * @since 1.0.0
 * @param string[]|string $class_list List of class names.
 * @return string
 */
function resolve_class_list(...$class_list): string
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
 * @since 1.0.0
 * @param mixed[] $attrs
 * @return string
 */
function html_attrs(array $attrs): string
{
	$attrs = array_map(function ($value) {
		return fallback_until("is_string", $value, esc_attr(var_export($value, true)));
	}, array_filter($attrs, "Denman_Utils\\v2\\is_not_null"));
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
 * @since 1.0.0
 * @param string $image_url
 * @return int
 */
function get_image_id(string $image_url): int
{
	global $wpdb;
	$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url));
	return $attachment[0] ?? 0;
}

/**
 * Get a post from its slug.
 *
 * @since 1.0.0
 * @param string $slug The post slug.
 * @param string $post_type The post-type slug, default is 'any'.
 * @return WP_Post|null
 */
function get_post_by_slug(string $slug, string $post_type = 'any', string $post_status = "any"): ?WP_Post
{
	$posts = get_posts([
		'name' => $slug,
		'post_type' => $post_type,
		'post_status' => $post_status,
		'numberposts' => 1,
	]);
	return $posts[0] ?? null;
}

/**
 * Join path segments with a slash
 * * strips extra slashes on segments
 *
 * @since 1.0.0
 * @param string[] $segments Array of path segments.
 * @return string
 */
function join_path_segments(array $segments): string
{
	$path = implode('/', array_map(function ($segment) {
		return trim($segment, '/');
	}, $segments));
	if (\str_starts_with($segments[0], '/')) {
		$path = '/' . $path;
	}
	if (\str_ends_with(array_nth($segments, -1), '/')) {
		$path .= '/';
	}
	return $path;
}

/**
 * Get the URI for a theme asset.
 *
 * @since 1.0.0
 * @uses str_prefix
 * @uses resolve_arglist
 * @uses join_path_segments
 * @param string[]|string ...$segments A series or array of path segments.
 * @return string
 */
function get_asset_uri(string ...$segments): string
{
	$path = join_path_segments(resolve_arglist($segments));
	return get_template_directory_uri() . str_prefix($path, '/');
}

/**
 * Get the path for a theme asset file.
 *
 * @since 1.0.0
 * @uses str_prefix
 * @uses resolve_arglist
 * @uses join_path_segments
 * @param string[]|string ...$segments A series or array of path segments.
 * @return string
 */
function get_asset_path(string ...$segments): string
{
	$path = join_path_segments(resolve_arglist($segments));
	return get_template_directory() . str_prefix($path, '/');
}

/**
 * Get the contents of a theme asset file.
 *
 * @since 1.0.0
 * @uses get_asset_path
 * @param string[]|string ...$segments A series or array of path segments.
 * @return string
 */
function get_asset_contents(string ...$path_segments): string
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
 * @since 1.2.0 added H1-6, UL, OL
 * @since 1.0.0
 * @param string $md Markdown content.
 * @return string Parsed HTML.
 */
function mini_markdown_parse(string $md): string
{
	$rules = [
		// '/^-|*|={3,}$/m' => 			// '<hr>', // hr
		'/-{3}/' => '&mdash;', // em
		'/-{2}/' => '&ndash;', // en
		'/(?:\*{2}((?:[^*]|(?:\\\*))+)\*{2})|(?:_{2}((?:[^_]|(?:\\_))+)_{2})/' => '<strong>$1$2</strong>', // bold
		'/(?:\*((?:[^*]|(?:\\\*))+)\*)|(?:_((?:[^_]|(?:\\_))+)_)/' => '<em>$1$2</em>', // italic
		'/`([^`]+)`/' => '<code>$1</code>', // code
		'/\[([^\]]+)\]\(([^)]+)\)/' => '<a href="$2">$1</a>', // link with text
		'/\[\]\(([^)]+)\)/' => '<a href="$1">$1</a>', // link
		'/^#{6}\s(.*)/m' => '<h6>$1</h6>', // h6s
		'/^#{5}\s(.*)/m' => '<h5>$1</h5>', // h5s
		'/^#{4}\s(.*)/m' => '<h4>$1</h4>', // h4s
		'/^#{3}\s(.*)/m' => '<h3>$1</h3>', // h3s
		'/^#{2}\s(.*)/m' => '<h2>$1</h2>', // h2s
		'/^#{1}\s(.*)/m' => '<h1>$1</h1>', // h1s
		'/^[*-]\s(.*)/m' => "<ul><li>$1</li></ul>", // ul lists
		'/^[0-9]+\.\s(.*)/m' => "<ol><li>$1</li></ol>", // ol lists
		'/<\/ul>\s*<ul>/' => "", // fix extra ul // fix extra ul
		'/<\/ol>\s*<ol>/' => "", // fix extra ol // fix extra ol
		'/^([^<]]+)(?:(?: {2,}\R*)|\R{2,}|(?:\s*$))/m' => '<p>$1</p>', // paragraphs
		"/(.+)\R{}/" => '$1<br>', // line breaks
	];

	foreach ($rules as $search => $replace) {
		$md = preg_replace($search, $replace, $md);
	}
	return $md;
}

/**
 * Strip the case of a string: no capitals, words separated by a single space.
 * @since 1.0.0
 * @param string $str
 * @return string
 */
function strip_case(string $str): string
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
 * @since 1.0.0
 * @param string $str
 * @return string
 */
function title_case(string $str): string
{
	return ucwords(strip_case($str));
}

/**
 * Sentence case a string, i.e. This text is sentenced.
 *
 * @uses strip_case
 * @since 1.0.0
 * @param string $str
 * @return string
 */
function sentence_case(string $str): string
{
	return ucfirst(strip_case($str));
}

/**
 * Pascal case a string, i.e. ThisTextIsPacaled.
 *
 * @uses strip_case
 * @since 1.0.0
 * @param string $str
 * @return string
 */
function pascal_case(string $str): string
{
	return str_replace(' ', '', ucwords(strip_case($str)));
}

/**
 * Snake case a string, i.e. this_text_is_snaked.
 *
 * @uses strip_case
 * @since 1.0.0
 * @param string $str
 * @return string
 */
function snake_case(string $str): string
{
	return str_replace(' ', '_', strip_case($str));
}

/**
 * Kebab case a string, i.e. this-text-is-kebabed.
 *
 * @uses strip_case
 * @since 1.0.0
 * @param string $str
 * @return string
 */
function kebab_case(string $str): string
{
	return str_replace(' ', '-', strip_case($str));
}

/**
 * Camel case a string, i.e. thisTextIsCameled.
 *
 * @uses pascal_case
 * @since 1.0.0
 * @param string $str
 * @return string
 */
function camel_case(string $str): string
{
	return lcfirst(pascal_case($str));
}

/** Get an array of registered public custom taxonomies.
 *
 * * Be sure to only call AFTER your custom taxonomies have been registered
 * * Taxonomies MUST have been registered with ['public' => true]
 * @since 1.0.0
 * @param string[]|string $exclude Optional. Taxonomy slug(s) to exclude.
 * @return WP_Taxonomy[]
 */
function get_custom_taxonomies(string|array $exclude = ''): array
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
 * @since 1.0.0
 * @param string[]|string $exclude Optional. Post type slug(s) to exclude.
 * @return WP_Post_Type[]
 */
function get_custom_post_types(string|array $exclude = ""): array
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

/**
 * Get a string representation of elapsed time.
 * @since 1.0.0
 * @param int $datetime Unix timestamp.
 * @param bool $full Optional. Whether to output all non-zero time divisions or just the largest. Default false.
 * @return string
 */
function time_elapsed_string($datetime, bool $full = false): string
{
	$now = new DateTime;
	$ago = new DateTime($datetime);
	$diff = (object) $now->diff($ago);

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

/**
 * Convert newline characters to <br> tags
 * @since 1.0.0
 * @param string $str
 * @return string
 */
function preserve_newlines(string $str): string
{
	return preg_replace("/\R/", "<br>", $str);
}

/**
 * Undo WordPress' automatic <p> tagging.
 * @since 1.0.0
 * @param string $str
 * @return string
 */
function reverse_wpautop(string $str): string
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

/**
 * Check for either of the "read more" tags within a post's content.
 * @since 1.1.0
 * @param WP_Post|int|string|null $post Optional. Post the check content, defaults to current post.
 * @return bool
 */
function has_read_more_tag($post = null): bool
{
	$post = resolve_post($post);
	return $post && (has_block("wp:more", $post) || preg_match("/<!--more(.*?)?-->/", $post->post_content));
}

/**
 * Given an array of blocks, append any contained Inner Blocks.
 * @since 1.1.0
 * @param WP_Block[] $blocklist
 * @return WP_Block[]
 */
function flatten_blocklist(array $blocklist): array
{
	return array_reduce($blocklist, function ($carry, $item) {
		$carry[] = $item;
		if (!empty($item["innerBlocks"])) {
			$carry = array_merge($carry, flatten_blocklist($item["innerBlocks"]));
		}
		return $carry;
	}, []);
}

/**
 * Escape a regex string
 * @since 1.0.0
 * @param string $str
 * @return string
 */
function esc_regex(string $str): string
{
	return preg_replace('/([^\w])/', '\\\$0', $str);
}

/**
 * Return a function that only runs if a global flag is falsey.
 * @since 1.0.0
 * @param string $flag_name - Name of flag variable in the global scope.
 * @param callable $callable - Callable to execute if flag is falsey.
 * @param bool $is_filter - Optional. If true, and flag prevents execution, return the first supplied argument when the returned function is called. Default false.
 * @return callable
 */
function flag_block(string $flag_name, callable $callable, bool $is_filter = false): callable
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
 * @since 1.0.0
 * @param string $flag_name - Name of flag variable in the global scope.
 * @param callable $callable - Callable to execute if flag is falsey.
 * @param bool $is_filter - Optional. If true, and flag prevents execution, return the first supplied argument when the returned function is called. Default false.
 * @return callable
 */
function flag_pass(string $flag_name, callable $callable, bool $is_filter = false): callable
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
 * Return a copy of a function with some arguments prefilled.
 *
 * @since 1.0.0
 * @param callable $callable - The function/method to prefill.
 * @param mixed $args - Arguments to prefill in the order supplied.
 * @return callable
 */
function prefill(callable $callable, ...$args): callable
{
	return function () use ($callable, $args) {
		return call_user_func_array($callable, array_merge($args, func_get_args()));
	};
}

/**
 * Try to make sure you have a desired amount of posts.
 *
 * @throws InvalidArgumentException if $posts is not an array or WP_Query.
 * @since 1.0.0
 * @param WP_Post[]|WP_Query $posts - The posts you have already.
 * @param int $desired_post_count - The desired number of posts.
 * @param mixed[] $wp_query_args - Optional. The query args to look for posts to pad out your results.
 * @param boolean|null $return_as_query - Optional. Whether to return the posts as a WP_Query. If a WP_Query is supplied as $posts, defaults to true, otherwise defaults to false.
 * @param boolean $exclude_global_post - Optional. Whether to exclude the current global $post from results. Default true.
 * @return WP_Post[]|WP_Query
 */
function pad_posts($posts, int $desired_post_count, array $wp_query_args = [], $return_as_query = null, $exclude_global_post = true)
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

/**
 * Wrap a string of content in a post permalink anchor tag HTML, unless the context is of a single post of that type.
 * @since 1.0.0
 * @param string $content_str
 * @param string $extra_attr_str
 * @param mixed $post
 * @return string
 */
function link_unless_singular(string $content_str, string $extra_attr_str = "", $post = null): string
{
	$post = resolve_post($post);

	if (!$post || is_singular($post->post_type)) return $content_str;

	$extra_attr_str = $extra_attr_str ? " $extra_attr_str" : "";
	return sprintf("<a href=\"%s\"%s>%s</a>", esc_url(get_the_permalink($post)), $extra_attr_str, $content_str);
}

/**
 * Pass a value through any number of filter hooks sequentially.
 * Basically, short for apply_filters("hook2", apply_filters("hook1", $value))...
 * @since 1.0.0
 * @param string[] $hooks - Filter hooks.
 * @param mixed ...$args - Arguments for filters.
 * @return mixed
 */
function apply_filters_sequence(array $hooks, ...$args)
{
	$hooks = array_filter($hooks, "has_filter");
	$value = array_pop($args);
	return array_reduce($hooks, function ($filtered, $next_hook) use ($args) {
		$filter_args = array_concat([$filtered], $args);
		return apply_filters_ref_array($next_hook, $filter_args);
	}, $value);
}

/**
 * Pass a value to any number of action hooks sequentially.
 * @since 1.0.0
 * @param string[] $hooks - Action hooks.
 * @param mixed ...$args - Arguments for actions.
 */
function do_actions_sequence(array $hooks, ...$args): void
{
	$hooks = array_filter($hooks, "has_action");
	foreach ($hooks as $hook) {
		do_action_ref_array($hook, $args);
	}
}

/**
 * Get the domain of a URL.
 * @since 1.1.0
 * @since 2.0.3 added $include_subdomains param.
 * @param string $url The URL to parse.
 * @param bool|int $include_subdomains The max number of subdomains allowed. Set boolean `true` to allow up to (almost) PHP_INT_MAX subdomains.
 * @return string
 */
function get_domain_of_url(string $url, bool|int $include_subdomains = false): string
{
	$host = parse_url($url, PHP_URL_HOST);
	if (empty($host)) return "";
	// get the number of allowable subdomains
	$include_subdomains = $include_subdomains === true ? PHP_INT_MAX - 2 : min_max(intval($include_subdomains), 0, PHP_INT_MAX - 2);
	return implode(".", array_slice(explode(".", $host), -1 * ($include_subdomains + 2)));
}

/**
 * Check if a URL is (most likely) to go to an external resource.
 * @since 1.1.0
 * @since 2.0.3 added $allow_subdomains param.
 * @param string $url The URL to parse.
 * @param bool $allow_subdomains Whether to treat different subdomains as local or external to each other.
 * @return bool
 */
function is_link_external(string $url, bool $allow_subdomains = true): bool
{
	$url_domain = get_domain_of_url($url, true);
	if (empty($url_domain)) return false;  // we will treat any url without an explicit domain as relative, ie: not external

	$local_domain = get_domain_of_url(get_bloginfo("url"), $allow_subdomains);
	if (strcasecmp($url_domain, $local_domain) === 0) return false; // url domain matches the local domain, ie: not external
	if ($allow_subdomains) {
		return (strrpos(strtolower($url_domain), ".$local_domain") !== strlen($url_domain) - strlen(".$local_domain")); // check if the url domain is a subdomain of the local domain
	} else {
		return strcasecmp($url_domain, "www.$local_domain") !== 0; // check if url domain uses "www" subdomain of local domain, ie: not external
	}
}

/**
 * Check if a URL is (most likely) to go to an asset file.
 * @since 1.2.0
 * @param string $url
 * @param string[] $exts Extensions to check for.
 * @return bool
 */
function is_link_asset(string $url, array $exts = []): bool
{
	$url_components = parse_url($url);
	$path = $url_components['path'] ?? '';
	if (!$path) {
		// no file/directory path in url
		return false;
	}
	$matches = [];
	if (!preg_match("/\\.([a-zA-Z]{3,4})(?:\\?.*?)?$/", $path, $matches)) {
		// no file extension in url
		return false;
	}
	$extension = $matches[0];

	if ($exts) {
		// If desired extension specified, check for that
		return in_array($extension, $exts);
	}
	// else just return if it's not a hypertext file extension
	return !in_array($extension, ["php", "html", "htm"]);
}

/**
 * Check if a URL is relative.
 * @since 1.1.0
 * @param string $url
 * @return bool
 */
function is_link_relative(string $url): bool
{
	$url_components = parse_url($url);
	return empty($url_components['host']);
}

/**
 * Get an array of RGBA values from a hexidecimal color string.
 * @since 1.1.0
 * @param string $hex_color
 * @param float $alpha_fallback
 * @return array
 */
function hex_str_to_rgba_array(string $hex_color, float $alpha_fallback = 1.0): array
{
	$hex_color = trim(strtolower($hex_color));
	if (\str_starts_with($hex_color, "#")) {
		$hex_color = substr($hex_color, 1);
	}
	$alpha = $alpha_fallback;
	switch (strlen($hex_color)) {
		case 8:
			$alpha =  hexdec(substr($hex_color, 6, 2));
		case 6:
			$red = hexdec(substr($hex_color, 0, 2));
			$green = hexdec(substr($hex_color, 2, 2));
			$blue = hexdec(substr($hex_color, 4, 2));
			return compact("red", "green", "blue", "alpha");
		case 3:
			$red = hexdec(str_repeat(substr($hex_color, 0, 1), 2));
			$green = hexdec(str_repeat(substr($hex_color, 1, 1), 2));
			$blue = hexdec(str_repeat(substr($hex_color, 2, 1), 2));
			return compact("red", "green", "blue", "alpha");
		default:
			return [];
	}
}

/**
 * Get an array of RGBA values from an RGBA color string.
 * @since 1.1.0
 * @param string $hex_color
 * @return array
 */
function rgba_str_to_rgba_array(string $rgba_str): array
{
	$rgba_str = trim(strtolower($rgba_str));
	$matches = [];
	if (preg_match("/rgba?\\(\\s*(\\d+)\\s*,\\s*(\\d+)\\s*,\\s*(\\d+)\\s*(?:,\\s*([01]\\.?[0-9]*)\\s*)?\\)/", $rgba_str, $matches)) {
		return [
			"red" => $matches[1],
			"green" => $matches[2],
			"blue" => $matches[3],
			"alpha" => $matches[4] ?? 1,
		];
	}
	return [];
}

/**
 * Calculate the approximate luma value of an RGB color.
 * @deprecated 2.0.3
 * @since 1.1.3
 * @param string|array $rgba
 * @return float
 */
function rgba_to_luma(string|array $rgba): float
{
	_deprecated_function("Denman_Utils\\v2\\rgba_to_luma", "Denman_Utils v2.0.3", "Denman_Utils\\v2\\rgba_to_luminance");
	if (is_string($rgba)) {
		$rgba = rgba_str_to_rgba_array($rgba);
	}
	foreach ($rgba as $name => $value) {
		$adjusted = 0;
		$value = $value / 255;

		if ($value < 0.03928) {
			$value = $value / 12.92;
		} else {
			$value = ($value + 0.055) / 1.055;
			$value = pow($value, 2.4);
		}
		$rgba[$name] = $value;
	}
	return (float) $rgba["red"] * 0.2126 + $rgba["green"] * 0.7152 +
		$rgba["blue"] * 0.0722;
}

/**
 * Calculate the approximate luminance value of an RGBA color
 * @param string|array $rgba
 * @return float
 */
function rgba_to_luminance(string|array $rgba): float
{
	if (is_string($rgba)) {
		$rgba = rgba_str_to_rgba_array($rgba);
	}
	$rgba_linearized = [];
	foreach ($rgba as $channel => $value) {
		$value = $value / 255;
		if ($value < 0.04045) {
			$value = $value / 12.92;
		} else {
			$value = ($value + 0.055) / 1.055;
			$value = pow($value, 2.4);
		}
		$rgba_linearized[$channel] = $value;
	}

	return (float) $rgba_linearized["red"] * 0.2126 + $rgba_linearized["green"] * 0.7152 +
		$rgba_linearized["blue"] * 0.0722;
}


/**
 * Assert an array of RGBA color information to be a sequentially indexed array.
 * @since 1.1.3
 * @param array $rgba RGBA color information
 * @return array
 */
function rgba_array_to_sequence(array $rgba): array
{
	return [
		(int) ($rgba["red"] ?? $rgba["r"] ?? $rgba[0] ?? 0),
		(int) ($rgba["green"] ?? $rgba["g"] ?? $rgba[1] ?? 0),
		(int) ($rgba["blue"] ?? $rgba["b"] ?? $rgba[2] ?? 0),
		(float) ($rgba["alpha"] ?? $rgba["a"] ?? $rgba[3] ?? 1)
	];
}

/**
 * Assert an array of RGBA color information to be an associatively indexed array.
 * @since 1.1.3
 * @param array $rgba RGBA color information
 * @return array
 */
function rgba_array_to_assoc(array $rgba): array
{
	return [
		"red" => (int) ($rgba["red"] ?? $rgba["r"] ?? $rgba[0] ?? 0),
		"green" => (int) ($rgba["green"] ?? $rgba["g"] ?? $rgba[1] ?? 0),
		"blue" => (int) ($rgba["blue"] ?? $rgba["b"] ?? $rgba[2] ?? 0),
		"alpha" => (float) ($rgba["alpha"] ?? $rgba["a"] ?? $rgba[3] ?? 1)
	];
}

/**
 * Mix 2 RGB colours.
 * ! Discards alpha information.
 * @since 1.1.3
 * @param int[] $rgb_color_1 RGB array.
 * @param int[] $rgb_color_2 RGB array.
 * @param float $weight
 * @return int[]
 */
function mix_rgb(array $rgb_color_1 = [0, 0, 0], array $rgb_color_2 = [0, 0, 0], float $weight = 0.5): array
{
	$color_1_weighted = array_map(function ($x) use ($weight) {
		return $weight * $x;
	}, $rgb_color_1);
	$color_2_weighted = array_map(function ($x) use ($weight) {
		return (1 - $weight) * $x;
	}, $rgb_color_2);
	return array_map(function ($x, $y) {
		return round($x + $y);
	}, $color_1_weighted, $color_2_weighted);
}

/**
 * Mix pure white into an RGBA color.
 * @since 1.1.3
 * @param array $color RGBA color data.
 * @param float $weight Proportion of white in tint.
 * @return array
 */
function tint_rgba(array $color, float $weight = 0.5): array
{
	$color = rgba_array_to_sequence($color);
	$alpha = 1;
	if (count($color) === 4) {
		$alpha = array_pop($color);
	}
	$tint = mix_rgb($color, [255, 255, 255], $weight);
	$tint[] = $alpha;
	return $tint;
}

/**
 * Mix 50% grey into an RGBA color.
 * @since 1.1.3
 * @param array $color RGBA color data.
 * @param float $weight Proportion of grey in tone.
 * @return int[]
 */
function tone_rgba(array $color, float $weight = 0.5): array
{
	$color = rgba_array_to_sequence($color);
	$alpha = 1;
	if (count($color) === 4) {
		$alpha = array_pop($color);
	}
	$tone = mix_rgb($color, [128, 128, 128], $weight);
	$tone[] = $alpha;
	return $tone;
}

/**
 * Mix pure black into an RGBA color.
 * @since 1.1.3
 * @param array $color RGBA color data.
 * @param float $weight Proportion of black in shade.
 * @return int[]
 */
function shade_rgba(array $color, float $weight = 0.5): array
{
	$color = rgba_array_to_sequence($color);
	$alpha = 1;
	if (count($color) === 4) {
		$alpha = array_pop($color);
	}
	$shade = mix_rgb($color, [0, 0, 0], $weight);
	$shade[] = $alpha;
	return $shade;
}

/**
 * Convert an array of RGBA color information to a hexidecimal color string.
 * @since 1.1.3
 * @param array $rgba_values
 * @param bool $strip_alpha
 * @return string
 */
function rgba_to_hex(array $rgba_values, bool $strip_alpha = false): string
{
	$red = min_max(($rgba_values["red"] ?? $rgba_values["r"] ?? 127), 0, 255);
	$green = min_max(($rgba_values["green"] ?? $rgba_values["g"] ?? 127), 0, 255);
	$blue = min_max(($rgba_values["blue"] ?? $rgba_values["b"] ?? 127), 0, 255);
	$alpha = floor(min_max(($rgba_values["alpha"] ?? $rgba_values["a"] ?? 1) * 255, 0, 255));
	$hex = sprintf("#%0x%0x%0x%0x", $red, $green, $blue, $alpha);
	return $strip_alpha ? substr($hex, 0, 7) : $hex;
}

/**
 * Render an RGBA array to string.
 * @since 1.1.0
 * @param array $rgba_values
 * @return string
 */
function render_rgba_array(array $rgba_values): string
{
	$red = $rgba_values["red"] ?? $rgba_values["r"] ?? 127;
	$green = $rgba_values["green"] ?? $rgba_values["g"] ?? 127;
	$blue = $rgba_values["blue"] ?? $rgba_values["b"] ?? 127;
	$alpha = $rgba_values["alpha"] ?? $rgba_values["a"] ?? 1;
	return sprintf("rgba(%d,%d,%d,%.3F)", $red, $green, $blue, $alpha);
}

/**
 * Register Admin AJAX callbacks easily.
 * @since 1.1.0
 * @param string $action
 * @param callable $generic_callback
 * @param callable|null $logged_in_callback
 * @return bool
 */
function register_ajax_callback(string $action, callable $generic_callback, $logged_in_callback = null): bool
{
	if (!$action || !$generic_callback || !is_callable($generic_callback)) return false;
	if (!$logged_in_callback || !is_callable($logged_in_callback)) {
		$logged_in_callback = $generic_callback;
	}
	add_action("wp_ajax_nopriv_{$action}", $generic_callback);
	add_action("wp_ajax_{$action}", $logged_in_callback);
	return true;
}

/**
 * Convert URLs and email addresses to links in plaintext.
 * @since 1.2.0
 * @param string $text
 * @param bool $new_tab
 * @return string
 */
function make_plaintext_clickable(string $text, bool $new_tab = false): string
{
	$text = make_clickable(strip_tags($text));
	if ($new_tab) {
		$text = preg_replace("/(<a[^>]*?rel=(['\"])nofollow)(\\2)([^>]*?>.*?<\/a>)/", '$1 noreferrer noopener$3 target="_blank"$4', $text);
	}
	$text = preg_replace("/(<a[^>]*?>)https?:\/\/(.*?<\/a>)/", "$1$2", $text);
	return $text;
}

/**
 * Sanitize title with multiple slug segments.
 * @since 1.2.0
 * @param string $value
 * @return string
 */
function sanitize_title_multiple(string $value): string
{
	return implode("/", array_filter(array_map("sanitize_title", explode("/", urldecode($value))), function ($val) {
		return !!$val;
	}));
}

/**
 * Combine multiple and optional style rules into an inline style string.
 * @since 1.2.0
 * @param array $styles
 * @return string
 */
function inline_styles(array ...$styles): string
{
	$inline_styles = [];
	$style_rules = array_merge(...$styles);
	foreach ((array) $style_rules as $property => $value) {
		if (is_string($property) && isset($value) && $value !== "") {
			$inline_styles[$property] = $value;
		}
	}
	$output = "";
	array_walk($inline_styles, function ($val, $prop) use (&$output) {
		$output .= "$prop:$val;";
	});
	return $output;
}

/**
 * Render a single style rule value for a Block.
 * @param ?string $value
 * @return ?string
 */
function render_wp_block_style_rule(?string $value): ?string
{
	if ($value && \str_starts_with($value, "var:")) {
		return "var(--wp--" . join("--", explode("|", substr($value, 4))) . ")";
	}
	return $value;
}

/**
 * Render a 4-directional spacing style rule value.
 * @param string[] $values
 * @return string
 */
function render_wp_block_spacing(array $values): string
{
	$values = wp_parse_args($values, ["top" => 0, "right" => 0, "bottom" => 0, "left" => 0]);
	$output = [];
	foreach ($values as $dir => $value) {
		$values[$dir] = render_wp_block_style_rule($value);
	}
	$output[] = $values["top"];
	if (count(array_unique($values)) > 1) {
		$output[] = $values["right"];
		if ($values["right"] !== $values["left"]) {
			$output[] = $values["bottom"];
			$output[] = $values["left"];
		} else if ($values["top"] !== $values["bottom"]) {
			$output[] = $values["bottom"];
		}
	}
	return implode(" ", $output);
}
