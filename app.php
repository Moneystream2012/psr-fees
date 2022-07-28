<?php

$file = $argv[1];

echo shell_exec('php bin/console fees '. $file);
