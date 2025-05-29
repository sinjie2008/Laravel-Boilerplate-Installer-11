<?php

namespace Installer\Validator;

class AdminValidator
{
    public function validate(array $data): array
    {
        if (empty($data['admin_name']) || empty($data['admin_email']) ||
            empty($data['admin_password']) || empty($data['admin_password_confirm'])) {
            return [
                'success' => false,
                'message' => 'Please fill in all required admin fields.'
            ];
        }

        if ($data['admin_password'] !== $data['admin_password_confirm']) {
            return [
                'success' => false,
                'message' => 'Passwords do not match.'
            ];
        }

        if (strlen($data['admin_password']) < 8) {
            return [
                'success' => false,
                'message' => 'Password must be at least 8 characters long.'
            ];
        }

        if (!filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'Please enter a valid email address.'
            ];
        }

        $_SESSION['admin'] = [
            'name' => $data['admin_name'],
            'email' => $data['admin_email'],
            'password' => $data['admin_password']
        ];

        return ['success' => true];
    }
}
