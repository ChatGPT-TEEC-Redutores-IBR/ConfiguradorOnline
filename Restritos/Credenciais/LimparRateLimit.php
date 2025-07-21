<?php
$dir = dirname(__DIR__) . '/LimitesSolicitacoes';
$agora = time();
foreach (glob("$dir/*.json") as $arquivo) {
    if ($agora - filemtime($arquivo) > 3600) {
        @unlink($arquivo);
    }
}
?>
