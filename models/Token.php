<?php
namespace app\models;

use Yii;
use yii\mongodb\ActiveRecord;

/**
 * Class Token
 * 
 * Esta clase representa el modelo de la colección de tokens en MongoDB.
 * 
 * @package app\models
 * @property \MongoDB\BSON\ObjectId|string $_id El ID del documento.
 * @property string $token El token de autenticación.
 * @property string $user_id El ID del usuario asociado con el token.
 * @property string $expiry La fecha de expiración del token.
 */
class Token extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function collectionName()
    {
        return ['biblioteca', 'tokens'];
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            '_id',      // El ID único del documento.
            'token',    // El token de autenticación generado.
            'user_id',  // El ID del usuario para quien se generó el token.
            'expiry',   // La fecha y hora en la que el token expira.
        ];
    }

    /**
     * Puedes agregar reglas de validación y otros métodos aquí si es necesario.
     * 
     * Por ejemplo, podrías añadir reglas para validar el formato del token,
     * o métodos para verificar si un token ha expirado.
     */
}
