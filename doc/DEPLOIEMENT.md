# Deploiement production (OVH)

Le deploiement est pilote par le repo : tout push sur `main` declenche
`.github/workflows/deploy.yml`, qui build et envoie en FTPS sur l'hebergement OVH.
Aucune action manuelle, aucun FileZilla.

## Ce que fait le workflow

1. Checkout de `main`
2. `composer install --no-dev --optimize-autoloader` (vendor/ est construit par la CI,
   il n'est pas dans le repo)
3. `php -l` sur `module/`, `config/` et `public/` : un fichier casse arrete le deploiement
4. Synchronisation FTPS incrementale (seuls les fichiers modifies remontent)
5. Suppression de `data/cache/*.php` pour invalider le cache de config Laminas

## Configuration GitHub (une seule fois)

`Settings > Secrets and variables > Actions`

Secrets :

| Nom | Valeur |
| --- | --- |
| `FTP_SERVER` | hote FTP OVH, ex. `ftp.cluster0XX.hosting.ovh.net` |
| `FTP_USERNAME` | login FTP |
| `FTP_PASSWORD` | mot de passe FTP |

Variables :

| Nom | Valeur |
| --- | --- |
| `FTP_SERVER_DIR` | repertoire cible, **doit finir par `/`**, ex. `/home/xxx/wse_laminas/` |
| `PHP_VERSION` | version PHP de l'hebergement OVH (defaut `8.3`) |

`PHP_VERSION` doit correspondre a la version reellement active sur OVH, sinon vendor/
est compile pour une version differente de celle qui l'execute.

## Arborescence serveur attendue

Le workflow recopie **la racine du repo** dans `FTP_SERVER_DIR`. Deux montages possibles :

**Option A (recommandee)** — racine web du domaine pointee sur le sous-dossier `public/`
(OVH : `Hebergements > Multisite > Racine du dossier`). `FTP_SERVER_DIR` vise le dossier
du projet ; `index.php` et `.htaccess` a la racine du repo ne servent plus a rien.

**Option B** — la racine web reste `www/`. `FTP_SERVER_DIR` vaut alors `/www/`, et il faut
corriger `index.php` a la racine du repo, qui pointe aujourd'hui vers un sous-dossier :

```php
require __DIR__ . '/wse_laminas/public/index.php';  // actuel
require __DIR__ . '/public/index.php';              // cible option B
```

L'option A est plus sure : elle met `config/`, `module/` et `vendor/` hors de la racine web.

## Fichiers jamais touches par le deploiement

Ils sont exclus du workflow ET absents du repo : ils vivent uniquement sur le serveur.

- `config/autoload/local.php` et `config/autoload/global.php` (identifiants BDD, SMTP)
- `public/photos/` (uploads utilisateurs)
- `data/sessions/` (sessions actives)

Une modification de ces fichiers se fait a la main sur le serveur. Les modeles de reference
sont `config/autoload/local.php.dist` et `config/autoload/global.php` en local.

## Preparation serveur (une seule fois)

1. Verifier que `data/cache/`, `data/sessions/` et `data/DoctrineORMModule/Proxy/` existent
   et sont accessibles en ecriture par PHP
2. Verifier l'absence de `config/development.config.php` (mode dev actif = stack traces
   exposees publiquement)
3. Premier upload de `vendor/` (61 Mo, ~9600 fichiers) a la main via FileZilla : le premier
   passage du workflow en FTP fichier par fichier prendrait des heures. Les suivants sont
   incrementaux et ne renvoient que le diff.
4. Supprimer les residus recenses lors du comparatif : `module/Game/src/Entity/Register.php`,
   `module/Faction/view/faction/index backup.phtml`, `public/diag.php`

## Rollback

`Actions > Deploiement production > Run workflow` depuis un commit anterieur, ou
`git revert` puis push. Le workflow est idempotent.

## Si un acces SSH est disponible

OVH fournit SSH sur les offres Pro et superieures. Si c'est le cas, l'etape `lftp` de
vidage du cache peut etre remplacee par `php bin/clear-config-cache.php`, et le transfert
FTP par un `rsync -az --delete`, nettement plus rapide sur vendor/.
