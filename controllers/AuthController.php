<?php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use app\models\User;
use app\models\Token;

/**
 * Class AuthController
 * 
 * Este controlador maneja la autenticación de usuarios.
 * Incluye acciones para el inicio de sesión y generación de tokens.
 * 
 * @package app\controllers
 */
class AuthController extends Controller
{
    /**
     * Acción para iniciar sesión y obtener un token de autenticación.
     * 
     * @return array El token de autenticación en formato JSON.
     * @throws \yii\web\BadRequestHttpException Si las credenciales proporcionadas son inválidas.
     */
    public function actionLogin()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        // Obtener el contenido crudo del cuerpo de la solicitud
        $rawData = Yii::$app->request->getRawBody();
        // Decodificar JSON manualmente
        $data = json_decode($rawData, true);

        // Validar las credenciales del usuario
        $userId = $this->validateUserCredentials($data['username'], $data['password']);

        if ($userId) {
            // Generar un nuevo token para el usuario
            $token = $this->generateToken($userId);
            return ['token' => $token];
        }

        // Lanzar una excepción si las credenciales son inválidas
        throw new BadRequestHttpException('Credenciales inválidas.');
    }

    /**
     * Genera un token de autenticación para un usuario.
     * 
     * @param string $userId El ID del usuario para el cual se genera el token.
     * @return string El token generado.
     */
    private function generateToken($userId)
    {
        // Generar un token aleatorio de 32 caracteres en hexadecimal
        $token = bin2hex(random_bytes(16));
        // Establecer la expiración del token a 30 minutos a partir de ahora
        $expiry = new \DateTime('+30 minutes');

        // Crear un nuevo modelo de Token y guardar los datos
        $tokenModel = new Token();
        $tokenModel->token = $token;
        $tokenModel->user_id = $userId;
        $tokenModel->expiry = $expiry->format(DATE_ISO8601);
        $tokenModel->save();

        return $token;
    }

    /**
     * Valida las credenciales del usuario (nombre de usuario y contraseña).
     * 
     * @param string $username El nombre de usuario.
     * @param string $password La contraseña proporcionada.
     * @return string|false El ID del usuario si las credenciales son válidas, o false si no lo son.
     */
    private function validateUserCredentials($username, $password)
    {
        // Buscar el modelo de usuario por nombre de usuario
        $userModel = User::findOne(['username' => $username]);

        // Verificar la contraseña utilizando el hash almacenado
        if ($userModel && password_verify($password, $userModel->password)) {
            return (string)$userModel->_id;
        }

        return false;
    }
}
