<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\SitesManager\SiteContentDetection;

use Piwik\Piwik;
use Piwik\SiteContentDetector;

class MatomoTagManager extends SiteContentDetectionAbstract
{
    public static function getName(): string
    {
        return Piwik::translate('SitesManager_SiteWithoutDataMatomoTagManager');
    }

    public static function getContentType(): string
    {
        return self::TYPE_TRACKER;
    }

    public static function getPriority(): int
    {
        return 10;
    }

    public function detectByContent(?string $data = null, ?array $headers = null): bool
    {
        $tests = ['/matomo ?tag ?manager/i', '/_mtm\.push/'];
        foreach ($tests as $test) {
            if (preg_match($test, $data) === 1) {
                return true;
            }
        }

        return false;
    }

    public function shouldShowInstructionTab(SiteContentDetector $detector = null): bool
    {
        return true;
    }

    public function renderInstructionsTab(SiteContentDetector $detector = null): string
    {
        return '<h3>' . Piwik::translate('SitesManager_SiteWithoutDataMatomoTagManager') . '</h3>
            <p>' . Piwik::translate( 'SitesManager_SiteWithoutDataMatomoTagManagerNotActive', ['<a href="https://matomo.org/docs/tag-manager/" rel="noreferrer noopener" target="_blank">', '</a>']) . '</p>';
    }
}