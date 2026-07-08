<?php
/**
 * Minimal rule-based validator.
 *
 * Usage:
 *   $errors = validate($_POST, [
 *       'email' => 'required|email',
 *       'name'  => 'required|min:3|max:100',
 *   ]);
 *   if (!empty($errors)) { ... }
 */
function validate(array $data, array $rules): array
{
    $errors = [];

    foreach ($rules as $field => $ruleString) {
        $value = $data[$field] ?? '';

        foreach (explode('|', $ruleString) as $rule) {
            [$ruleName, $ruleValue] = array_pad(explode(':', $rule, 2), 2, null);

            switch ($ruleName) {
                case 'required':
                    if ($value === '' || $value === null) {
                        $errors[$field][] = ucfirst($field) . ' is required.';
                    }
                    break;

                case 'email':
                    if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = 'Enter a valid email address.';
                    }
                    break;

                case 'min':
                    if ($value !== '' && strlen((string) $value) < (int) $ruleValue) {
                        $errors[$field][] = ucfirst($field) . " must be at least {$ruleValue} characters.";
                    }
                    break;

                case 'max':
                    if (strlen((string) $value) > (int) $ruleValue) {
                        $errors[$field][] = ucfirst($field) . " must not exceed {$ruleValue} characters.";
                    }
                    break;

                case 'numeric':
                    if ($value !== '' && !is_numeric($value)) {
                        $errors[$field][] = ucfirst($field) . ' must be numeric.';
                    }
                    break;

                case 'date':
                    if ($value !== '' && strtotime((string) $value) === false) {
                        $errors[$field][] = ucfirst($field) . ' must be a valid date.';
                    }
                    break;

                case 'not_future':
                    if ($value !== '' && strtotime((string) $value) !== false && strtotime((string) $value) > time()) {
                        $errors[$field][] = ucfirst($field) . ' cannot be in the future.';
                    }
                    break;

                case 'age_max':
                    if ($value !== '' && strtotime((string) $value) !== false) {
                        $age = (int) floor((time() - strtotime((string) $value)) / 31557600);
                        if ($age > (int) $ruleValue) {
                            $errors[$field][] = ucfirst($field) . " indicates an age over {$ruleValue} years — please verify.";
                        }
                    }
                    break;

                case 'alpha_spaces':
                    if ($value !== '' && !preg_match("/^[\p{L}\s'-]+$/u", (string) $value)) {
                        $errors[$field][] = ucfirst($field) . ' may only contain letters, spaces, hyphens, and apostrophes.';
                    }
                    break;

                case 'alpha_num_dash':
                    if ($value !== '' && !preg_match('/^[A-Za-z0-9-]+$/', (string) $value)) {
                        $errors[$field][] = ucfirst($field) . ' may only contain letters, numbers, and hyphens.';
                    }
                    break;

                case 'phone':
                    if ($value !== '' && !preg_match('/^\+?[0-9\s-]{7,20}$/', (string) $value)) {
                        $errors[$field][] = 'Enter a valid phone number.';
                    }
                    break;

                case 'in':
                    $allowed = explode(',', (string) $ruleValue);
                    if ($value !== '' && !in_array((string) $value, $allowed, true)) {
                        $errors[$field][] = 'Invalid value for ' . str_replace('_', ' ', $field) . '.';
                    }
                    break;
            }
        }
    }

    return $errors;
}
