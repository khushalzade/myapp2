<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Db\Schema;

use Piwik\Config\DatabaseConfig;
use Piwik\Db;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

class TidbTest extends IntegrationTestCase
{
    public function testGetDefaultCollationForCharsetReplacesUtf8mb4Binary(): void
    {
        if (DatabaseConfig::getConfigValue('schema') !== 'Tidb') {
            self::markTestSkipped('Tidb is not available');
        }

        $schema = Db\Schema::getInstance();

        self::assertSame(
            'utf8mb4_0900_ai_ci',
            $schema->getDefaultCollationForCharset('utf8mb4')
        );
    }

    /**
     * @dataProvider getTableCreateOptionsTestData
     */
    public function testTableCreateOptions(array $optionOverrides, string $expected): void
    {
        if (DatabaseConfig::getConfigValue('schema') !== 'Tidb') {
            self::markTestSkipped('Tidb is not available');
        }

        foreach ($optionOverrides as $name => $value) {
            DatabaseConfig::setConfigValue($name, $value);
        }

        $schema = Db\Schema::getInstance();

        self::assertSame($expected, $schema->getTableCreateOptions());
    }

    public function getTableCreateOptionsTestData(): iterable
    {
        yield 'default charset, empty collation' => [
            ['collation' => ''],
            'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci ROW_FORMAT=DYNAMIC'
        ];

        yield 'override charset, empty collation' => [
            ['charset' => 'utf8mb3', 'collation' => ''],
            'ENGINE=InnoDB DEFAULT CHARSET=utf8mb3'
        ];

        yield 'default charset, override collation' => [
            ['collation' => 'utf8mb4_swedish_ci'],
            'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_swedish_ci ROW_FORMAT=DYNAMIC'
        ];

        yield 'override charset and collation' => [
            ['charset' => 'utf8mb3', 'collation' => 'utf8mb3_general_ci'],
            'ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci'
        ];
    }
}
