<?php

declare(strict_types=1);

namespace WebServCo\I18n\DataTransfer;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

/**
 * A plural, selectordinal or select argument of an ICU message (`MessagePatternParser`): its cases.
 */
final readonly class ComplexArgument implements DataTransferInterface
{
    /**
     * @param string $type `plural`, `selectordinal` or `select`.
     * @param list<string> $selectors In the order of the message: keywords (`one`, `other`, a select value) and exact
     *     values (`=0`). A selector the message has twice is listed twice.
     */
    public function __construct(public string $name, public string $type, public array $selectors)
    {
    }
}
