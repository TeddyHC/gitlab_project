<?php

namespace gitUpdate\bin;

require_once __DIR__.'/../Loader.php';

use gitUpdate\src\GitUpdate;

$namespace = $argc > 1 ? $argv[1]: null;
$project = $argc > 2 ? $argv[2]: null;

(new GitUpdate())->run($namespace, $project);
