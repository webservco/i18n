<?php

declare(strict_types=1);

// Catalog check fixture, valid set: no check finds a problem.
// `spelling`: British spellings, and words that only look like US spellings.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'apostrophe' => "Don't panic, it's fine",
    'count' => '{count, number} plays',
    'greeting' => 'Hello, {name}!',
    'quoted' => "Braces: '{' and '}'",
    'spelling' => 'Colour, favourite, centre, catalogue, cancelled, grey, synchronise, size, resize, prize, '
        . 'seized, capsize, maize, citizen, honorary, humorous, laboratory, literal, analogy, '
        . 'cancellation, horizon, wizard, Colorado, major',
    'title' => 'Records',
];
