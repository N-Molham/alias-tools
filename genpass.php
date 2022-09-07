<?php

require 'safe-password-generator.php';

echo 'Generated Password ...', "\n",
'"', Safe_Password_Generator::generate_password($argv[1] ?? 28, $argv[2] ?? true, $argv[3] ?? false), '"', "\n";
