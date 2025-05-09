<?php

declare(strict_types=1);

namespace User\Controller;

use User\Form\RegisterForm;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Doctrine\ORM\EntityManager;
use User\Entity\User;
use Laminas\Http\Request;
use Laminas\Crypt\Password\Bcrypt;
use Laminas\Http\Headers;
use Application\Service\FirestoreService;
use Laminas\View\Helper\Placeholder\Container;


use PDO;

class IndexController extends AbstractActionController
{
    private $entityManager;
    protected $firestoreService;
    private $usersManager;

    // protected $pdo;

    public function __construct(EntityManager $entityManager, $usersManager,)
    {
        $this->entityManager = $entityManager;
        $this->usersManager = $usersManager;
       
    }

    public function indexAction()
    {
        return new JsonModel();
    }


  
    // public function loginAction()
    // {
    //     if ($this->authService->hasIdentity()) {
    //         return $this->redirect()->toRoute('home');
    //     }

    //     $form = new LoginForm();
    //     $request = $this->getRequest();

    //     if ($request->isPost()) {
    //         $form->setData($request->getPost());

    //         if ($form->isValid()) {
    //             $data = $form->getData();

    //             // Authentification
    //             $adapter = $this->authService->getAdapter();
    //             $adapter->setIdentity($data['email']);
    //             $adapter->setCredential($data['password']);

    //             $result = $this->authService->authenticate();

    //             if ($result->isValid()) {
    //                 $this->flashMessenger()->addSuccessMessage('Connexion réussie !');
    //                 return $this->redirect()->toRoute('home');
    //             } else {
    //                 $this->flashMessenger()->addErrorMessage('Identifiants incorrects.');
    //             }
    //         }
    //     }

    //     return new ViewModel(['form' => $form]);
    // }




    public function getAllUsersAction()
    {   
        //Check Clé API
        $validationResult = $this->validateApiKey($this);
        if ($validationResult) {
            return $validationResult;
        }

        $orderBy = $this->params()->fromQuery('order','firstname');
        $direction = $this->params()->fromQuery('direction','asc');

        // $users = $this->entityManager->getRepository(User::class)->findAll();
       
        $users = $this->entityManager->getRepository(User::class)->findAllOrdered($orderBy, $direction);
        $userArray = array_map(function($user) {
            return [
                'id' => $user->getId(),
                    'firstname' => $user->getFirstname(),
                    'lastname' => $user->getLastname(),
                    'birthday' => $user->getBirthday(),
                    'email' => $user->getEmail(),
                    'password' => $user->getPassword(),
                    'role' => json_decode($user->getRole(), true),
                    'blacklist' => $user->isBlacklist(),
                    'avatar' => $user->getAvatar(),
                    'gotKey' => $user->getGotKey(),
                    'gotRemote' => $user->getGotRemote(),
            ];
        }, $users);
    
        return new JsonModel([
            'data' => $userArray,
            'order'=>$orderBy,
            'direction'=>$direction,
        ]);
    }

    public function getUserAction()
    {
        //Check Clé API
        $validationResult = $this->validateApiKey($this);
        if ($validationResult) {
            return $validationResult;
        }
        $id = (int) $this->params()->fromRoute('id', 0);
    
        if ($id === 0) {
            return new JsonModel([
                'success' => false,
                'message' => 'ID utilisateur non spécifié ou invalide',
            ]);
        }
    
        $user = $this->entityManager->getRepository(User::class)->find($id);
    
        if (!$user) {
            return new JsonModel([
                'success' => false,
                'message' => 'Utilisateur non trouvé',
            ]);
        }
    
        $userArray = [
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'birthday' => $user->getBirthday(),
            'email' => $user->getEmail(),
            'password' => $user->getPassword(),
            'role' => json_decode($user->getRole(), true),
            'blacklist' => $user->isBlacklist(),
            'avatar' => $user->getAvatar(),
            'gotKey' => $user->getGotKey(),
            'gotRemote' => $user->getGotRemote(),
        ];
    
        return new JsonModel([
            'success' => true,
            'data' => $userArray,
        ]);
    }
    
    public function addUserAction()
    {
        //Check Clé API
        $validationResult = $this->validateApiKey($this);
        if ($validationResult) {
            return $validationResult;
        }

        $request = $this->getRequest();
        /** @var \Laminas\Http\Request $request */
        if ($request->isPost()) {
            $data = json_decode($request->getContent(), true);
            
            $requiredFields = ['firstname', 'lastname', 'email','birthday','password']; 
            $missingFields = array_filter($requiredFields, function ($field) use ($data) {
                return !isset($data[$field]) || empty($data[$field]); 
            });
            
            if(isset($data['email'])){

                $users= $this->entityManager->getRepository(User::class)->findBy(['email'=>$data['email']]);
                if(count($users)>0){
                    return new JsonModel([
                        'success' => false,
                        'message' => "L'adresse email existe déjà",
                    ]);
                }else{
                    if (!empty($missingFields)) {
                        return new JsonModel([
                            'success' => false,
                            'message' => 'Les champs obligatoires suivants sont manquants : ' . implode(', ', $missingFields),
                        ]);
                    }
                }
            }
            $this->usersManager->addUser($data);
            return new JsonModel([
                'success' => true,
                'message' => 'Utilisateur ajouté avec succès!',
            ]);
        }
    
        return new JsonModel([
            'success' => false,
            'message' => 'Méthode de requête non supportée.',
        ]);
    }

