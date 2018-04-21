<?php

namespace gitUpdate\bin;

require_once __DIR__.'/../Loader.php';

use gitUpdate\src\GitUpdate;

(new gitUpdate())->run();
