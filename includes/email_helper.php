<?php
/**
 * Clase auxiliar para el manejo de emails
 * 
 * Esta clase contiene métodos para enviar diferentes tipos de correos electrónicos
 * del sistema SIAP, como recuperación de contraseña, notificaciones, etc.
 */
class EmailHelper {
    
    // Configuración del servidor de correo
    private $host = 'smtp.gmail.com';  // Reemplaza con tu servidor SMTP
    private $port = 587;               // Puerto SMTP (587 para TLS, 465 para SSL)
    private $username = 'tu_correo@gmail.com'; // Correo emisor (reemplazar)
    private $password = 'tu_password_app'; // Contraseña o clave de aplicación (reemplazar)
    private $from_email = 'no-reply@siap.com';
    private $from_name = 'SIAP - Sistema de Inventario de Asociación Pepsi';
    
    // Directorio base para logs
    private $log_dir;
    
    /**
     * Constructor de la clase
     */
    public function __construct() {
        // Determinar la ruta para los logs
        $this->log_dir = $this->getLogDirectory();
        
        // Crear directorio de logs si no existe
        if (!file_exists($this->log_dir)) {
            mkdir($this->log_dir, 0755, true);
        }
    }
    
    /**
     * Obtiene la ruta del directorio de logs
     * 
     * @return string Ruta del directorio de logs
     */
    private function getLogDirectory() {
        // Intenta varios métodos para localizar el directorio logs
        $base_dir = __DIR__;
        
        // Opción 1: ../logs (desde includes)
        $log_dir = dirname($base_dir) . "/logs";
        
        // Verificar y crear si es necesario
        if (!file_exists($log_dir) && is_writable(dirname($base_dir))) {
            mkdir($log_dir, 0755, true);
        }
        
        // Si no podemos escribir allí, intentar usar el directorio temporal del sistema
        if (!is_writable($log_dir)) {
            $log_dir = sys_get_temp_dir() . "/siap_logs";
            
            // Intentar crear en el directorio temporal
            if (!file_exists($log_dir)) {
                mkdir($log_dir, 0755, true);
            }
        }
        
        return $log_dir;
    }
    
    /**
     * Envía un código de verificación para la recuperación de contraseña
     * 
     * @param string $email Correo del destinatario
     * @param string $code Código de verificación
     * @return boolean Resultado del envío
     */
    public function sendVerificationCode($email, $code) {
        // Para desarrollo y pruebas, simular que el envío fue exitoso
        // Comentar esta línea en producción
        return $this->simulateEmailSending($email, $code);
        
        // Descomenta y completa el código a continuación para producción
        /*
        $subject = 'Código de verificación para recuperación de contraseña';
        
        // Cuerpo del correo en formato HTML
        $body = '
        <html>
        <head>
            <title>Recuperación de contraseña</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #0056b3; color: white; padding: 10px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .code { font-size: 24px; font-weight: bold; text-align: center; 
                        padding: 10px; margin: 20px 0; background: #e9ecef; }
                .footer { text-align: center; font-size: 12px; color: #6c757d; padding-top: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>SIAP - Recuperación de contraseña</h2>
                </div>
                <div class="content">
                    <p>Hola,</p>
                    <p>Has solicitado restablecer tu contraseña. Utiliza el siguiente código para completar el proceso:</p>
                    <div class="code">' . $code . '</div>
                    <p>Este código es válido por 1 hora. Si no solicitaste restablecer tu contraseña, puedes ignorar este correo.</p>
                    <p>Saludos,<br>El equipo de SIAP</p>
                </div>
                <div class="footer">
                    <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        return $this->sendEmail($email, $subject, $body);
        */
    }
    
    /**
     * Envía una notificación de que la contraseña fue cambiada
     * 
     * @param string $email Correo del destinatario
     * @return boolean Resultado del envío
     */
    public function sendPasswordChangedNotification($email) {
        // Para desarrollo y pruebas, simular que el envío fue exitoso
        return $this->simulateEmailSending($email, "password_changed");
        
        // Descomenta y completa el código a continuación para producción
        /*
        $subject = 'Tu contraseña ha sido actualizada';
        
        // Cuerpo del correo en formato HTML
        $body = '
        <html>
        <head>
            <title>Contraseña actualizada</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #28a745; color: white; padding: 10px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { text-align: center; font-size: 12px; color: #6c757d; padding-top: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>SIAP - Contraseña actualizada</h2>
                </div>
                <div class="content">
                    <p>Hola,</p>
                    <p>Te confirmamos que tu contraseña ha sido actualizada correctamente.</p>
                    <p>Si no realizaste este cambio, por favor contacta inmediatamente al administrador del sistema.</p>
                    <p>Saludos,<br>El equipo de SIAP</p>
                </div>
                <div class="footer">
                    <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        return $this->sendEmail($email, $subject, $body);
        */
    }
    
    /**
     * Función para simular el envío de correos durante el desarrollo
     * Esta función debe ser eliminada o deshabilitada en producción
     * 
     * @param string $email Correo del destinatario
     * @param string $code Código enviado
     * @return boolean Siempre retorna true
     */
    private function simulateEmailSending($email, $code) {
        // Registrar el intento de envío en un archivo de log
        $log_file = $this->log_dir . "/email_" . date("Y-m-d") . ".log";
        $timestamp = date("Y-m-d H:i:s");
        $log_message = "[$timestamp] Se simuló el envío de código de verificación '$code' a '$email'\n";
        
        // Escribir en el log
        file_put_contents($log_file, $log_message, FILE_APPEND);
        
        // En desarrollo, siempre retornar como exitoso
        return true;
    }
    
    /**
     * Envía un correo electrónico utilizando PHPMailer
     * 
     * @param string $to Correo del destinatario
     * @param string $subject Asunto del correo
     * @param string $body Cuerpo del correo (HTML)
     * @return boolean Resultado del envío
     */
    private function sendEmail($to, $subject, $body) {
        // Esta es solo una implementación de ejemplo usando mail() nativo de PHP
        // Puedes implementar PHPMailer o cualquier otro sistema de envío de correos
        
        // Cabeceras para el correo HTML
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: {$this->from_name} <{$this->from_email}>" . "\r\n";
        
        // Intentar enviar el correo
        $sent = mail($to, $subject, $body, $headers);
        
        // Registrar el resultado en el log
        $log_file = $this->log_dir . "/email_" . date("Y-m-d") . ".log";
        $timestamp = date("Y-m-d H:i:s");
        $log_message = "[$timestamp] Intento de envío a '$to' - Resultado: " . ($sent ? "Éxito" : "Fallido") . "\n";
        file_put_contents($log_file, $log_message, FILE_APPEND);
        
        return $sent;
    }
}
?>