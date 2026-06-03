<?php
// ============================================================
// includes/constants.php — Constantes da Aplicação
// ============================================================

// Horários de funcionamento
if (!defined('HORARIOS_FUNCIONAMENTO')) {
    define('HORARIOS_FUNCIONAMENTO', [
        '07:00', '08:00', '09:00', '10:00', '11:00', '12:00',
        '13:00', '14:00', '15:00', '16:00', '17:00', '18:00',
        '19:00', '20:00', '21:00'
    ]);
}

if (!defined('HORARIO_ABERTURA')) {
    define('HORARIO_ABERTURA', '07:00'); // Alinhado com o primeiro horário disponível no formulário
}

if (!defined('HORARIO_FECHAMENTO')) {
    define('HORARIO_FECHAMENTO', '21:00'); // Alinhado com o último horário disponível no formulário
}
