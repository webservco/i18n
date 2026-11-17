<?php

declare(strict_types=1);

// Catalog check fixture (MessageSyntaxCheck).
// `title` ends with a line break.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'count' => '{count, number} Wiedergaben',
    'title' => "Platten\n",
];
