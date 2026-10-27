<?php
/**
 * =====================================================================
 *  expedicion.php · Forja de Héroes II · La party y la mazmorra
 * =====================================================================
 *  0613 DWES · 2.º DAW A · UD3 · Tarea de refuerzo (sin nota)
 *  Autor/a: TODO (tu nombre y apellidos) · Variante: A
 *
 *  Estructura (paso 09 del caso guiado ARES-7 II):
 *   1) strict_types + carga de reglas.php y del fichero de datos
 *   2) CÁLCULOS: todo arriba, sin HTML; la página sale de los datos
 *   3) PRESENTACIÓN: HTML con sintaxis alternativa
 *      (foreach: ... endforeach;   if: ... else: ... endif;)
 *
 *  Permitido en UD3: if, switch, match, bucles, arrays y matrices.
 *  Todavía NO: funciones propias ni formularios (llegan en la UD4).
 *  Nota del paso 08: «#[» NO abre un comentario en PHP 8.
 */

declare(strict_types=1);

require_once __DIR__ . '/inc/reglas.php';
require_once __DIR__ . '/inc/datos-' . VARIANTE . '.php';

// =====================================================================
// R2 · LA PARTY (matriz + foreach)                              [CE 3.b, 3.c]
// =====================================================================
// Recorremos la matriz y enriquecemos CADA héroe con los datos derivados
// (nivel, vida, daño, estado...). Lo guardamos en la propia matriz para
// que la presentación solo tenga que recorrer y mostrar.

foreach ($party as $i => $heroe) {
    // Nivel y vida máxima salen de las constantes de reglas.php.
    $nivel   = intdiv($heroe['xp'], XP_POR_NIVEL) + 1;
    $vidaMax = VIDA_BASE + $heroe['constitucion'] * $nivel * MULT_VIDA[$heroe['clase']];
    $pctVida = $heroe['vida'] / $vidaMax * 100;

    // TODO 2.2 (R3) · Daño: match sobre la clase, SIN default. Así, si el
    // profesor mete una clase olvidada, el programa falla en el acto (ver memoria).
    $danio = match ($heroe['clase']) {
        'Guerrera'  => $heroe['fuerza'] ** 2 / 10 + $nivel * 2,
        'Arquero'   => $heroe['destreza'] * 2 + $nivel * 1.5,
        'Hechicero' => $heroe['inteligencia'] * 3 + $nivel ** 2 / 4,
        'Pícara'    => $heroe['destreza'] * 2 + $nivel ** 1.5,
        'Clérigo'   => ($heroe['inteligencia'] + $heroe['constitucion']) * 1.5 + $nivel,
    };

    // TODO 2.3 (R3) · Estado: umbrales del enunciado con if / elseif / else.
    if ($heroe['vida'] <= 0) {
        $estado = 'K.O.';
    } elseif ($pctVida < 35) {
        $estado = 'En peligro';
    } elseif ($pctVida < 70) {
        $estado = 'Herido';
    } else {
        $estado = 'En pie';
    }

    // TODO 2.4 (R3) · Posición: switch con casos agrupados.
    switch ($heroe['clase']) {
        case 'Guerrera':
        case 'Clérigo':
            $posicion = 'Primera línea';
            break;
        case 'Arquero':
            $posicion = 'Flanco';
            break;
        case 'Pícara':
            $posicion = 'Flanco';
            break;
        case 'Hechicero':
            $posicion = 'Retaguardia';
            break;
        default:
            $posicion = 'Reserva';
    }

    // TODO 2.5 · Barra de 20 bloques con un for (str_repeat prohibido).
    $llenos = (int) round($pctVida / 100 * BLOQUES_BARRA);
    $barra  = '';
    for ($b = 0; $b < BLOQUES_BARRA; $b++) {
        $barra .= $b < $llenos ? '█' : '░';
    }

    // TODO 2.6 · Guardamos todo en la matriz, que es de quien la recorre.
    $party[$i]['nivel']    = $nivel;
    $party[$i]['vidaMax']  = $vidaMax;
    $party[$i]['pctVida']  = $pctVida;
    $party[$i]['danio']    = $danio;
    $party[$i]['poder']    = (int) round($danio * $nivel);
    $party[$i]['estado']   = $estado;
    $party[$i]['posicion'] = $posicion;
    $party[$i]['barra']    = $barra;
}

