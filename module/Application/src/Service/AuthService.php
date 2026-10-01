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
    // Duree de validite d'un lien de reinitialisation, en secondes
    public const RESET_TOKEN_TTL = 3600;
    public const PASSWORD_MIN_LENGTH = 8;

    private EntityManager $entityManager;
    private AuthenticationService $authenticationService;
    private array $mailSettings;

    public function __construct(EntityManager $entityManager, AuthenticationService $authenticationService, array $mailSettings = [])
    {
        $this->entityManager = $entityManager;
        $this->authenticationService = $authenticationService;
        $this->mailSettings = $mailSettings;
    }

    private function findUserByEmail(string $email): ?User
    {
        // Comparaison insensible à la casse et aux espaces (ex: majuscule auto
        // ajoutee par le clavier mobile sur le premier caractere du champ email)
        return $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->where('LOWER(u.email) = LOWER(:email)')
            ->setParameter('email', trim($email))
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function login(string $email, string $password): bool
    {
        // Cherche l'utilisateur par email
        $user = $this->findUserByEmail($email);
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

    public function sendPasswordResetLink($email): bool
    {
        // Cherche l'utilisateur par email
        $user = $this->findUserByEmail($email);
        if (!$user) {
            return false;
        }

        // Le token embarque sa date d'expiration ("<timestamp>.<aleatoire>") :
        // pas besoin de colonne dediee en base
        $token = (time() + self::RESET_TOKEN_TTL) . '.' . bin2hex(random_bytes(16));
        $user->setResetToken($token);
        $this->entityManager->flush();

        $resetLink = 'https://www.wolfsofteure.fr/reset-password?token=' . urlencode($token);

        return $this->sendMail(
            $user->getEmail(),
            'Réinitialisation de mot de passe',
            "Bonjour,\n\nCliquez sur ce lien pour réinitialiser votre mot de passe : " . $resetLink . "\n\nSi vous n'êtes pas à l'origine de cette demande, ignorez ce message."
        );
    }

    public function sendAdminResetNotification(string $adminEmail, User $targetUser): bool
    {
        return $this->sendMail(
            $adminEmail,
            'Lien de réinitialisation envoyé',
            "Bonjour,\n\nUn lien de réinitialisation de mot de passe a été envoyé à "
                . $targetUser->getFirstname() . ' ' . $targetUser->getLastname()
                . " (" . $targetUser->getEmail() . ")."
        );
    }

    private function sendMail(string $toEmail, string $subject, string $body): bool
    {
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

            $mail->setFrom($this->mailSettings['username'], $this->mailSettings['from_name']);
            $mail->addAddress($toEmail);

            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erreur envoi email a {$toEmail}: {$mail->ErrorInfo}");
            return false;
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

    /**
     * Retourne l'utilisateur associe a un token de reinitialisation encore valide.
     * Les tokens sans date d'expiration (ancien format) sont refuses.
     */
    public function findUserByResetToken(string $token): ?User
    {
        if (!preg_match('/\A(\d+)\.[0-9a-f]{32}\z/', $token, $matches)) {
            return null;
        }
        if ((int) $matches[1] < time()) {
            return null;
        }

        return $this->entityManager->getRepository(User::class)->findOneBy(['resetToken' => $token]);
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        // L'utilisateur est retrouve par le token recu par mail, jamais par
        // un email envoye dans le formulaire
        $user = $this->findUserByResetToken($token);
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
        // Tout passe si GOD
        if ($user->isGod()) {
            return null;
        }
        foreach ($rolesAutorises as $role) {
            // "admin" suit la meme regle que le menu d'administration
            if (strtolower($role) === 'admin' ? $user->hasAdminAccess() : $user->isInRoles($role)) {
                return null;
            }
        }
        return $redirectPlugin->toRoute('home');
    }


}
