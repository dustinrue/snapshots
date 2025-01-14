<?php
/**
 * Snapshot meta interface
 *
 * @package TenUp\Snapshots
 */

namespace TenUp\Snapshots\Snapshots;

use TenUp\Snapshots\Exceptions\SnapshotsException;
use TenUp\Snapshots\Infrastructure\SharedService;

/**
 * Interface for napshot meta wrapper with support for downloading remote meta
 *
 * @package TenUp\Snapshots\Snapshots
 */
interface SnapshotMetaInterface extends SharedService {

	/**
	 * Save snapshot meta locally
	 *
	 * @param string $id Snapshot ID.
	 * @param array  $meta Snapshot meta.
	 * @return int Number of bytes written
	 */
	public function save_local( string $id, array $meta );

	/**
	 * Download meta from remote DB
	 *
	 * @param string $id Snapshot ID
	 * @param string $profile AWS profile
	 * @param string $repository Repository name
	 * @param string $region AWS region
	 * @return array
	 */
	public function get_remote( string $id, string $profile, string $repository, string $region ) : array;

	/**
	 * Get local snapshot meta
	 *
	 * @param  string $id Snapshot ID
	 * @param  string $repository Repository name
	 * @return mixed
	 *
	 * @throws SnapshotsException Snapshot meta invalid.
	 */
	public function get_local( string $id, string $repository );

	/**
	 * Generates snapshot meta
	 *
	 * @param string $id Snapshot ID.
	 * @param array  $args Snapshot data.
	 */
	public function generate( string $id, array $args ) : void;
}
