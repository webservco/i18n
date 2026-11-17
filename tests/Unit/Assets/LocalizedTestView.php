<?php

declare(strict_types=1);

namespace Tests\Unit\Assets;

use WebServCo\I18n\Contract\LocalizationContainerInterface;
use WebServCo\I18n\View\AbstractLocalizedView;
use WebServCo\View\Contract\ViewInterface;

/**
 * The smallest localized view: one public property, as a project's view has its data.
 */
final class LocalizedTestView extends AbstractLocalizedView implements ViewInterface
{
    public function __construct(LocalizationContainerInterface $localizationContainer, public readonly string $title)
    {
        parent::__construct($localizationContainer);
    }
}
