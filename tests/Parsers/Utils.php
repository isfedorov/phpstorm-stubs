<?php
declare(strict_types=1);

namespace StubTests\Parsers;

use LogicException;
use phpDocumentor\Reflection\DocBlock\Tags\Deprecated;
use phpDocumentor\Reflection\DocBlock\Tags\Since;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use StubTests\Model\BasePHPElement;
use StubTests\Model\PHPConst;
use StubTests\Model\PHPMethod;
use StubTests\Model\PhpVersions;
use StubTests\Model\Tags\RemovedTag;
use StubTests\TestData\Providers\PhpStormStubsSingleton;

class Utils
{
    public static function flattenArray(array $array, bool $group): array
    {
        return iterator_to_array(new RecursiveIteratorIterator(new RecursiveArrayIterator($array)), $group);
    }

    public static function tagDoesNotHaveZeroPatchVersion(Since|RemovedTag|Deprecated $tag): bool
    {
        return (bool)preg_match('/^[1-9]+\.\d+(\.[1-9]+\d*)*$/', $tag->getVersion()); //find version like any but 7.4.0
    }

    /**
     * @throws RuntimeException
     */
    public static function getDeclaredSinceVersion(BasePHPElement $element): ?float
    {
        $allSinceVersions = self::getSinceVersionsFromPhpDoc($element);
        $allSinceVersions[] = self::getSinceVersionsFromAttribute($element);
        if ($element instanceof PHPMethod) {
            if ($element->hasInheritDocTag) {
                return null;
            }
            $allSinceVersions[] = self::getSinceVersionsFromParentClass($element);
        } elseif ($element instanceof PHPConst) {
            $allSinceVersions[] = self::getSinceVersionsFromParentClass($element);
        }
        $flattenedArray = Utils::flattenArray($allSinceVersions, false);
        sort($flattenedArray, SORT_DESC);
        return array_pop($flattenedArray);
    }

    /**
     * @throws RuntimeException
     */
    public static function getLatestAvailableVersion(BasePHPElement $element): ?float
    {
        $latestVersionsFromPhpDoc = self::getLatestAvailableVersionFromPhpDoc($element);
        $latestVersionsFromAttribute = self::getLatestAvailableVersionsFromAttribute($element);
        if ($element instanceof PHPMethod) {
            if ($element->hasInheritDocTag) {
                return null;
            }
            $latestVersionsFromPhpDoc[] = self::getLatestAvailableVersionsFromParentClass($element);
        } elseif ($element instanceof PHPConst) {
            $latestVersionsFromPhpDoc[] = self::getLatestAvailableVersionsFromParentClass($element);
        }
        if (empty($latestVersionsFromAttribute)) {
            $flattenedArray = Utils::flattenArray($latestVersionsFromPhpDoc, false);
        } else {
            $flattenedArray = Utils::flattenArray($latestVersionsFromAttribute, false);
        }
        sort($flattenedArray, SORT_DESC);
        return array_pop($flattenedArray);
    }

    /**
     * @return float[]
     * @throws RuntimeException
     */
    public static function getAvailableInVersions(BasePHPElement $element): array
    {
        $firstSinceVersion = self::getDeclaredSinceVersion($element);
        if ($firstSinceVersion === null) {
            $firstSinceVersion = PhpVersions::getFirst();
        }
        $lastAvailableVersion = self::getLatestAvailableVersion($element);
        if ($lastAvailableVersion === null) {
            $lastAvailableVersion = PhpVersions::getLatest();
        }
        return array_filter(iterator_to_array(new PhpVersions()),
            fn ($version) => $version >= $firstSinceVersion && $version <= $lastAvailableVersion);
    }

    /**
     * @return float[]
     */
    private static function getSinceVersionsFromPhpDoc(BasePHPElement $element): array
    {
        $allSinceVersions = [];
        if (!empty($element->sinceTags) && $element->stubBelongsToCore) {
            $allSinceVersions[] = array_map(fn (Since $tag) => (float)$tag->getVersion(), $element->sinceTags);
        }
        return $allSinceVersions;
    }

    /**
     * @return float[]
     */
    private static function getLatestAvailableVersionFromPhpDoc(BasePHPElement $element): array
    {
        $latestAvailableVersion = [PhpVersions::getLatest()];
        if (!empty($element->removedTags) && $element->stubBelongsToCore) {
            $allRemovedVersions = array_map(fn (RemovedTag $tag) => (float)$tag->getVersion(), $element->removedTags);
            sort($allRemovedVersions, SORT_DESC);
            $removedVersion = array_pop($allRemovedVersions);
            $allVersions = new PhpVersions();
            $indexOfRemovedVersion = array_search($removedVersion, iterator_to_array($allVersions));
            $latestAvailableVersion = [$allVersions[$indexOfRemovedVersion - 1]];
        }
        return $latestAvailableVersion;
    }

    /**
     * @return float[]
     * @throws RuntimeException|LogicException
     */
    private static function getSinceVersionsFromParentClass(PHPMethod|PHPConst $element): array
    {
        $parentClass = PhpStormStubsSingleton::getPhpStormStubs()->getClass($element->parentName);
        if ($parentClass === null) {
            $parentClass = PhpStormStubsSingleton::getPhpStormStubs()->getInterface($element->parentName);
        }
        $allSinceVersions[] = self::getSinceVersionsFromPhpDoc($parentClass);
        $allSinceVersions[] = self::getSinceVersionsFromAttribute($parentClass);
        return $allSinceVersions;
    }

    /**
     * @return float[]
     * @throws RuntimeException|LogicException
     */
    public static function getLatestAvailableVersionsFromParentClass(PHPMethod|PHPConst $element): array
    {
        $parentClass = PhpStormStubsSingleton::getPhpStormStubs()->getClass($element->parentName);
        if ($parentClass === null) {
            $parentClass = PhpStormStubsSingleton::getPhpStormStubs()->getInterface($element->parentName);
        }
        $latestAvailableVersionFromPhpDoc = self::getLatestAvailableVersionFromPhpDoc($parentClass);
        $latestAvailableVersionFromAttribute = self::getLatestAvailableVersionsFromAttribute($parentClass);
        return empty($latestAvailableVersionFromAttribute) ? $latestAvailableVersionFromPhpDoc : $latestAvailableVersionFromAttribute;
    }

    /**
     * @return float[]
     */
    private static function getSinceVersionsFromAttribute(BasePHPElement $element): array
    {
        $allSinceVersions = [];
        if (!empty($element->availableVersionsRangeFromAttribute)) {
            $allSinceVersions[] = $element->availableVersionsRangeFromAttribute['from'];
        }
        return $allSinceVersions;
    }

    /**
     * @return float[]
     */
    private static function getLatestAvailableVersionsFromAttribute(BasePHPElement $element): array
    {
        $latestAvailableVersions = [];
        if (!empty($element->availableVersionsRangeFromAttribute)) {
            $latestAvailableVersions[] = $element->availableVersionsRangeFromAttribute['to'];
        }
        return $latestAvailableVersions;
    }
}
