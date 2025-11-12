<?php
echo "System timezone: " . date_default_timezone_get() . "\n";
echo "Current time (system): " . date('Y-m-d H:i:s') . "\n";
echo "Current time (UTC): " . gmdate('Y-m-d H:i:s') . "\n";