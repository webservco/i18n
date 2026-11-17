<?php

declare(strict_types=1);

// Catalog check fixture, valid set: no check finds a problem.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'place' => '{place, selectordinal, other {#.}} Platz',
    'records' => '{count, plural, =0 {Keine Platten} one {# Platte} other {# Platten}}',
    'shelf' => '{gender, select, female {Ihr Regal} male {Sein Regal} other {Ihr Regal}}',
    'summary' => '{name} hat {count, plural, one {# Platte} other {# Platten}} gespielt',
];
