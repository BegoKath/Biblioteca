<?php

namespace app\models;

use yii\mongodb\ActiveRecord;
use Yii;
use MongoDB\BSON\ObjectId;
use DateTime;
use DateTimeZone;

/**
 * Class User
 * 
 * Este modelo representa a un usuario en la colección de MongoDB.
 * Implementa la interfaz `yii\web\IdentityInterface` para la autenticación.
 * 
 * @package app\models
 * @property \MongoDB\BSON\ObjectId|string $_id El ID del usuario.
 * @property string $username El nombre de usuario.
 * @property string $password La contraseña del usuario (almacenada como hash).
 * @property string $authKey La clave de autenticación.
 * @property string $accessToken El token de acceso.
 */
class User extends ActiveRecord implements \yii\web\IdentityInterface
{
    /**
     * @inheritdoc
     */
    public static function collectionName()
    {
        return ['biblioteca', 'users'];
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            '_id',         // El ID único del usuario.
            'username',    // El nombre de usuario.
            'password',    // La contraseña del usuario (almacenada como hash).
            'authKey',     // La clave de autenticación.
            'accessToken', // El token de acceso.
        ];
    }

    /**
     * {@inheritdoc}
     * 
     * Encuentra una identidad de usuario basado en el ID.
     * 
     * @param string $id El ID del usuario.
     * @return static|null El modelo de usuario encontrado, o null si no se encuentra.
     */
    public static function findIdentity($id)
    {
        return static::findOne(['_id' => new ObjectId($id)]);
    }

    /**
     * {@inheritdoc}
     * 
     * Encuentra una identidad de usuario basado en el token de acceso.
     * 
     * @param string $token El token de acceso.
     * @param mixed $type Tipo de token (opcional).
     * @return static|null El modelo de usuario encontrado, o null si no se encuentra o el token ha expirado.
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $tokenCollection = Yii::$app->mongodb->getCollection('tokens');
        $tokenData = $tokenCollection->findOne(['token' => $token]);

        if ($tokenData) {
            $expiryDate = new DateTime($tokenData['expiry']);
            $now = new DateTime('now', new DateTimeZone('UTC'));

            // Verifica si el token ha expirado
            if ($now < $expiryDate) {
                $user = static::findOne(['_id' => new ObjectId($tokenData['user_id'])]);
                return $user;
            } else {
                Yii::warning("Token ha expirado: {$token}", __METHOD__);
            }
        } else {
            Yii::warning("Token no encontrado: {$token}", __METHOD__);
        }

        return null;
    }

    /**
     * Encuentra un usuario por nombre de usuario.
     * 
     * @param string $username El nombre de usuario.
     * @return static|null El modelo de usuario encontrado, o null si no se encuentra.
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username]);
    }

    /**
     * {@inheritdoc}
     * 
     * Obtiene el ID del usuario.
     * 
     * @return string El ID del usuario.
     */
    public function getId()
    {
        return (string) $this->_id;
    }

    /**
     * {@inheritdoc}
     * 
     * Obtiene la clave de autenticación del usuario.
     * 
     * @return string La clave de autenticación.
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * {@inheritdoc}
     * 
     * Valida la clave de autenticación del usuario.
     * 
     * @param string $authKey La clave de autenticación para validar.
     * @return bool True si la clave de autenticación es válida, false de lo contrario.
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /**
     * Valida la contraseña del usuario.
     * 
     * @param string $password La contraseña a validar.
     * @return bool True si la contraseña es válida para el usuario actual, false de lo contrario.
     */
    public function validatePassword($password)
    {
        return password_verify($password, $this->password);
    }
}
