<?php
/**
 * Validador de Input - Seguridad
 * Funciones helpers para validar y sanitizar datos
 */

class InputValidator {
    
    /**
     * Validar email
     */
    public static function validateEmail($email): ?string {
        $email = trim($email ?? '');
        if (empty($email)) {
            return 'El correo es requerido';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'Formato de correo inválido';
        }
        if (strlen($email) > 255) {
            return 'El correo es demasiado largo';
        }
        return null; // Sin error
    }
    
    /**
     * Validar password
     */
    public static function validatePassword($password): ?string {
        if (empty($password)) {
            return 'La contraseña es requerida';
        }
        if (strlen($password) < 6) {
            return 'La contraseña debe tener al menos 6 caracteres';
        }
        return null;
    }
    
    /**
     * Validar ID (formato alphanumeric con guiones)
     */
    public static function validateId($id): ?string {
        if (empty($id)) {
            return 'El ID es requerido';
        }
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $id)) {
            return 'Formato de ID inválido';
        }
        if (strlen($id) > 50) {
            return 'El ID es demasiado largo';
        }
        return null;
    }
    
    /**
     * Validar nombre
     */
    public static function validateName($name, $fieldName = 'Nombre'): ?string {
        $name = trim($name ?? '');
        if (empty($name)) {
            return "$fieldName es requerido";
        }
        if (strlen($name) > 100) {
            return "$fieldName es demasiado largo";
        }
        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $name)) {
            return "$fieldName contiene caracteres inválidos";
        }
        return null;
    }
    
    /**
     * Validar teléfono
     */
    public static function validatePhone($phone): ?string {
        $phone = trim($phone ?? '');
        if (empty($phone)) {
            return null; // Teléfono opcional
        }
        // Solo dígitos, guiones, paréntesis y espacios
        $clean = preg_replace('/[\s\-\(\)]/', '', $phone);
        if (!preg_match('/^[0-9]+$/', $clean)) {
            return 'Formato de teléfono inválido';
        }
        if (strlen($clean) < 10 || strlen($clean) > 15) {
            return 'El teléfono debe tener entre 10 y 15 dígitos';
        }
        return null;
    }
    
    /**
     * Sanitizar string para HTML (prevenir XSS)
     */
    public static function sanitizeHtml($input): string {
        return htmlspecialchars(trim($input ?? ''), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitizar para SQL (usar siempre prepared statements!)
     */
    public static function sanitizeSql($input): string {
        return trim($input ?? '');
    }
    
    /**
     * Validar role
     */
    public static function validateRole($role): ?string {
        $validRoles = ['admin', 'supervisor', 'usuario'];
        if (!in_array($role, $validRoles)) {
            return 'Rol inválido';
        }
        return null;
    }
    
    /**
     * Validar rating (1-5)
     */
    public static function validateRating($rating, $fieldName = 'Rating'): ?string {
        if (!is_numeric($rating)) {
            return "$fieldName debe ser un número";
        }
        $rating = intval($rating);
        if ($rating < 1 || $rating > 5) {
            return "$fieldName debe estar entre 1 y 5";
        }
        return null;
    }
    
    /**
     * Validar sexo
     */
    public static function validateSexo($sexo): ?string {
        $validSexos = ['M', 'F', 'Otro', 'Prefiero no decir'];
        if (!in_array($sexo, $validSexos)) {
            return 'Sexo inválido';
        }
        return null;
    }
    
    /**
     * Validar booleano
     */
    public static function validateBoolean($value): ?bool {
        if ($value === true || $value === 'true' || $value === 1 || $value === '1') {
            return true;
        }
        if ($value === false || $value === 'false' || $value === 0 || $value === '0') {
            return false;
        }
        return null;
    }
    
    /**
     * Validar y devolver respuesta JSON con error
     */
    public static function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode(['error' => $message, 'code' => $code]);
        exit;
    }
    
    /**
     * Validar y devolver respuesta JSON exitosa
     */
    public static function sendSuccess($data, $message = 'OK') {
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}
