# Site scout avec Strass

Cette image embarque [Strass](https://github.com/bersace/strass) dans une image
Docker avec PHP5.

``` console
$ docker run --rm -v ./htdocs:/strass/htdocs bersace/strass devserver
```


## Variables d'environnement

- `STRASS_EMETTEUR` : l'expéditeur des mails
- `STRASS_MODE` : `devel` pour le mode développement. `prod` sinon.
- `STRASS_SMTP` : le nom d'hôte du serveur SMTP.


## Volumes

- `/strass/htdocs` : les données du site
- `/var/lib/php5/sessions` : les sessions stockées sur disque par PHP5.


## Commandes

Le point d'entrée de l'image est un `Makefile` acceptant les commandes suivantes:

- `backup` : restaure l'instantannée pré-migration.
- `devserver` : lance le serveur HTTP embarqué de PHP pour tester, sur le port `8000`.
- `fcgi` : lance PHP5-FPM sur le port `9000`.
- `upgrade` : met en maintenance, sauvegarde un instantannée, migre les données,
  regénère les pages statiques et met en ligne le site.
- `setmaint` / `unsetmaint` : met le site en-ligne / hors-ligne avec la page
  personnalisée, sans arrêter le conteneur.

Voire `docker/entrypoint.mk` pour les autres commandes internes.
