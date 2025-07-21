<?php
function buscarPermissaoPorEmail($emailInput) {
    require $_SERVER['DOCUMENT_ROOT'] . '/Restritos/Credenciais/BancoDados.php';

    $pdo = new PDO("sqlsrv:server=$dbhost;Database=$db", $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8
    ]);

    $sql = "SELECT DS_NOME, DS_EMAIL, NR_CPFCNPJ
            FROM _USR_CONF_SITE_CADASTROS
            WHERE LOWER(DS_EMAIL) = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([strtolower(trim($emailInput))]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $pdo = null;
        return null;
    }

    $cpfcnpjNumeros = preg_replace('/\D/', '', $usuario['NR_CPFCNPJ']);
    $codStmt = $pdo->prepare(
        "SELECT TOP 1 CD_PESSOA FROM MBAD_PESSOA WHERE NR_CPFCNPJ = ?"
    );
    $codStmt->execute([$cpfcnpjNumeros]);
    $codigo = $codStmt->fetchColumn() ?: 0;

    $grupo = 'BRONZE';
    if ($codigo) {
        $permStmt = $pdo->prepare(
            "SELECT MAX(TIPO.DS_TIPO) AS PERMISSAO\n" .
            "FROM MBAD_PESSOACONTATO AS CONTATO\n" .
            "INNER JOIN MBAD_PESSOACONTATOTIPO AS TIPO ON CONTATO.CD_TIPO = TIPO.CD_TIPO\n" .
            "WHERE CONTATO.CD_PESSOA = ? AND CONTATO.CD_FUNCAO = 'SITE' AND LOWER(CONTATO.DS_EMAIL) = ?"
        );
        $permStmt->execute([$codigo, strtolower(trim($emailInput))]);
        $permissao = $permStmt->fetchColumn();
        $grupo = $permissao ? $permissao : 'PRATA';
    }

    $pdo = null;
    return [
        'DS_NOME' => $usuario['DS_NOME'],
        'DS_EMAIL' => $usuario['DS_EMAIL'],
    ];
}
?>