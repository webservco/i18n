<?php

declare(strict_types=1);

// Catalog check fixture, valid set: no check finds a problem.
// Not adding namespace declaration (Psalm dead code error).
// Not using docblock here to prevent "The file-level docblock must follow the opening PHP tag in the file header"
return [
    'place' => '{place, selectordinal, one {primul} other {al #-lea}} loc',
    'records' => '{count, plural, =0 {Niciun disc} one {# disc} few {# discuri} other {# de discuri}}',
    'shelf' => '{gender, select, female {Raftul ei} male {Raftul lui} other {Raftul}}',
    'summary' => '{name} a ascultat {count, plural, one {# disc} few {# discuri} other {# de discuri}}',
];
