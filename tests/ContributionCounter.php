<?php

namespace StubTests;
use StubTests\TestData\Providers\Stubs\PhpCoreStubsProvider;
use StubTests\Tools\ModelAutoloader;
use function array_intersect;
use function explode;
use function in_array;
use function shell_exec;
use function trim;
use function var_dump;

require_once 'Tools/ModelAutoloader.php';
ModelAutoloader::register();
class ContributionCounter
{
    public static function countCommits()
    {
        $commitsInCoreAmount = 0;
        $commitsInNonCoreAmount = 0;
        $hashes = array();
        $gitLog = [];
        $hashes = explode("\n", shell_exec("git log -3000 --pretty='format:%H'"));
        foreach ($hashes as $item) {
            $shell_exec = shell_exec("git diff-tree --no-commit-id --name-only -r $item");
            if ($shell_exec !== null) {
                $string = trim($shell_exec);
                $gitLog["$item"] = explode("\n", $string);
            }
        }
        foreach ($gitLog as $key => $value) {
            $coreIncreased = false;
            $nonCoreIncreased = false;
            foreach ($value as $item) {
                $dir = explode("/", $item)[0];
                if (array_intersect([$dir], PhpCoreStubsProvider::getCoreStubsDirectories())) {
                    if (!$coreIncreased) {
                        $commitsInCoreAmount++;
                        $coreIncreased = true;
                    }
                }
                if (array_intersect([$dir], PhpCoreStubsProvider::getNonCoreStubsDirectories())){
                    if (!$nonCoreIncreased) {
                        $commitsInNonCoreAmount++;
                        $nonCoreIncreased = true;
                    }
                }
            }
        }
        echo "Commits in core $commitsInCoreAmount\n";
        echo "Commits in non core $commitsInNonCoreAmount\n";
    }
}
ContributionCounter::countCommits();