// TODO 2.7 · Totales de la party con las funciones de arrays (CE 3.c).
$vidaParty    = array_sum(array_column($party, 'vida'));
$vidaMaxParty = array_sum(array_column($party, 'vidaMax'));
$poderParty   = array_sum(array_column($party, 'poder'));
$nivelMedio   = array_sum(array_column($party, 'nivel')) / count($party);

// TODO 2.8 · Daño conjunto, héroes activos y líder: los K.O. no aportan
// nada, así que los saltamos con continue. El líder es null hasta que un
// héroe activo supera el poder del mejor hasta ese momento.
$danioParty    = 0.0;
$heroesActivos = 0;
$lider         = null;
$poderDelLider = 0;

foreach ($party as $heroe) {
    if ($heroe['estado'] === 'K.O.') {
        continue;
    }
    $heroesActivos++;
    $danioParty += $heroe['danio'];
    if ($lider === null || $heroe['poder'] > $poderDelLider) {
        $lider         = $heroe['nombre'];
        $poderDelLider = $heroe['poder'];
    }
}

// TODO 2.9 · Presencias clave: afectan a la trampa y al descanso. La
// comparación estricta evita sorpresas si algún día cambia el tipo.
$clases     = array_column($party, 'clase');
$hayPicara  = in_array('Pícara', $clases, true);
$hayClerigo = in_array('Clérigo', $clases, true);

// =====================================================================
// R4 · LA EXPEDICIÓN, SALA A SALA                               [CE 3.a, 3.b, 3.c]
// =====================================================================
// La party lleva una única reserva de vida (la suma de sus héroes). Cada
// sala cambia el oro, la XP, la vida o el inventario, y escribe una línea
// en el diario: nada se calcula dos veces ni a mano.

# Estado inicial de la expedición.
$oro             = $oroInicial;
$xpGanada        = 0;
$diario          = [];
$salasExploradas = 0;
$salasPendientes = 0;
$vidaAlEntrar   = $vidaParty;   // para el diario: vida al entrar

// Primera línea del diario: el punto de partida de la party.
$diario[] = "La party entra en la mazmorra con $vidaAlEntrar puntos de vida.";

foreach ($salas as $n => $sala) {
    $numero = $n + 1;                       // las salas se numeran desde 1
    $salasExploradas++;

    switch ($sala['tipo']) {
        case 'vacía':
            // Nada ocurre: pasamos a la siguiente sala. Dentro de un switch
            // un solo continue solo sale del switch, así que hacen falta dos.
            $diario[] = "Sala $numero · {$sala['nombre']}: vacía, la party sigue avanzando.";
            continue 2;

        case 'trampa':
            // La Pícara desactiva las trampas; sin ella, la party sangre.
            if ($hayPicara) {
                $diario[] = "Sala $numero · {$sala['nombre']}: ¡trampa! La Pícara la desactiva sin daño.";
            } else {
                $vidaParty -= $sala['valor'];
                $diario[]  = "Sala $numero · {$sala['nombre']}: ¡trampa! −{$sala['valor']} de vida.";
            }
            break;

        case 'combate':
            // Coste de vida = un cuarto del valor; la XP = el valor entero.
            $coste = intdiv($sala['valor'], 4);
            $vidaParty -= $coste;
            $xpGanada  += $sala['valor'];
            $diario[]   = "Sala $numero · {$sala['nombre']}: combate, −$coste de vida y +{$sala['valor']} XP.";
            break;

        case 'tesoro':
            // El oro siempre; el objeto solo si la sala trae la clave 'objeto'.
            $oro   += $sala['valor'];
            $linea  = "Sala $numero · {$sala['nombre']}: +{$sala['valor']} mo";
            if (array_key_exists('objeto', $sala)) {
                $inventario[$sala['objeto']] = ($inventario[$sala['objeto']] ?? 0) + 1;
                $linea .= " y «" . htmlspecialchars($sala['objeto']) . "»";
            }
            $diario[] = $linea . '.';
            break;

        case 'descanso':
            // El clérigo duplica la bendición, pero nadie cura por encima de
            // su vida máxima: min() evita curar por encima del tope.
            $cura   = $sala['valor'] * ($hayClerigo ? 2 : 1);
            $antes  = $vidaParty;
            $vidaParty = min($vidaMaxParty, $vidaParty + $cura);
            $curado = $vidaParty - $antes;    // lo realmente curado (con tope)
            $sufijo = $hayClerigo ? ' (bendición del Clérigo)' : '';
            $diario[] = "Sala $numero · {$sala['nombre']}: descanso, +$curado de vida$sufijo.";
            break;
    }

    // Tras el switch: si la party ha caído, anotamos el momento y cortamos
    // el recorrido con break (las salas que quedan quedan pendientes).
    if ($vidaParty <= 0) {
        $vidaParty = 0;
        $diario[]  = "Sala $numero: ¡la party ha caído! El recorrido se detiene.";
        break;
    }
}

