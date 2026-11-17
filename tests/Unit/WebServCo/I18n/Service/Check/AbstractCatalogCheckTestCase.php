<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Service\Check;

use PHPUnit\Framework\TestCase;
use Tests\Unit\Assets\TestLanguage;
use Tests\Unit\Assets\ValueLanguage;
use WebServCo\Configuration\Service\PHPConfigurationLoader;
use WebServCo\I18n\Service\CatalogLoader;
use WebServCo\I18n\Service\Check\CatalogReader;
use WebServCo\I18n\Service\LanguageList;

use function dirname;
use function sprintf;

/**
 * The fixture catalogs of the checks (`tests/Unit/Assets/CatalogCheck/`): a valid set, and one broken set per check.
 *
 * Languages: German, English (the default), Hungarian, and Romanian (a language that is not an enum).
 */
abstract class AbstractCatalogCheckTestCase extends TestCase
{
    protected function createCatalogReader(string $setName): CatalogReader
    {
        $directoryPath = $this->getFixturePath($setName);

        return new CatalogReader(
            new CatalogLoader(new PHPConfigurationLoader(), $directoryPath),
            new LanguageList(
                [
                    TestLanguage::German,
                    TestLanguage::English,
                    TestLanguage::Hungarian,
                    new ValueLanguage('ro', 'ro', 'Română'),
                ],
                TestLanguage::DEFAULT,
            ),
            $directoryPath,
        );
    }

    protected function getFixturePath(string $name): string
    {
        return sprintf('%s/Assets/CatalogCheck/%s/', dirname(__DIR__, 4), $name);
    }
}
