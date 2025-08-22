<div class="container is-fluid">
    <h1 class="title">HOME</h1>
    <h2 class="subtitle">Bienvenido a la página de inicio - <?= $_SESSION['nombre']; ?></h2>
    <p>Esta es la sección principal de tu aplicación.</p>
    <p>Aquí puedes agregar más contenido, como estadísticas, gráficos o cualquier otra información relevante.</p>
    <p>Utiliza el menú de navegación para acceder a otras secciones.</p>
</div>
 <div class="container pb-6 pt-6">
<?php
     require_once "./php/main.php";
     
     require_once "./php/inicio.php";
    ?>
</div>

