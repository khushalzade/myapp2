<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\CoreConsole\tests\System;

use Interop\Container\ContainerInterface;
use Piwik\Common;
use Piwik\Config;
use Piwik\CronArchive;
use Piwik\Date;
use Piwik\Db;
use Piwik\Option;
use Piwik\Plugins\SitesManager\API;
use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Piwik\Tests\Fixtures\ManySitesImportedLogs;
use Piwik\Tests\Framework\Fixture;
use Exception;

/**
 * Tests to call the cron core:archive command script and check there is no error,
 * Then call the API testing for "Browser archiving is disabled" (see disableArchiving)
 * This tests that, when archiving is disabled,
 *  then Piwik API will return data that was pre-processed during archive.php run
 *
 * @group Core
 * @group ArchiveCronTest
 */
class ArchiveCronTest extends SystemTestCase
{
    /**
     * @var ManySitesImportedLogs
     */
    public static $fixture = null; // initialized below class definition

    public function getApiForTesting()
    {
        $apiRequiringSegments = ['Goals.get', 'VisitFrequency.get'];

        $results = array();

        foreach (self::$fixture->getDefaultSegments() as $segmentName => $info) {
            $results[] = array('VisitsSummary.get', array('idSite'     => 'all',
                                                          'date'       => '2012-08-09',
                                                          'periods'    => array('day', 'week', 'month', 'year'),
                                                          'segment'    => $info['definition'],
                                                          'testSuffix' => '_' . $segmentName));


        }

        // API Call Without segments
        $results[] = array('VisitsSummary.get', array('idSite'  => 'all',
                                                      'date'    => '2012-08-09',
                                                      'periods' => array('day', 'month', 'year',  'week')));
        $results[] = array($apiRequiringSegments, array('idSite'  => 'all',
            'date'    => '2012-08-09',
            'periods' => array('month')));

        $results[] = array('VisitsSummary.get', array('idSite'     => 'all',
                                                      'date'       => '2012-08-09',
                                                      'periods'    => array('day', 'week', 'month', 'year'),
                                                      'segment'    => 'browserCode==EP',
                                                      'testSuffix' => '_nonPreArchivedSegment'));

        $segments = array(ManySitesImportedLogs::SEGMENT_PRE_ARCHIVED,
                          ManySitesImportedLogs::SEGMENT_PRE_ARCHIVED_CONTAINS_ENCODED
        );
        foreach($segments as $index => $segment) {
            // Test with a pre-processed segment
            $results[] = array(array('VisitsSummary.get', 'Live.getLastVisitsDetails', 'VisitFrequency.get'),
                               array('idSite'     => '1',
                                     'date'       => '2012-08-09',
                                     'periods'    => array('day', 'year'),
                                     'segment'    => $segment,
                                     'testSuffix' => '_preArchivedSegment' . $index,
                                     'otherRequestParameters' => array(
                                        'hideColumns' => 'latitude,longitude'
                                     ),
                                     'xmlFieldsToRemove' => array(
                                         'fingerprint'
                                     )
                               )
            );
        }

        return $results;
    }

    public function testArchivePhpCron()
    {
        $output = $this->runArchivePhpCron();

        $expectedInvalidations = [];
        $invalidationEntries = $this->getInvalidatedArchiveTableEntries();
        $this->assertEquals($expectedInvalidations, $invalidationEntries);

        $this->compareArchivePhpOutputAgainstExpected($output);

        foreach ($this->getApiForTesting() as $testInfo) {

            list($api, $params) = $testInfo;

            if (!isset($params['testSuffix'])) {
                $params['testSuffix'] = '';
            }
            $params['testSuffix'] .= '_noOptions';
            $params['disableArchiving'] = true;

            $success = $this->runApiTests($api, $params);

            if (!$success) {
                var_dump($output);
            }
        }
    }

