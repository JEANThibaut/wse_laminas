<?php
namespace Application\Service;

use Doctrine\ORM\EntityManager;
use Laminas\Authentication\AuthenticationService;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Mvc\Controller\Plugin\Redirect;
use Application\Util\ClientIp;
use User\Entity\LoginLog;
use User\Entity\User;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class AuthService
{
    // Duree de validite d'un lien de reinitialisation, en secondes
    public const RESET_TOKEN_TTL = 3600;
    public const PASSWORD_MIN_LENGTH = 8;
    // Duree de validite d'un lien de validation d'email, en secondes
    public const EMAIL_VALIDATION_TTL = 30 * 86400;

    private EntityManager $entityManager;
    private AuthenticationService $authenticationService;
    private array $mailSettings;
    // Proxies publics de l'hebergeur dont on croit X-Forwarded-For (cf. ClientIp)
    private array $trustedProxies;

    public function __construct(EntityManager $entityManager, AuthenticationService $authenticationService, array $mailSettings = [], array $trustedProxies = [])
    {
        $this->entityManager = $entityManager;
        $this->authenticationService = $authenticationService;
        $this->mailSettings = $mailSettings;
        $this->trustedProxies = $trustedProxies;
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

    /**
     * @param Request|null $request pour journaliser l'IP et le navigateur
     * @param bool $afterSignup connexion automatique juste apres l'inscription
     */
    public function login(string $email, string $password, ?Request $request = null, bool $afterSignup = false): bool
    {
        // Cherche l'utilisateur par email
        $user = $this->findUserByEmail($email);
        if (!$user) {
            $this->logLogin(LoginLog::STATE_UNKNOWN_EMAIL, $email, null, $request);
            return false;
        }

        if (password_verify($password, $user->getPassword())) {
            // store only the user id in session so we can re-hydrate on each request
            $this->authenticationService->getStorage()->write($user->getIdUser());
            $this->logLogin($afterSignup ? LoginLog::STATE_SIGNUP : LoginLog::STATE_SUCCESS, $email, $user, $request);
            return true;
        }

        $this->logLogin(LoginLog::STATE_WRONG_PASSWORD, $email, $user, $request);
        return false;
    }

    public function logout(?Request $request = null): void
    {
        $user = $this->getIdentity();
        if ($user) {
            $this->logLogin(LoginLog::STATE_LOGOUT, $user->getEmail(), $user, $request);
        }
        $this->authenticationService->clearIdentity();
    }

    /**
     * Journalise un evenement de connexion et purge les logs expires.
     * En SQL direct et sans jamais lever d'exception : un probleme de log ne
     * doit pas empecher de se connecter.
     */
    private function logLogin(string $state, string $email, ?User $user, ?Request $request): void
    {
        $server = $request ? $request->getServer()->toArray() : [];
        try {
            $connection = $this->entityManager->getConnection();
            $connection->insert('login_log', [
                'user_id' => $user ? $user->getIdUser() : null,
                'email' => mb_substr(trim($email), 0, 180),
                'state' => $state,
                'ip' => mb_substr(ClientIp::resolve($server, $this->trustedProxies), 0, 45),
                'user_agent' => $this->truncateOrNull($server['HTTP_USER_AGENT'] ?? null),
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
            $this->entityManager->getRepository(LoginLog::class)->purgeExpired();
        } catch (\Throwable $e) {
            error_log('login_log : ' . $e->getMessage());
        }
    }

    private function truncateOrNull($value): ?string
    {
        return ($value === null || $value === '') ? null : mb_substr((string) $value, 0, 255);
    }

    /**
     * Date de derniere visite du compte connecte, ecrite au plus une fois par
     * heure. Ne leve jamais d'exception.
     */
    public function touchLastSeen(): void
    {
        $id = $this->authenticationService->getIdentity();
        if (!$id) {
            return;
        }
        try {
            $now = new \DateTime();
            $this->entityManager->getConnection()->executeStatement(
                'UPDATE user SET last_seen_at = ? WHERE iduser = ? AND (last_seen_at IS NULL OR last_seen_at < ?)',
                [$now->format('Y-m-d H:i:s'), (int) $id, (clone $now)->modify('-1 hour')->format('Y-m-d H:i:s')]
            );
        } catch (\Throwable $e) {
            error_log('last_seen_at : ' . $e->getMessage());
        }
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

    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
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
        $mail->isHTML(false);

        return $mail;
    }

    private function sendMail(string $toEmail, string $subject, string $body): bool
    {
        try {
            $mail = $this->createMailer();
            $mail->addAddress($toEmail);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erreur envoi email a {$toEmail}: " . (isset($mail) ? $mail->ErrorInfo : $e->getMessage()));
            return false;
        }
    }

    // ----------------------------------------------------------------------
    // Validation de l'adresse email
    // ----------------------------------------------------------------------

    /**
     * Parametres du lien de validation : id du compte, expiration et signature.
     * La signature couvre l'email : changer d'adresse invalide les anciens liens.
     */
    public function createEmailValidationParams(User $user): array
    {
        $expires = time() + self::EMAIL_VALIDATION_TTL;
        return [
            'u' => $user->getIdUser(),
            'e' => $expires,
            's' => $this->signEmailValidation($user, $expires),
        ];
    }

    /**
     * Compte vise par un lien de validation encore valide, sinon null.
     */
    public function findUserByEmailValidation(int $userId, int $expires, string $signature): ?User
    {
        if ($expires < time() || $signature === '') {
            return null;
        }
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user || !hash_equals($this->signEmailValidation($user, $expires), $signature)) {
            return null;
        }
        return $user;
    }

    /**
     * L'adresse est-elle deja utilisee par un autre compte (casse ignoree) ?
     */
    public function isEmailTakenByAnother(string $email, User $user): bool
    {
        return (int) $this->entityManager->createQueryBuilder()
            ->select('COUNT(u.iduser)')
            ->from(User::class, 'u')
            ->where('LOWER(u.email) = LOWER(:email)')
            ->andWhere('u.iduser <> :id')
            ->setParameter('email', trim($email))
            ->setParameter('id', $user->getIdUser())
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function markEmailValidated(User $user): void
    {
        if (!$user->isMailValidated()) {
            $user->setMailValidation(true);
            $user->setDateValidation(new \DateTime());
            $this->entityManager->flush();
        }
    }

    private function signEmailValidation(User $user, int $expires): string
    {
        // Cle derivee d'un secret deja present dans la config (mot de passe SMTP)
        $secret = (string) ($this->mailSettings['password'] ?? '');
        if ($secret === '') {
            throw new \RuntimeException('mail_settings.password manquant : impossible de signer les liens de validation.');
        }
        $key = hash('sha256', 'email-validation|' . $secret, true);
        $payload = $user->getIdUser() . '|' . mb_strtolower(trim((string) $user->getEmail())) . '|' . $expires;

        return hash_hmac('sha256', $payload, $key);
    }

    /**
     * Envoie le lien de validation a chaque compte, sur une seule connexion SMTP.
     *
     * @param User[]   $users
     * @param callable $buildLink fn(array $params): string, URL absolue du lien
     * @return array{sent: int, failed: string[]}
     */
    public function sendEmailValidationLinks(array $users, callable $buildLink): array
    {
        $result = ['sent' => 0, 'failed' => []];
        if (!$users) {
            return $result;
        }
        @set_time_limit(0);

        try {
            $mail = $this->createMailer();
            $mail->SMTPKeepAlive = true;
        } catch (Exception $e) {
            error_log('Erreur configuration SMTP: ' . $e->getMessage());
            $result['failed'] = array_map(fn (User $user) => $user->getEmail(), $users);
            return $result;
        }

        foreach ($users as $user) {
            try {
                $mail->clearAddresses();
                $mail->addAddress($user->getEmail());
                $mail->Subject = 'Validez votre adresse email - Wolf Soft Eure';
                $mail->Body = "Bonjour " . $user->getFirstname() . ",\n\n"
                    . "Merci de confirmer votre adresse email en cliquant sur ce lien :\n"
                    . $buildLink($this->createEmailValidationParams($user)) . "\n\n"
                    . "La validation de votre adresse est obligatoire pour vous inscrire aux parties.\n\n"
                    . "Ce lien est valable " . intdiv(self::EMAIL_VALIDATION_TTL, 86400) . " jours.\n\n"
                    . "L'équipe Wolf Soft Eure";
                $mail->send();
                $result['sent']++;
            } catch (Exception $e) {
                error_log("Erreur envoi validation a {$user->getEmail()}: {$mail->ErrorInfo}");
                $result['failed'][] = $user->getEmail();
                // Une erreur peut laisser la connexion dans un etat incoherent
                $mail->smtpClose();
            }
        }
        $mail->smtpClose();

        return $result;
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
