<?php

$nome = $_GET['name'] ?? 'name must be provided';

$nomeUpper = mb_strtoupper($nome);

echo $nomeUpper;