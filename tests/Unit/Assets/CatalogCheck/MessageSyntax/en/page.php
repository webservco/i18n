<?php

declare(strict_types=1);

// Catalog check fixture (MessageSyntaxCheck).
// Invalid: `Title` (name), `icu_error` (ICU), `offset` (valid ICU the parser can not read), `padded`.
// `count` is valid: ICU gives a warning, not an error.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'count' => '{count, number} plays',
    'icu_error' => 'Hello, {name',
    'offset' => '{count, plural, offset:1other {#}}',
    'padded' => ' Records ',
    'Title' => 'Records',
    'title' => 'Records',
];
