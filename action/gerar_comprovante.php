<?php
require_once __DIR__ . '../vendor/autoload.php'; // se usar Composer (dompdf/dompdf)

use Dompdf\Dompdf;

function gerarComprovante($cliente, $valor, $tipo, $observacao) {
    $dompdf = new Dompdf();

    $html = "
    <h2 style='text-align:center;'>Comprovante de {$tipo}</h2>
    <p><strong>Cliente:</strong> {$cliente}</p>
    <p><strong>Valor:</strong> R$ ".number_format($valor, 2, ',', '.')."</p>
    <p><strong>Observação:</strong> {$observacao}</p>
    <p><strong>Data:</strong> ".date('d/m/Y H:i')."</p>
    ";

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Força download
    $dompdf->stream("comprovante.pdf", array("Attachment" => true));
}
