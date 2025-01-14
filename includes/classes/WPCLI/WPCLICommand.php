<?php
/**
 * Abstract WPCLICommand class.
 *
 * @package TenUp\Snapshots
 */

namespace TenUp\Snapshots\WPCLI;

use TenUp\Snapshots\Exceptions\SnapshotsException;
use TenUp\Snapshots\FileSystem;
use TenUp\Snapshots\Infrastructure\{Module, Conditional};
use TenUp\Snapshots\Log\{Logging, WPCLILogger};
use TenUp\Snapshots\Snapshots\{DBConnectorInterface, SnapshotMetaFromFileSystem, SnapshotMetaInterface, StorageConnectorInterface};
use TenUp\Snapshots\SnapshotsConfig\SnapshotsConfigInterface;
use TenUp\Snapshots\SnapshotsDirectory;
use TenUp\Snapshots\WordPress\Database;
use TenUp\Snapshots\SnapshotsConfig\SnapshotsConfigFromFileSystem;

use function TenUp\Snapshots\Utils\wp_cli;

/**
 * Abstract class WPCLICommand
 *
 * @package TenUp\Snapshots\WPCLI
 */
abstract class WPCLICommand implements Conditional, Module {

	use Logging;

	/**
	 * Prompt instance.
	 *
	 * @var Prompt
	 */
	protected $prompt;

	/**
	 * ConfigConnectorInterface instance.
	 *
	 * @var SnapshotsConfigInterface
	 */
	protected $config;

	/**
	 * StorageConnectorInterface instance.
	 *
	 * @var StorageConnectorInterface
	 */
	protected $storage_connector;

	/**
	 * DBConnectorInterface instance.
	 *
	 * @var DBConnectorInterface
	 */
	protected $db_connector;

	/**
	 * SnapshotMetaInteface instance.
	 *
	 * @var SnapshotMetaInterface
	 */
	protected $snapshot_meta;

	/**
	 * SnapshotsDirectory instance.
	 *
	 * @var SnapshotsDirectory
	 */
	protected $snapshots_filesystem;

	/**
	 * Database instance.
	 *
	 * @var Database
	 */
	protected $wordpress_database;

	/**
	 * FileSystem instance.
	 *
	 * @var FileSystem
	 */
	protected $filesystem;

	/**
	 * Args passed to the command.
	 *
	 * @var array
	 */
	protected $args = [];

	/**
	 * Associative args passed to the command.
	 *
	 * @var array
	 */
	protected $assoc_args = [];

	/**
	 * Returns whether the module is needed.
	 *
	 * @return bool
	 */
	public static function is_needed() : bool {
		return defined( 'WP_CLI' );
	}

	/**
	 * WPCLICommand constructor.
	 *
	 * @param WPCLILogger               $logger WPCLILogger instance.
	 * @param Prompt                    $prompt Prompt instance.
	 * @param SnapshotsConfigInterface  $config ConfigConnectorInterface instance.
	 * @param StorageConnectorInterface $storage_connector StorageConnectorInterface instance.
	 * @param DBConnectorInterface      $db_connector DBConnectorInterface instance.
	 * @param SnapshotMetaInterface     $snapshot_meta SnapshotMetaInterface instance.
	 * @param SnapshotsDirectory        $snapshots_filesystem SnapshotsDirectory instance.
	 * @param Database                  $wordpress_database Database instance.
	 * @param FileSystem                $filesystem FileSystem instance.
	 */
	public function __construct(
		WPCLILogger $logger,
		Prompt $prompt,
		SnapshotsConfigInterface $config,
		StorageConnectorInterface $storage_connector,
		DBConnectorInterface $db_connector,
		SnapshotMetaInterface $snapshot_meta,
		SnapshotsDirectory $snapshots_filesystem,
		Database $wordpress_database,
		FileSystem $filesystem
	) {
		$this->prompt               = $prompt;
		$this->config               = $config;
		$this->storage_connector    = $storage_connector;
		$this->db_connector         = $db_connector;
		$this->snapshot_meta        = $snapshot_meta;
		$this->snapshots_filesystem = $snapshots_filesystem;
		$this->wordpress_database   = $wordpress_database;
		$this->filesystem           = $filesystem;
		$this->set_logger( $logger );
	}

	/**
	 * Registers the module.
	 */
	public function register() {
		wp_cli()::add_command(
			'snapshots ' . $this->get_command(),
			[ $this, 'execute' ],
			$this->get_command_parameters()
		);
	}

	/**
	 * Get args.
	 *
	 * @return array
	 */
	public function get_args() : array {
		return $this->args;
	}

