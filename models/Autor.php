<?php
namespace app\models;

use yii\mongodb\ActiveRecord;

/**
 * Class Autor
 * 
 * Este modelo representa a un autor en la colección de MongoDB.
 * 
 * @package app\models
 * @property \MongoDB\BSON\ObjectId|string $_id El ID del documento del autor.
 * @property string $nombre_completo El nombre completo del autor.
 * @property string $fecha_nacimiento La fecha de nacimiento del autor en formato ISO 8601.
 */
class Autor extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function collectionName()
    {
        return ['biblioteca', 'autores'];
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            '_id',              // El ID único del documento del autor.
            'nombre_completo',  // El nombre completo del autor.
            'fecha_nacimiento', // La fecha de nacimiento del autor en formato ISO 8601.
        ];
    }
}
