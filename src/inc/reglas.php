<?php
/**
 * =====================================================================
 *  inc/reglas.php · Reglas comunes de Forja de Héroes II        [CE 3.c]
 * =====================================================================
 *  Fichero SOLO PHP: sin etiqueta de cierre (PSR-12).
 *  Se carga desde expedicion.php con require_once.
 *
 *  @author  TODO (tu nombre)
 *  @version UD3
 */

declare(strict_types=1);

// Variante elegida en el aula virtual: 'A', 'B' o 'C'.
// Decide qué fichero de datos se carga (inc/datos-A.php, ...).
const VARIANTE = 'A';

// --- Reglas heredadas de la UD2 ---------------------------------------
const XP_POR_NIVEL  = 400;   // experiencia necesaria por nivel
const VIDA_BASE     = 50;    // vida de cualquier héroe antes de multiplicar
const BLOQUES_BARRA = 20;    // longitud de las barras de progreso

// --- Novedad UD3: una constante puede ser un ARRAY ---------------------
// Multiplicador de vida según la clase (array asociativo clase => valor).
const MULT_VIDA = [
    'Guerrera'  => 3,
    'Hechicero' => 1,
    'Pícara'    => 2,
    'Clérigo'   => 2,
];

// Combate contra el jefe: tope de rondas para que el bucle no sea infinito.
const MAX_RONDAS = 8;

// Leyenda del mapa: carácter de la matriz => [clase CSS, descripción].
// (Matriz: array de arrays.)
const LEYENDA_MAPA = [
    '#' => ['muro',    'Muro'],
    '.' => ['suelo',   'Pasillo'],
    '~' => ['agua',    'Agua'],
    'E' => ['entrada', 'Entrada'],
    'S' => ['sala',    'Sala'],
    'J' => ['jefe',    'Guarida del jefe'],
];
