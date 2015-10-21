import datetime
from strass.client import ClientTestCase

from fixtures import ADMIN_EMAIL, ADMIN_PASSWORD


class TestInstaller(ClientTestCase):
    def test_00_missing_association(self):
        (
            self.client
            .get()
            .click('button[type=submit]')

            # S'assurer que le champ association est refusé.
            .find('#control-installation-site-association.invalid')
        )

    def test_01_association(self):
        (
            self.client
            .get()

            # Étape association
            .click('input#installation-site-association-suf')
            .click('button[type=submit]')

            # Étape administrateur
            .fill('input#installation-admin-prenom', 'Isidore')
            .fill('input#installation-admin-nom', 'Installeur')
            .click('input#installation-admin-sexe-h')
            .fill(
                '#control-installation-admin-naissance',
                datetime.date(1990, 10, 21))
            .fill('input#installation-admin-adelec', ADMIN_EMAIL)
            .fill('input#installation-admin-motdepasse', ADMIN_PASSWORD)
            .fill('input#installation-admin-confirmation', ADMIN_PASSWORD)
            .click('button[type=submit].primary')
        )

        # L'installation est terminée.
        #
        # Page d'erreur qu'il n'y a pas d'unité :-)
        self.assertIn("Pas d'unit", self.client.page_source)
