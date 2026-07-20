<?php
namespace Application\Service;

use Doctrine\ORM\EntityManager;
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\Controller\Plugin\Redirect;
use User\Entity\User;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthService
{
    private EntityManager $entityManager;
    private AuthenticationService $authenticationService;
    private array $mailSettings;

    public function __construct(EntityManager $entityManager, AuthenticationService $authenticationService, array $mailSettings = [])
    {
        $this->entityManager = $entityManager;
        $this->authenticationService = $authenticationService;
        $this->mailSettings = $mailSettings;
    }

    public function login(string $email, string $password): bool
    {
        // Cherche l'utilisateur par email
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return false;
        }   

        if (password_verify($password, $user->getPassword())) {
            // store only the user id in session so we can re-hydrate on each request
            $this->authenticationService->getStorage()->write($user->getIdUser());
            return true;
        }

        return false;
    }

    public function logout(): void
    {
        $this->authenticationService->clearIdentity();
    }

    public function getIdentity()
    {
        $id = $this->authenticationService->getIdentity();
        if (!$id) return null;
        return $this->entityManager->getRepository(User::class)->find($id);
    }
    public function getStorage()
    {
        return $this->authenticationService->getStorage();
    }

    public function sendPasswordResetLink($email)
    {
        // Cherche l'utilisateur par email
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return;
        }

        // Génère un token de réinitialisation
        $token = bin2hex(random_bytes(16));
        $user->setResetToken($token);
        // Si tu as aussi un champ pour la date d'expiration, ajoute-le ici
        // $user->setPasswordResetTokenExpiry((new \DateTime())->modify('+1 hour'));
        $this->entityManager->flush();

        // Prépare le lien de réinitialisation
        // $resetLink = 'https://yourdomain.com/reset-password?token=' . $token;

        $resetLink = 'http://www.wolfsofteure.fr/reset-password?token=' . $token;

        // Envoi avec PHPMailer
        $mail = new PHPMailer(true);
        try {
        // --- Paramètres Serveur SMTP (config/autoload/local.php: mail_settings) ---
        $mail->isSMTP();
        $mail->Host       = $this->mailSettings['host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->mailSettings['username'];
        $mail->Password   = $this->mailSettings['password'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = $this->mailSettings['port'];
        $mail->CharSet    = 'UTF-8';

        // --- Destinataires ---
        $mail->setFrom($this->mailSettings['username'], $this->mailSettings['from_name']);
        $mail->addAddress($user->getEmail());

        // --- Contenu du mail ---
        $mail->isHTML(false); // On reste en texte brut pour ce lien
        $mail->Subject = 'Réinitialisation de mot de passe';
        $mail->Body    = "Bonjour,\n\nCliquez sur ce lien pour réinitialiser votre mot de passe : " . $resetLink . "\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez ce message.";

        $mail->send();
        
    } catch (Exception $e) {
        // En cas d'erreur, on peut loguer le message de PHPMailer
        // error_log("Erreur PHPMailer : " . $mail->ErrorInfo);
        echo "Erreur d'envoi : {$mail->ErrorInfo}";
    }
    }

    public function generateNewPassword(User $user): string
    {
        $plainPassword = $this->generateRandomPassword();
        $user->setPassword(password_hash($plainPassword, PASSWORD_BCRYPT));
        $this->entityManager->flush();

        return $plainPassword;
    }

    private function generateRandomPassword(int $length = 12): string
    {
        // Sans caractères ambigus (I, O, l, o, 0, 1)
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $max = strlen($chars) - 1;
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, $max)];
        }
        return $password;
    }

    public function resetPassword($email, $newPassword)
    {
        // Cherche l'utilisateur par email
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            return false;
        }
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        $user->setPassword($hashedPassword);
        $user->setResetToken(null);
    
        $this->entityManager->flush();

        return true;
    }




    public function requireRoles(array $rolesAutorises, Redirect $redirectPlugin)
    {
        $user = $this->getIdentity();
        if (!$user) {
            return $redirectPlugin->toRoute('login');
        }
        $roles = $user->getRoles();
        if (is_string($roles)) {
            $roles = json_decode($roles, true);
        }
        if (!is_array($roles)) {
            $roles = [];
        }
        // Tout passe si GOD
        $rolesLower = array_map('strtolower', $roles);
        if (in_array('god', $rolesLower, true)) {
            return null;
        }
        $rolesAutorisesLower = array_map('strtolower', $rolesAutorises);
        if (!array_intersect($rolesLower, $rolesAutorisesLower)) {
            return $redirectPlugin->toRoute('home');
        }
        return null;
    }


}
