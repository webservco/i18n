<?php

declare(strict_types=1);

// Catalog check fixture, valid set: no check finds a problem.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'place' => '{place, selectordinal, one {#st} two {#nd} few {#rd} other {#th}} place',
    'records' => '{count, plural, =0 {No records} one {# record} other {# records}}',
    'shelf' => '{gender, select, female {Her shelf} male {His shelf} other {Their shelf}}',
    'summary' => '{name} played {count, plural, one {# record} other {# records}}',
];
