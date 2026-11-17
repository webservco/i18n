<?php

declare(strict_types=1);

namespace WebServCo\I18n\Contract;

/**
 * Checks the translation catalogs of a project, and returns the problems it finds instead of failing.
 */
interface CatalogCheckInterface
{
    /**
     * @return list<\WebServCo\I18n\DataTransfer\CatalogProblem> Empty when the catalogs pass the check.
     */
    public function check(): array;
}
