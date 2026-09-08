<?php
declare(strict_types=1);

namespace Prescia\Tests;

use PHPUnit\Framework\TestCase;

final class SecurityRegressionTest extends TestCase
{
    public function testForumPreviewUsesParameterizedQueriesForRequestIds(): void
    {
        $payload = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_bb/payload/content/preview.php');

        self::assertStringContainsString('$core->dbo->queryPrepared(', $payload);
        self::assertStringContainsString("'ii'", $payload);
        self::assertStringContainsString("'i'", $payload);
        self::assertStringNotContainsString(<<<'SQL'
WHERE id_forum=".$_POST['id_forum']
SQL, $payload);
        self::assertStringNotContainsString(<<<'SQL'
WHERE f.id=".$_POST['id_forum']
SQL, $payload);
    }

    public function testAuthenticatedUserHistoryUsesParameterizedUpdate(): void
    {
        $authControl = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_auth/authControl.php');

        self::assertStringContainsString('SET history=?,userprefs=? WHERE id=?', $authControl);
        self::assertStringContainsString("array(\$history, serialize(\$_SESSION[CONS_SESSION_ACCESS_USER]['userprefs']), (int)\$_SESSION[CONS_SESSION_ACCESS_USER]['id'])", $authControl);
        self::assertStringNotContainsString('simpleQuery("UPDATE ".$loginModule->dbname." SET history=', $authControl);
    }

    public function testUniqueAjaxRouteUsesFieldAllowlistRbacAndPreparedValues(): void
    {
        $route = (string) file_get_contents(__DIR__ . '/../prescia/lazyload/ajaxqueryunique.php');

        self::assertStringContainsString('in_array($_REQUEST[\'field\'],$obj->keys,true)', $route);
        self::assertStringContainsString('checkPermission($obj,CONS_ACTION_SELECT)', $route);
        self::assertStringContainsString(<<<'PHP'
fetchPrepared($sql,'s',array($_REQUEST['value']))
PHP, $route);
        self::assertStringNotContainsString('addslashes($_REQUEST[\'value\'])', $route);
        self::assertStringNotContainsString('error on SQL: ".$sql', $route);
    }

    public function testAjaxSelectRouteDoesNotDisableSafetyChecks(): void
    {
        $route = (string) file_get_contents(__DIR__ . '/../prescia/lazyload/ajaxQuery.php');

        self::assertStringContainsString('checkPermission($module,CONS_ACTION_SELECT)', $route);
        self::assertStringNotContainsString('$this->safety = false', $route);
    }

    public function testAdministrativeImportRestoresSafetyAfterIgnoreErrors(): void
    {
        $route = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/actions/import.php');

        self::assertStringContainsString('$previousSafety = $core->safety;', $route);
        self::assertStringContainsString('$core->safety = $previousSafety;', $route);
    }

    public function testFileManagerDoesNotUseRawDeletePathOrPrefixOnlySafeCheck(): void
    {
        $deleteRoute = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/actions/files.php');
        $fileManager = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_fm/module.php');

        self::assertStringNotContainsString('$_REQUEST[\'delfile\']\n', $deleteRoute);
        self::assertStringContainsString("preg_match('#^[A-Za-z0-9_-]+(?:/[A-Za-z0-9_-]+)*$#'", $deleteRoute);
        self::assertStringContainsString('realpath(CONS_FMANAGER.CONS_FMANAGER_SAFE)', $fileManager);
        self::assertStringContainsString('str_starts_with($candidate, $base.DIRECTORY_SEPARATOR)', $fileManager);
        self::assertStringNotContainsString('substr($dir,0,strlen("/".CONS_FMANAGER.CONS_FMANAGER_SAFE', $fileManager);
    }

