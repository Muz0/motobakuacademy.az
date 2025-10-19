<?php

declare(strict_types=1);

namespace MotoBaku\Admin;

class Validation
{
    /**
     * @return array{data: array<string, mixed>, errors: array<string, array<int, string>>}
     */
    public static function make(array $input, array $rules): array
    {
        $errors = [];
        $data = [];

        foreach ($rules as $field => $ruleSet) {
            $rulesList = is_array($ruleSet) ? $ruleSet : explode('|', (string)$ruleSet);
            $value = $input[$field] ?? null;
            $originalValue = $value;
            $isNullable = in_array('nullable', $rulesList, true);

            if ($value === null || $value === '') {
                if ($isNullable) {
                    $data[$field] = null;
                    continue;
                }
            }

            foreach ($rulesList as $rule) {
                if ($rule === 'nullable') {
                    continue;
                }

                [$ruleName, $parameter] = self::parseRule($rule);

                switch ($ruleName) {
                    case 'required':
                        if ($value === null || (is_string($value) && trim($value) === '') || $value === []) {
                            $errors[$field][] = 'This field is required.';
                        }
                        break;
                    case 'string':
                        if (!is_string($value)) {
                            $errors[$field][] = 'Must be a string.';
                        } else {
                            $value = trim($value);
                        }
                        break;
                    case 'integer':
                        if (filter_var($value, FILTER_VALIDATE_INT) === false) {
                            $errors[$field][] = 'Must be an integer.';
                        } else {
                            $value = (int)$value;
                        }
                        break;
                    case 'array':
                        if (!is_array($value)) {
                            $errors[$field][] = 'Must be an array.';
                        }
                        break;
                    case 'min':
                        $min = (int)$parameter;
                        if (is_string($value) && mb_strlen($value) < $min) {
                            $errors[$field][] = "Must be at least {$min} characters.";
                        }
                        break;
                    case 'max':
                        $max = (int)$parameter;
                        if (is_string($value) && mb_strlen($value) > $max) {
                            $errors[$field][] = "Must be at most {$max} characters.";
                        }
                        break;
                    case 'slug':
                        if (!is_string($value) || !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
                            $errors[$field][] = 'Invalid slug format.';
                        }
                        break;
                    case 'in':
                        $options = explode(',', (string)$parameter);
                        if (!in_array((string)$value, $options, true)) {
                            $errors[$field][] = 'Invalid selection.';
                        }
                        break;
                }
            }

            if (!isset($errors[$field])) {
                $data[$field] = $value;
            } else {
                $data[$field] = $originalValue;
            }
        }

        return [
            'data' => $data,
            'errors' => $errors,
        ];
    }

    private static function parseRule(string $rule): array
    {
        if (str_contains($rule, ':')) {
            [$name, $param] = explode(':', $rule, 2);
            return [$name, $param];
        }

        return [$rule, null];
    }
}
