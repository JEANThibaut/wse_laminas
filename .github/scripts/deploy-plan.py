#!/usr/bin/env python3
"""Prepare le deploiement : ecrit les commandes lftp a executer (deploy.lftp).

Envoi PARTIEL quand c'est possible : le serveur garde dans .deployed-commit le
commit deploye la derniere fois, Git donne la liste exacte des fichiers
ajoutes, modifies et supprimes depuis. Seuls ceux-la partent.

Envoi COMPLET (lftp mirror, comme avant) quand le partiel n'est pas sur :
demande explicite, premier deploiement, commit inconnu ou deploiement d'un
commit plus ancien (retour arriere).

Dans les deux cas, .deployed-commit est ecrit EN DERNIER : si un envoi echoue
en route, le deploiement suivant repart de l'ancien commit et renvoie tout ce
qui manque.

Variables d'environnement :
  OLD_COMMIT  commit lu sur le serveur (vide si absent)
  FULL        "true" pour forcer l'envoi complet
  DRY_RUN     "true" pour une simulation (rien n'est ecrit sur le serveur)
  REMOTE_DIR  dossier du projet sur le serveur
"""
import os
import re
import subprocess
import sys

EXCLUDES_FILE = '.github/deploy-excludes.txt'
MARKER = '.deployed-commit'
# Fichiers de travail hors du projet : l'envoi complet ne doit pas les embarquer
WORK_DIR = os.environ.get('RUNNER_TEMP') or '.'
PLAN_FILE = os.path.join(WORK_DIR, 'deploy.lftp')
LOCAL_MARKER = os.path.join(WORK_DIR, MARKER)
# Fichiers de cache Laminas : l'envoi complet les efface (--delete), le partiel
# doit le faire explicitement, sinon une nouvelle route ou un nouveau service
# serait ignore
CACHE_FILES = [
    'data/cache/module-config-cache.application.config.cache.php',
    'data/cache/module-classmap-cache.application.module.cache.php',
]
# Generes au build, absents de Git : toujours renvoyes en partiel.
# vendor/composer contient la table des classes, modules compris.
GENERATED_FILES = ['vendor/autoload.php', 'config/autoload/push.global.php']
GENERATED_DIRS = ['vendor/composer']
# Un changement de dependances renvoie tout vendor/
VENDOR_TRIGGERS = {'composer.json', 'composer.lock'}


def git(*args, check=True):
    return subprocess.run(['git', *args], capture_output=True, text=True, check=check)


def load_excludes():
    with open(EXCLUDES_FILE, encoding='utf-8') as handle:
        patterns = [line.strip() for line in handle]
    return [pattern for pattern in patterns if pattern and not pattern.startswith('#')]


def quote(path):
    # Chemins entre guillemets doubles pour lftp ; un guillemet dans un nom de
    # fichier n'est pas gere : on bascule alors sur l'envoi complet
    if '"' in path or '\n' in path:
        raise ValueError(path)
    return '"' + path + '"'


def remote(remote_dir, path):
    return path if remote_dir in ('', '.', './') else remote_dir.rstrip('/') + '/' + path


def full_plan(excludes, remote_dir, dry_run):
    exclude_args = ' '.join("--exclude '" + pattern.replace("'", "") + "'" for pattern in excludes)
    commands = [
        # Une erreur arrete tout : .deployed-commit n'est alors pas ecrit
        'set cmd:fail-exit yes',
        'set mirror:parallel-transfer-count 4',
        f"mirror --reverse --delete --verbose {'--dry-run ' if dry_run else ''}{exclude_args} ./ {quote(remote_dir or '.')}",
    ]
    return commands