    public function testInputDrivenSqlFiltersEscapeValuesBeforeBuildingLegacySql(): void
    {
        $remoteKeys = (string) file_get_contents(__DIR__ . '/../prescia/components/module.php');
        $ajaxQuery = (string) file_get_contents(__DIR__ . '/../prescia/lazyload/ajaxQuery.php');
        $adminList = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/content/list.php');

        self::assertStringContainsString('addslashes_EX((string)$value, true, $rmodule->parent->dbo)', $remoteKeys);
        self::assertStringContainsString('addslashes_EX((string)$_GET[$fname],true,$this->dbo)', $ajaxQuery);
        self::assertStringContainsString("addslashes_EX((string)\$_REQUEST['affrefererkeys']", $adminList);
        self::assertStringNotContainsString('"=\\\"".$_GET[$fname]."\\\""', $ajaxQuery);
    }

    public function testBiStatsEscapesExternalTelemetryBeforeLegacySql(): void
    {
        $stats = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/module.php');

        self::assertStringContainsString('$sqlEscape = static function ($value) use ($core): string', $stats);
        self::assertStringContainsString('$pageToBelogged = $sqlEscape($pageToBelogged);', $stats);
        self::assertStringContainsString('$domain = $sqlEscape($domain);', $stats);
        self::assertStringContainsString('$pages = $sqlEscape($pages);', $stats);
        self::assertStringContainsString('$referer = $sqlEscape($referer);', $stats);
        self::assertStringContainsString('$browser = $sqlEscape($browser);', $stats);
    }

    public function testUndoUsesPreparedQueriesForRecordKeysAndHistoryDeletion(): void
    {
        $undo = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_undo/module.php');

        self::assertStringContainsString('$module->getPreparedKeys($ws,$wTypes,$wParams,$ka,$data);', $undo);
        self::assertStringContainsString('$this->parent->dbo->queryPrepared($sql,$wTypes,$wParams,$r,$n)', $undo);
        self::assertStringContainsString('queryPrepared("DELETE FROM ".$undo->dbname." WHERE id=?", \'i\'', $undo);
        self::assertStringContainsString("queryPrepared(\$sql, 'sssssi'", $undo);
        self::assertStringNotContainsString('$core->dbo->simpleQuery($sql);', $undo);
    }

    public function testAdministrativeUndoRoutesValidateRequestIdsBeforeBuildingSql(): void
    {
        $undo = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/actions/undo.php');
        $multipleUndo = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/actions/multipleundo.php');

        self::assertStringContainsString('filter_var($_REQUEST[\'id\'], FILTER_VALIDATE_INT)', $undo);
        self::assertStringContainsString('$sql = $undo->get_base_sql($undo->name.".id=".$id);', $undo);
        self::assertStringNotContainsString('$undo->name.".id=".$_REQUEST[\'id\']', $undo);
        self::assertStringContainsString('$id = filter_var($id, FILTER_VALIDATE_INT);', $multipleUndo);
        self::assertStringContainsString('if ($id === false || $id < 1) continue;', $multipleUndo);
    }

    public function testThumbnailRedirectUsesContextualQueryEncoding(): void
    {
        $route = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_adm/payload/actions/edit_thumbnails.php');

        self::assertStringContainsString("http_build_query(\$query, '', '&', PHP_QUERY_RFC3986)", $route);
        self::assertStringContainsString("'field' => \$field", $route);
        self::assertStringNotContainsString('implode("&",$qs)', $route);
        self::assertStringNotContainsString('$_REQUEST[\'field\']."&"', $route);
    }

    public function testLoadUrlRestrictsProtocolsPortsAndPrivateNetworks(): void
    {
        $loader = (string) file_get_contents(__DIR__ . '/../prescia/lib/loadURL.php');

        self::assertStringContainsString("in_array(\$scheme, array('http', 'https'), true)", $loader);
        self::assertStringContainsString("FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE", $loader);
        self::assertStringContainsString('function presciaLoadUrlIsValidHost', $loader);
        self::assertStringContainsString('strpbrk($host, "\\r\\n\\0")', $loader);
        self::assertStringContainsString('$port < 1 || $port > 65535', $loader);
        self::assertStringContainsString('foreach ($ips as $ip)', $loader);
        self::assertStringContainsString("stream_socket_client", $loader);
        self::assertStringContainsString("'verify_peer' => true", $loader);
        self::assertStringContainsString("PRESCIA_LOADURL_MAX_BYTES", $loader);
        self::assertStringNotContainsString('fsockopen(', $loader);
        self::assertStringNotContainsString('Location:', $loader);
    }

