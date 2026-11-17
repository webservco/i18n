<?php

declare(strict_types=1);

// Catalog check fixture (ParameterCheck).
// `greeting` lost `{name}`, `records` is no plural, `shelf` lacks the case `male`.
// `only_german` is not in the default language: StructureCheck reports it.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'count' => '{count, number} Wiedergaben',
    'greeting' => 'Hallo!',
    'only_german' => 'Nur auf Deutsch, {name}',
    'records' => '{count} Platten',
    'shelf' => '{gender, select, female {Ihr Regal} other {Sein Regal}}',
];
