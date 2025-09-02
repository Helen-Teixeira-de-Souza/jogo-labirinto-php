<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title>Jogo de Labirinto</title>
  <link rel="stylesheet" href="inicio.css">
</head>
<body>

  <div class="container">
    <h1>Bem-vindo ao Labirinto 🎮</h1>
    <h2>Digite seu nome para começar a jogar!</h2>

    <form action= "labirinto.php" method="get">
      <input type="text" name="jogador" placeholder="Seu nome" required>
      <a href="labirinto.php"><button>Começar</button></a>
    </form>
  </div>

</body>
</html>