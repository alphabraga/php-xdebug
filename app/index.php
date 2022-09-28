<?php

$name = $_GET['name'] ?? 'name must be provided';

$nameUpper = mb_strtoupper($name);

echo $nameUpper;