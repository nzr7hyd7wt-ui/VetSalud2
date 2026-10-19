<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

$titulo = 'VetSalud · Cuidado y peluquería para mascotas';
$pagina = 'inicio';
$sinContenedor = true;

$servicios = $mysqli->query(
    'SELECT id_servicio, nombre, descripcion, precio, duracion_min, imagen
     FROM servicio WHERE activo = 1 ORDER BY precio ASC'
)->fetch_all(MYSQLI_ASSOC);

require __DIR__ . '/includes/header.php';
?>

<section class="hero-bleed" aria-label="Inicio VetSalud">
    <div class="hero-bleed__media">
        <img src="img/hero-mascota.jpg" alt="Cachorro feliz listo para su cita de grooming" width="1280" height="853">
    </div>
    <div class="hero-bleed__overlay" aria-hidden="true"></div>
    <div class="hero-bleed__content">
        <p class="hero-bleed__brand">VetSalud</p>
        <p class="hero-bleed__lead">Baño, peluquería y mimos para tu mascota. Agenda en minutos: eliges el servicio, dejas tus datos y confirmamos por WhatsApp.</p>
        <div class="hero-actions">
            <a class="btn btn-aqua" href="cita.php">Solicitar cita</a>
            <a class="btn btn-ghost" href="#servicios">Ver precios</a>
        </div>
    </div>
</section>

<div class="contenedor">
    <section class="seccion" id="servicios">
        <div class="seccion-titulo">
            <span class="eyebrow">Servicios y precios</span>
            <h2>Todo lo que necesita tu compañero</h2>
            <p>Precios de referencia en pesos colombianos. La cita queda pendiente hasta que el equipo la confirme.</p>
        </div>

        <div class="servicios-grid">
            <?php foreach ($servicios as $s):
                $img = trim((string)($s['imagen'] ?? ''));
                if ($img === '' || !is_file(__DIR__ . '/img/' . $img)) {
                    $img = 'servicio-bano.jpg';
                }
            ?>
                <article class="servicio-tile">
                    <div class="servicio-tile__img">
                        <img src="img/<?= e($img) ?>" alt="<?= e($s['nombre']) ?>" loading="lazy" width="800" height="600">
                    </div>
                    <div class="servicio-tile__body">
                        <h3><?= e($s['nombre']) ?></h3>
                        <p class="servicio-tile__desc"><?= e($s['descripcion'] ?? '') ?></p>
                        <div class="servicio-tile__meta">
                            <span class="precio"><?= e(formatearPrecio($s['precio'])) ?></span>
                            <span class="duracion"><?= (int)$s['duracion_min'] ?> min</span>
                        </div>
                        <a class="btn btn-primary btn-sm" href="cita.php?servicio=<?= (int)$s['id_servicio'] ?>">Agendar este servicio</a>
                    </div>
                </article>
            <?php endforeach; ?>
            <?php if (!$servicios): ?>
                <p>No hay servicios activos por ahora.</p>
            <?php endif; ?>
        </div>
    </section>

    <section class="banda-confianza" id="como">
        <div class="banda-confianza__texto">
            <span class="eyebrow">Cómo funciona</span>
            <h2>Pedir cita es simple y sin cuenta</h2>
            <p>Pensamos el flujo para dueños ocupados: pocos campos, precios visibles y confirmación clara.</p>
            <div class="pasos">
                <div class="paso">
                    <span class="paso-num">1</span>
                    <div><strong>Elige el servicio</strong><span>Con precio y duración a la vista.</span></div>
                </div>
                <div class="paso">
                    <span class="paso-num">2</span>
                    <div><strong>Cuéntanos de ti y tu mascota</strong><span>Nombre, teléfono, especie y horario preferido.</span></div>
                </div>
                <div class="paso">
                    <span class="paso-num">3</span>
                    <div><strong>Recibe confirmación</strong><span>Queda pendiente; puedes avisarnos por WhatsApp.</span></div>
                </div>
            </div>
            <p style="margin-top:1.25rem">
                <a class="btn btn-aqua" href="cita.php">Empezar solicitud</a>
            </p>
        </div>
        <div class="banda-confianza__img">
            <img src="img/about-clinica.jpg" alt="Profesional cuidando a una mascota" loading="lazy" width="1100" height="733">
        </div>
    </section>

    <section class="seccion">
        <div class="seccion-titulo">
            <span class="eyebrow">Ambiente</span>
            <h2>Mascotas felices, dueños tranquilos</h2>
            <p>Espacio calmado, productos pet-safe y manejo paciente.</p>
        </div>
        <div class="galeria-strip">
            <figure><img src="img/galeria-1.jpg" alt="Perro sonriente" loading="lazy"></figure>
            <figure><img src="img/galeria-2.jpg" alt="Gato doméstico" loading="lazy"></figure>
            <figure><img src="img/galeria-3.jpg" alt="Perro listo para paseo" loading="lazy"></figure>
            <figure><img src="img/galeria-4.jpg" alt="Pug adorable" loading="lazy"></figure>
        </div>
    </section>

    <section class="cta-final">
        <h2>¿Listo para agendar?</h2>
        <p>Sin registro. Te respondemos para confirmar fecha y hora.</p>
        <div class="hero-actions" style="justify-content:center">
            <a class="btn btn-light" href="cita.php">Solicitar cita ahora</a>
            <a class="btn btn-ghost" href="<?= e(whatsappUrl('Hola VetSalud, quiero información sobre sus servicios.')) ?>" target="_blank" rel="noopener">WhatsApp</a>
        </div>
    </section>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