$salasPendientes = count($salas) - $salasExploradas;

// Vida al salir de las salas (antes del jefe se muerde la reserva).
$vidaTrasSalas = $vidaParty;

// =====================================================================
// R5 · EL JEFE                                                  [CE 3.a, 3.b]
// =====================================================================
// Un golpe de la party por ronda, un ataque del jefe por ronda. El while
// guarda cada ronda en una matriz para que la tabla la pueda recorrer.

/* La llave decide si la puerta de la guarida se abre o no. */
$tieneLlave = array_key_exists($jefe['llave'], $inventario);
$golpe      = (int) round($danioParty);   // el golpe se redondea a entero

$vidaJefe = $jefe['vida'];
$ronda    = 0;
$rondas   = [];

if ($vidaParty <= 0) {
    // No llegó: nadie combate y el jefe ni enterarse.
    $resultado = 'Derrota';
    $motivo    = 'la party no llegó con vida a la guarida';
} elseif (!$tieneLlave) {
    $resultado = 'Puerta sellada';
    $motivo    = 'la party no tiene la «' . htmlspecialchars($jefe['llave']) . '»';
} else {
    // Combate por rondas, con tope para que el bucle no dure para siempre.
    while ($vidaJefe > 0 && $vidaParty > 0 && $ronda < MAX_RONDAS) {
        $ronda++;
        $vidaJefe = max(0, $vidaJefe - $golpe);
        if ($vidaJefe > 0) {
            $vidaParty = max(0, $vidaParty - $jefe['ataque']);
        }
        $rondas[] = ['ronda' => $ronda, 'vidaJefe' => $vidaJefe, 'vidaParty' => $vidaParty];
    }

    // match (true) permite comparar condiciones en lugar de valores.
    $resultado = match (true) {
        $vidaJefe <= 0  => 'Victoria',
        $vidaParty <= 0 => 'Derrota',
        default         => 'Retirada',
    };
    $motivo = match ($resultado) {
        'Victoria' => 'el jefe ha caído',
        'Derrota'  => 'la party ha caído ante el jefe',
        default    => 'no se resolvió en ' . MAX_RONDAS . ' rondas',
    };
    if ($resultado === 'Victoria') {
        $oro += $jefe['recompensa'];
    }
}

// =====================================================================
// R4 (cont.) · INVENTARIO FINAL                                 [CE 3.b, 3.c]
// =====================================================================
// Una vez cerrada la expedición (con los objetos de las salas de tesoro
// ya dentro), valoramos, contamos y ordenamos. arsort ordena por cantidad
// de mayor a menor conservando las claves (PHP >= 8.0: orden estable).

$valorInventario = 0.0;
foreach ($inventario as $objeto => $cantidad) {
    $valorInventario += $cantidad * ($precios[$objeto] ?? 0.0);
}

