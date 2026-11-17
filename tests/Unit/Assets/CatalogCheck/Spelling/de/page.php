<?php

declare(strict_types=1);

// Catalog check fixture (SpellingCheck).
// German is not checked for US spelling.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'argument' => 'Wähle {color}',
    'plural' => '{count, plural, one {# Farbe} other {# Farben}}',
    'us' => 'color',
];
