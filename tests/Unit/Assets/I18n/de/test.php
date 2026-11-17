<?php

declare(strict_types=1);

// Test catalog (TranslatorTest): `english_only` is missing on purpose. There is no `hu` directory.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'apostrophe' => 'Keine Panik',
    'greeting' => 'Hallo, {name}!',
    'records' => '{count, plural, one {# Platte} other {# Platten}}',
];
