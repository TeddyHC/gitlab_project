<?php

namespace gitUpdate\bin;

require_once __DIR__.'/../Loader.php';

use gitUpdate\src\GitUpdate;

$project = $argc > 1 ? $argv[1]: null;
(new GitUpdate())->run($project);
