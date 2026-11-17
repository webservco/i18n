<?php

declare(strict_types=1);

// Catalog check fixture (StructureCheck): no `hu` directory, and a `fr` directory.
// Complete.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'records' => '{count, plural, one {# disc} few {# discuri} other {# de discuri}}',
];
