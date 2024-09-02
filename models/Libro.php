<?php
namespace app\models;

use yii\mongodb\ActiveRecord;

/**
 * Class Libro
 * 
 * Este modelo representa el libro en la colección de MongoDB.
 * 
 * @package app\models
 * @property \MongoDB\BSON\ObjectId|string $_id El ID del documento del libro.
 * @property string $titulo El título del libro.
 * @property array $autores La lista de autores del libro.
 * @property int $anio_publicacion El año de publicación del libro.
 * @property string $descripcion Una descripción del libro.
 */
class Libro extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function collectionName()
    {
        return ['biblioteca', 'libros'];
    }

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        return [
            '_id',             // El ID único del documento del libro.
            'titulo',          // El título del libro.
            'autores',         // La lista de autores del libro.
            'anio_publicacion',// El año en el que se publicó el libro.
            'descripcion',     // Una descripción del libro.
        ];
    }
}