    public function updateUserAction()
    {
        //Check Clé API
        $validationResult = $this->validateApiKey($this);
        if ($validationResult) {
            return $validationResult;
        }

        $request = $this->getRequest();
        /** @var \Laminas\Http\Request $request */
        if ($request->isPost()) {
            $data = json_decode($request->getContent(), true);
            
            $requiredFields = ['firstname', 'lastname', 'email','birthday']; 
            $missingFields = array_filter($requiredFields, function ($field) use ($data) {
                return !isset($data[$field]) || empty($data[$field]); 
            });
            
            if(isset($data['email'])){

                $user= $this->entityManager->getRepository(User::class)->findOneBy(['email'=>$data['email']]);
                if($user){
                    $this->usersManager->updateUser($user,$data);
                    return new JsonModel([
                        'success' => true,
                        'message' => 'Utilisateur modifié avec succès!',
                    ]);
                }else{
                    if (!empty($missingFields)) {
                        return new JsonModel([
                            'success' => false,
                            'message' => 'Les champs obligatoires suivants sont manquants : ' . implode(', ', $missingFields),
                        ]);
                    }
                    return new JsonModel([
                        'success' => false,
                        'message' => 'Utilisateur inconnu.',
                    ]);
                }
            }
        }
    
        return new JsonModel([
            'success' => false,
            'message' => 'Méthode de requête non supportée.',
        ]);
    }






    public function migrateUsersAction()
    {
        // Récupérer les utilisateurs depuis Firestore
        $users = $this->firestoreService->getUsers();
     
        // Insérer les utilisateurs dans la base SQL
        // $stmt = $this->pdo->prepare('INSERT INTO users (id, firstname, lastname, email) VALUES (:id, :firstname, :lastname, :email)');

        // foreach ($users as $user) {
        //     $stmt->execute([
        //         ':id' => $user['id'],
        //         ':firstname' => $user['firstname'],
        //         ':lastname' => $user['lastname'],
        //         ':email' => $user['email'],
        //     ]);
        //     echo "Utilisateur " . $user['id'] . " migré avec succès.\n";
        // }

        return new ViewModel([
            'message' => 'Migration terminée.',
        ]);
    }



    // public function loginAction()
    // {
    //     $request = $this->getRequest();

    //     header('Access-Control-Allow-Origin: http://localhost:3000'); // Remplacez par votre URL front-end
    //         header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    //         header('Access-Control-Allow-Headers: Content-Type, Authorization');
    //         header('Access-Control-Allow-Credentials: true');
    
    //     if ($request instanceof Request && $request->isPost()) {
    //         $data = json_decode($request->getContent(), true);
    
    //         $email = $data['email'] ?? '';
    //         $password = $data['password'] ?? '';
    
    //         // Récupérer l'utilisateur par email
    //         $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
    
    //         if (!$user) {
    //             return new JsonModel([
    //                 'success' => false,
    //                 'message' => 'Email non trouvé.'
    //             ]);
    //         }
    
    //         // Récupérer le mot de passe stocké en base de données
    //         $storedPassword = $user->getPassword();
    
    //         // Vérifier si le mot de passe est déjà haché avec bcrypt
    //         $bcrypt = new Bcrypt();
    
    //         if ($bcrypt->verify($password, $storedPassword)) {
    //             // Le mot de passe est déjà en bcrypt
    //             session_regenerate_id(true);

    //             $this->storeUserSession($user); 
    
    //             return new JsonModel([
    //                 'success' => true,
    //                 'message' => 'Connexion réussie avec le nouveau format de mot de passe (bcrypt).',
    //                 'user_id' => $user->getId(),
    //             ]);
    //         }
    
    //         // Si le mot de passe n'est pas en bcrypt, vérifier avec SHA-256
    //         if (hash('sha256', $password) === $storedPassword) {
    //             // Mot de passe correct avec l'ancien hachage SHA-256, migrer vers bcrypt
    //             $newHashedPassword = $bcrypt->create($password);
    
    //             // Mettre à jour le mot de passe dans la base de données
    //             $user->setPassword($newHashedPassword);
    //             $this->entityManager->flush();
             
    //             $this->storeUserSession($user);
    //             return new JsonModel([
    //                 'success' => true,
    //                 'message' => 'Connexion réussie et mot de passe migré vers bcrypt!',
    //                 'user_id' => $user->getId(),
    //             ]);
    //         }
    
    //         // Mot de passe incorrect
    //         return new JsonModel([
    //             'success' => false,
    //             'message' => 'Mot de passe incorrect.',
    //         ]);
    //     }
    
    //     return new JsonModel([
    //         'success' => false,
    //         'message' => 'Requête non valide.',
    //     ]);
    // }

    private function storeUserSession(User $user): void
    {
        // Démarrer la session si ce n'est pas déjà fait
        // if (session_status() === PHP_SESSION_NONE) {
        //     session_start();
        // }
    
        // Utiliser $_SESSION pour stocker les données de l'utilisateur
        $_SESSION['userId'] = $user->getId();
        $_SESSION['userEmail'] = $user->getEmail();
        $_SESSION['userRole'] = $user->getRole();
    }

    public function getUserInfoAction()
    {
        // Démarrer la session si ce n'est pas déjà fait
        // if (session_status()=== PHP_SESSION_NONE) {
        //     session_start();
        // }
    
        if (isset($_SESSION['userId'])) {
            return new \Laminas\View\Model\JsonModel([
                'success' => true,
                'userId' => $_SESSION['userId'],
                'userEmail' => $_SESSION['userEmail'],
                'userRole' => $_SESSION['userRole'],
            ]);
        } else {
            return new \Laminas\View\Model\JsonModel([
                'success' => false,
                'message' => 'Utilisateur non connecté.',
            ]);
        }
    }
    
}
