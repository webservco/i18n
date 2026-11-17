<?php

declare(strict_types=1);

namespace WebServCo\I18n\DataTransfer;

use WebServCo\Data\Contract\Transfer\DataTransferInterface;

/**
 * The structure of an ICU message (`MessagePatternParser`), for the catalog checks.
 */
final readonly class MessagePattern implements DataTransferInterface
{
    /**
     * @param array<string,list<string>> $arguments Argument name => its types, sorted: `none` for `{name}`, else the
     *     type in lowercase (`number`, `plural`, ...). Arguments inside cases are included.
     * @param list<\WebServCo\I18n\DataTransfer\ComplexArgument> $complexArguments The plural, selectordinal and select
     *     arguments, nested ones included.
     * @param string $text The literal text, quoting resolved, the cases included. A space stands for each argument
     *     and each `#` of a plural case, and comes before each case, so that no two words run together.
     */
    public function __construct(public array $arguments, public array $complexArguments, public string $text)
    {
    }
}
