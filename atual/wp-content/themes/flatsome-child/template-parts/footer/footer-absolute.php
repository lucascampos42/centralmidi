<?php
/**
 * Footer Absolute Override - Central MIDI Child Theme
 *
 * Garante que qualquer template (como o checkout-focused e o cart)
 * renderize o Footer Moderno unificado da Central MIDI.
 */

if (!defined('ABSPATH')) {
    exit;
}

get_template_part('template-parts/footer/footer-modern');
