<?php
session_start();
if(isset($_GET['reiniciar'])){
    $_SESSION['fimJogo'] = false;
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// REINICIALIZAÇÃO DE SESSÃO
if(!isset($_SESSION['fase']) || isset($_GET['reiniciar'])){
    $_SESSION['fase'] = 1;
    $_SESSION['pontuacao'] = 0;
    $_SESSION['fimJogo'] = false; // <- resetar fimJogo
    $_SESSION['inicioFase'] = time();
    unset($_SESSION['labirinto'], $_SESSION['jogador']);
}

// CONFIGURAÇÕES DAS FASES
$fases = [
    1 => ['linhas'=>7, 'colunas'=>7, 'tempo'=>60,  'pontos'=>50],
    2 => ['linhas'=>11, 'colunas'=>11, 'tempo'=>120, 'pontos'=>100],
    3 => ['linhas'=>14, 'colunas'=>14, 'tempo'=>180, 'pontos'=>150],
];

$fase = $_SESSION['fase'];
$linhas = $fases[$fase]['linhas'];
$colunas = $fases[$fase]['colunas'];
$tempoFase = $fases[$fase]['tempo'];

// CONTROLE DE TEMPO
if (!isset($_SESSION['inicioFase'])) {
    $_SESSION['inicioFase'] = time();
}

$tempoDecorrido = time() - $_SESSION['inicioFase'];
$tempoRestante = $tempoFase - $tempoDecorrido;

if ($tempoRestante <= 0) {
    // Tempo esgotado: reset completo e volta ao início
    session_destroy();
    header("Location: index.php?msg=tempo_esgotado");
    exit;
}

// GERADOR DE LABIRINTO (backtraking)
function criarLabirinto(&$labirinto, $linha, $coluna){
    $linhas = count($labirinto);
    $colunas = count($labirinto[0]);
    $labirinto[$linha][$coluna] = 0;

    $direcoes = [[-2,0],[2,0],[0,-2],[0,2]];
    shuffle($direcoes);

    foreach($direcoes as $d){
        $novaLinha = $linha + $d[0];
        $novaColuna = $coluna + $d[1];
        if($novaLinha >= 0 && $novaLinha < $linhas && $novaColuna >= 0 && $novaColuna < $colunas && $labirinto[$novaLinha][$novaColuna] == 1){
            $labirinto[$linha + intdiv($d[0],2)][$coluna + intdiv($d[1],2)] = 0;
            criarLabirinto($labirinto, $novaLinha, $novaColuna);
        }
    }
}

// INICIALIZAÇÃO DO LABIRINTO 
if (!isset($_SESSION['labirinto'])) {
    $labirinto = array_fill(0, $linhas, array_fill(0, $colunas, 1));
    criarLabirinto($labirinto, 0, 0);

    // Garante saída
    $labirinto[$linhas-1][$colunas-1] = 0;
    $labirinto[$linhas-2][$colunas-1] = 0;
    $labirinto[$linhas-1][$colunas-2] = 0;

    $_SESSION['labirinto'] = $labirinto;
    $_SESSION['jogador'] = ['linha'=>0,'coluna'=>0];
    $_SESSION['inicioFase'] = time(); // Reset do temporizador a cada fase
}

// MOVIMENTO
if (isset($_GET['mover'])) {
    $dl = $dc = 0;
    switch($_GET['mover']){
        case 'cima': $dl=-1; break;
        case 'baixo': $dl=1; break;
        case 'esquerda': $dc=-1; break;
        case 'direita': $dc=1; break;
    }

    $labirinto = $_SESSION['labirinto'];
    $jogador = $_SESSION['jogador'];
    $saida = ['linha'=>$linhas-1,'coluna'=>$colunas-1];

    $novaLinha = $jogador['linha'] + $dl;
    $novaColuna = $jogador['coluna'] + $dc;

    if ($novaLinha >= 0 && $novaLinha < $linhas && $novaColuna >= 0 && $novaColuna < $colunas && $labirinto[$novaLinha][$novaColuna] == 0) {
        $_SESSION['jogador'] = ['linha'=>$novaLinha,'coluna'=>$novaColuna];

        // Se chegou na saída
        if ($novaLinha==$saida['linha'] && $novaColuna==$saida['coluna']) {
            $_SESSION['pontuacao'] += $fases[$_SESSION['fase']]['pontos'];

            if ($_SESSION['fase'] < 3) {
                $_SESSION['fase']++;
                unset($_SESSION['labirinto'], $_SESSION['jogador']);
                $_SESSION['inicioFase'] = time(); // reset temporizador
                header("Location: labirinto.php");
                exit;
            } else {
                $_SESSION['fimJogo'] = true;
            }
        }
    }
}

// HTML DO LABIRINTO
$labirinto = $_SESSION['labirinto'];
$jogador = $_SESSION['jogador'];
$saida = ['linha'=>$linhas-1,'coluna'=>$colunas-1];

$htmlLabirinto = '';
for ($l=0;$l<$linhas;$l++){
    $htmlLabirinto .= "<tr>";
    for ($c=0;$c<$colunas;$c++){
        $classe = '';
        if ($l==$jogador['linha'] && $c==$jogador['coluna']) $classe='jogador';
        elseif ($l==$saida['linha'] && $c==$saida['coluna']) $classe='saida';
        elseif ($labirinto[$l][$c]==1) $classe='parede';
        else $classe='caminho';
        $htmlLabirinto .= "<td class='$classe'></td>";
    }
    $htmlLabirinto .= "</tr>";
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Jogo de Labirinto</title>
<link rel="stylesheet" href="style.css">
</head>
<body>

<h1>Labirinto - Fase <?php echo $_SESSION['fase']; ?></h1>
<p><strong>Pontuação:</strong> <?php echo $_SESSION['pontuacao']; ?></p>

<div id="mostrador" data-tempo="<?php echo $tempoRestante; ?>">00:00</div>

<?php if(!empty($_SESSION['fimJogo'])): ?>
    <div class="fimJogo">
        <h2>🎉 Parabéns! Você terminou todas as fases!</h2>
        <a href="index.php?reiniciar=1"><button>Voltar ao início</button></a>
    </div>
<?php endif; ?>

    <table>
        <?php echo $htmlLabirinto; ?>
    </table>

<script>
if(!<?php echo !empty($_SESSION['fimJogo']) ? 'true' : 'false'; ?>){
    // Movimento pelo teclado
    document.addEventListener("keydown", e => {
        switch(e.key){
            case "ArrowUp": window.location.href="?mover=cima"; break;
            case "ArrowDown": window.location.href="?mover=baixo"; break;
            case "ArrowLeft": window.location.href="?mover=esquerda"; break;
            case "ArrowRight": window.location.href="?mover=direita"; break;
        }
    });

    // Temporizador
    const mostrador = document.getElementById("mostrador");
    let tempoRestante = parseInt(mostrador.dataset.tempo,10);
    let timer;

    function atualizarTimer(){
        const minutos = Math.floor(tempoRestante/60);
        const segundos = tempoRestante % 60;
        mostrador.textContent = String(minutos).padStart(2,"0")+":"+String(segundos).padStart(2,"0");

        if (tempoRestante>0){
            tempoRestante--;
        } else {
            clearInterval(timer);
            alert("⏰ Tempo esgotado!");
            window.location.href="index.php?reiniciar=1"; // volta ao início
        }
    }

    atualizarTimer();
    timer = setInterval(atualizarTimer,1000);
}
</script>

</body>
</html>


