<?php

declare(strict_types=1);

// Catalog check fixture, valid set: no check finds a problem.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'apostrophe' => 'Keine Panik, alles gut',
    'count' => '{count, number} Wiedergaben',
    'greeting' => 'Hallo, {name}!',
    'quoted' => "Klammern: '{' und '}'",
    'spelling' => 'Farbe, Lieblingsplatte, Zentrum, Katalog, storniert, grau',
    'title' => 'Platten',
];