$totalObjetos        = array_sum($inventario);
$inventarioOrdenado  = $inventario;   // copia: el original queda intacto
arsort($inventarioOrdenado);
$objetoMasAbundante  = array_key_first($inventarioOrdenado);

// =====================================================================
// R6 · EL MAPA (matriz 2D)                                      [CE 3.b, 3.c]
// =====================================================================
// Contamos celdas con dos for anidados y un array asociativo de recuentos;
// luego comprobamos que el mapa y la lista de salas se cuenten la misma cosa.

$conteoCeldas = [];
for ($f = 0; $f < count($mapa); $f++) {
    for ($c = 0; $c < count($mapa[$f]); $c++) {
        $celda = $mapa[$f][$c];
        $conteoCeldas[$celda] = ($conteoCeldas[$celda] ?? 0) + 1;
    }
}
$mapaCoherente = ($conteoCeldas['S'] ?? 0) === count($salas);

// Barra global de vida de la party tras toda la expedición y el jefe.
$llenosParty = (int) round($vidaParty / $vidaMaxParty * BLOQUES_BARRA);
$barraParty  = '';
for ($b = 0; $b < BLOQUES_BARRA; $b++) {
    $barraParty .= $b < $llenosParty ? '█' : '░';
}

# Formato español listo para pintar (mmil con punto, decimales con coma).
$oroFormateado          = number_format($oro, 2, ',', '.');
$valorInvFormateado     = number_format($valorInventario, 2, ',', '.');
$nivelMedioFormateado   = number_format($nivelMedio, 1, ',', '.');
$danioPartyFormateado   = number_format($danioParty, 1, ',');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Expedición · <?= htmlspecialchars($nombreMazmorra) ?> · Forja de Héroes II</title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
<main>
    <span class="insignia">VARIANTE <?= VARIANTE ?></span>
    <h1><?= htmlspecialchars($nombreMazmorra) ?></h1>
    <p class="subtitulo">Informe de expedición · líder: <?= $lider === null ? '—' : htmlspecialchars($lider) ?></p>

    <h2>La party</h2>
    <table>
        <tr><th>Héroe</th><th>Clase</th><th>Nivel</th><th>Vida</th><th>Daño</th><th>Poder</th><th>Posición</th><th>Estado</th></tr>
        <!-- Una sola fila escrita en el fuente: el foreach la repite en el HTML -->
        <?php foreach ($party as $heroe): ?>
        <tr>
            <td><?= htmlspecialchars($heroe['nombre']) ?></td>
            <td><?= htmlspecialchars($heroe['clase']) ?></td>
            <td><?= $heroe['nivel'] ?></td>
            <td><span class="barra mini"><?= $heroe['barra'] ?></span> <?= $heroe['vida'] ?>/<?= $heroe['vidaMax'] ?></td>
            <td><?= number_format($heroe['danio'], 1, ',', '.') ?></td>
            <td><?= $heroe['poder'] ?></td>
            <td><?= $heroe['posicion'] ?></td>
            <?php if ($heroe['estado'] === 'K.O.' || $heroe['estado'] === 'En peligro'): ?>
                <td class="peligro"><?= $heroe['estado'] ?></td>
            <?php else: ?>
                <td class="vida"><?= $heroe['estado'] ?></td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="rejilla">
        <div class="stat"><div class="etq">Poder total</div><div class="val"><?= $poderParty ?></div></div>
        <div class="stat"><div class="etq">Nivel medio</div><div class="val"><?= $nivelMedioFormateado ?></div></div>
        <div class="stat"><div class="etq">Daño por ronda</div><div class="val"><?= $danioPartyFormateado ?></div></div>
        <div class="stat"><div class="etq">Héroes activos</div><div class="val"><?= $heroesActivos ?> / <?= count($party) ?></div></div>
        <div class="stat"><div class="etq">Vida de la party</div><div class="val"><?= $vidaTrasSalas ?> / <?= $vidaMaxParty ?></div></div>
    </div>

    <h2>Diario de la expedición</h2>
    <ol class="diario">
        <?php foreach ($diario as $linea): ?>
        <li><?= $linea ?></li>
        <?php endforeach; ?>
    </ol>
    <p>Salas exploradas: <?= $salasExploradas ?> de <?= count($salas) ?>
        <?php if ($salasPendientes > 0): ?>
            (quedan <span class="peligro"><?= $salasPendientes ?></span> sin explorar)
        <?php endif; ?>
        · XP ganada: <?= $xpGanada ?></p>

    <h2>El jefe: <?= htmlspecialchars($jefe['nombre']) ?></h2>
    <p>Vida <?= $jefe['vida'] ?> · ataque <?= $jefe['ataque'] ?> · golpe de la party: <?= $golpe ?> por ronda</p>
    <?php if ($rondas !== []): ?>
    <!-- La tabla de rondas solo existe si hubo combate -->
    <table>
        <tr><th>Ronda</th><th>Vida del jefe</th><th>Vida de la party</th></tr>
        <?php foreach ($rondas as $r): ?>
        <tr><td><?= $r['ronda'] ?></td><td><?= $r['vidaJefe'] ?></td><td><?= $r['vidaParty'] ?></td></tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
    <p class="resultado <?= $resultado === 'Victoria' ? 'vida' : 'peligro' ?>"><strong><?= $resultado ?></strong> · <?= $motivo ?></p>
    <p class="barra vida">PARTY <?= $barraParty ?> <?= $vidaParty ?>/<?= $vidaMaxParty ?></p>

    <h2>Inventario</h2>
    <table>
        <tr><th>Objeto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr>
        <?php foreach ($inventarioOrdenado as $objeto => $cantidad): ?>
        <tr>
            <td><?= htmlspecialchars($objeto) ?></td>
            <td><?= $cantidad ?></td>
            <td><?= number_format($precios[$objeto] ?? 0.0, 2, ',', '.') ?> mo</td>
            <td><?= number_format($cantidad * ($precios[$objeto] ?? 0.0), 2, ',', '.') ?> mo</td>
        </tr>
        <?php endforeach; ?>
    </table>
    <div class="rejilla">
        <div class="stat"><div class="etq">Objetos</div><div class="val"><?= $totalObjetos ?></div></div>
        <div class="stat"><div class="etq">Valor del inventario</div><div class="val"><?= $valorInvFormateado ?> mo</div></div>
        <div class="stat"><div class="etq">Oro final</div><div class="val"><?= $oroFormateado ?> mo</div></div>
        <div class="stat"><div class="etq">Más abundante</div><div class="val peq"><?= htmlspecialchars($objetoMasAbundante ?? '—') ?></div></div>
    </div>

    <h2>Mapa de la mazmorra</h2>
    <table class="mapa">
        <?php foreach ($mapa as $fila): ?>
        <tr>
            <?php foreach ($fila as $celda): ?>
            <td class="<?= LEYENDA_MAPA[$celda][0] ?? 'desconocida' ?>"><?= htmlspecialchars($celda) ?></td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
    </table>
    <p>
        <?php foreach (LEYENDA_MAPA as $caracter => $datos): ?>
        <strong><?= htmlspecialchars($caracter) ?></strong> <?= $datos[1] ?> (<?= $conteoCeldas[$caracter] ?? 0 ?>)
        <?php endforeach; ?>
    </p>
    <?php if ($mapaCoherente): ?>
    <p>El mapa es coherente: sus <?= $conteoCeldas['S'] ?> celdas «S» coinciden con las <?= count($salas) ?> salas de la mazmorra.</p>
    <?php else: ?>
    <p class="peligro">¡Mapa incoherente! El mapa dibuja <?= $conteoCeldas['S'] ?? 0 ?> salas «S», pero la mazmorra tiene <?= count($salas) ?>.</p>
    <?php endif; ?>

    <footer>Informe generado el <?= date('d/m/Y H:i') ?> con PHP <?= PHP_VERSION ?> · cada recarga lo recalcula todo desde cero.</footer>
</main>
</body>
</html>
