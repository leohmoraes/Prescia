<?php

/**
 * Static-analysis declarations for Prescia's legacy global API.
 *
 * This file is never loaded by the application. It intentionally contains
 * signatures only, so PHPStan can analyze dynamically loaded framework code
 * without executing filesystem, mail, image, or database side effects.
 */

function addslashes_EX(mixed $value, bool $isHtml = true, mixed $dbo = false): string {}
function arrayToString(mixed $array = false, array $exclude = [], bool $noArrays = false): string {}
function cWriteFile(string $file, string $content, bool $append = false, bool $binary = false): bool {}
function cleanString(mixed $data, bool $isHtml = false, bool $allowAdvanced = false, mixed $dbo = false): string {}
function console(mixed $core, mixed $command): mixed {}
function cropImage(mixed ...$arguments): mixed {}
function datecalc(mixed ...$arguments): mixed {}
function datecompare(mixed ...$arguments): mixed {}
function extractUri(string $installRoot = '', string $uri = ''): array {}
function fd(mixed $date, string $mask = 'd/m/Y'): string {}
function fv(mixed $value): string {}
function getmicrotime(): float {}
function htmlentities_ex(mixed $value, mixed ...$arguments): string {}
function humanSize(mixed $size): string {}
function isMail(mixed $mail, bool $allowExtended = false): int {}
function listFiles(mixed ...$arguments): array {}
function locateAnyFile(mixed ...$arguments): mixed {}
function locateFile(mixed ...$arguments): mixed {}
function makeDirs(mixed ...$arguments): bool {}
function parseHTML(mixed $html, bool $simplify = false): mixed {}
function quota(mixed ...$arguments): mixed {}
function recursive_del(mixed ...$arguments): bool {}
function removeBOM(mixed &$data): void {}
function removeSimbols(mixed ...$arguments): string {}
function resizeImage(mixed ...$arguments): mixed {}
function resizeImageCond(mixed ...$arguments): mixed {}
function safe_mkdir(mixed ...$arguments): bool {}
function scriptTime(): float {}
function sendMail(mixed ...$arguments): mixed {}
function storeFile(mixed ...$arguments): mixed {}
function stripHTML(mixed ...$arguments): string {}
function time_diff(mixed ...$arguments): mixed {}
function tomktime(mixed ...$arguments): int {}
function truncate(mixed $content, int $size = 50, string $final = '…', bool $stripHtml = false, bool $preserveEol = false): string {}
function xmlParamsParser(mixed $data): array {}

class CPrescia {}
class CPresciaFull {}
class CKTCexternal {}
class TTree {}
class xmlHandler {}
