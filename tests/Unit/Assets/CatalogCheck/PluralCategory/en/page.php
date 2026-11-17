<?php

declare(strict_types=1);

// Catalog check fixture (PluralCategoryCheck).
// `place` lacks `few`, `records` has `zero`. Exact values are free.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'exact' => '{count, plural, =0 {None} =1 {One} one {# record} other {# records}}',
    'place' => '{place, selectordinal, one {#st} two {#nd} other {#th}}',
    'records' => '{count, plural, zero {No records} one {# record} other {# records}}',
    'shelf' => '{gender, select, female {Her shelf} other {Their shelf}}',
];
