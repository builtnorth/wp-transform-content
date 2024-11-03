<?php

if (!defined('WP_CLI')) {
	return;
}

if (!class_exists('WP_CLI')) {
	return;
}

WP_CLI::add_command(
	'content-transform',
	[
		'post-type' => function ($args, $assoc_args) {
			$command = new \BuiltNorth\WPTransformContent\Commands\TransformCommand();
			$command->post_type($args, $assoc_args);
		}
	],
	[
		'shortdesc' => 'Transform content from one type to another.',
		'when' => 'after_wp_load'
	]
);
