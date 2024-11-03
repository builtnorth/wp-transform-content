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


use WP_CLI;
use WP_CLI_Command;
use BuiltNorth\WPTransformContent\Transformers\TransformPostType;
use BuiltNorth\WPTransformContent\Exceptions\TransformException;

class TransformCommand extends WP_CLI_Command
{
	public function __construct()
	{
		parent::__construct();
	}

	public function __invoke($args, $assoc_args)
	{
		WP_CLI::line('Usage: wp content-transform <subcommand>');
		WP_CLI::line('');
		WP_CLI::line('Subcommands:');
		WP_CLI::line('  post-type    Transform posts from one type to another');
		WP_CLI::line('');
		WP_CLI::line('For more information about a subcommand, run:');
		WP_CLI::line('  wp help content-transform <subcommand>');
	}

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
