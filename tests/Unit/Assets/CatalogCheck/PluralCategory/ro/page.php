<?php

declare(strict_types=1);

// Catalog check fixture (PluralCategoryCheck).
// `records` lacks `few`.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'exact' => '{count, plural, =0 {Niciun disc} one {# disc} few {# discuri} other {# de discuri}}',
    'place' => '{place, selectordinal, one {primul} other {al #-lea}}',
    'records' => '{count, plural, one {# disc} other {# de discuri}}',
    'shelf' => '{gender, select, female {Raftul ei} other {Raftul}}',
];
