<?php

declare(strict_types=1);

// Catalog check fixture (StructureCheck): no `hu` directory, and a `fr` directory.
// `title` missing, `subtitle` not in the default language. No `play_log` catalog.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'greeting' => 'Hallo, {name}!',
    'subtitle' => 'Untertitel',
];
