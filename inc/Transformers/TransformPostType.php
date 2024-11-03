<?php

/**
 * -----------------------------------------------------------------------------
 * Transform Post Type
 * -----------------------------------------------------------------------------
 * 
 * Transform a post type to another.
 * 
 * @package WPTransformContent
 * @since 0.0.1
 */

namespace BuiltNorth\WPTransformContent\Transformers;

use WP_CLI;
use BuiltNorth\WPTransformContent\Exceptions\TransformException;


// Don't load directly.
defined('ABSPATH') || exit;

class TransformPostType
{
	/**
	 * @var int
	 */
	protected $batch_size;

	/**
	 * @var bool
	 */
	protected $dry_run;

	/**
	 * @var string
	 */
	protected $from_type;

	/**
	 * @var string
	 */
	protected $to_type;

	public function __construct(string $from_type, string $to_type, int $batch_size = 100, bool $dry_run = false)
	{
		$this->from_type = $from_type;
		$this->to_type = $to_type;
		$this->batch_size = $batch_size;
		$this->dry_run = $dry_run;
	}

	public function validate(): bool
	{
		WP_CLI::log("Checking post types...");
		WP_CLI::log("From type: " . $this->from_type);
		WP_CLI::log("To type: " . $this->to_type);

		if (!post_type_exists($this->from_type)) {
			throw new TransformException("Source post type '{$this->from_type}' does not exist.");
		}
		if (!post_type_exists($this->to_type)) {
			throw new TransformException("Target post type '{$this->to_type}' does not exist.");
		}
		return true;
	}

	public function transform(callable $progress_callback = null): array
	{
		$this->validate();

		$stats = [
			'total' => $this->getTotal(),
			'processed' => 0,
			'failed' => 0
		];

		if ($this->dry_run) {
			WP_CLI::log(sprintf(
				'Would transform %d posts from %s to %s',
				$stats['total'],
				$this->from_type,
				$this->to_type
			));
			return $stats;
		}

		$paged = 1;
		while (true) {
			$query = new \WP_Query([
				'post_type' => $this->from_type,
				'posts_per_page' => $this->batch_size,
				'post_status' => 'any',
				'fields' => 'ids',
				'paged' => $paged,
				'no_found_rows' => true
			]);

			if (empty($query->posts)) {
				break;
			}

			foreach ($query->posts as $post_id) {
				try {
					wp_update_post([
						'ID' => $post_id,
						'post_type' => $this->to_type
					]);
					$stats['processed']++;

					if ($progress_callback) {
						call_user_func($progress_callback, $stats['processed'], $stats['total']);
					}
				} catch (\Exception $e) {
					$stats['failed']++;
					WP_CLI::warning(sprintf('Failed to transform post %d: %s', $post_id, $e->getMessage()));
				}
			}

			$paged++;
			wp_cache_flush();
		}

		return $stats;
	}

	public function getTotal(): int
	{
		$query = new \WP_Query([
			'post_type' => $this->from_type,
			'posts_per_page' => -1,
			'post_status' => 'any',
			'fields' => 'ids',
			'no_found_rows' => false
		]);

		return (int) $query->found_posts;
	}
}
