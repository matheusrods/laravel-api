<!DOCTYPE html>
<html>
<head>
    <title>Processamento Concluído</title>
</head>
<body>
    <h1>Processamento Concluído</h1>
    <p>O upload do arquivo foi processado com sucesso.</p>
    <p>Resumo:</p>
    <ul>
        <li>Total de colaboradores processados: {{ $details['total_processed'] }}</li>
        <li>Data e hora: {{ $details['timestamp'] }}</li>
    </ul>
</body>
</html>
