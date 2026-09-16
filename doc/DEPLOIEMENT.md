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

1. Checkout de la branche choisie au lancement (`main` par defaut)
2. `composer install --no-dev --optimize-autoloader` (vendor/ est construit par la
   CI, il n'est pas dans le repo)
3. `php -l` sur `module/`, `config/` et `public/` : un fichier casse arrete tout
4. Inspection de l'arborescence distante (diagnostic en lecture seule)
5. `lftp mirror --reverse --delete` : seuls les fichiers modifies remontent, et ce
   qui a disparu du repo est supprime cote serveur. C'est aussi ce qui invalide le
   cache de config Laminas, `data/cache/` ne contenant que `.gitkeep` dans le repo

## Configuration GitHub

`Settings > Secrets and variables > Actions`

Secrets : `FTP_SERVER` (hote SFTP OVH), `FTP_USERNAME`, `FTP_PASSWORD`.

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
tout ce qui serait envoye et supprime, sans ecrire une ligne sur le serveur.

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
