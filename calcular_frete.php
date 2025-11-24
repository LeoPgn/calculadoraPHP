<?php
// calcular_frete.php
// EXPLICAÇÃO: este arquivo será o endpoint que processa POST JSON em /calcular_frete

// 1) Lê o body JSON
$body = file_get_contents('php://input');      // pega o corpo bruto da request
$data = json_decode($body, true);              // decodifica JSON para array associativo
// TODO: verificar se json_decode retornou null -> tratar erro 400

$cep_raw = $data['cep'] ?? '';                 // receber o cep fornecido
$peso = $data['peso'] ?? null;                 // receber o peso

// 2) Normalizar CEP: remover caracteres não numéricos e converter para inteiro
$cep_num_str = preg_replace('/\D/', '', $cep_raw); // remove tudo que não é dígito
$cep_num = intval($cep_num_str);                   // converte para número

// 3) Conectar ao PostgreSQL (usar PDO)
try {
    // EXPLICAÇÃO: DSN conecta ao PostgreSQL. Ajuste host, dbname, user, pass
    $dsn = 'pgsql:host=127.0.0.1;port=5432;dbname=nome_do_banco';
    $pdo = new PDO($dsn, 'usuario', 'senha', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    // TODO: retornar erro 500 com mensagem genérica (não vazar credenciais)
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao conectar ao banco']);
    exit;
}

// 4) Buscar transportadoras que atendem o CEP (exemplo de query)
// EXPLICAÇÃO: seleciona faixas onde cep_num está entre cep_inicio e cep_fim
$sql = "
SELECT t.id, t.nome, t.valor_kg, t.valor_kg_adicional, COALESCE(f.prazo, t.prazo_default) AS prazo, f.tarifa_adicional
FROM transportadora t
JOIN faixa_cep f ON f.transportadora_id = t.id
WHERE :cep_num BETWEEN f.cep_inicio AND f.cep_fim
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':cep_num' => $cep_num]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 5) Para cada transportadora, calcular valor com a regra que você decidiu
$results = [];
foreach ($rows as $r) {
    $valor = 0.0;
    // TODO: implementar sua regra de cálculo aqui
    // Exemplo (Regra A - direta por kg):
    // $valor = floatval($r['valor_kg']) * floatval($peso);
    //
    // Se usar Regra B ou C, implemente as fórmulas correspondentes
    //
    // aplicar tarifa_adicional se existir:
    // if ($r['tarifa_adicional']) { $valor += floatval($r['tarifa_adicional']); }

    // EXPLICAÇÃO: formatar para string com vírgula (pt_BR)
    $valor_str = number_format($valor, 2, ',', '.');

    $results[] = [
        'transportadora' => $r['nome'],
        'valor' => $valor_str,
        'prazo' => (string)$r['prazo'] . ' dias'
    ];
}

// 6) Retornar JSON
header('Content-Type: application/json; charset=utf-8');
echo json_encode($results, JSON_UNESCAPED_UNICODE);