<?php
namespace app\commands;

use yii\console\Controller;
use MongoDB\Client;

class MongoController extends Controller
{
    //Verificación de la conexion con Mongodb y la base de datos con el sig comando
    //php yii mongo/check-connection 
    public function actionCheckConnection()
    {
        // Obtener la configuración de MongoDB desde el componente
        $mongodbConfig = \Yii::$app->mongodb;
        $dsn = $mongodbConfig->dsn;
        
        try {
            // Crear el cliente MongoDB usando la configuración
            $client = new Client($dsn);
            
            // Seleccionar la base de datos 'biblioteca'
            $database = $client->selectDatabase('biblioteca');
            
            // Verificar la conexión con un comando ping
            $ping = $database->command(['ping' => 1]);
            
            echo "Conexión a MongoDB exitosa con la base de datos 'biblioteca'.\n";
        } catch (\Exception $e) {
            echo "Error de conexión: " . $e->getMessage() . "\n";
        }
    }
    //Crear las coleciones para autores y libros con el sig comando
    // php yii mongo/create-collections
    public function actionCreateCollections()
    {
        $mongodbConfig = \Yii::$app->mongodb;
        $dsn = $mongodbConfig->dsn;
        $client = new Client($dsn);
        $database = $client->selectDatabase('biblioteca');

         // Crear colección de libros
         $booksCollection = $database->selectCollection('books');
         if ($booksCollection->countDocuments() == 0) {
             $database->createCollection('books');
             echo "Colección 'books' creada.\n";
         }
 
         // Crear colección de autores
         $authorsCollection = $database->selectCollection('authors');
         if ($authorsCollection->countDocuments() == 0) {
             $database->createCollection('authors');
             echo "Colección 'authors' creada.\n";
         }
 
         // Crear colección de usuarios
         $usersCollection = $database->selectCollection('users');
         if ($usersCollection->countDocuments() == 0) {
             $database->createCollection('users');
             echo "Colección 'users' creada.\n";
         }
 
         // Crear colección de tokens
         $tokensCollection = $database->selectCollection('tokens');
         if ($tokensCollection->countDocuments() == 0) {
             $database->createCollection('tokens');
             echo "Colección 'tokens' creada.\n";
         }
 
         // Insertar un usuario con contraseña hasheada
         $passwordHash = password_hash('testpassword', PASSWORD_BCRYPT);
 
         $user = [
             'username' => 'testuser',
             'password' => $passwordHash
         ];
 
         // Insertar el documento en la colección de usuarios
         $result = $usersCollection->insertOne($user);
         if ($result->getInsertedCount() > 0) {
             echo "Usuario 'testuser' creado con contraseña hasheada.\n";
         } else {
             echo "Error al crear el usuario.\n";
         }
    }
}