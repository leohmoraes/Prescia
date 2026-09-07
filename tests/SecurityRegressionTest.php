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
}
