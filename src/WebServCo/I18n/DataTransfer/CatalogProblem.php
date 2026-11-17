<?php

declare(strict_types=1);

namespace WebServCo\I18n\DataTransfer;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

/**
 * A problem a catalog check found (`CatalogCheckInterface`).
 */
final readonly class CatalogProblem implements DataTransferInterface
{
    /**
     * @param string $check The class of the check.
     * @param ?string $languageCode The language with the problem; null when it concerns no language of the list.
     * @param ?string $key The message key (`{catalog}.{name}`); null when the problem concerns no single message.
     * @param string $description What is wrong, one sentence.
     */
    public function __construct(
        public string $check,
        public ?string $languageCode,
        public ?string $key,
        public string $description,
    ) {
    }
}
