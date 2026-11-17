<?php

declare(strict_types=1);

// Catalog check fixture (ParameterCheck).
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'count' => '{count, number} plays',
    'greeting' => 'Hello, {name}!',
    'only_english' => 'Only in English',
    'records' => '{count, plural, one {# record} other {# records}}',
    'shelf' => '{gender, select, female {Her shelf} male {His shelf} other {Their shelf}}',
];
