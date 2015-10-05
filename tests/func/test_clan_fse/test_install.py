# -*- coding: utf-8 -*-

import datetime
from strass.client import ClientTestCase


class TestInstaller(ClientTestCase):
    def test_00_install(self):
        (
            self.client
            .get()

            # Étape association
            .click('input#installation-site-association-fse')
            .click('button[type=submit]')

            # Étape administrateur
            .fill('input#installation-admin-prenom', 'Prénom admin')
            .fill('input#installation-admin-nom', 'Nom admin')
            .click('input#installation-admin-sexe-h')
            .fill(
                '#control-installation-admin-naissance',
                datetime.date(1990, 10, 21))
            .fill('input#installation-admin-adelec', 'admin@groupe-suf')
            .fill('input#installation-admin-motdepasse', 'secret')
            .fill('input#installation-admin-confirmation', 'secret')
            .click('button[type=submit].primary')
        )

        # L'installation est terminée.
        #
        # Page d'erreur qu'il n'y a pas d'unité :-)
        self.assertIn("Pas d'unit", self.client.page_source)
