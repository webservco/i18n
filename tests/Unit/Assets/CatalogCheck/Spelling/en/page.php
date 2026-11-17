<?php

declare(strict_types=1);

// Catalog check fixture (SpellingCheck).
// `argument`: an argument name is no text. `plural` and `us` have US spellings.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'argument' => 'Pick a {color}',
    'plural' => '{count, plural, one {# color} other {# colors}}',
    'us' => 'synchronize Organization analyzing color Favorite behavioral centers catalog dialogs canceled '
        . 'labeling gray',
];
