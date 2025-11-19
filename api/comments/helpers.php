<?php

declare(strict_types=1);

function sanitize_comment_author(?string $value): string
{
    $text = strip_tags((string)$value);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
    $text = str_replace("\u{00A0}", ' ', $text);
    $text = preg_replace('/\s+/u', ' ', $text) ?? '';

    return trim($text);
}

function sanitize_comment_message(?string $value): string
{
    $text = (string)$value;
    if ($text === '') {
        return '';
    }

    $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $text) ?? $text;
    $text = preg_replace('/<\/p\s*>/i', "\n\n", $text) ?? $text;
    $text = preg_replace('/<p[^>]*>/i', '', $text) ?? $text;
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE, 'UTF-8');
    $text = str_replace("\u{00A0}", ' ', $text);
    $text = preg_replace("/\r\n?/", "\n", $text) ?? $text;
    $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

    return trim($text);
}
