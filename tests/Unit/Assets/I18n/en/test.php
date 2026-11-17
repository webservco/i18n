<?php

declare(strict_types=1);

// Test catalog (TranslatorTest): the default language has every key.
// `date_argument` uses an ICU date argument, which fails on a string parameter.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'apostrophe' => "Don't panic",
    'date_argument' => 'Played on {when, date}',
    'english_only' => 'Only in English',
    'greeting' => 'Hello, {name}!',
    'records' => '{count, plural, one {# record} other {# records}}',
];
