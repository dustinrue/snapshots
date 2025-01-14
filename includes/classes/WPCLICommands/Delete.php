<?php
/**
 * Delete command class.
 *
 * @package TenUp\Snapshots
 */

namespace TenUp\Snapshots\WPCLICommands;

use Exception;
use TenUp\Snapshots\Exceptions\SnapshotsException;
use TenUp\Snapshots\WPCLI\WPCLICommand;

use function TenUp\Snapshots\Utils\wp_cli;

/**
 * Delete command
 *
 * @package TenUp\Snapshots\WPCLI
 */
class Delete extends WPCLICommand {

	/**
	 * Search for snapshots within a repository.
	 *
	 * @param array $args Arguments passed to the command.
	 * @param array $assoc_args Associative arguments passed to the command.
	 *
	 * @throws SnapshotsException If the snapshot cannot be created.
	 */
	public function execute( array $args, array $assoc_args ) {
		try {
			$this->set_args( $args );
			$this->set_assoc_args( $assoc_args );

			$id              = $this->get_id();
			$repository_name = $this->get_repository_name();
			$region          = $this->get_region();
			$profile         = $this->get_profile_for_repository();

			$snapshot = $this->db_connector->get_snapshot( $id, $profile, $repository_name, $region );

			if ( ! $snapshot ) {
				throw new SnapshotsException( sprintf( 'Snapshot %s not found in repository %s.', $id, $repository_name ) );
			}

			$this->storage_connector->delete_snapshot( $id, $snapshot['project'], $profile, $repository_name, $region );
			$this->db_connector->delete_snapshot( $id, $profile, $repository_name, $region );

			wp_cli()::success( sprintf( 'Snapshot %s deleted.', $id ) );
		} catch ( Exception $e ) {
			wp_cli()::error( $e->getMessage() );
		}
	}

	/**
	 * Returns the command name.
	 *
	 * @inheritDoc
	 */
	protected function get_command() : string {
		return 'delete';
	}

	/**
	 * Provides command parameters.
	 *
	 * @inheritDoc
	 */
	protected function get_command_parameters() : array {
		return [
			'shortdesc' => 'Delete a remote snapshot',
			'synopsis'  => [
				[
					'type'        => 'positional',
					'name'        => 'snapshot_id',
					'description' => 'Snapshot ID to pull.',
					'optional'    => false,
				],
				[
					'type'        => 'assoc',
					'name'        => 'repository',
					'description' => 'Repository to use.',
					'optional'    => true,
				],
			],
			'when'      => 'before_wp_load',
		];
	}

	/**
	 * Gets the snapshot ID.
	 *
	 * @return string
	 */
	private function get_id() : string {
		return $this->get_args()[0];
	}
}
