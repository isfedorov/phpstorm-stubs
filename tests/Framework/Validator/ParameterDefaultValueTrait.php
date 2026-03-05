<?php

namespace StubTests\Sources\Validator;

use StubTests\Sources\Parsers\Entities\Model\PHPParameter;

trait ParameterDefaultValueTrait
{
    /**
     * Compare default values for each reflection parameter against the stub param map.
     * Returns an array of human-readable mismatch descriptions.
     *
     * Skips parameters that:
     * - have no default in reflection
     * - are absent from the stub param map (ParametersCountCheck's responsibility)
     * - have no default in the stub (OptionalParametersCheck's responsibility)
     * - have null on either side (unevaluable constant expression or genuine null default)
     *
     * @param  iterable                     $reflParams       reflection parameters
     * @param  array<string, PHPParameter>  $stubParamsByName stub parameters indexed by name
     * @return string[]
     */
    protected function buildParamMismatches(iterable $reflParams, array $stubParamsByName): array
    {
        $mismatches = [];

        foreach ($reflParams as $reflParam) {
            $name = $reflParam->getName();

            if (!$reflParam->hasDefaultValue()) {
                continue;
            }

            if (!isset($stubParamsByName[$name])) {
                continue;
            }

            $stubParam = $stubParamsByName[$name];

            if (!$stubParam->hasDefaultValue()) {
                continue;
            }

            $reflValue = $reflParam->getDefaultValue();
            $stubValue = $stubParam->getDefaultValue();

            if ($reflValue === null || $stubValue === null) {
                continue;
            }

            if ($reflValue !== $stubValue) {
                $mismatches[] = sprintf(
                    '$%s: reflection \'%s\', stubs \'%s\'',
                    $name,
                    $this->formatValue($reflValue),
                    $this->formatValue($stubValue)
                );
            }
        }

        return $mismatches;
    }

    protected function formatValue(mixed $value): string
    {
        if ($value === true) {
            return 'true';
        }
        if ($value === false) {
            return 'false';
        }
        if (is_string($value)) {
            return "'{$value}'";
        }
        if (is_array($value)) {
            return '[]';
        }
        return (string) $value;
    }
}
