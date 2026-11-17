<?php

declare(strict_types=1);

// Catalog check fixture (DuplicateCaseCheck).
// `records` has `one` and `other` twice.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'records' => '{count, plural, one {# record} one {# disc} other {# records} other {# discs}}',
    'shelf' => '{gender, select, male {His} other {Their}}',
];
