<?php
$code = file_get_contents('app/Http/Controllers/ControladorReportes.php');
$open = 0;
$close = 0;
for ($i = 0; $i < strlen($code); $i++) {
    if ($code[$i] === '{') $open++;
    if ($code[$i] === '}') $close++;
}
echo "Open: $open, Close: $close, Balance: " . ($open - $close) . "\n";