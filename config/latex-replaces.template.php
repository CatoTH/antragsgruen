<?php

return function (string $latex): string {
    $latex = str_replace('FOO', 'BAR', $latex);

    return $latex;
};
