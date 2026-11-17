<?php

declare(strict_types=1);

namespace WebServCo\I18n\View;

use WebServCo\View\Contract\ViewInterface;
use WebServCo\View\View\AbstractView;

/**
 * A language version of the page, for search engines: `<link rel="alternate" hreflang="..." href="...">`
 * (`AlternateLinkViewFactory`).
 */
final class AlternateLinkView extends AbstractView implements ViewInterface
{
    public function __construct(
        // A language code, or `x-default` (the page without `?lang=`: the language of the browser decides).
        public readonly string $hreflang,
        // Absolute.
        public readonly string $url,
    ) {
    }
}
