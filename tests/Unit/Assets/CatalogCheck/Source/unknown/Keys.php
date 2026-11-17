<?php

declare(strict_types=1);

// Catalog check fixture (LiteralKeyCheck): keys the valid set lacks, and one it has (first found in `known`).
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'page.no_such_key',
    'page.title',
    'play_log.typo',
];
