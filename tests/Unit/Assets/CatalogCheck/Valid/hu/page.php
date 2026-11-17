<?php

declare(strict_types=1);

// Catalog check fixture, valid set: no check finds a problem.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'apostrophe' => 'Semmi pánik, minden rendben',
    'count' => '{count, number} lejátszás',
    'greeting' => 'Szia, {name}!',
    'quoted' => "Kapcsos zárójelek: '{' és '}'",
    'spelling' => 'Helyesírás: őszi szél, fűszer, Őrség, Űr',
    'title' => 'Lemezek',
];
