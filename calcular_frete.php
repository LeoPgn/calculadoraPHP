<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calculadora de Frete</title>
</head>
<body>
    <?php
        echo "Hello World!";
// calcular_frete.php
// Endpoint que processa POST JSON em /calcular_frete

// body JSON
$body = file_get_contents('php://input');      // pega o corpo bruto da request
$data = json_decode($body, true);              // decodifica JSON para array associativo
// verificar se json_decode retornou null -> tratar erro 400

$cep_raw = $data['cep'] ?? '';                 // receber o cep fornecido
$peso = $data['peso'] ?? null;                 // receber o peso
$frete_adicional = $data['frete_adicional'] 

// Normalizar CEP: remover caracteres não numéricos e converter para inteiro
$cep_num_str = preg_replace('/\D/', '', $cep_raw); // remove tudo que não é dígito
$cep_num = intval($cep_num_str);                   // converte para número


// 3) Conectar ao PostgreSQL (usar PDO)
try {
    //
    $dsn = 'pgsql:host=127.0.0.1;port=5432;dbname=paganiniDB';
    $pdo = new PDO($dsn, 'paganiniDB', '501938', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    // Retornar erro 500 com mensagem genérica (não vazar credenciais)
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao conectar ao banco']);
    exit;
}

// Buscar transportadoras que atendem o CEP
// Seleciona faixas onde cep_num está entre cep_inicio e cep_fim
$sql = "
SELECT t.id, t.nome, t.valor_kg, t.valor_kg_adicional, COALESCE(f.prazo, t.prazo_default) AS prazo,
FROM transportadora t
JOIN faixa_cep f ON f.transportadora_id = t.id
WHERE :cep_num BETWEEN f.cep_inicio AND f.cep_fim
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':cep_num' => $cep_num]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Para cada transportadora, calculo de valor
$results = [];
foreach ($rows as $r) {
    $valor = flatval($['valor_kg']) + floatval($['valor_adicional']) * floatval($peso);
    

    // formatar para string com vírgula
    $valor_str = number_format($valor, 2, ',', '.');

    $results[] = [
        'transportadora' => $r['nome'],
        'valor' => $valor_str,
        'prazo' => (string)$r['prazo'] . ' dias'
    ];
}

// Retornar JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($results, JSON_UNESCAPED_UNICODE);
?>


</body>
</html>