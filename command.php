<?php

if (!class_exists('WP_CLI')) {
	return;
}

$autoloader = dirname(__FILE__) . '/vendor/autoload.php';
if (file_exists($autoloader)) {
	require_once $autoloader;
}

/**
 * Transform WordPress content from one type to another.
 *
 * ## EXAMPLES
 *
 *     # Transform posts to a different post type
 *     $ wp content-transform post-type post article
 *
 *     # Show available transformations
 *     $ wp content-transform --help
 */
WP_CLI::add_command(
	'content-transform',
	'BuiltNorth\WPTransformContent\Commands\TransformCommand',
	[
		'shortdesc' => 'Transform content from one type to another.',
		'when' => 'after_wp_load',
		'is_composite' => true
	]
);
