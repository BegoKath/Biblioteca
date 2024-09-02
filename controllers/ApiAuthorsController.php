<?php
namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\BadRequestHttpException;
use MongoDB\Client as MongoClient;
use MongoDB\BSON\ObjectId;
use app\models\Token;
use yii\filters\auth\HttpBearerAuth;

/**
 * ApiAuthorsController maneja las acciones CRUD para la colección de autores en MongoDB.
 * 
 * @package app\controllers
 */
class ApiAuthorsController extends Controller
{
    /**
     * @var \MongoDB\Collection $collection La colección de autores en MongoDB.
     */
    private $collection;

    /**
     * Inicializa el controlador y establece la conexión con la colección de MongoDB.
     * 
     * @throws \yii\web\ServerErrorHttpException Si ocurre un error al conectar con MongoDB.
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
            $this->collection = $client->selectDatabase('biblioteca')->selectCollection('authors');
        } catch (\Exception $e) {
            // Lanzar una excepción si ocurre un error al conectar con MongoDB
            throw new \yii\web\ServerErrorHttpException('Error al conectar con MongoDB: ' . $e->getMessage());
        }
    }

    /**
     * Configura los comportamientos del controlador.
     * 
     * @return array Configuración de comportamientos del controlador.
     */
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
     * Devuelve una lista de todos los autores en la colección.
     * 
     * @return array La lista de autores en formato JSON.
     */
    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        // Retorna todos los documentos de la colección de autores
        return $this->collection->find()->toArray();
    }

    /**
     * Muestra un autor específico basado en su ID.
     * 
     * @param string $id El ID del autor en formato de cadena.
     * @return array|object El documento del autor en formato JSON.
     * @throws \yii\web\NotFoundHttpException Si el autor no se encuentra.
     * @throws \yii\web\BadRequestHttpException Si el ID es inválido.
     */
    public function actionView($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        // Validar el ID proporcionado
        $this->validateId($id);
        
        // Buscar el autor por su ID
        $author = $this->collection->findOne(['_id' => new ObjectId($id)]);
        if ($author === null) {
            throw new NotFoundHttpException('Autor no encontrado.');
        }
        return $author;
    }

    /**
     * Crea un nuevo autor en la colección.
     * 
     * @return array El ID del autor creado.
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
        $this->validateAuthorData($data);
        
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
     * Actualiza un autor existente en la colección.
     * 
     * @param string $id El ID del autor a actualizar.
     * @return array El estado de la actualización.
     * @throws \yii\web\NotFoundHttpException Si el autor no se encuentra.
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
        $this->validateAuthorData($data, false);
        
        // Actualizar el documento en la colección
        $result = $this->collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => $data]
        );
        
        // Verificar si se encontró y actualizó el autor
        if ($result->getMatchedCount() === 0) {
            throw new NotFoundHttpException('Autor no encontrado.');
        }
        return ['status' => 'success'];
    }

    /**
     * Elimina un autor de la colección.
     * 
     * @param string $id El ID del autor a eliminar.
     * @return array El estado de la eliminación.
     * @throws \yii\web\NotFoundHttpException Si el autor no se encuentra.
     * @throws \yii\web\BadRequestHttpException Si el ID es inválido.
     */
    public function actionDelete($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        
        // Validar el ID proporcionado
        $this->validateId($id);
        
        // Eliminar el documento de la colección
        $result = $this->collection->deleteOne(['_id' => new ObjectId($id)]);
        
        // Verificar si se encontró y eliminó el autor
        if ($result->getDeletedCount() === 0) {
            throw new NotFoundHttpException('Autor no encontrado.');
        }
        return ['status' => 'success'];
    }

    /**
     * Valida los datos del autor proporcionados.
     * 
     * @param array $data Los datos del autor a validar.
     * @param bool $isNew Indica si el autor es nuevo (true) o se está actualizando (false).
     * @throws \yii\web\BadRequestHttpException Si los datos son inválidos.
     */
    private function validateAuthorData($data, $isNew = true)
    {
        // Lista de campos obligatorios
        $requiredFields = ['name', 'birth_date', 'books_written'];
        
        // Verificar que todos los campos obligatorios estén presentes
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new BadRequestHttpException("Falta el campo obligatorio: $field.");
            }
        }
        
        // Validar que el nombre no se repita
        $existingAuthor = $this->collection->findOne(['name' => $data['name']]);
        if ($existingAuthor !== null && ($isNew || $existingAuthor['_id'] != $data['_id'])) {
            throw new BadRequestHttpException("El autor con el nombre '{$data['name']}' ya existe.");
        }
        
        // Validar la estructura de 'books_written' (debe ser un array)
        if (isset($data['books_written']) && !is_array($data['books_written'])) {
            throw new BadRequestHttpException("El campo 'books_written' debe ser un array.");
        }
    
        // Aquí puedes agregar más validaciones según sea necesario.
    }

    /**
     * Valida el formato del ID proporcionado.
     * 
     * @param string $id El ID del autor a validar.
     * @throws \yii\web\BadRequestHttpException Si el ID es inválido.
     */
    private function validateId($id)
    {
        // Validar que el ID sea un ObjectId válido de MongoDB
        if (!preg_match('/^[0-9a-fA-F]{24}$/', $id)) {
            throw new BadRequestHttpException('ID de autor inválido.');
        }
    }
}
