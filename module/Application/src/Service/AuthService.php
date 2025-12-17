<?php
namespace Application\Service;

use Doctrine\ORM\EntityManager;
use Laminas\Authentication\AuthenticationService;
use User\Entity\User;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthService
{
    private EntityManager $entityManager;
    private AuthenticationService $authenticationService;

    public function __construct(EntityManager $entityManager, AuthenticationService $authenticationService)
    {
        $this->entityManager = $entityManager;
        $this->authenticationService = $authenticationService;
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

        $resetLink = 'http://wolfsofteure:8080/reset-password?token=' . $token;

        // Envoi avec PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Paramètres SMTP o2switch
            $mail->isSMTP();
            $mail->Host = 'mail.wolf-soft-eure.fr';
            $mail->SMTPAuth = true;
            $mail->Username = 'contact@wolf-soft-eure.fr'; // ton adresse email
            $mail->Password = 'c,@DTo%*zGSj';         // ton mot de passe email
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('contact@wolf-soft-eure.fr', 'Wolf Soft Eure');
            $mail->addAddress($user->getEmail());

            $mail->Subject = 'Réinitialisation de mot de passe';
            $mail->Body = "Cliquez sur ce lien pour réinitialiser votre mot de passe : " . $resetLink;

            $mail->send();
        } catch (Exception $e) {
            // Pour le debug en local
            echo "Erreur d'envoi : {$mail->ErrorInfo}";
        }
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

}
