<?php

/**
 * -----------------------------------------------------------------------------
 * Transform Command
 * -----------------------------------------------------------------------------
 *
 * @package WPTransformContent
 * @since 0.0.1
 */

namespace BuiltNorth\WPTransformContent\Commands;

// Don't load directly.
defined('ABSPATH') || exit;

use WP_CLI;
use BuiltNorth\WPTransformContent\Transformers\TransformPostType;
use BuiltNorth\WPTransformContent\Exceptions\TransformException;

class TransformCommand
{
	/**
	 * Transform posts from one post type to another.
	 *
	 * ## OPTIONS
	 *
	 * <from>
	 * : Source post type
	 *
	 * <to>
	 * : Target post type
	 *
	 * [--batch-size=<number>]
	 * : Number of posts to process at once
	 * ---
	 * default: 100
	 * ---
	 *
	 * [--dry-run]
	 * : Show what would be transformed without making changes
	 *
	 * ## EXAMPLES
	 *
	 *     # Transform all posts to articles
	 *     $ wp content-transform post-type post article
	 *
	 *     # Dry run with custom batch size
	 *     $ wp content-transform post-type post article --batch-size=50 --dry-run
	 */
	public function post_type($args, $assoc_args)
	{
		list($from_type, $to_type) = $args;

		$batch_size = (int) ($assoc_args['batch-size'] ?? 100);
		$dry_run = isset($assoc_args['dry-run']);

		try {
			$transformer = new TransformPostType($from_type, $to_type, $batch_size, $dry_run);

			WP_CLI::log(sprintf('Starting transformation from "%s" to "%s"...', $from_type, $to_type));

			$progress = \WP_CLI\Utils\make_progress_bar(
				'Transforming posts',
				$transformer->getTotal()
			);

			$stats = $transformer->transform(function ($current, $total) use ($progress) {
				$progress->tick();
			});

			$progress->finish();

			WP_CLI::success(sprintf(
				'Transformation complete. Processed: %d, Failed: %d, Total: %d',
				$stats['processed'],
				$stats['failed'],
				$stats['total']
			));
		} catch (TransformException $e) {
			WP_CLI::error($e->getMessage());
		}
	}
}
