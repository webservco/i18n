<?php

declare(strict_types=1);

// Test catalog (CatalogLoaderTest): an empty message is invalid.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'empty' => '',
    'valid' => 'Valid',
];
