<?php

declare(strict_types=1);

// Catalog check fixture (LiteralKeyCheck): keys the valid set has, and strings that are no keys.
// A key in a comment is no key: page.no_such_comment.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'page.title',
    "play_log.records",
    // Not a catalog of the default language.
    'example.com',
    'index.php',
    // Not the form of a key.
    'page',
    'Page.Title',
    'page.title ',
];
