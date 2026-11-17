<?php

declare(strict_types=1);

// Catalog check fixture (DuplicateCaseCheck).
// `shelf` has the case `male` three times.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'records' => '{count, plural, one {# Platte} other {# Platten}}',
    'shelf' => '{gender, select, male {Sein} male {Ihr} male {Das} other {Ihr}}',
];
