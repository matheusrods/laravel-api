<!DOCTYPE html>
<html>
<head>
    <title>Processamento Concluído</title>
</head>
<body>
    <h1>Processamento Concluído</h1>
    
    @if(isset($details['total_processed']) && isset($details['timestamp']))
        <p>O upload do arquivo foi processado com sucesso.</p>
        <p>Resumo:</p>
        <ul>
            <li>Total de colaboradores processados: {{ $details['total_processed'] }}</li>
            <li>Data e hora: {{ $details['timestamp'] }}</li>
        </ul>
    @elseif(isset($details['error']))
        <p>Ocorreu um erro durante o processamento:</p>
        <p>{{ $details['error'] }}</p>
    @else
        <p>Informações insuficientes para gerar o relatório.</p>
    @endif
</body>
</html>
