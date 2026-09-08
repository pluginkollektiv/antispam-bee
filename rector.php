<?php
/**
 * Rector configuration.
 *
 * @package AntispamBee
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\ValueObject\PhpVersion;

return RectorConfig::configure()
	->withPaths(
		[
			__DIR__ . '/src',
			__DIR__ . '/tests',
		]
	)
	->withRootFiles()
	->withSkip(
		[
			// Hand-written WordPress stubs must keep mirroring core's signatures.
			__DIR__ . '/tests/_stubs',
		]
	)
	// The supported floor from composer.json, not the local PHP version.
	->withPhpVersion( PhpVersion::PHP_74 )
	->withCache( __DIR__ . '/tmp/rector' )
	// PHP sets are deliberately off; enabling them is its own change.
	->withTypeCoverageLevel( 0 )
	->withDeadCodeLevel( 0 )
	->withCodeQualityLevel( 0 );