    public function testValidatedConnectionDoesNotReResolveHostnameDuringDnsRebinding(): void
    {
        require_once __DIR__ . '/../prescia/lib/loadURL.php';
        $targets = array();
        $socket = fopen('php://temp', 'r+');
        self::assertIsResource($socket);

        $connector = static function (string $target, int $port, $context) use (&$targets, $socket) {
            $targets[] = $target . ':' . $port;
            // A later DNS lookup would return 127.0.0.1; the connector must
            // still receive the IP approved by the first validation step.
            return $socket;
        };

        $connection = presciaLoadUrlOpenValidatedConnection(
            array('93.184.216.34'),
            'http',
            80,
            stream_context_create(),
            $connector
        );

        self::assertSame($socket, $connection);
        self::assertSame(array('93.184.216.34:80'), $targets);
        self::assertNotContains('127.0.0.1:80', $targets);
        fclose($socket);
    }

    public function testFrontControllerEmitsBaselineSecurityHeaders(): void
    {
        $frontController = (string) file_get_contents(__DIR__ . '/../index.php');

        self::assertStringContainsString('X-Content-Type-Options: nosniff', $frontController);
        self::assertStringContainsString('X-Frame-Options: SAMEORIGIN', $frontController);
        self::assertStringContainsString('Referrer-Policy: strict-origin-when-cross-origin', $frontController);
        self::assertStringContainsString('Permissions-Policy:', $frontController);
        self::assertStringContainsString('Cross-Origin-Opener-Policy: same-origin', $frontController);
        self::assertStringContainsString('Strict-Transport-Security:', $frontController);
    }

    public function testFrontControllerUsesPerRequestNonceInEnforcedCsp(): void
    {
        $frontController = (string) file_get_contents(__DIR__ . '/../index.php');
        $core = (string) file_get_contents(__DIR__ . '/../prescia/core.php');

        self::assertStringContainsString('$cspNonce = base64_encode(random_bytes(16));', $frontController);
        self::assertStringContainsString("Content-Security-Policy: default-src 'self';", $frontController);
        self::assertStringContainsString("script-src 'self' 'nonce-", $frontController);
        self::assertStringContainsString("object-src 'none'", $frontController);
        self::assertStringContainsString("'CSP_NONCE' =>", $frontController);
        self::assertStringContainsString('htmlspecialchars($cspNonce, ENT_QUOTES, \'UTF-8\')', $core);
        self::assertStringContainsString("preg_replace('/<script\\b(?![^>]*\\bnonce=)/i'", $core);
        self::assertStringNotContainsString('Content-Security-Policy-Report-Only:', $frontController);
    }

    public function testCkfinderDoesNotSelectOrAdvertiseThePhp4Runtime(): void
    {
        $entrypoint = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/ckfinder.php');
        $constants = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/constants.php');

        self::assertStringContainsString("throw new RuntimeException('CKFinder no longer supports PHP 4.');", $entrypoint);
        self::assertStringContainsString("require_once 'core/ckfinder_php5.php';", $entrypoint);
        self::assertStringNotContainsString("require_once 'core/ckfinder_php4.php'", $entrypoint);
        self::assertStringContainsString("define('CKFINDER_CONNECTOR_PHP_MODE', 5);", $constants);
        self::assertStringContainsString("define('CKFINDER_CONNECTOR_LIB_DIR', \"./php5\");", $constants);
        self::assertStringNotContainsString('"./php4"', $constants);
    }
}
