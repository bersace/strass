#!/usr/bin/make -rf
# -*- makefile -*-

ENTRYPOINT=$(lastword $(MAKEFILE_LIST)) --no-print-directory
STRASSDO=sudo -Eu strass
export LC_ALL=fr_FR.UTF-8
export STRASS_ROOT?=/strass/htdocs

default:

devserver: fixperms statics
	$(STRASSDO) scripts/serve.sh

fcgi: fixperms statics
	/usr/sbin/php5-fpm --nodaemonize --fpm-config /etc/php5/fpm/php-fpm.conf

# S'assurer que le volume est accessible à l'utilisateur strass.
fixperms:
	chown -vR strass $${STRASS_ROOT}
	find $${STRASS_ROOT} -type d -exec chmod u+rwx {} ';'
	find $${STRASS_ROOT} -type f -exec chmod u+rw {} ';'
	test -d $${STRASS_ROOT}/private && chmod -vR o-rwx $${STRASS_ROOT}/private

migrate: fixperms
	$(STRASSDO) scripts/$@
	$(ENTRYPOINT) statics

restore: fixperms
	rsync --verbose --archive --delete $${STRASS_ROOT}/snapshot/data $${STRASS_ROOT}/snapshot/*.html $${STRASS_ROOT}/snapshot/private $${STRASS_ROOT}/

setmaint:
	touch $${STRASS_ROOT}/MAINTENANCE

# Générer les pages statiques 500.html et maintenance.html avec le script adhoc.
$(STRASS_ROOT)/%.html: FORCE
	$(STRASSDO) scripts/$* > $@

# Pour sécuriser les migrations des données en prod, on créer un instantannée
# des données. L'instantannée est optimisé pour data/ avec des liens physiques.
# Souvent, une migration va renommer des fichiers, c'est pas la peine de
# recopier la données (surtout les images, les PDF, etc.). Parcontre, dans
# private/ on a la base de données SQLite et la configuration. Ça, on le copie
# réellement car la migration va très certainement éditer le contenu du fichier.
# Enfin, on utilise rsync pour nettoyer les fichiers plutôt que de repartir d'un
# dossier vide.
snapshot: fixperms
	$(STRASSDO) mkdir -vp $${STRASS_ROOT}/snapshot $${STRASS_ROOT}/data
	cp --verbose --archive --force --update --link $${STRASS_ROOT}/data $${STRASS_ROOT}/*.html $${STRASS_ROOT}/snapshot/
	cp --verbose --archive --force --update $${STRASS_ROOT}/private $${STRASS_ROOT}/snapshot/
	rsync --verbose --archive --delete $${STRASS_ROOT}/data $${STRASS_ROOT}/snapshot/
	rm -rf $${STRASS_ROOT}/snapshot/private/cache/*

statics: $(STRASS_ROOT)/500.html $(STRASS_ROOT)/maintenance.html

unsetmaint:
	rm -vf $${STRASS_ROOT}/MAINTENANCE

FORCE:
