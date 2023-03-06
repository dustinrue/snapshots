<?php
/**
 * Tests covering general plugin functionality.
 * 
 * @package TenUp\Snapshots
 */

namespace TenUp\Snapshots\Tests;

use TenUp\Snapshots\Plugin;
use TenUp\Snapshots\Tests\Fixtures\PrivateAccess;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Class PluginTest
 *
 * @package TenUp\Snapshots\Tests
 * 
 * @coversDefaultClass \TenUp\Snapshots\Plugin
 */
class PluginTest extends TestCase {

    use PrivateAccess;

    /**
     * @covers ::get_modules
     */
    public function test_get_modules() {
        $plugin = new Plugin();
        $modules = $this->call_private_method( $plugin, 'get_modules' );

        $this->assertIsArray( $modules );
        $this->assertNotEmpty( $modules );
    }

    /**
     * @covers ::get_services
     */
    public function test_get_services() {
        $plugin = new Plugin();
        $services = $this->call_private_method( $plugin, 'get_services' );

        $this->assertIsArray( $services );
        $this->assertNotEmpty( $services );
    }
}