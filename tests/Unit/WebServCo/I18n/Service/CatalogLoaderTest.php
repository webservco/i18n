<?php

declare(strict_types=1);

namespace Tests\Unit\WebServCo\I18n\Service;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Assets\TestLanguage;
use UnexpectedValueException;
use WebServCo\Configuration\Service\PHPConfigurationLoader;
use WebServCo\I18n\Service\CatalogLoader;

use function dirname;
use function sprintf;

#[CoversClass(CatalogLoader::class)]
final class CatalogLoaderTest extends TestCase
{
    public function testACatalogIsLoaded(): void
    {
        self::assertSame(
            [
                'apostrophe' => 'Keine Panik',
                'greeting' => 'Hallo, {name}!',
                'records' => '{count, plural, one {# Platte} other {# Platten}}',
            ],
            $this->createLoader()->load(TestLanguage::German, 'test'),
        );
    }

    public function testAMissingCatalogIsEmpty(): void
    {
        self::assertSame([], $this->createLoader()->load(TestLanguage::Hungarian, 'test'));
        self::assertSame([], $this->createLoader()->load(TestLanguage::English, 'noexist'));
    }

    public function testAnEmptyMessageThrows(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessageMatches('/^Invalid message in catalog ".+\/en\/invalid\.php"\.$/');

        $this->createLoader()->load(TestLanguage::English, 'invalid');
    }

    public function testAnInvalidCatalogNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIs('Invalid catalog name: "../en/test".');

        $this->createLoader()->load(TestLanguage::English, '../en/test');
    }

    private function createLoader(): CatalogLoader
    {
        return new CatalogLoader(new PHPConfigurationLoader(), sprintf('%s/Assets/I18n/', dirname(__DIR__, 3)));
    }
}
