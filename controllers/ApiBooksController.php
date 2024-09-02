<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use app\models\Book;
use app\models\Token;
use yii\filters\auth\HttpBearerAuth;
use MongoDB\Client as MongoClient;
use MongoDB\BSON\ObjectId;

/**
 * ApiBooksController maneja las acciones CRUD para la colección de libros en MongoDB.
 */
class ApiBooksController extends Controller
{
    /**
     * @var \MongoDB\Collection $collection La colección de libros en MongoDB.
     */
    private $collection;
    
    /**
     * Inicializa el controlador y establece la conexión con la colección de MongoDB.
     * 
     * @throws \yii\web\ServerErrorHttpException Si no se puede conectar a MongoDB.
     */
    public function init()
    {
        parent::init();
        
        // Obtener la configuración de MongoDB desde el componente de Yii
        $mongodbConfig = Yii::$app->mongodb;
        $dsn = $mongodbConfig->dsn;

        try {
            // Crear el cliente MongoDB usando la configuración proporcionada
            $client = new MongoClient($dsn);
            $this->collection = $client->selectDatabase('biblioteca')->selectCollection('books');
        } catch (\Exception $e) {
            // Lanzar una excepción si ocurre un error al conectar con MongoDB
            throw new \yii\web\ServerErrorHttpException('Error al conectar con MongoDB: ' . $e->getMessage());
        }
    }

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::className(),
            'optional' => ['login'], // Si tienes una acción de login o pública, agrégala aquí
        ];
        return $behaviors;
    }

    /**
     * Devuelve una lista de todos los libros en la colección.
     * 
     * @return array La lista de libros en formato JSON.
     */
    public function actionIndex()
    {
        try {
            Yii::$app->response->format = Response::FORMAT_JSON;
            return $this->collection->find()->toArray();
        } catch (\Exception $e) {
            Yii::$app->response->statusCode = 500; // Código de estado HTTP para errores internos del servidor
            return [
                'error' => 'Ocurrió un error interno del servidor.',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Muestra un libro específico basado en su ID.
     * 
     * @param string $id El ID del libro en formato de cadena.
     * @return array|object El documento del libro en formato JSON.
     * @throws \yii\web\NotFoundHttpException Si el libro no se encuentra.
     * @throws \yii\web\BadRequestHttpException Si el ID es inválido.
     */
    public function actionView($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        // Validar el ID proporcionado
        $this->validateId($id);
        
        // Buscar el libro por su ID
        $book = $this->collection->findOne(['_id' => new ObjectId($id)]);
        if ($book === null) {
            throw new NotFoundHttpException('Libro no encontrado.');
        }
        return $book;
    }

    /**
     * Crea un nuevo libro en la colección.
     * 
     * @return array Los datos del libro creado y su ID.
     * @throws \yii\web\BadRequestHttpException Si los datos proporcionados son inválidos.
     */
    public function actionCreate()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
    
        // Obtener el contenido crudo del cuerpo de la solicitud
        $rawData = Yii::$app->request->getRawBody();
        
        // Decodificar JSON manualmente
        $data = json_decode($rawData, true);
    
        // Validar los datos recibidos
        $this->validateBookData($data);
    
        // Insertar el documento en la colección
        $result = $this->collection->insertOne($data);
    
        // Retornar el ID del documento insertado
        return [
            'status' => 'success',
            'data' => $data,
            '_id' => (string) $result->getInsertedId(),
        ];
    }

    /**
     * Actualiza un libro existente en la colección.
     * 
     * @param string $id El ID del libro a actualizar.
     * @return array El estado de la actualización.
     * @throws \yii\web\NotFoundHttpException Si el libro no se encuentra.
     * @throws \yii\web\BadRequestHttpException Si los datos proporcionados son inválidos o si el ID es inválido.
     */
    public function actionUpdate($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        // Validar el ID proporcionado
        $this->validateId($id);
        
        // Obtener el contenido crudo del cuerpo de la solicitud
        $rawData = Yii::$app->request->getRawBody();
    
        // Decodificar JSON manualmente
        $data = json_decode($rawData, true);
    
        // Validar los datos para la actualización
        $this->validateBookData($data, false);
        
        // Actualizar el documento en la colección
        $result = $this->collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => $data]
        );
        
        // Verificar si se encontró y actualizó el libro
        if ($result->getMatchedCount() === 0) {
            throw new NotFoundHttpException('Libro no encontrado.');
        }
        return ['status' => 'success'];
    }

    /**
     * Elimina un libro de la colección.
     * 
     * @param string $id El ID del libro a eliminar.
     * @return array El estado de la eliminación.
     * @throws \yii\web\NotFoundHttpException Si el libro no se encuentra.
     * @throws \yii\web\BadRequestHttpException Si el ID es inválido.
     */
    public function actionDelete($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        // Validar el ID proporcionado
        $this->validateId($id);
        
        // Eliminar el documento de la colección
        $result = $this->collection->deleteOne(['_id' => new ObjectId($id)]);
        
        // Verificar si se encontró y eliminó el libro
        if ($result->getDeletedCount() === 0) {
            throw new NotFoundHttpException('Libro no encontrado.');
        }
        return ['status' => 'success'];
    }

    /**
     * Valida los datos del libro proporcionados.
     * 
     * @param array $data Los datos del libro a validar.
     * @param bool $isNew Indica si el libro es nuevo (true) o se está actualizando (false).
     * @throws \yii\web\BadRequestHttpException Si los datos son inválidos.
     */
    private function validateBookData($data, $isNew = true)
    {
        // Obtener la colección de autores
        $authorsCollection = Yii::$app->mongodb->getCollection('authors');

        // Campos obligatorios al crear un nuevo libro
        if ($isNew) {
            $requiredFields = ['title', 'authors', 'publication_year', 'description'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new BadRequestHttpException("Falta el campo obligatorio: $field.");
                }
            }
        }

        // Validar que el año de publicación sea un número entero
        if (isset($data['publication_year']) && !is_int($data['publication_year'])) {
            throw new BadRequestHttpException('El campo publication_year debe ser un número entero.');
        }

        // Verificar que todos los autores existen en la colección de autores
        foreach ($data['authors'] as $authorName) {
            $authorExists = $authorsCollection->findOne(['name' => $authorName]);
            if (!$authorExists) {
                throw new BadRequestHttpException("El autor $authorName no existe en la colección de autores.");
            }
        }

        // Verificar que el título del libro no exista ya en la colección de libros (solo para nuevos libros)
        if ($isNew) {
            $bookExists = $this->collection->findOne(['title' => $data['title']]);
            if ($bookExists) {
                throw new BadRequestHttpException('El título del libro ya existe en la colección.');
            }
        }

        // Agrega más validaciones según tus necesidades
    }

    /**
     * Valida el formato del ID proporcionado.
     * 
     * @param string $id El ID del libro a validar.
     * @throws \yii\web\BadRequestHttpException Si el ID es inválido.
     */
    private function validateId($id)
    {
        if (!preg_match('/^[0-9a-fA-F]{24}$/', $id)) {
            throw new BadRequestHttpException('ID de libro inválido.');
        }
    }
}
