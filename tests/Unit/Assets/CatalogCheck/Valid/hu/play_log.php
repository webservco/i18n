<?php

declare(strict_types=1);

// Catalog check fixture, valid set: no check finds a problem.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'place' => '{place, selectordinal, one {#.} other {#.}} hely',
    'records' => '{count, plural, =0 {Nincs lemez} one {# lemez} other {# lemez}}',
    'shelf' => '{gender, select, female {A polca} male {A polca} other {A polca}}',
    'summary' => '{name}: {count, plural, one {# lemez} other {# lemez}} lejátszva',
];