def partial_plan(old, excludes, remote_dir):
    output = git('diff', '--name-status', '--no-renames', '-z', old, 'HEAD').stdout
    fields = [field for field in output.split('\0') if field]
    changes = list(zip(fields[0::2], fields[1::2]))
    compiled = [re.compile(pattern) for pattern in excludes]
    excluded = lambda path: any(pattern.search(path) for pattern in compiled)

    uploads = [path for status, path in changes if status[0] in 'AMT' and not excluded(path)]
    deletions = [path for status, path in changes if status[0] == 'D' and not excluded(path)]
    vendor_full = any(path in VENDOR_TRIGGERS for _, path in changes)

    print(f'Fichiers modifies ou ajoutes : {len(uploads)}')
    for path in uploads:
        print(f'  + {path}')
    print(f'Fichiers supprimes : {len(deletions)}')
    for path in deletions:
        print(f'  - {path}')
    print('Dependances (vendor/) : ' + ('renvoyees en entier (composer modifie)' if vendor_full else 'inchangees'))

    commands = ['set cmd:fail-exit yes']
    created = set()
    for path in uploads + [path for path in GENERATED_FILES if os.path.isfile(path)]:
        directory = os.path.dirname(path)
        if directory and directory not in created:
            commands.append(f'mkdir -p -f {quote(remote(remote_dir, directory))}')
            created.add(directory)
        commands.append(f'put {quote(path)} -o {quote(remote(remote_dir, path))}')
    for directory in (['vendor'] if vendor_full else GENERATED_DIRS):
        if os.path.isdir(directory):
            commands.append(f'mirror --reverse --delete {quote(directory)} {quote(remote(remote_dir, directory))}')

    # Suppressions et purge du cache : un fichier deja absent n'est pas une erreur
    commands.append('set cmd:fail-exit no')
    for path in deletions + CACHE_FILES:
        commands.append(f'rm -f {quote(remote(remote_dir, path))}')
    commands.append('set cmd:fail-exit yes')
    return commands


def main():
    old = os.environ.get('OLD_COMMIT', '').strip()
    full = os.environ.get('FULL') == 'true'
    dry_run = os.environ.get('DRY_RUN') == 'true'
    remote_dir = os.environ.get('REMOTE_DIR', '.').strip() or '.'
    head = git('rev-parse', 'HEAD').stdout.strip()
    excludes = load_excludes()

    reason = None
    if full:
        reason = 'envoi complet demande'
    elif not re.fullmatch(r'[0-9a-f]{40}', old):
        reason = 'aucun commit deploye connu sur le serveur'
    elif git('cat-file', '-e', old + '^{commit}', check=False).returncode != 0:
        reason = f'commit {old[:7]} introuvable dans l\'historique'
    elif git('merge-base', '--is-ancestor', old, 'HEAD', check=False).returncode != 0:
        reason = f'{head[:7]} ne descend pas de {old[:7]} (retour arriere ?)'
    elif old == head:
        print(f'Commit {head[:7]} deja deploye : seuls les fichiers generes et le cache sont rafraichis.')

    commands = None
    if reason is None:
        print(f'Envoi PARTIEL : {old[:7]} -> {head[:7]}')
        try:
            commands = partial_plan(old, excludes, remote_dir)
        except ValueError as error:
            reason = f'nom de fichier non gere ({error})'
    if commands is None:
        print(f'Envoi COMPLET : {reason}')
        commands = full_plan(excludes, remote_dir, dry_run)

    if dry_run:
        print('\nSIMULATION : rien ne sera ecrit sur le serveur.')
        if reason is None:
            # En partiel, la liste ci-dessus suffit : aucune commande n'est executee.
            # En complet, lftp mirror --dry-run liste lui-meme ce qu'il ferait.
            commands = []
    else:
        with open(LOCAL_MARKER, 'w', encoding='utf-8') as handle:
            handle.write(head + '\n')
        commands.append(f'put {quote(LOCAL_MARKER)} -o {quote(remote(remote_dir, MARKER))}')

    # Fichier vide en simulation partielle : l'etape d'envoi n'a rien a executer
    with open(PLAN_FILE, 'w', encoding='utf-8') as handle:
        handle.write('\n'.join(commands) + '\n' if commands else '')


if __name__ == '__main__':
    sys.exit(main())