    public function testArchivePhpCronArchivesFullRanges()
    {
        self::$fixture->getTestEnvironment()->overrideConfig('General', 'enable_browser_archiving_triggering', 0);
        self::$fixture->getTestEnvironment()->overrideConfig('General', 'archiving_range_force_on_browser_request', 0);
        self::$fixture->getTestEnvironment()->overrideConfig('General', 'archiving_custom_ranges', ['2012-08-09,2012-08-13']);
        self::$fixture->getTestEnvironment()->save();

        Config::getInstance()->General['enable_browser_archiving_triggering'] = 0;
        Config::getInstance()->General['archiving_range_force_on_browser_request'] = 0;
        Config::getInstance()->General['archiving_custom_ranges'][] = '';

        $output = $this->runArchivePhpCron(['--force-periods' => 'range', '--force-idsites' => 1]);

        $expectedInvalidations = [];
        $invalidationEntries = $this->getInvalidatedArchiveTableEntries();
        $invalidationEntries = array_filter($invalidationEntries, function ($entry) {
            return $entry['period'] != 5;
        });
        $this->assertEquals($expectedInvalidations, $invalidationEntries);

        $this->runApiTests(array(
            'VisitsSummary.get', 'Actions.get', 'DevicesDetection.getType'),
            array('idSite'     => '1',
                'date'       => '2012-08-09,2012-08-13',
                'periods'    => array('range'),
                'testSuffix' => '_range_archive'
            )
        );
    }

    public function test_archivePhpScript_DoesNotFail_WhenCommandHelpRequested()
    {
        $output = $this->runArchivePhpCron(array('--help' => null), PIWIK_INCLUDE_PATH . '/misc/cron/archive.php');

        $this->assertRegExp('/Usage:\s*core:archive/', $output);
        self::assertStringNotContainsString("Starting Piwik reports archiving...", $output);
    }

    private function runArchivePhpCron($options = array(), $archivePhpScript = false)
    {
        $archivePhpScript = $archivePhpScript ?: PIWIK_INCLUDE_PATH . '/tests/PHPUnit/proxy/archive.php';
        $urlToProxy = Fixture::getRootUrl() . 'tests/PHPUnit/proxy/index.php';

        // create the command
        $cmd = "php \"$archivePhpScript\" --url=\"$urlToProxy\" --force-date-last-n=10";
        foreach ($options as $name => $value) {
            $cmd .= " $name";
            if ($value !== null) {
                $cmd .= "=" . escapeshellarg($value);
            }
        }
        $cmd .= " 2>&1";

        // run the command
        exec($cmd, $output, $result);
        $output = implode("\n", $output);

        if ($result !== 0 || strpos($output, "ERROR") || strpos($output, "Error")) {
            $this->fail("archive cron failed (result = $result): " . $output . "\n\ncommand used: $cmd");
        }

        return $output;
    }

    private function compareArchivePhpOutputAgainstExpected($output)
    {
        $fileName = 'test_ArchiveCronTest_archive_php_cron_output.txt';
        list($pathProcessed, $pathExpected) = static::getProcessedAndExpectedDirs();

        $expectedOutputFile = $pathExpected . $fileName;
        $processedFile = $pathProcessed . $fileName;

        file_put_contents($processedFile, $output);

        try {
            $this->assertTrue(is_readable($expectedOutputFile));
            $this->assertEquals(file_get_contents($expectedOutputFile), $output);
        } catch (Exception $ex) {
            $this->comparisonFailures[] = $ex;
        }
    }

    public static function provideContainerConfigBeforeClass()
    {
        return array(
            'Psr\Log\LoggerInterface' => \DI\get('Monolog\Logger'),

            // for some reason, w/o real translations archiving segments in CronArchive fails. the data returned by CliMulti
            // is a translation token, and nothing else.
            'Piwik\Translation\Translator' => function (ContainerInterface $c) {
                return new \Piwik\Translation\Translator($c->get('Piwik\Translation\Loader\LoaderInterface'));
            },

            'Tests.log.allowAllHandlers' => true,
        );
    }

    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }

    private function getInvalidatedArchiveTableEntries()
    {
        return Db::fetchAll("SELECT idinvalidation, idarchive, idsite, date1, date2, period, name, status FROM " . Common::prefixTable('archive_invalidations'));
    }
}

ArchiveCronTest::$fixture = new ManySitesImportedLogs();
ArchiveCronTest::$fixture->addSegments = true;