<?php
/**
 * Interface for Storage Connectors.
 *
 * @package TenUp\Snapshots
 */

namespace TenUp\Snapshots\Snapshots;

use TenUp\Snapshots\Infrastructure\SharedService;

/**
 * Interface StorageConnectorInterface
 *
 * @package TenUp\Snapshots
 */
interface DBConnectorInterface extends SharedService {

	/**
	 * Searches the database.
	 *
	 * @param  string|array $query Search query string
	 * @param string       $profile AWS profile.
	 * @param  string       $repository Repository name
	 * @param string       $region AWS region
	 * @return array
	 */
	public function search( $query, string $profile, string $repository, string $region ) : array;

	/**
	 * Get a snapshot given an id
	 *
	 * @param  string $id Snapshot ID
	 * @param string $profile AWS profile.
	 * @param  string $repository Repository name
	 * @param string $region AWS region
	 * @return mixed
	 */
	public function get_snapshot( string $id, string $profile, string $repository, string $region );

	/**
	 * Create default DB tables. Only need to do this once ever for repo setup.
	 *
	 * @param string $profile AWS profile.
	 * @param string $repository Repository name
	 * @param string $region AWS region
	 */
	public function create_tables( string $profile, string $repository, string $region );

	/**
	 * Insert a snapshot into the DB
	 *
	 * @param  string $id Snapshot ID
	 * @param string $profile AWS profile.
	 * @param  string $repository Repository name.
	 * @param string $region AWS region.
	 * @param array  $meta Snapshot meta.
	 */
	public function insert_snapshot( string $id, string $profile, string $repository, string $region, array $meta ) : void;

	/**
	 * Delete a snapshot given an id
	 *
	 * @param  string $id Snapshot ID
	 * @param string $profile AWS profile.
	 * @param string $repository Repository name.
	 * @param string $region AWS region.
	 */
	public function delete_snapshot( string $id, string $profile, string $repository, string $region ) : void;
}