	/**
	 * Get assoc_args.
	 *
	 * @return array
	 */
	public function get_assoc_args() : array {
		return $this->assoc_args;
	}

	/**
	 * Get an associative arg.
	 *
	 * @param string $key Key.
	 * @param ?array $prompt_config Configuration for prompting.
	 * @return mixed
	 */
	public function get_assoc_arg( string $key, ?array $prompt_config = null ) {
		if ( $prompt_config ) {
			$this->assoc_args = $this->prompt->get_arg_or_prompt( $this->assoc_args, array_merge( $prompt_config, compact( 'key' ) ) );
		}

		if ( ! isset( $this->assoc_args[ $key ] ) ) {
			$value = $this->get_default_arg_value( $key );

			if ( $value ) {
				$this->assoc_args[ $key ] = $value;
			}

			return $value;
		}

		return $this->assoc_args[ $key ];
	}

	/**
	 * Set args.
	 *
	 * @param array $args Arguments passed to the command.
	 */
	public function set_args( array $args ) {
		$this->args = $args;
	}

	/**
	 * Set assoc_args.
	 *
	 * @param array $assoc_args Associative arguments passed to the command.
	 */
	public function set_assoc_args( array $assoc_args ) {
		$this->assoc_args = $assoc_args;
	}

	/**
	 * Set a single assoc_arg.
	 *
	 * @param string $key Key.
	 * @param mixed  $value Value.
	 */
	public function set_assoc_arg( string $key, $value ) {
		$this->assoc_args[ $key ] = $value;
	}

	/**
	 * Gets the repository name.
	 *
	 * @param bool $required Whether the arg is required.
	 * @param ?int $positional_arg_index Positional arg index. If null, the repository will be retrieved form assoc args.
	 * @return string
	 *
	 * @throws SnapshotsException If no repository name is provided.
	 */
	protected function get_repository_name( bool $required = true, ?int $positional_arg_index = null ) : string {
		if ( is_int( $positional_arg_index ) ) {
			$args = $this->get_args();

			$repository_name = $args[ $positional_arg_index ] ?? null;
		} else {
			$repository_name = $this->get_assoc_arg( 'repository' ) ?? null;
		}

		if ( ! $repository_name ) {
			// Attempt to load from configuration file.
			$repository_name = $this->config->get_default_repository_name();
		}

		if ( ! $repository_name && $required ) {
			throw new SnapshotsException( 'A repository name is required. Please run the configure command or pass a --repository argument.' );
		}

		return $repository_name;
	}

	/**
	 * Gets the region from the repository.
	 *
	 * @param ?string $repository_name Repository name.
	 *
	 * @return string
	 */
	protected function get_region( ?string $repository_name = null ) : string {
		if ( ! $repository_name ) {
			$repository_name = $this->get_repository_name();
		}

		$region = $this->config->get_repository_region( $repository_name );

		return $region;
	}

	/**
	 * Gets the profile property from the repository.
	 *
	 * @return string
	 *
	 * @throws SnapshotsException If no profile is found.
	 */
	protected function get_profile_for_repository() : string {
		$repository_name = $this->get_repository_name();

		$profile = $this->config->get_repository_profile( $repository_name );

		return $profile;
	}

	/**
	 * Gets the default value for a given arg.
	 *
	 * @param string $arg Arg.
	 * @return mixed
	 */
	protected function get_default_arg_value( string $arg ) {
		$synopsis = $this->get_command_parameters()['synopsis'] ?? [];

		foreach ( $synopsis as $synopsis_arg ) {
			if ( $arg === $synopsis_arg['name'] ) {
				return $synopsis_arg['default'] ?? null;
			}
		}

		return null;
	}

	/**
	 * Format bytes to pretty file size
	 *
	 * @param  int $size     Number of bytes
	 * @param  int $precision Decimal precision
	 * @return string
	 */
	protected function format_bytes( $size, $precision = 2 ) {
		$base     = log( $size, 1024 );
		$suffixes = [ '', 'KB', 'MB', 'GB', 'TB' ];

		return round( pow( 1024, $base - floor( $base ) ), $precision ) . ' ' . $suffixes[ floor( $base ) ];
	}

	/**
	 * Callback for the command.
	 *
	 * @param array $args Arguments passed to the command.
	 * @param array $assoc_args Associative arguments passed to the command.
	 */
	abstract protected function execute( array $args, array $assoc_args );

	/**
	 * Gets the command.
	 *
	 * @return string
	 */
	abstract protected function get_command() : string;

	/**
	 * Gets the parameters.
	 *
	 * @return array
	 */
	abstract protected function get_command_parameters() : array;
}
