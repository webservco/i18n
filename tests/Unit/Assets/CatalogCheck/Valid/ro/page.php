<?php

declare(strict_types=1);

// Catalog check fixture, valid set: no check finds a problem.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'apostrophe' => 'Fără panică, totul e bine',
    'count' => '{count, number} redări',
    'greeting' => 'Salut, {name}!',
    'quoted' => "Acolade: '{' și '}'",
    'spelling' => 'Ortografie: știință, țară, Ștefan, Țara, Ăla',
    'title' => 'Discuri',
];
