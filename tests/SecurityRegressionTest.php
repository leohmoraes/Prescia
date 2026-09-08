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

    public function testAuthBootstrapAndPermissionUpdatesUsePreparedValues(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_auth/module.php');
        $authControl = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_auth/authControl.php');

        self::assertStringContainsString('INSERT INTO ".$groupTable." SET name=?, level=?, id=?, permissions=?', $module);
        self::assertStringContainsString('INSERT INTO ".$userTable." SET name=?, id=?, id_group=?, login=?, password=?, active=?', $module);
        self::assertStringContainsString('UPDATE ".$groups->dbname." SET permissions=? WHERE id=?', $authControl);
        self::assertStringNotContainsString('simpleQuery("INSERT INTO ".$this->parent->modules[CONS_AUTH_USERMODULE]->dbname', $module);
    }

    public function testLoginUsesAccountAndIpRateLimiterWithoutUserEnumeration(): void
    {
        $authControl = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_auth/authControl.php');

        self::assertStringContainsString("require_once dirname(__DIR__, 2) . '/lib/loginRateLimiter.php';", $authControl);
        self::assertStringContainsString('$this->getLoginLimiter()->isAllowed($login, $ip)', $authControl);
        self::assertStringContainsString('$this->recordLoginFailure($login, $ip);', $authControl);
        self::assertStringContainsString('$this->getLoginLimiter()->clear($login, $ip);', $authControl);
        self::assertStringContainsString('return CONS_AUTH_SESSION_FAIL_UNKNOWN;', $authControl);
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

    public function testGenericCrudUsesPreparedKeysForPruningParentChecksAndMutations(): void
    {
        $module = (string) file_get_contents(__DIR__ . '/../prescia/components/module.php');

        self::assertStringContainsString('function getPreparedKeys(', $module);
        self::assertStringContainsString('$this->parent->dbo->queryPrepared($sql,"s",array((string)$data[$field])', $module);
        self::assertStringContainsString('fetchPrepared("SELECT ".$name." FROM ".$this->dbname." WHERE ".$this->keys[0]."=?"', $module);
        self::assertStringContainsString('$this->parent->dbo->queryPrepared("DELETE FROM ".$this->dbname." WHERE ".$wS', $module);
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

    public function testBiStatsResolutionUsesValidationAndPreparedQueries(): void
    {
        $stats = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/module.php');

        self::assertStringContainsString(<<<'PHP'
preg_match('/^[1-9][0-9]{1,4}x[1-9][0-9]{1,4}$/', $resolution)
PHP, $stats);
        self::assertStringContainsString('fetchPrepared("SELECT hits FROM ".$statsResolution." WHERE data=? AND resolution=?"', $stats);
        self::assertStringContainsString('queryPrepared("UPDATE ".$statsResolution." SET hits=hits+1 WHERE data=? AND resolution=?"', $stats);
        self::assertStringContainsString('fetchPrepared("SELECT hits FROM ".$statsResolution." WHERE data=? AND resolution=?", \'ss\'', $stats);
        self::assertStringNotContainsString('resolution=\\"".$_SESSION[CONS_USER_RESOLUTION]', $stats);
    }

    public function testBiStatsRealtimeEndpointAuthorizesAndEscapesOutput(): void
    {
        $route = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/payload/actions/stats_rtajax.php');

        self::assertStringContainsString('$_SESSION[CONS_SESSION_ACCESS_LEVEL] < 10', $route);
        self::assertStringContainsString("filter_var(\$_REQUEST['ip'] ?? '', FILTER_VALIDATE_IP)", $route);
        self::assertStringContainsString('queryPrepared("SELECT * from ".$rt->dbname." WHERE ip=?"', $route);
        self::assertStringContainsString("htmlspecialchars((string)\$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')", $route);
        self::assertStringNotContainsString('WHERE ip=\'$ip\'', $route);
        self::assertStringNotContainsString("echo \"Navegador: \".\$dados['agent']", $route);
    }

    public function testBiStatsCsvExportUsesStructuredRowsAndSafeHeaders(): void
    {
        $export = (string) file_get_contents(__DIR__ . '/../prescia/plugins/bi_stats/payload/actions/stats_export.php');

        self::assertStringContainsString("fputcsv(\$csv", $export);
        self::assertStringContainsString("preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', \$date)", $export);
        self::assertStringContainsString('X-Content-Type-Options: nosniff', $export);
        self::assertStringContainsString('Cache-Control: no-store, no-cache, must-revalidate', $export);
        self::assertStringNotContainsString('$outputstr .= "\\\"".array_shift($o)', $export);
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
        self::assertStringContainsString('X-Download-Options: noopen', $frontController);
        self::assertStringContainsString('X-DNS-Prefetch-Control: off', $frontController);
        self::assertStringContainsString('Cross-Origin-Opener-Policy: same-origin', $frontController);
        self::assertStringContainsString('Cross-Origin-Resource-Policy: same-origin', $frontController);
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

    public function testCKFinderP0UploadPolicyUsesPositiveLimitsAndNoActiveTypes(): void
    {
        $config = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/config.php');

        self::assertSame(2, substr_count($config, "'maxSize' => '"));
        self::assertStringContainsString("'maxSize' => '8M'", $config);
        self::assertStringContainsString("'maxSize' => '5M'", $config);
        self::assertStringNotContainsString("'name' => 'Flash'", $config);
        self::assertStringNotContainsString("'allowedExtensions' => 'swf,flv'", $config);
        self::assertStringContainsString("\$config['HtmlExtensions'] = array('html', 'htm', 'xml', 'js', 'svg')", $config);
    }

    public function testCKFinderUploadValidatesMimeAndCanonicalDestination(): void
    {
        $upload = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php');
        $resourceConfig = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php5/Core/ResourceTypeConfig.php');

        self::assertStringContainsString("is_uploaded_file(", $upload);
        self::assertStringContainsString("finfo_open(FILEINFO_MIME_TYPE)", $upload);
        self::assertStringContainsString('isPathInside($sServerDir, $sFilePath)', $upload);
        self::assertStringContainsString('$extension));', $resourceConfig);
        self::assertStringNotContainsString('$this->_deniedExtensions[] = strtolower(trim((string)$e));', $resourceConfig);
    }

    public function testCKFinderDownloadUsesSafeContentDisposition(): void
    {
        $download = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DownloadFile.php');

        self::assertStringContainsString("filename*=UTF-8''", $download);
        self::assertStringContainsString('X-Content-Type-Options: nosniff', $download);
        self::assertStringNotContainsString('HTTP_USER_AGENT', $download);
        self::assertStringNotContainsString('Content-type: application/octet-stream; name=', $download);
    }

    public function testCKFinderConfigurationDoesNotExposeErrorsOrGrantWorldWrite(): void
    {
        $config = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/config.php');

        self::assertStringContainsString("ini_set('display_errors', '0')", $config);
        self::assertStringContainsString(<<<'PHP'
$config['ChmodFiles'] = 0640
PHP, $config);
        self::assertStringContainsString(<<<'PHP'
$config['ChmodFolders'] = 0750
PHP, $config);
        self::assertStringNotContainsString("ini_set('display_errors', 1)", $config);
        self::assertStringNotContainsString(<<<'PHP'
$config['ChmodFiles'] = 0775
PHP, $config);
        self::assertStringNotContainsString(<<<'PHP'
$config['ChmodFolders'] = 0775
PHP, $config);
    }

    public function testCKFinderAccessControlBindsFullAccessToAuthenticatedAdminRole(): void
    {
        $config = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/config.php');

        self::assertStringContainsString("\$_SESSION['CKFinder_UserRole'] = 'admin'", $config);
        self::assertStringContainsString("'role' => 'admin'", $config);
        self::assertStringNotContainsString("'role' => '*',\n\t\t'resourceType' => '*',\n\t\t'folder' => '/'", $config);
    }

    public function testCKFinderUploadEnforcesSizeBeforeScalingAndDetectsHtmlForAllExtensions(): void
    {
        $upload = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php');

        self::assertStringContainsString('if ($maxSize && $uploadedFile[\'size\']>$maxSize)', $upload);
        self::assertStringContainsString(<<<'PHP'
if (($detectHtml = CKFinder_Connector_Utils_FileSystem::detectHtml($uploadedFile['tmp_name'])) === true )
PHP, $upload);
        self::assertStringNotContainsString('!$_config->checkSizeAfterScaling() && $maxSize', $upload);
        self::assertStringNotContainsString('inArrayCaseInsensitive($sExtension, $htmlExtensions)', $upload);
    }

    public function testCKFinderImageValidationNormalizesUppercaseExtensions(): void
    {
        $upload = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php');

        self::assertSame(2, substr_count($upload, 'strtolower(CKFinder_Connector_Utils_FileSystem::getExtension($sFileNameOrginal))'));
    }

    public function testCKFinderMutableHandlersUseCanonicalPathContainment(): void
    {
        $fileSystem = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php');
        self::assertStringContainsString('public static function isPathInside($basePath, $candidatePath)', $fileSystem);
        self::assertStringContainsString('$base = realpath($basePath);', $fileSystem);
        self::assertStringContainsString('$candidate = realpath($candidatePath);', $fileSystem);
        self::assertStringContainsString('self::combinePaths($parent, basename($candidatePath))', $fileSystem);

        foreach (array('CopyFiles', 'MoveFiles', 'RenameFile', 'RenameFolder', 'DeleteFile', 'DeleteFolder', 'CreateFolder') as $handler) {
            $source = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php5/CommandHandler/' . $handler . '.php');
            self::assertStringContainsString('isPathInside(', $source, $handler . ' must validate canonical paths');
        }
    }

    public function testCKFinderDownloadAndCallbacksRejectHeaderAndScriptInjection(): void
    {
        $download = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php5/CommandHandler/DownloadFile.php');
        self::assertStringContainsString('isPathInside($_resourceTypeInfo->getDirectory(), $filePath)', $download);
        self::assertStringContainsString('strpbrk($fileName, "\\r\\n\\0")', $download);
        self::assertStringContainsString('X-Content-Type-Options: nosniff', $download);

        foreach (array('FileUpload', 'QuickUpload') as $handler) {
            $source = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php5/ErrorHandler/' . $handler . '.php');
            self::assertStringContainsString('json_encode(', $source, $handler . ' must encode callback values as JSON');
            self::assertStringContainsString('JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP', $source);
            self::assertStringNotContainsString("str_replace(\"'\", \"\\\\'\"", $source);
        }
    }

    public function testCKFinderUploadValidatesTheCompleteHttpUploadContract(): void
    {
        $upload = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php');

        foreach (array('name', 'type', 'tmp_name', 'error', 'size') as $field) {
            self::assertStringContainsString("'" . $field . "'", $upload);
        }
        self::assertStringContainsString('is_uploaded_file($uploadedFile[\'tmp_name\'])', $upload);
        self::assertStringContainsString('$uploadedFile[\'size\'] < 0', $upload);
        self::assertStringContainsString('default:', $upload);
        self::assertStringNotContainsString('switch ($uploadedFile[\'error\'])', substr($upload, strpos($upload, '$sServerDir')));
    }

    public function testCKFinderUploadAndCallbacksRejectAmbiguousRequestShapes(): void
    {
        $upload = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php');
        $fileUploadError = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php5/ErrorHandler/FileUpload.php');
        $quickUploadError = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php5/ErrorHandler/QuickUpload.php');

        self::assertStringContainsString('count($_FILES) !== 1', $upload);
        self::assertStringContainsString("is_scalar(\$_GET['CKFinderFuncNum'])", $fileUploadError);
        self::assertStringContainsString("is_scalar(\$_GET['CKEditorFuncNum'])", $quickUploadError);
        foreach (array($fileUploadError, $quickUploadError) as $handler) {
            self::assertStringContainsString('X-Content-Type-Options: nosniff', $handler);
            self::assertStringContainsString('Cache-Control: no-store, no-cache, must-revalidate', $handler);
        }
        self::assertStringNotContainsString('preg_replace("/[^0-9]/", "", $_GET[', $fileUploadError);
        self::assertStringNotContainsString('preg_replace("/[^0-9]/", "", $_GET[', $quickUploadError);
    }

    public function testCKFinderUploadReservesDestinationWithoutCheckThenMoveRace(): void
    {
        $upload = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php5/CommandHandler/FileUpload.php');

        self::assertStringContainsString("fopen(\$sFilePath, 'x')", $upload);
        self::assertStringContainsString('fclose($destinationHandle)', $upload);
        self::assertStringContainsString('@unlink($sFilePath)', $upload);
        self::assertStringNotContainsString('if (file_exists($sFilePath))', $upload);
    }

    public function testCKFinderMutableCopiesPublishAtomically(): void
    {
        $fileSystem = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php5/Utils/FileSystem.php');
        $copy = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php5/CommandHandler/CopyFiles.php');
        $move = (string) file_get_contents(__DIR__ . '/../pages/_js/ckfinder/core/connector/php/php5/CommandHandler/MoveFiles.php');

        self::assertStringContainsString('public static function copyFileAtomic($sourcePath, $destinationPath)', $fileSystem);
        self::assertStringContainsString('tempnam(dirname($destinationPath), \'.ckfinder-\')', $fileSystem);
        self::assertStringContainsString('copyFileAtomic($sourceFilePath, $destinationFilePath)', $copy);
        self::assertStringNotContainsString('@unlink($destinationFilePath)', $move);
        self::assertStringContainsString('@rename($sourceFilePath, $destinationFilePath)', $move);
    }
}
