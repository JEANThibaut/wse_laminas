# Deploiement production (OVH)

Le deploiement est pilote par le repo mais **jamais automatique** : il se declenche
uniquement a la demande, depuis `Actions > Deploiement production > Run workflow`.
Aucun push, pas meme sur `main`, ne met quoi que ce soit en ligne.

Le workflow `.github/workflows/deploy.yml` build puis synchronise en **SFTP** sur
l'hebergement OVH. Plus de FileZilla.

## Protocole

L'hebergement expose du **SFTP sur le port 22** (sous-systeme SSH), pas du FTPS.
Les deux n'ont rien en commun : tenter du FTPS donne `500 This security scheme is
not implemented` puis `wrong version number`. Le transfert utilise donc `lftp`,
qui parle SFTP et fait de la synchronisation incrementale.

Le compte SFTP est cloisonne : la connexion atterrit directement dans la racine du
projet. `FTP_SERVER_DIR` vaut `.`, pas un chemin absolu.

## Ce que fait le workflow

1. Checkout de la branche choisie au lancement (`main` par defaut), historique
   complet (necessaire a l'envoi partiel)
2. `composer install --no-dev --optimize-autoloader` (vendor/ est construit par la
   CI, il n'est pas dans le repo)
3. `php -l` sur `module/`, `config/` et `public/` : un fichier casse arrete tout
4. Generation de `config/autoload/push.global.php` depuis les secrets VAPID
5. Lecture de `.deployed-commit` sur le serveur : le commit deploye la derniere fois
6. `.github/scripts/deploy-plan.py` decide de l'envoi et l'affiche dans le journal :
   - **partiel** (cas normal) : seuls les fichiers ajoutes, modifies ou supprimes
     depuis ce commit (`git diff`), plus a chaque fois `vendor/autoload.php`,
     `vendor/composer/` (la table des classes inclut les modules) et
     `push.global.php`. Tout `vendor/` si `composer.json` ou `composer.lock` a
     change. Le cache de config Laminas (`data/cache/*.php`) est supprime
     explicitement, sinon une nouvelle route serait ignoree
   - **complet** (`lftp mirror --reverse --delete`, l'ancien fonctionnement) :
     case "Envoi complet" cochee, pas de `.deployed-commit`, commit inconnu, ou
     commit deploye plus recent que celui envoye (retour arriere)
7. `.deployed-commit` est ecrit **en dernier**, seulement si tout l'envoi a reussi :
   apres un echec, le deploiement suivant repart de l'ancien commit et renvoie tout
   ce qui manque

Les exclusions (fichiers jamais envoyes ni supprimes) sont dans
`.github/deploy-excludes.txt`, source unique des deux modes.

Un fichier modifie a la main sur le serveur n'est plus ecrase tant qu'il ne change
pas dans le repo : cocher "Envoi complet" pour tout resynchroniser.

## Configuration GitHub

`Settings > Secrets and variables > Actions`

Secrets : `FTP_SERVER` (hote SFTP OVH), `FTP_USERNAME`, `FTP_PASSWORD`.

Publication Facebook : `FACEBOOK_PAGE_ID` (secret ou variable) et `FACEBOOK_PAGE_TOKEN`
(**secret obligatoirement**), le jeton d'un utilisateur systeme du portefeuille business
de l'association (Business Suite > Parametres de l'entreprise > Utilisateurs systeme,
expiration "Jamais", permissions pages_manage_posts, pages_read_engagement,
pages_show_list). Le workflow en genere `config/autoload/facebook.global.php` (jamais
commite). Sans ces deux valeurs, la case "Publier sur Facebook" n'apparait pas.

Notifications : `VAPID_PUBLIC_KEY` (secret ou variable) et `VAPID_PRIVATE_KEY`
(**secret obligatoirement** : le depot est public, ses journaux aussi, et seuls les
secrets y sont masques ; une variable s'afficherait en clair). Le workflow en
genere `config/autoload/push.global.php` (jamais commite), charge par Laminas comme
tout `*.global.php`. Sans ces deux secrets, le fichier n'est pas cree et les
notifications restent desactivees. Ne jamais changer ces cles une fois en service :
tous les appareils abonnes devraient reactiver les notifications.

Variables : `FTP_SERVER_DIR` = `.`, `SFTP_PORT` = `22`, `PHP_VERSION` = `8.3`.

`PHP_VERSION` reste en 8.3 car `composer.json` n'autorise pas encore PHP 8.4
(`~8.1.0 || ~8.2.0 || ~8.3.0`). Ce n'est pas genant : `vendor/composer/platform_check.php`
ne verifie qu'un minimum (`>= 8.2.0`), donc un vendor construit en 8.3 tourne sur
un serveur en 8.4. Pour builder directement en 8.4, elargir la contrainte dans
`composer.json` puis regenerer `composer.lock`.

**Piege Windows** : renseigner ces variables depuis Git Bash corrompt les valeurs
qui ressemblent a un chemin Unix (`/www/` devient `C:/Program Files/Git/www/`).
Passer par l'interface web, ou prefixer la commande par `MSYS_NO_PATHCONV=1`.

## Mode simulation

`Actions > Deploiement production > Run workflow`, cocher **dry_run**. Le log liste
tout ce qui serait envoye et supprime, sans ecrire une ligne sur le serveur (etape
"Prepare l'envoi" en partiel, "Envoi SFTP" en complet).

A utiliser systematiquement avant un deploiement qui touche a la structure des
fichiers. Pour lire le resultat, comparer les lignes `Removing old file` et
`Transferring file` : un fichier present dans les deux est simplement remplace,
seul un fichier present uniquement dans la premiere liste est reellement supprime.

## Fichiers jamais touches par le deploiement

Ils sont exclus du miroir ET absents du repo : ils n'existent que sur le serveur.
Retirer une de ces exclusions entrainerait une perte de donnees, `--delete` etant
actif.

- `config/autoload/local.php` et `config/autoload/global.php` (identifiants BDD, SMTP)
- `public/photos/` (uploads utilisateurs)
- `data/sessions/` (sessions actives)

Une modification de ces fichiers se fait a la main sur le serveur.

## Sensibilite a la casse

Le serveur est sous Linux, le poste de dev sous Windows. Un fichier dont le nom ne
correspond pas exactement a la classe qu'il declare passe inapercu en local et
casse l'autoload PSR-4 en production. Composer le signale au build :

```
Class X located in ./chemin/y.php does not comply with psr-4 autoloading standard. Skipping.
```

Un avertissement de ce type doit etre corrige avant deploiement : la classe est
absente du classmap optimise. C'est ce qui est arrive a `SumUpService`, dont le
fichier s'appelait `SumupService.php`.

## Rollback

`Actions > Deploiement production > Run workflow` en selectionnant une branche ou un
tag anterieur, ou `git revert` puis relancer le workflow. Il est idempotent.
